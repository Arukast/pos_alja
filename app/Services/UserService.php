<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly LastActiveOwnerGuard $lastActiveOwnerGuard
    ) {}

    /**
     * Create a user and log activity.
     */
    public function createUser(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'role' => $data['role'],
                'branch_id' => $data['branch_id'] ?? null,
                'password' => Hash::make($data['password']),
            ]);

            $payload = $user->toArray();
            unset($payload['password']);

            $this->audit->record(
                $actor,
                'CREATE',
                "Membuat pengguna: {$user->username} ({$user->role})",
                $user,
                $payload,
            );

            return $user;
        });
    }

    /**
     * Update a user and log activity.
     */
    public function updateUser(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $oldData = $user->toArray();
            unset($oldData['password']);

            $updateData = [
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'role' => $data['role'],
                'branch_id' => $data['branch_id'] ?? null,
            ];

            if (! empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);

            $newData = $user->fresh()->toArray();
            unset($newData['password']);

            $this->audit->record(
                $actor,
                'UPDATE',
                "Memperbarui pengguna: {$user->username} ({$user->role})",
                $user,
                [
                    'before' => $oldData,
                    'after' => $newData,
                ],
            );

            return $user;
        });
    }

    /**
     * Toggle a user's active status and log activity.
     */
    public function toggleUserStatus(User $user, User $actor): User
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri dari Manajemen Pengguna. Gunakan menu Profil.',
            ]);
        }

        return DB::transaction(function () use ($user, $actor): User {
            $oldStatus = $user->is_active;

            if ($oldStatus) {
                $this->lastActiveOwnerGuard->ensureNotLastActiveOwner($user);
            }

            $user->update(['is_active' => ! $oldStatus]);

            $this->audit->record(
                $actor,
                'UPDATE',
                "Mengubah status aktif pengguna: {$user->username} menjadi ".($user->is_active ? 'aktif' : 'nonaktif'),
                $user,
                [
                    'before' => ['is_active' => $oldStatus],
                    'after' => ['is_active' => $user->is_active],
                ],
            );

            return $user;
        });
    }

    /**
     * Delete a user and log activity.
     */
    public function deleteUser(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $payload = $user->toArray();
            unset($payload['password']);

            $this->audit->record(
                $actor,
                'DELETE',
                "Menghapus pengguna: {$user->username} ({$user->role})",
                $user,
                $payload,
            );

            $user->delete();
        });
    }

    /**
     * Restore a deleted user and log activity.
     */
    public function restoreUser(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $user->restore();
            $user->update(['is_active' => true]);

            $payload = $user->toArray();
            unset($payload['password']);

            $this->audit->record(
                $actor,
                'RESTORE',
                "Memulihkan pengguna: {$user->username} ({$user->role})",
                $user,
                $payload,
            );
        });
    }
}
