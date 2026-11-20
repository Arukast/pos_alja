<?php

declare(strict_types=1);

namespace App\Modules\Sales\Queries;

use App\Models\Invoice;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class InvoiceList
{
    /**
     * @param  array{branch_id?: string|null, status?: int|null, pickup_status?: int|null, search?: string|null}  $filters
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Invoice::query()->with(['customer', 'designer', 'branch']);

        if ($actor->role === 'owner') {
            $branchId = $filters['branch_id'] ?? null;

            if (is_string($branchId) && $branchId !== '' && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
        } else {
            BranchScope::apply($query, $actor);
        }

        if (($filters['status'] ?? null) !== null) {
            $query->where('status', $filters['status']);
        }

        if (($filters['pickup_status'] ?? null) !== null) {
            $query->where('pickup_status', $filters['pickup_status']);
        }

        if ($filters['search'] ?? null) {
            $search = $filters['search'];

            $query->where(function (Builder $query) use ($search): void {
                $query->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function (Builder $query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        return $query
            ->orderByDesc('created_date')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }
}
