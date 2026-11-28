<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Exceptions\InvalidAttributeSelectionException;
use App\Exceptions\InvalidMaterialSelectionException;
use App\Exceptions\InvalidProductAttributeSelectionException;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MaterialAvailability;
use App\Support\Decimal;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Replaces invoice order items and recalculates balances before production starts. */
final class UpdateInvoiceItems
{
    /** Injects the server-side quote calculator. */
    public function __construct(
        private readonly CalculateQuote $quote,
        private readonly MaterialAvailability $availability,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Reprices all submitted items, preserves payment rows, and records the change atomically.
     *
     * @param  array{deadline?: string|null, items: array<int, array<string, mixed>>}  $data
     *
     * @throws ValidationException
     * @throws HttpException
     */
    public function execute(Invoice $invoice, array $data, User $designer): Invoice
    {
        $branchId = $designer->branch_id;

        if ($branchId === null) {
            throw ValidationException::withMessages([
                'branch_id' => 'Desainer harus ditugaskan ke cabang.',
            ]);
        }

        $updatedInvoice = DB::transaction(function () use ($invoice, $data, $designer, $branchId): Invoice {
            $lockedInvoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();
            $existingOrders = $lockedInvoice->orders()->with(['product', 'material'])->lockForUpdate()->get();

            // Invoice-wide allowance: any attribute already stored on this invoice may be kept.
            $invoiceAttributeIds = $existingOrders
                ->flatMap(static fn (Order $order): array => is_array($order->selected_attributes)
                    ? $order->selected_attributes
                    : [])
                ->unique()
                ->values()
                ->all();

            if ($lockedInvoice->status === 2 || $existingOrders->contains(
                static fn (Order $order): bool => $order->status !== 0
            )) {
                throw new HttpException(409, 'Invoice lunas atau pesanan sudah masuk produksi.');
            }

            if ($existingOrders->contains(
                static fn (Order $order): bool => (bool) $order->is_manual_price
            )) {
                throw new HttpException(409, 'Invoice dengan harga manual tidak dapat diubah.');
            }

            $allowedMaterialIds = $existingOrders
                ->map(static fn (Order $order): ?string => $order->material_id)
                ->filter()
                ->unique()
                ->all();

            $customerType = $lockedInvoice->customer()->value('type') ?: 'Reguler';
            $lines = [];
            $subtotal = 0.0;
            $totalCost = 0.0;
            $retainedIds = [];

            foreach ($data['items'] as $index => $item) {
                $materialId = $item['material_id'] ?? null;
                $material = null;

                if ($materialId !== null) {
                    $material = Material::query()->withTrashed()->findOrFail($materialId);
                }

                $selectedAttributes = is_array($item['selected_attributes'] ?? null)
                    ? array_values($item['selected_attributes'])
                    : [];

                $existingOrder = null;

                if (isset($item['id'])) {
                    $existingOrder = $existingOrders->firstWhere('id', $item['id']);

                    if (! $existingOrder instanceof Order) {
                        throw ValidationException::withMessages([
                            "items.{$index}.id" => 'Item tidak termasuk dalam invoice ini.',
                        ]);
                    }

                    $retainedIds[] = $existingOrder->id;
                }

                // C2: only a whole unchanged line is grandfathered; no client flag widens this.
                $unchanged = $existingOrder instanceof Order
                    && strtolower($existingOrder->product_id ?? '') === strtolower((string) $item['product_id'])
                    && strtolower($existingOrder->material_id ?? '') === strtolower((string) ($item['material_id'] ?? ''))
                    && $this->normalizeAttributeSet($existingOrder->selected_attributes)
                        === $this->normalizeAttributeSet($selectedAttributes);

                if ($materialId !== null
                    && ! $this->availability->isAvailable($material, $branchId)
                    && ($material->trashed()
                        || ! in_array($material->id, $allowedMaterialIds, true))) {
                    throw ValidationException::withMessages([
                        "items.{$index}.material_id" => 'Bahan tidak aktif atau tidak tersedia di cabang ini.',
                    ]);
                }

                try {
                    $quote = $this->quote->execute([
                        'product_id' => $item['product_id'],
                        'material_id' => $materialId,
                        'selected_attributes' => $selectedAttributes,
                        'quantity' => $item['quantity'],
                        'width' => $item['width'] ?? null,
                        'length' => $item['length'] ?? null,
                        'customer_type' => $customerType,
                        'branch_id' => $branchId,
                        'inactive_attribute_allowance' => $unchanged ? $invoiceAttributeIds : [],
                        'skip_product_capability_checks' => $unchanged,
                    ]);
                } catch (InvalidMaterialSelectionException $exception) {
                    throw ValidationException::withMessages([
                        "items.{$index}.material_id" => $exception->getMessage(),
                    ]);
                } catch (InvalidAttributeSelectionException|InvalidProductAttributeSelectionException $exception) {
                    throw ValidationException::withMessages([
                        "items.{$index}.selected_attributes" => $exception->getMessage(),
                    ]);
                } catch (InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => $exception->getMessage(),
                    ]);
                }

                if ((float) $quote['area'] > 999999.99) {
                    throw ValidationException::withMessages([
                        "items.{$index}.width" => 'Luas yang dihitung melebihi batas invoice.',
                    ]);
                }

                $lines[] = [
                    'existing' => $existingOrder,
                    'product_id' => $quote['product_id'],
                    'material_id' => $quote['material_id'],
                    'customer_type' => $quote['customer_type'],
                    'reseller_discount_percent' => $quote['reseller_discount_percent'],
                    'quantity_discount_percent' => $quote['quantity_discount_percent'],
                    'discount_amount' => $quote['discount_amount'],
                    'file_name' => $this->normalizeText($item['file_name'] ?? null),
                    'notes' => $this->normalizeText($item['notes'] ?? null),
                    'area' => $quote['area'],
                    'width' => $item['width'] ?? null,
                    'length' => $item['length'] ?? null,
                    'selected_attributes' => $selectedAttributes,
                    'quantity' => $quote['quantity'],
                    'unit_price' => $quote['unit_price'],
                    'subtotal' => $quote['subtotal'],
                    'cost' => $quote['cost'],
                    'profit' => $quote['profit'],
                ];
                $subtotal += (float) $quote['subtotal'];
                $totalCost += (float) $quote['cost'];
            }

            $discount = round((float) $lockedInvoice->discount, 2);

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'Diskon invoice melebihi subtotal item yang baru.',
                ]);
            }

            $grandTotal = round($subtotal - $discount, 2);
            $totalPaid = round((float) $lockedInvoice->payments()->sum('amount'), 2);

            if ($totalPaid > $grandTotal) {
                throw ValidationException::withMessages([
                    'items' => 'Total baru tidak boleh lebih kecil dari pembayaran yang sudah diterima.',
                ]);
            }

            $before = [
                'grand_total' => Decimal::money($lockedInvoice->grand_total),
                'accounts_receivable' => Decimal::money($lockedInvoice->accounts_receivable),
                'orders' => $existingOrders->map(static fn (Order $order): array => [
                    'id' => $order->id,
                    'product_id' => $order->product_id,
                    'material_id' => $order->material_id,
                    'file_name' => $order->file_name,
                    'notes' => $order->notes,
                    'quantity' => (string) $order->quantity,
                    'width' => $order->width,
                    'length' => $order->length,
                    'selected_attributes' => $order->selected_attributes ?? [],
                ])->all(),
            ];

            foreach ($existingOrders as $existingOrder) {
                if (! in_array($existingOrder->id, $retainedIds, true)) {
                    $existingOrder->delete();
                }
            }

            foreach ($lines as $line) {
                /** @var Order|null $existingOrder */
                $existingOrder = $line['existing'];
                unset($line['existing']);
                $orderData = [
                    ...$line,
                    'invoice_id' => $lockedInvoice->id,
                    'operator_id' => $existingOrder?->operator_id,
                    'ordered_at' => $existingOrder->ordered_at ?? now()->toDateString(),
                    'status' => 0,
                    'pickup_status' => 0,
                    'is_manual_price' => false,
                ];

                if ($existingOrder instanceof Order) {
                    $existingOrder->update($orderData);
                    $order = $existingOrder->refresh();
                    $statusNote = 'Order item updated';
                } else {
                    $order = Order::create($orderData);
                    $statusNote = 'Order item added during invoice update';
                }

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => 0,
                    'user_id' => $designer->id,
                    'user_name' => $designer->name,
                    'notes' => $statusNote,
                ]);
            }

            $receivable = round($grandTotal - $totalPaid, 2);

            if (array_key_exists('deadline', $data)) {
                $lockedInvoice->deadline = $data['deadline'];
            }

            $lockedInvoice->grand_total = $grandTotal;
            $lockedInvoice->total_cost = round($totalCost, 2);
            $lockedInvoice->total_profit = round($grandTotal - $totalCost, 2);
            $lockedInvoice->accounts_receivable = $receivable;
            $lockedInvoice->dp_amount = round((float) $lockedInvoice->payments()
                ->where('type', 'down_payment')->sum('amount'), 2);
            $lockedInvoice->status = $receivable === 0.0 ? 2 : ($totalPaid > 0 ? 1 : 0);
            if ($receivable === 0.0) {
                $lockedInvoice->payment_date = $lockedInvoice->payment_date ?: now()->toDateString();
            }
            $lockedInvoice->save();

            $this->audit->record(
                $designer,
                'UPDATE',
                'Memperbarui item invoice '.$lockedInvoice->invoice_number,
                $lockedInvoice,
                [
                    'before' => $before,
                    'after' => [
                        'grand_total' => Decimal::money($grandTotal),
                        'accounts_receivable' => Decimal::money($receivable),
                        'orders' => $lockedInvoice->orders()->get()->map(static fn (Order $order): array => [
                            'id' => $order->id,
                            'product_id' => $order->product_id,
                            'material_id' => $order->material_id,
                            'file_name' => $order->file_name,
                            'notes' => $order->notes,
                            'quantity' => (string) $order->quantity,
                            'width' => $order->width,
                            'length' => $order->length,
                            'selected_attributes' => $order->selected_attributes ?? [],
                        ])->all(),
                    ],
                ],
                $branchId,
            );

            return $lockedInvoice->load(['customer', 'branch', 'designer', 'orders', 'payments']);
        });

        SafeBroadcast::orderUpdated($branchId, $designer);

        return $updatedInvoice;
    }

    /**
     * @return list<string>
     */
    private function normalizeAttributeSet(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): ?string => is_string($id) && $id !== '' ? strtolower($id) : null,
            $value
        ))));

        sort($ids);

        return $ids;
    }

    /** Trims optional text, storing empty values as null. */
    private function normalizeText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
