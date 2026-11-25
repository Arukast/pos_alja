<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;

final class MaterialQueries
{
    /**
     * @param  array{branch_id?: string|null, search?: string|null, include_deleted?: bool, low_stock?: bool}  $filters
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $branchId = $actor->role === 'owner'
            ? ($filters['branch_id'] ?? 'all')
            : $actor->branch_id;

        if ($actor->role !== 'owner' && $branchId === null) {
            return Material::query()->whereRaw('1 = 0')->paginate(20);
        }

        $resolvedBranchId = is_string($branchId) && $branchId !== '' && $branchId !== 'all'
            ? $branchId
            : null;
        $lowStock = $filters['low_stock'] ?? false;

        $query = Material::query()->with([
            'category',
            'branchStocks' => function (HasMany $query) use ($resolvedBranchId): void {
                if ($resolvedBranchId !== null) {
                    $query->where('branch_id', $resolvedBranchId);
                }

                $query->with('branch');
            },
        ]);

        if ($actor->role !== 'production' && ($filters['include_deleted'] ?? false)) {
            $query->withTrashed();
        }

        if ($filters['search'] ?? null) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        if ($resolvedBranchId !== null || $lowStock) {
            $query->whereHas(
                'branchStocks',
                function (Builder $query) use ($resolvedBranchId, $lowStock): void {
                    if ($resolvedBranchId !== null) {
                        $query->where('branch_id', $resolvedBranchId);
                    }

                    if ($lowStock) {
                        $query->whereColumn('current_stock', '<=', 'min_stock');
                    }
                }
            );
        }

        $materials = $query->orderBy('name')->paginate(20)->withQueryString();

        foreach ($materials->items() as $material) {
            $material->setAttribute('resolved_stock_branch_id', $resolvedBranchId ?? 'all');
        }

        return $materials;
    }
}
