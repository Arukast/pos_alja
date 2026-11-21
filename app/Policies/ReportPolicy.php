<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class ReportPolicy
{
    public function viewDashboard(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager', 'cashier', 'designer', 'production'], true);
    }

    public function viewOwnerReports(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function viewLeaderboard(User $user): bool
    {
        return $user->role === 'manager';
    }
}
