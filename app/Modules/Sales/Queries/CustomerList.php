<?php

declare(strict_types=1);

namespace App\Modules\Sales\Queries;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class CustomerList
{
    public function paginate(?string $search): LengthAwarePaginator
    {
        return Customer::query()
            ->when($search, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);
    }
}
