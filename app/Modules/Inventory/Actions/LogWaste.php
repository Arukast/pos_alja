<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Support\SafeBroadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LogWaste
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $operator): WastedMaterial
    {
        $branchId = $operator->branch_id;

        if ($branchId === null) {
            throw ValidationException::withMessages([
                'branch_id' => 'Pengguna produksi harus ditugaskan ke cabang.',
            ]);
        }

        $hasStock = MaterialBranchStock::query()
            ->where('material_id', $data['material_id'])
            ->where('branch_id', $branchId)
            ->exists();

        if (! $hasStock) {
            throw ValidationException::withMessages([
                'material_id' => 'Bahan ini tidak tersedia di cabang Anda.',
            ]);
        }

        if (isset($data['designer_id'])) {
            $designerInBranch = User::query()
                ->whereKey($data['designer_id'])
                ->where('role', 'designer')
                ->where('is_active', true)
                ->where('branch_id', $branchId)
                ->exists();

            if (! $designerInBranch) {
                throw ValidationException::withMessages([
                    'designer_id' => 'Desainer ini tidak aktif atau bukan milik cabang Anda.',
                ]);
            }
        }

        $order = Order::query()
            ->whereKey($data['order_id'])
            ->whereHas(
                'invoice',
                fn ($query) => $query->where('branch_id', $branchId)
            )
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages([
                'order_id' => 'Pesanan ini milik cabang lain.',
            ]);
        }

        if (! in_array($order->status, [0, 1, 3], true)) {
            throw ValidationException::withMessages([
                'order_id' => 'Pesanan yang sudah selesai tidak dapat dicatat sisa bahannya.',
            ]);
        }

        $material = Material::query()->withTrashed()->findOrFail($data['material_id']);
        $reason = $data['notes'] ?? 'Print failure';

        $waste = DB::transaction(function () use ($material, $branchId, $data, $operator, $order, $reason): WastedMaterial {
            $result = $this->inventory->logWaste([
                'branch_id' => $branchId,
                'material_id' => $material->id,
                'designer_id' => $data['designer_id'] ?? null,
                'operator_id' => $operator->id,
                'order_id' => $order->id,
                'quantity' => $data['quantity'],
                'dimensions' => $data['dimensions'] ?? null,
                'notes' => $reason,
                'actor' => $operator,
            ]);

            $waste = $result['waste'];

            $this->audit->record(
                $operator,
                'STOCK_USAGE',
                "Mencatat sisa bahan: {$material->name}",
                $material,
                [
                    'waste_id' => $waste->id,
                    'order_id' => $order->id,
                    'material_id' => $material->id,
                    'quantity' => (float) $data['quantity'],
                    'stock_before' => $result['old_stock'],
                    'stock_after' => $result['new_stock'],
                    'reason' => $reason,
                    'ledger_id' => $result['ledger_id'],
                ],
                $branchId,
            );

            return $waste;
        });

        SafeBroadcast::orderUpdated($branchId, $operator);

        return $waste->load(['material', 'designer', 'operator', 'branch']);
    }
}
