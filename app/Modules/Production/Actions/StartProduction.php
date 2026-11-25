<?php

declare(strict_types=1);

namespace App\Modules\Production\Actions;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use App\Services\InventoryService;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StartProduction
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function execute(Order $order, User $operator): Order
    {
        $updated = DB::transaction(function () use ($order, $operator): Order {
            $locked = Order::query()
                ->with(['invoice', 'pricing.product.category'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 0) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan dalam antrean yang dapat memulai produksi.',
                ]);
            }

            $locked->status = 1;
            $locked->operator_id = $operator->id;
            $locked->save();

            $pricing = $locked->pricing;

            if ($pricing !== null) {
                $archetype = $pricing->product?->category?->calculation_archetype;
                // shortcut: orders store area but not length, so linear jobs fall back to quantity.
                $quantity = $archetype === 'surface_area'
                    ? (float) $locked->area * (float) $locked->quantity
                    : (float) $locked->quantity;

                $branchId = $operator->branch_id ?? $locked->invoice?->branch_id;

                if ($branchId !== null) {
                    $this->inventory->deductStockOnPrint(
                        $pricing->material_id,
                        $quantity,
                        $branchId,
                        $locked->id,
                        $operator,
                    );
                }
            }

            OrderStatusLog::create([
                'order_id' => $locked->id,
                'status' => 1,
                'user_id' => $operator->id,
                'user_name' => $operator->name,
                'notes' => 'Production started',
            ]);

            return $locked;
        });

        SafeBroadcast::orderUpdated($updated->invoice->branch_id, $operator);

        return $updated->load(['invoice.customer', 'invoice.designer', 'operator', 'pricing']);
    }
}
