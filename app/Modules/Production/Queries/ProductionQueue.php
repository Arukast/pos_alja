<?php

declare(strict_types=1);

namespace App\Modules\Production\Queries;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductionQueue
{
    /**
     * @param  array{branch_id?: string|null, status?: int|null, search?: string|null}  $filters
     */
    public function paginate(User $actor, array $filters): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['invoice.customer', 'invoice.designer', 'operator'])
            ->withExists('wastedMaterials')
            ->whereIn('status', [0, 1, 3]);

        if (($filters['status'] ?? null) !== null) {
            $query->where('status', $filters['status']);
        }

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function (Builder $query) use ($search): void {
                $query->where('file_name', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('invoice', function (Builder $query) use ($search): void {
                        $query->where('invoice_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($actor->role === 'owner') {
            $branchId = $filters['branch_id'] ?? null;

            if (is_string($branchId) && $branchId !== '' && $branchId !== 'all') {
                $query->whereHas(
                    'invoice',
                    fn (Builder $query) => $query->where('branch_id', $branchId)
                );
            }
        } else {
            $query->whereHas(
                'invoice',
                fn (Builder $query) => $query->where('branch_id', $actor->branch_id)
            );
        }

        return $query
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();
    }
}
