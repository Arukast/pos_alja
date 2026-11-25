<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class PurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'owner'
            || ($user->role === 'manager' && $user->branch_id !== null);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }
}
