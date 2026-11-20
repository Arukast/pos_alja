<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function view(User $user, User $target): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        if ($user->role !== 'manager' || $user->branch_id === null) {
            return false;
        }

        return $target->branch_id === $user->branch_id
            && in_array($target->role, ['cashier', 'designer', 'production'], true);
    }

    public function updateOwnProfile(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function deleteOwnProfile(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }
}
