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
                ->with(['invoice', 'pricing.material'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 0) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan dalam antrean yang dapat memulai produksi.',
                ]);
            }

            $pricing = $locked->pricing;
            $unit = $pricing?->material?->unit;
            $area = (float) $locked->area;

            if ($unit === 'm2' && $area <= 0) {
                throw ValidationException::withMessages([
                    'area' => 'Luas pesanan harus lebih dari 0 untuk bahan bersatuan m².',
                ]);
            }

            $locked->status = 1;
            $locked->operator_id = $operator->id;
            $locked->save();

            if ($pricing !== null) {
                $quantity = $unit === 'm2'
                    ? $area * (float) $locked->quantity
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
