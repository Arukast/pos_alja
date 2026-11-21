<?php

declare(strict_types=1);

namespace App\Modules\Sales\Queries;

use App\Models\Expense;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Pagination\LengthAwarePaginator;

final class ExpenseList
{
    /**
     * @param  array{branch_id?: string|null, from?: string|null, to?: string|null}  $filters
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Expense::query()->with('branch');

        if ($actor->role === 'owner') {
            $branchId = $filters['branch_id'] ?? null;

            if (is_string($branchId) && $branchId !== '' && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
        } else {
            BranchScope::apply($query, $actor);
        }

        if ($filters['from'] ?? null) {
            $query->whereDate('incurred_at', '>=', $filters['from']);
        }

        if ($filters['to'] ?? null) {
            $query->whereDate('incurred_at', '<=', $filters['to']);
        }

        return $query
            ->orderByDesc('incurred_at')
            ->paginate(15)
            ->withQueryString();
    }
}
