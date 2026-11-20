<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
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
        return $user->role !== 'production' && $this->sameBranch($user, $invoice);
    }

    private function sameBranch(User $user, Invoice $invoice): bool
    {
        return $user->role === 'owner'
            || ($user->branch_id !== null && $invoice->branch_id === $user->branch_id);
    }
}
