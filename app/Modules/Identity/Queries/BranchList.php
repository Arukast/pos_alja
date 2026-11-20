<?php

declare(strict_types=1);

namespace App\Modules\Identity\Queries;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class BranchList
{
    public function paginate(?string $search, bool $includeDeleted): LengthAwarePaginator
    {
        return Branch::query()
            ->when($includeDeleted, fn (Builder $query) => $query->withTrashed())
            ->when($search, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }
}
