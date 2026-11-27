<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager', 'cashier', 'designer'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager', 'designer'], true);
    }
}
