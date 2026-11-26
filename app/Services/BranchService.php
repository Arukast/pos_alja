<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BranchService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Create a new branch and log activity.
     */
    public function createBranch(array $data, User $actor): Branch
    {
        return DB::transaction(function () use ($data, $actor): Branch {
            $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
            $resellerDiscount = (float) ($data['reseller_discount_percent'] ?? ($settings['reseller_discount_percent'] ?? 0));
            $settings['reseller_discount_percent'] = $resellerDiscount;

            $branch = Branch::create([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'reseller_discount_percent' => $resellerDiscount,
                'settings' => $settings,
            ]);

            $this->audit->record(
                $actor,
                'CREATE',
                "Membuat cabang: {$branch->name} ({$branch->code})",
                $branch,
                $branch->toArray(),
            );

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

            $resellerDiscount = array_key_exists('reseller_discount_percent', $data)
                ? (float) $data['reseller_discount_percent']
                : (float) $branch->reseller_discount_percent;
            $settings = is_array($data['settings'] ?? null)
                ? $data['settings']
                : (is_array($branch->settings) ? $branch->settings : []);
            $settings['reseller_discount_percent'] = $resellerDiscount;

            $branch->update([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? $branch->is_active,
                'reseller_discount_percent' => $resellerDiscount,
                'settings' => $settings,
            ]);

            $newData = $branch->fresh()->toArray();

            $this->audit->record(
                $actor,
                'UPDATE',
                "Memperbarui cabang: {$branch->name} ({$branch->code})",
                $branch,
                [
                    'before' => $oldData,
                    'after' => $newData,
                ],
            );

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

            $this->audit->record(
                $actor,
                'UPDATE',
                "Mengubah status aktif cabang: {$branch->name} menjadi ".($newStatus ? 'aktif' : 'nonaktif'),
                $branch,
                [
                    'before' => ['is_active' => $oldStatus],
                    'after' => ['is_active' => $newStatus],
                ],
            );

            return $branch;
        });
    }

    /**
     * Delete a branch and log activity.
     */
    public function deleteBranch(Branch $branch, User $actor): void
    {
        DB::transaction(function () use ($branch, $actor): void {
            $this->audit->record(
                $actor,
                'DELETE',
                "Menghapus cabang: {$branch->name} ({$branch->code})",
                $branch,
                $branch->toArray(),
            );

            $branch->delete();
        });
    }
}
