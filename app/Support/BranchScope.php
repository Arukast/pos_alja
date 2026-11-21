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
}
