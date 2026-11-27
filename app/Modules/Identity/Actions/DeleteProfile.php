<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\LastActiveOwnerGuard;
use Illuminate\Support\Facades\DB;

final class DeleteProfile
{
    public function __construct(
        private readonly LastActiveOwnerGuard $lastActiveOwnerGuard,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->lastActiveOwnerGuard->ensureNotLastActiveOwner($user);

            $oldStatus = $user->is_active;
            $user->update(['is_active' => false]);

            $this->audit->record(
                $user,
                'UPDATE',
                "Menonaktifkan akun sendiri: {$user->username}",
                $user,
                [
                    'before' => ['is_active' => $oldStatus],
                    'after' => ['is_active' => false],
                ],
            );
        });
    }
}
