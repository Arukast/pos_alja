<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLedger;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class CatalogQueries
{
    /**
     * @return Collection<int, ProductCategory>
     */
    public function categories(): Collection
    {
        return ProductCategory::query()->with('discountTiers')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, AttributeType>
     */
    public function attributeTypes(): Collection
    {
        return AttributeType::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Attribute>
     */
    public function attributes(User $actor, ?string $branchId): Collection
    {
        $query = Attribute::query()->with('branch');

        if ($actor->role === 'owner') {
            if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
        } else {
            $query->where(function (Builder $query) use ($actor): void {
                $query->where('branch_id', $actor->branch_id)
                    ->orWhereNull('branch_id');
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * @param  array{search?: string|null, category_id?: string|null, is_active?: bool|null, include_deleted?: bool}  $filters
     */
    public function products(array $filters): LengthAwarePaginator
    {
        $query = Product::query()->with('category');

        if (($filters['include_deleted'] ?? false) === true) {
            $query->withTrashed();
        }

        $isActive = $filters['is_active'] ?? null;

        if (is_bool($isActive)) {
            $query->where('is_active', $isActive);
        }

        return $query
            ->when(
                $filters['category_id'] ?? null,
                fn (Builder $query, string $categoryId) => $query->where(
                    'product_category_id',
                    $categoryId
                )
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search) => $query->where(
                    function (Builder $nested) use ($search): void {
                        $nested->whereLike('name', "%{$search}%", false)
                            ->orWhereHas(
                                'category',
                                fn (Builder $categoryQuery) => $categoryQuery->whereLike('name', "%{$search}%", false)
                            );
                    }
                )
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @param  array{branch_id?: string|null}  $filters
     */
    public function pricing(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Pricing::query()->with(['product', 'material', 'branch']);

        if ($actor->role === 'owner') {
            $branchId = $filters['branch_id'] ?? null;

            if (is_string($branchId) && $branchId !== '' && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
        } else {
            BranchScope::apply($query, $actor);
        }

        return $query->orderBy('min_qty')->paginate(20)->withQueryString();
    }

    /**
     * @param  array{branch_id?: string|null}  $filters
     */
    public function stockLedgers(User $actor, array $filters): LengthAwarePaginator
    {
        $query = StockLedger::query()->with(['material', 'branch', 'user']);

        if ($actor->role === 'owner') {
            $branchId = $filters['branch_id'] ?? null;

            if (is_string($branchId) && $branchId !== '' && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
        } else {
            BranchScope::apply($query, $actor);
        }

        return $query->orderByDesc('logged_at')->paginate(15)->withQueryString();
    }
}
