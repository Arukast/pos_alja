<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Pricing;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create an order invoice with its line items, status logs, and activity logs.
     */
    public function createInvoice(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $customerId = $data['customer_id'];
            $items = $data['items'];
            $discount = (int) ($data['discount'] ?? 0);
            $deadline = $data['deadline'] ?? null;
            $source = $data['source'] ?? 'Offline';
            $userId = $data['user_id'];
            $branchId = $data['branch_id'];

            $user = User::findOrFail($userId);
            $branch = Branch::find($branchId);

            $grandTotal = 0;
            $totalCost = 0;
            $totalProfit = 0;

            foreach ($items as $item) {
                $grandTotal += $item['subtotal'];
                $totalCost += $item['cost'];
                $totalProfit += $item['profit'];
            }

            $grandTotal = max(0, $grandTotal - $discount);
            $totalProfit = $grandTotal - $totalCost;

            // Generate clean prefix: e.g. "Main Branch (Jakarta)" -> "MBJ" or "MN"
            $branchPrefix = 'BR';
            if ($branch) {
                $cleanedName = preg_replace('/[^A-Za-z0-9 ]/', '', $branch->name);
                $words = explode(' ', $cleanedName);
                if (count($words) >= 2) {
                    $branchPrefix = strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
                } else {
                    $branchPrefix = strtoupper(substr($cleanedName, 0, 2));
                }
            }

            // Generate branch-prefixed structured invoice number
            $todayDate = now()->format('Ymd');
            $count = Invoice::where('created_date', now()->toDateString())
                ->where('branch_id', $branchId)
                ->count();
            $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $invoiceNumber = "INV-{$branchPrefix}-{$todayDate}-{$sequence}";

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'branch_id' => $branchId,
                'created_date' => now()->toDateString(),
                'customer_id' => $customerId,
                'designer_id' => $userId,
                'status' => 0, // Unpaid
                'discount' => $discount,
                'dp_amount' => 0,
                'accounts_receivable' => $grandTotal,
                'grand_total' => $grandTotal,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'deadline' => $deadline,
                'pickup_status' => 0,
                'source' => $source === 'Offline' ? 0 : 1,
            ]);

            foreach ($items as $item) {
                $order = Order::create([
                    'invoice_id' => $invoice->id,
                    'pricing_id' => $item['pricing_id'],
                    'operator_id' => null,
                    'file_name' => $item['file_name'],
                    'notes' => $item['notes'] ?: $item['details_string'],
                    'area' => $item['width'] * $item['length'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'cost' => $item['cost'],
                    'profit' => $item['profit'],
                    'ordered_at' => now()->toDateString(),
                    'status' => 0, // Pending
                    'pickup_status' => 0,
                    'is_manual_price' => $item['is_manual_price'],
                ]);

                // Create initial Status log for each order item
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => 0, // Pending
                    'user_id' => $userId,
                    'user_name' => $user->name,
                    'notes' => 'Order created by designer',
                ]);
            }

            // Create global Activity Log
            ActivityLog::create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'username' => $user->username,
                'role' => $user->role,
                'action' => 'CREATE',
                'description' => "Placed order invoice: {$invoice->invoice_number} (Grand Total: {$invoice->grand_total})",
                'model_type' => Invoice::class,
                'model_id' => $invoice->id,
                'payload' => $invoice->toArray(),
            ]);

            return $invoice;
        });
    }

    /**
     * Start production on an order.
     */
    public function startProduction(Order $order, User $operator): void
    {
        DB::transaction(function () use ($order, $operator) {
            $order->status = 1; // Processing/Printing
            $order->operator_id = $operator->id;
            $order->save();

            // Proactively deduct stock when production starts if a pricing tier is linked
            if ($order->pricing_id) {
                $pricing = Pricing::with('product')->find($order->pricing_id);
                if ($pricing && $pricing->material_id) {
                    $isArea = $pricing->product && $pricing->product->pricing_type === 'area';

                    $qtyToDeduct = $isArea
                        ? (float) ($order->area * $order->quantity)
                        : (float) $order->quantity;

                    $inventoryService = new InventoryService;
                    $inventoryService->deductStockOnPrint($pricing->material_id, $qtyToDeduct);
                }
            }

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 1,
                'user_id' => $operator->id,
                'user_name' => $operator->name,
                'notes' => 'Production started',
            ]);
        });
    }

    /**
     * Complete production on an order.
     */
    public function completeProduction(Order $order, User $operator): void
    {
        DB::transaction(function () use ($order, $operator) {
            $order->status = 2; // Completed
            $order->operator_id = $operator->id;
            $order->save();

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 2,
                'user_id' => $operator->id,
                'user_name' => $operator->name,
                'notes' => 'Production completed',
            ]);
        });
    }
}
