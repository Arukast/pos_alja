<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

final class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function create(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function download(User $user, Backup $backup): bool
    {
        return $user->role === 'owner';
    }

    public function update(User $user, Backup $backup): bool
    {
        return $user->role === 'owner';
    }
}
