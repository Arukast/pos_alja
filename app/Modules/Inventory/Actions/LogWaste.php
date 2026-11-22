<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Models\Machine;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Services\InventoryService;
use App\Support\SafeBroadcast;
use Illuminate\Validation\ValidationException;

final class LogWaste
{
    public function __construct(private readonly InventoryService $inventory) {}

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

        if (isset($data['machine_id'])) {
            $machineInBranch = Machine::query()
                ->whereKey($data['machine_id'])
                ->where('branch_id', $branchId)
                ->exists();

            if (! $machineInBranch) {
                throw ValidationException::withMessages([
                    'machine_id' => 'Mesin ini milik cabang lain.',
                ]);
            }
        }

        if (isset($data['designer_id'])) {
            $designerInBranch = User::query()
                ->whereKey($data['designer_id'])
                ->where('role', 'designer')
                ->where('branch_id', $branchId)
                ->exists();

            if (! $designerInBranch) {
                throw ValidationException::withMessages([
                    'designer_id' => 'Desainer ini milik cabang lain.',
                ]);
            }
        }

        if (isset($data['order_id'])) {
            $orderInBranch = Order::query()
                ->whereKey($data['order_id'])
                ->whereHas(
                    'invoice',
                    fn ($query) => $query->where('branch_id', $branchId)
                )
                ->exists();

            if (! $orderInBranch) {
                throw ValidationException::withMessages([
                    'order_id' => 'Pesanan ini milik cabang lain.',
                ]);
            }
        }

        $waste = $this->inventory->logWaste([
            'branch_id' => $branchId,
            'material_id' => $data['material_id'],
            'machine_id' => $data['machine_id'] ?? null,
            'designer_id' => $data['designer_id'] ?? null,
            'operator_id' => $operator->id,
            'order_id' => $data['order_id'] ?? null,
            'quantity' => $data['quantity'],
            'dimensions' => $data['dimensions'] ?? null,
            'notes' => $data['notes'] ?? 'Print failure',
        ]);

        SafeBroadcast::orderUpdated($branchId, $operator);

        return $waste->load(['material', 'machine', 'designer', 'operator', 'branch']);
    }
}
