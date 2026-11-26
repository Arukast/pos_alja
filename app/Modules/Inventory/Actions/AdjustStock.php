<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Models\Material;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Support\BranchScope;
use Illuminate\Support\Facades\DB;

final class AdjustStock
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Material $material, array $data, User $actor): Material
    {
        $branchId = BranchScope::writeBranchId($actor, $data['branch_id'] ?? null);
        $reason = (string) $data['reason'];
        $absolute = ($data['new_quantity'] ?? null) !== null;
        $quantity = (float) ($absolute ? $data['new_quantity'] : $data['delta']);

        DB::transaction(function () use ($material, $branchId, $reason, $absolute, $quantity, $actor): void {
            $movement = $this->inventory->applyMovement(
                $material->id,
                $branchId,
                'adjustment',
                $quantity,
                reason: $reason,
                actor: $actor,
                absolute: $absolute,
            );

            $this->audit->record(
                $actor,
                'ADJUST',
                'Menyesuaikan stok bahan',
                $material,
                [
                    'before' => $movement['old_stock'],
                    'after' => $movement['new_stock'],
                    'delta' => round($movement['new_stock'] - $movement['old_stock'], 2),
                    'reason' => $reason,
                ],
                $branchId,
            );
        });

        return $material;
    }
}
