<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
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

    public function start(User $user, Order $order): bool
    {
        return $user->role === 'production' && $this->sameBranch($user, $order);
    }

    public function complete(User $user, Order $order): bool
    {
        return $user->role === 'production' && $this->sameBranch($user, $order);
    }

    public function fail(User $user, Order $order): bool
    {
        return $user->role === 'production' && $this->sameBranch($user, $order);
    }

    public function reopen(User $user, Order $order): bool
    {
        return $user->role === 'production' && $this->sameBranch($user, $order);
    }

    private function sameBranch(User $user, Order $order): bool
    {
        return $user->branch_id !== null
            && $order->invoice?->branch_id === $user->branch_id;
    }
}
