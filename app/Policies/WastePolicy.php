<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class WastePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'production';
    }

    public function create(User $user): bool
    {
        return $user->role === 'production' && $user->branch_id !== null;
    }
}
