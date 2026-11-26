<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class BackupSettingPolicy
{
    public function view(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function update(User $user): bool
    {
        return $user->role === 'owner';
    }
}
