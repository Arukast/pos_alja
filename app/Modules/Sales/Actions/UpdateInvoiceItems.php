<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Exceptions\PricingNotFoundException;
use App\Models\ActivityLog;
use App\Models\Attribute;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Pricing;
use App\Models\User;
use App\Support\Decimal;
use App\Support\SafeBroadcast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Replaces invoice order items and recalculates balances before production starts. */
final class UpdateInvoiceItems
{
    /** Injects the server-side quote calculator. */
    public function __construct(private readonly CalculateQuote $quote) {}

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
            $existingOrders = $lockedInvoice->orders()->lockForUpdate()->get();

            if ($lockedInvoice->status === 2 || $existingOrders->contains(
                static fn (Order $order): bool => $order->status !== 0
            )) {
                throw new HttpException(409, 'Invoice lunas atau pesanan sudah masuk produksi.');
            }

            $customerType = $lockedInvoice->customer()->value('type') ?: 'Reguler';
            $lines = [];
            $subtotal = 0.0;
            $totalCost = 0.0;
            $retainedIds = [];

            foreach ($data['items'] as $index => $item) {
                $pricing = Pricing::query()
                    ->with(['product.category', 'material'])
                    ->findOrFail($item['pricing_id']);

                if ($pricing->branch_id !== $branchId) {
                    throw ValidationException::withMessages([
                        "items.{$index}.pricing_id" => 'Baris harga ini milik cabang lain.',
                    ]);
                }

                $selectedAttributes = is_array($item['selected_attributes'] ?? null)
                    ? $item['selected_attributes']
                    : [];
                $attributes = Attribute::query()
                    ->whereIn('id', $selectedAttributes)
                    ->where(function (Builder $query) use ($branchId): void {
                        $query->where('branch_id', $branchId)->orWhereNull('branch_id');
                    })
                    ->get();

                if ($attributes->count() !== count($selectedAttributes)
                    || $attributes->groupBy('type')->contains(
                        static fn ($group): bool => $group->count() > 1
                    )) {
                    throw ValidationException::withMessages([
                        "items.{$index}.selected_attributes" => 'Pilihan finishing/display tidak valid untuk item ini.',
                    ]);
                }

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

                try {
                    $quote = $this->quote->forPricing($pricing, $item, $branchId, $customerType);
                } catch (PricingNotFoundException|InvalidArgumentException $exception) {
                    throw ValidationException::withMessages([
                        "items.{$index}.pricing_id" => $exception->getMessage(),
                    ]);
                }

                if ((float) $quote['area'] > 999999.99) {
                    throw ValidationException::withMessages([
                        "items.{$index}.width" => 'Luas yang dihitung melebihi batas invoice.',
                    ]);
                }

                $lines[] = [
                    'existing' => $existingOrder,
                    'pricing_id' => $quote['pricing_id'],
                    'file_name' => $this->normalizeText($item['file_name'] ?? null),
                    'notes' => $this->normalizeText($item['notes'] ?? null),
                    'area' => $quote['area'],
                    'width' => $item['width'] ?? null,
                    'length' => $item['length'] ?? null,
                    'selected_attributes' => array_values($selectedAttributes),
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
                    'pricing_id' => $order->pricing_id,
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
            $lockedInvoice->deadline = $data['deadline'] ?? null;
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

            ActivityLog::create([
                'branch_id' => $branchId,
                'user_id' => $designer->id,
                'username' => $designer->username,
                'role' => $designer->role,
                'action' => 'UPDATE',
                'description' => 'Memperbarui item invoice '.$lockedInvoice->invoice_number,
                'model_type' => Invoice::class,
                'model_id' => $lockedInvoice->id,
                'payload' => [
                    'before' => $before,
                    'after' => [
                        'grand_total' => Decimal::money($grandTotal),
                        'accounts_receivable' => Decimal::money($receivable),
                        'orders' => $lockedInvoice->orders()->get()->map(static fn (Order $order): array => [
                            'id' => $order->id,
                            'pricing_id' => $order->pricing_id,
                            'file_name' => $order->file_name,
                            'notes' => $order->notes,
                            'quantity' => (string) $order->quantity,
                            'width' => $order->width,
                            'length' => $order->length,
                            'selected_attributes' => $order->selected_attributes ?? [],
                        ])->all(),
                    ],
                ],
            ]);

            return $lockedInvoice->load(['customer', 'branch', 'designer', 'orders', 'payments']);
        });

        SafeBroadcast::orderUpdated($branchId, $designer);

        return $updatedInvoice;
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
