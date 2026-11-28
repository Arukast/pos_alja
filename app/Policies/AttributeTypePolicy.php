<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AttributeType;
use App\Models\User;

final class AttributeTypePolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        return $user->role === 'manager' && $user->branch_id !== null;
    }

    public function create(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function update(User $user, AttributeType $type): bool
    {
        return $user->role === 'owner';
    }
}
