<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
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

            ActivityLog::create([
                'branch_id' => $actor->branch_id,
                'user_id' => $actor->id,
                'username' => $actor->username,
                'role' => $actor->role,
                'action' => 'CREATE',
                'description' => "Membuat pengguna: {$user->username} ({$user->role})",
                'model_type' => User::class,
                'model_id' => $user->id,
                'payload' => $payload,
            ]);

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

            ActivityLog::create([
                'branch_id' => $actor->branch_id,
                'user_id' => $actor->id,
                'username' => $actor->username,
                'role' => $actor->role,
                'action' => 'UPDATE',
                'description' => "Memperbarui pengguna: {$user->username} ({$user->role})",
                'model_type' => User::class,
                'model_id' => $user->id,
                'payload' => [
                    'before' => $oldData,
                    'after' => $newData,
                ],
            ]);

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

            ActivityLog::create([
                'branch_id' => $actor->branch_id,
                'user_id' => $actor->id,
                'username' => $actor->username,
                'role' => $actor->role,
                'action' => 'DELETE',
                'description' => "Menghapus pengguna: {$user->username} ({$user->role})",
                'model_type' => User::class,
                'model_id' => $user->id,
                'payload' => $payload,
            ]);

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

            $payload = $user->toArray();
            unset($payload['password']);

            ActivityLog::create([
                'branch_id' => $actor->branch_id,
                'user_id' => $actor->id,
                'username' => $actor->username,
                'role' => $actor->role,
                'action' => 'RESTORE',
                'description' => "Memulihkan pengguna: {$user->username} ({$user->role})",
                'model_type' => User::class,
                'model_id' => $user->id,
                'payload' => $payload,
            ]);
        });
    }
}
