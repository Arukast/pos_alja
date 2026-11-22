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
     * @param  array{branch_id?: string|null, search?: string|null, include_deleted?: bool}  $filters
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $branchId = $actor->role === 'owner'
            ? ($filters['branch_id'] ?? 'all')
            : $actor->branch_id;

        if ($actor->role !== 'owner' && $branchId === null) {
            return Material::query()->whereRaw('1 = 0')->paginate(20);
        }

        $query = Material::query()->with([
            'category',
            'branchStocks' => function (HasMany $query) use ($branchId): void {
                if (is_string($branchId) && $branchId !== '' && $branchId !== 'all') {
                    $query->where('branch_id', $branchId);
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

        if (is_string($branchId) && $branchId !== '' && $branchId !== 'all') {
            $query->whereHas(
                'branchStocks',
                fn (Builder $query) => $query->where('branch_id', $branchId)
            );
        }

        return $query->orderBy('name')->paginate(20)->withQueryString();
    }
}
