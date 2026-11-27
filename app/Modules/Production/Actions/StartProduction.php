<?php

declare(strict_types=1);

namespace App\Modules\Production\Actions;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StartProduction
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(Order $order, User $operator): Order
    {
        $updated = DB::transaction(function () use ($order, $operator): Order {
            $locked = Order::query()
                ->with(['invoice', 'pricing.material', 'pricing.product'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 0) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya pesanan dalam antrean yang dapat memulai produksi.',
                ]);
            }

            $statusBefore = $locked->status;
            $operatorBefore = $locked->operator_id;

            $pricing = $locked->pricing;

            // A null material is only valid for a product that needs no material;
            // a soft-deleted material keeps material_id set but material null, so it stays rejected.
            $isNoMaterialOrder = $pricing !== null
                && $pricing->material_id === null
                && $pricing->product?->requires_material === false;

            if (! $isNoMaterialOrder && $pricing !== null && $pricing->material === null) {
                throw ValidationException::withMessages([
                    'material' => 'Bahan untuk pesanan ini tidak ditemukan.',
                ]);
            }

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

            $materialUsage = [];

            if ($pricing !== null && $pricing->material_id !== null) {
                $quantity = $unit === 'm2'
                    ? $area * (float) $locked->quantity
                    : (float) $locked->quantity;

                $branchId = $operator->branch_id ?? $locked->invoice?->branch_id;

                if ($branchId !== null) {
                    $usageLedger = $this->inventory->deductStockOnPrint(
                        $pricing->material_id,
                        $quantity,
                        $branchId,
                        $locked->id,
                        $operator,
                    );

                    $materialUsage[] = [
                        'material_id' => $pricing->material_id,
                        'quantity' => round($quantity, 2),
                        'stock_before' => (float) $usageLedger->initial_stock,
                        'stock_after' => (float) $usageLedger->remaining_stock,
                        'ledger_id' => $usageLedger->id,
                        'direction' => 'usage',
                    ];
                }
            }

            OrderStatusLog::create([
                'order_id' => $locked->id,
                'status' => 1,
                'user_id' => $operator->id,
                'user_name' => $operator->name,
                'notes' => 'Production started',
            ]);

            $this->audit->record(
                $operator,
                'ORDER_START',
                'Memulai produksi pesanan '.$locked->file_name,
                $locked,
                [
                    'status_before' => $statusBefore,
                    'status_after' => $locked->status,
                    'operator_before' => $operatorBefore,
                    'operator_after' => $locked->operator_id,
                    'material_usage' => $materialUsage,
                ],
            );

            return $locked;
        });

        SafeBroadcast::orderUpdated($updated->invoice->branch_id, $operator);

        return $updated->load(['invoice.customer', 'invoice.designer', 'operator', 'pricing']);
    }
}
