<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SetMaterialBranchAvailability
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Material $material, array $data, User $actor): void
    {
        if ($actor->role !== 'owner'
            && array_key_exists('branch_id', $data)
            && $data['branch_id'] !== null
            && $data['branch_id'] !== $actor->branch_id) {
            abort(403);
        }

        $branchId = BranchScope::writeBranchId($actor, $data['branch_id'] ?? null);

        DB::transaction(function () use ($material, $branchId, $data): void {
            $stock = MaterialBranchStock::query()
                ->where('material_id', $material->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if ($stock === null) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Bahan belum tersedia di cabang ini.',
                ]);
            }

            $stock->update(['is_active' => (bool) $data['is_active']]);
        });
    }
}
