<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BranchService
{
    /**
     * Create a new branch and log activity.
     */
    public function createBranch(array $data, User $actor): Branch
    {
        return DB::transaction(function () use ($data, $actor): Branch {
            $branch = Branch::create([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'reseller_discount_percent' => $data['reseller_discount_percent'] ?? 0,
                'settings' => $data['settings'] ?? [],
            ]);

            ActivityLog::create([
                'branch_id' => $actor->branch_id,
                'user_id' => $actor->id,
                'username' => $actor->username,
                'role' => $actor->role,
                'action' => 'CREATE',
                'description' => "Membuat cabang: {$branch->name} ({$branch->code})",
                'model_type' => Branch::class,
                'model_id' => $branch->id,
                'payload' => $branch->toArray(),
            ]);

            return $branch;
        });
    }

    /**
     * Update an existing branch and log activity.
     */
    public function updateBranch(Branch $branch, array $data, User $actor): Branch
    {
        return DB::transaction(function () use ($branch, $data, $actor): Branch {
            $oldData = $branch->toArray();

            $branch->update([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? $branch->is_active,
                'reseller_discount_percent' => array_key_exists('reseller_discount_percent', $data)
                    ? $data['reseller_discount_percent']
                    : $branch->reseller_discount_percent,
                'settings' => $data['settings'] ?? $branch->settings ?? [],
            ]);

            $newData = $branch->fresh()->toArray();

            ActivityLog::create([
                'branch_id' => $actor->branch_id,
                'user_id' => $actor->id,
                'username' => $actor->username,
                'role' => $actor->role,
                'action' => 'UPDATE',
                'description' => "Memperbarui cabang: {$branch->name} ({$branch->code})",
                'model_type' => Branch::class,
                'model_id' => $branch->id,
                'payload' => [
                    'before' => $oldData,
                    'after' => $newData,
                ],
            ]);

            return $branch;
        });
    }

    /**
     * Toggle branch active status and log activity.
     */
    public function toggleBranchStatus(Branch $branch, User $actor): Branch
    {
        return DB::transaction(function () use ($branch, $actor): Branch {
            $oldStatus = $branch->is_active;
            $newStatus = ! $oldStatus;

            $branch->update(['is_active' => $newStatus]);

            ActivityLog::create([
                'branch_id' => $actor->branch_id,
                'user_id' => $actor->id,
                'username' => $actor->username,
                'role' => $actor->role,
                'action' => 'UPDATE',
                'description' => "Mengubah status aktif cabang: {$branch->name} menjadi ".($newStatus ? 'aktif' : 'nonaktif'),
                'model_type' => Branch::class,
                'model_id' => $branch->id,
                'payload' => [
                    'before' => ['is_active' => $oldStatus],
                    'after' => ['is_active' => $newStatus],
                ],
            ]);

            return $branch;
        });
    }

    /**
     * Delete a branch and log activity.
     */
    public function deleteBranch(Branch $branch, User $actor): void
    {
        DB::transaction(function () use ($branch, $actor): void {
            ActivityLog::create([
                'branch_id' => $actor->branch_id,
                'user_id' => $actor->id,
                'username' => $actor->username,
                'role' => $actor->role,
                'action' => 'DELETE',
                'description' => "Menghapus cabang: {$branch->name} ({$branch->code})",
                'model_type' => Branch::class,
                'model_id' => $branch->id,
                'payload' => $branch->toArray(),
            ]);

            $branch->delete();
        });
    }
}
