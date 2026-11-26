<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Exceptions\PricingNotFoundException;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Pricing;
use App\Models\User;
use App\Support\Decimal;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Membuat invoice baru beserta item pesanan, log status awal, dan jejak audit dalam satu transaksi.
 */
final class CreateInvoice
{
    /**
     * Membuat instance aksi pembuatan invoice.
     */
    public function __construct(private readonly CalculateQuote $quote) {}

    /**
     * Menghitung ulang harga dari server, menyimpan invoice beserta item pesanan, dan menyiarkan pembaruan.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function execute(array $data, User $designer): Invoice
    {
        $branchId = $designer->branch_id;

        if ($branchId === null) {
            throw ValidationException::withMessages([
                'branch_id' => 'Desainer harus ditugaskan ke cabang.',
            ]);
        }

        $invoice = DB::transaction(function () use ($data, $designer, $branchId): Invoice {
            $now = now();
            $today = $now->toDateString();
            $customer = Customer::findOrFail($data['customer_id']);
            $customerType = $customer->type ?: 'Reguler';

            // Serialize invoice numbering per branch.
            $branch = Branch::query()->whereKey($branchId)->lockForUpdate()->first();

            $lines = [];
            $subtotal = 0.0;
            $totalCost = 0.0;

            foreach ($data['items'] as $index => $item) {
                $pricing = Pricing::query()
                    ->with(['product.category', 'material'])
                    ->findOrFail($item['pricing_id']);

                if ($pricing->branch_id !== $branchId) {
                    throw ValidationException::withMessages([
                        "items.{$index}.pricing_id" => 'Baris harga ini milik cabang lain.',
                    ]);
                }

                try {
                    $quote = $this->quote->forPricing(
                        $pricing,
                        $item,
                        $branchId,
                        $customerType
                    );
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

                $isManual = (bool) ($item['is_manual_price'] ?? false);

                if ($isManual) {
                    if (! isset($item['unit_price'], $item['cost'])) {
                        throw ValidationException::withMessages([
                            "items.{$index}.unit_price" => 'Harga manual memerlukan harga satuan dan biaya.',
                        ]);
                    }

                    $factor = $pricing->product?->pricing_type === 'area'
                        ? (float) $quote['area']
                        : 1.0;

                    $unitPrice = round((float) $item['unit_price'], 2);
                    $unitCost = round((float) $item['cost'], 2);

                    if ($unitPrice <= 0 || $unitCost < 0 || $unitCost > $unitPrice) {
                        throw ValidationException::withMessages([
                            "items.{$index}.unit_price" => 'Harga manual memerlukan harga positif dan biaya tidak melebihi harga.',
                        ]);
                    }
                    $lineSubtotal = round($unitPrice * (float) $quote['quantity'] * $factor, 2);
                    $lineCost = round($unitCost * (float) $quote['quantity'] * $factor, 2);

                    $quote = array_merge($quote, [
                        'unit_price' => $unitPrice,
                        'unit_cost' => $unitCost,
                        'subtotal' => $lineSubtotal,
                        'cost' => $lineCost,
                        'profit' => round($lineSubtotal - $lineCost, 2),
                    ]);
                }

                $lines[] = [
                    'pricing_id' => $quote['pricing_id'],
                    'file_name' => $this->normalizeOptionalText($item['file_name'] ?? null),
                    'notes' => $this->normalizeOptionalText($item['notes'] ?? null),
                    'area' => $quote['area'],
                    'width' => $item['width'] ?? null,
                    'length' => $item['length'] ?? null,
                    'selected_attributes' => array_values($item['selected_attributes'] ?? []),
                    'quantity' => $quote['quantity'],
                    'unit_price' => $quote['unit_price'],
                    'subtotal' => $quote['subtotal'],
                    'cost' => $quote['cost'],
                    'profit' => $quote['profit'],
                    'is_manual_price' => $isManual,
                ];

                $subtotal += (float) $quote['subtotal'];
                $totalCost += (float) $quote['cost'];
            }

            $discount = round((float) ($data['discount'] ?? 0), 2);

            if ($discount < 0) {
                throw ValidationException::withMessages([
                    'discount' => 'Diskon tidak boleh bernilai negatif.',
                ]);
            }

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'Diskon tidak boleh melebihi subtotal invoice.',
                ]);
            }

            $grandTotal = round(max(0, $subtotal - $discount), 2);
            $totalProfit = round($grandTotal - $totalCost, 2);
            $cleanedCode = $branch?->code !== null
                ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $branch->code) ?? '')
                : '';
            $prefix = $cleanedCode !== '' ? $cleanedCode : $this->branchPrefix($branch?->name);
            $count = Invoice::query()
                ->where('created_date', $today)
                ->where('branch_id', $branchId)
                ->count();
            $invoiceNumber = 'INV-'.$prefix.'-'.$now->format('Ymd').'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'branch_id' => $branchId,
                'created_date' => $today,
                'customer_id' => $customer->id,
                'designer_id' => $designer->id,
                'status' => 0,
                'discount' => $discount,
                'dp_amount' => 0,
                'accounts_receivable' => $grandTotal,
                'grand_total' => $grandTotal,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'deadline' => $data['deadline'] ?? null,
                'pickup_status' => 0,
                'source' => ($data['source'] ?? 'Offline') === 'Online' ? 1 : 0,
            ]);

            foreach ($lines as $line) {
                $order = Order::create([
                    'invoice_id' => $invoice->id,
                    'pricing_id' => $line['pricing_id'],
                    'operator_id' => null,
                    'file_name' => $line['file_name'],
                    'notes' => $line['notes'],
                    'area' => $line['area'],
                    'width' => $line['width'],
                    'length' => $line['length'],
                    'quantity' => $line['quantity'],
                    'selected_attributes' => $line['selected_attributes'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                    'cost' => $line['cost'],
                    'profit' => $line['profit'],
                    'ordered_at' => $today,
                    'status' => 0,
                    'pickup_status' => 0,
                    'is_manual_price' => $line['is_manual_price'],
                ]);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => 0,
                    'user_id' => $designer->id,
                    'user_name' => $designer->name,
                    'notes' => 'Order created',
                ]);

                if ($line['is_manual_price']) {
                    ActivityLog::create([
                        'branch_id' => $branchId,
                        'user_id' => $designer->id,
                        'username' => $designer->username,
                        'role' => $designer->role,
                        'action' => 'MANUAL_PRICE',
                        'description' => 'Harga manual diterapkan pada '.($line['file_name'] ?? 'item pesanan'),
                        'model_type' => Order::class,
                        'model_id' => $order->id,
                        'payload' => [
                            'unit_price' => Decimal::money($line['unit_price']),
                            'cost' => Decimal::money($line['cost']),
                            'subtotal' => Decimal::money($line['subtotal']),
                        ],
                    ]);
                }
            }

            ActivityLog::create([
                'branch_id' => $branchId,
                'user_id' => $designer->id,
                'username' => $designer->username,
                'role' => $designer->role,
                'action' => 'CREATE',
                'description' => 'Membuat invoice '.$invoiceNumber,
                'model_type' => Invoice::class,
                'model_id' => $invoice->id,
                'payload' => [
                    'invoice_number' => $invoiceNumber,
                    'grand_total' => Decimal::money($grandTotal),
                    'total_cost' => Decimal::money($totalCost),
                    'total_profit' => Decimal::money($totalProfit),
                ],
            ]);

            return $invoice->load(['customer', 'branch', 'designer', 'orders', 'payments']);
        });

        SafeBroadcast::orderUpdated($branchId, $designer);

        return $invoice;
    }

    /**
     * Menghasilkan awalan dua huruf dari nama cabang saat kode cabang belum tersedia.
     */
    private function branchPrefix(?string $name): string
    {
        if ($name === null || $name === '') {
            return 'BR';
        }

        $cleaned = preg_replace('/[^A-Za-z0-9 ]/', '', $name) ?? '';
        $words = array_values(array_filter(explode(' ', $cleaned)));

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
        }

        return strtoupper(substr($cleaned, 0, 2)) ?: 'BR';
    }

    /**
     * Merapikan teks opsional agar spasi kosong disimpan sebagai null.
     */
    private function normalizeOptionalText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
