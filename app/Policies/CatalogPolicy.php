<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class CatalogPolicy
{
    public function viewCategories(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function viewAttributeTypes(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function viewAttributes(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function viewProducts(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager', 'cashier', 'designer'], true);
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
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function manageAttributeTypes(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function manageAttributes(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }

    public function managePricing(User $user): bool
    {
        return in_array($user->role, ['owner', 'manager'], true);
    }
}
