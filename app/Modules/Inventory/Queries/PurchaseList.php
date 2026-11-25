<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Queries;

use App\Models\Purchase;
use App\Models\User;
use App\Support\BranchScope;
use Illuminate\Pagination\LengthAwarePaginator;

final class PurchaseList
{
    public function paginate(User $actor, ?string $branchId = null): LengthAwarePaginator
    {
        $query = Purchase::query()->with(['branch', 'user', 'items.material']);

        if ($actor->role === 'owner') {
            if ($branchId !== null && $branchId !== '' && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }
        } else {
            BranchScope::apply($query, $actor);
        }

        return $query
            ->orderByDesc('purchased_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();
    }
}
