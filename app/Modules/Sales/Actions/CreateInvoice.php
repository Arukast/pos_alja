<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Events\OrderUpdated;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Pricing;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateInvoice
{
    public function __construct(private readonly CalculateQuote $quote) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $designer): Invoice
    {
        $branchId = $designer->branch_id;

        if ($branchId === null) {
            throw ValidationException::withMessages([
                'branch_id' => 'A designer must be assigned to a branch.',
            ]);
        }

        $invoice = DB::transaction(function () use ($data, $designer, $branchId): Invoice {
            $customer = Customer::findOrFail($data['customer_id']);
            $customerType = $customer->type ?: 'Reguler';

            // Serialize invoice numbering per branch.
            Branch::query()->whereKey($branchId)->lockForUpdate()->first();

            $lines = [];
            $subtotal = 0.0;
            $totalCost = 0.0;

            foreach ($data['items'] as $index => $item) {
                $pricing = Pricing::query()
                    ->with(['product.category', 'material'])
                    ->findOrFail($item['pricing_id']);

                if ($pricing->branch_id !== $branchId) {
                    throw ValidationException::withMessages([
                        "items.{$index}.pricing_id" => 'This pricing row belongs to another branch.',
                    ]);
                }

                $quote = $this->quote->forPricing(
                    $pricing,
                    $item,
                    $branchId,
                    $customerType
                );

                $isManual = (bool) ($item['is_manual_price'] ?? false);

                if ($isManual) {
                    if (! isset($item['unit_price'], $item['cost'])) {
                        throw ValidationException::withMessages([
                            "items.{$index}.unit_price" => 'Manual pricing requires both unit price and cost.',
                        ]);
                    }

                    $archetype = $pricing->product?->category?->calculation_archetype;
                    $factor = match ($archetype) {
                        'surface_area' => (float) $quote['area'],
                        'linear_dimension' => (float) ($item['length'] ?? 0),
                        default => 1.0,
                    };

                    $unitPrice = round((float) $item['unit_price'], 2);
                    $unitCost = round((float) $item['cost'], 2);
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
                    'file_name' => $item['file_name'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'area' => $quote['area'],
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

            if ($discount > $subtotal) {
                throw ValidationException::withMessages([
                    'discount' => 'Discount cannot exceed the invoice subtotal.',
                ]);
            }

            $grandTotal = round(max(0, $subtotal - $discount), 2);
            $totalProfit = round($grandTotal - $totalCost, 2);
            $branch = Branch::find($branchId);
            $prefix = $this->branchPrefix($branch?->name);
            $count = Invoice::query()
                ->where('created_date', now()->toDateString())
                ->where('branch_id', $branchId)
                ->count();
            $invoiceNumber = 'INV-'.$prefix.'-'.now()->format('Ymd').'-'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'branch_id' => $branchId,
                'created_date' => now()->toDateString(),
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
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                    'cost' => $line['cost'],
                    'profit' => $line['profit'],
                    'ordered_at' => now()->toDateString(),
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
                        'description' => 'Manual price applied to '.($line['file_name'] ?? 'order item'),
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
                'description' => 'Created invoice '.$invoiceNumber,
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

        event(new OrderUpdated($branchId));

        return $invoice;
    }

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
}
