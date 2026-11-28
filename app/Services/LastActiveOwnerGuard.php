<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Prevents the only active owner from being removed, deactivated, or demoted.
 */
final class LastActiveOwnerGuard
{
    public function ensureNotLastActiveOwner(User $target, string $errorKey = 'is_active'): void
    {
        if ($target->role !== 'owner' || ! $target->is_active || $target->trashed()) {
            return;
        }

        $activeOwnerIds = User::query()
            ->where('role', 'owner')
            ->where('is_active', true)
            ->lockForUpdate()
            ->pluck('id');

        if ($activeOwnerIds->reject(fn (string $id): bool => $id === $target->id)->isEmpty()) {
            throw ValidationException::withMessages([
                $errorKey => 'Pemilik aktif terakhir tidak dapat dinonaktifkan, dihapus, atau diturunkan perannya.',
            ]);
        }
    }
}
