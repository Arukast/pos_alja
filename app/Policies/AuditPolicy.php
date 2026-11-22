<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ActivityLog;
use App\Models\User;

final class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $user->role === 'owner';
    }
}
