<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Models\Machine;
use App\Models\MaterialBranchStock;
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
                'branch_id' => 'A production user must be assigned to a branch.',
            ]);
        }

        $hasStock = MaterialBranchStock::query()
            ->where('material_id', $data['material_id'])
            ->where('branch_id', $branchId)
            ->exists();

        if (! $hasStock) {
            throw ValidationException::withMessages([
                'material_id' => 'This material is not stocked in your branch.',
            ]);
        }

        if (isset($data['machine_id'])) {
            $machineInBranch = Machine::query()
                ->whereKey($data['machine_id'])
                ->where('branch_id', $branchId)
                ->exists();

            if (! $machineInBranch) {
                throw ValidationException::withMessages([
                    'machine_id' => 'This machine belongs to another branch.',
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
                    'designer_id' => 'This designer belongs to another branch.',
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
