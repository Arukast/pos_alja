<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class BranchScope
{
    /**
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    public static function apply(Builder $query, User $actor, string $column = 'branch_id'): Builder
    {
        if ($actor->role === 'owner') {
            return $query;
        }

        if ($actor->branch_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($column, $actor->branch_id);
    }

    /**
     * Resolve the branch a write targets: owners must name one, staff are forced to theirs.
     */
    public static function writeBranchId(User $actor, ?string $requested): string
    {
        $branchId = $actor->role === 'owner' ? $requested : $actor->branch_id;

        if (! is_string($branchId) || $branchId === '') {
            abort(422, 'Cabang wajib diisi.');
        }

        return $branchId;
    }
}
