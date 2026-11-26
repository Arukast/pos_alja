<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class CatalogPolicy
{
    public function viewCategories(User $user): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        return $user->role === 'manager' && $user->branch_id !== null;
    }

    public function viewAttributes(User $user): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        return $user->role === 'manager' && $user->branch_id !== null;
    }

    public function viewProducts(User $user): bool
    {
        if ($user->role === 'owner') {
            return true;
        }

        return in_array($user->role, ['manager', 'cashier', 'designer'], true)
            && $user->branch_id !== null;
    }

    public function manageProducts(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function viewPricing(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function viewStockLedgers(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function manageCategories(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function manageAttributes(User $user): bool
    {
        return $user->role === 'owner';
    }

    public function managePricing(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }
}
