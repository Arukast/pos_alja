<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager', 'cashier'], true);
    }

    public function create(User $user): bool
    {
        return $user->role === 'cashier' && $user->branch_id !== null;
    }
}
