<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function viewQueue(User $user): bool
    {
        return true;
    }

    public function viewSummary(User $user): bool
    {
        return $user->role === 'production';
    }
}
