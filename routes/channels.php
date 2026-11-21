<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('branch.{branchId}', function (?User $user, string $branchId): bool {
    return $user !== null
        && ($user->role === 'owner' || $user->branch_id === $branchId);
});

Broadcast::channel('owners', fn (?User $user): bool => $user?->role === 'owner');
