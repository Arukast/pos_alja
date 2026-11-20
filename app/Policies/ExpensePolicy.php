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
}
