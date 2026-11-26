<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SetMinimumStock
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Material $material, array $data, User $actor): void
    {
        $branchId = $this->resolveBranchId($data['branch_id'] ?? null, $actor);

        DB::transaction(function () use ($material, $branchId, $data): void {
            $stock = MaterialBranchStock::query()
                ->where('material_id', $material->id)
                ->where('branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if ($stock === null) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Data stok cabang belum ada untuk bahan ini. Aktifkan bahan pada cabang tersebut terlebih dahulu.',
                ]);
            }

            $stock->update(['min_stock' => $data['min_stock']]);
        });
    }

    private function resolveBranchId(?string $requested, User $actor): string
    {
        if ($actor->role === 'owner') {
            if (! is_string($requested) || $requested === '') {
                abort(422, 'Cabang wajib diisi.');
            }

            return $requested;
        }

        if ($actor->branch_id === null) {
            abort(403);
        }

        if (is_string($requested) && $requested !== '' && $requested !== $actor->branch_id) {
            abort(403);
        }

        return $actor->branch_id;
    }
}
