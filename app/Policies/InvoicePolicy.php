<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
{
    private const ROLES = ['owner', 'manager', 'cashier', 'designer', 'production'];

    public function viewAny(User $user): bool
    {
        return in_array($user->role, self::ROLES, true);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->sameBranch($user, $invoice);
    }

    public function ticket(User $user, Invoice $invoice): bool
    {
        return $this->sameBranch($user, $invoice);
    }

    public function receipt(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, ['owner', 'manager', 'cashier', 'designer'], true)
            && $this->sameBranch($user, $invoice);
    }

    private function sameBranch(User $user, Invoice $invoice): bool
    {
        return in_array($user->role, self::ROLES, true)
            && ($user->role === 'owner'
                || ($user->branch_id !== null && $invoice->branch_id === $user->branch_id));
    }
}
