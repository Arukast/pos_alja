<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function updateOwnProfile(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function deleteOwnProfile(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }
}
