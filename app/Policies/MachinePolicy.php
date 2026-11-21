<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Machine;
use App\Models\User;

final class MachinePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function update(User $user, Machine $machine): bool
    {
        return $user->role === 'owner'
            || ($user->role === 'manager' && $machine->branch_id === $user->branch_id);
    }

    public function delete(User $user, Machine $machine): bool
    {
        return $this->update($user, $machine);
    }

    public function restore(User $user, Machine $machine): bool
    {
        return $this->update($user, $machine);
    }
}
