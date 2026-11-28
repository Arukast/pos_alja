<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

final class MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager', 'production'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function update(User $user, Material $material): bool
    {
        return $user->role === 'owner';
    }

    public function restock(User $user, Material $material): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function adjust(User $user, Material $material): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function activate(User $user, Material $material): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function setMinimumStock(User $user, Material $material): bool
    {
        return $user->role === 'owner'
            || ($user->role === 'manager' && $user->branch_id !== null);
    }

    public function updateActive(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function setBranchAvailability(User $user, Material $material): bool
    {
        return $user->role === 'owner'
            || ($user->role === 'manager' && $user->branch_id !== null);
    }

    public function delete(User $user, Material $material): bool
    {
        return $user->role === 'owner';
    }

    public function restore(User $user, Material $material): bool
    {
        return $user->role === 'owner';
    }
}
