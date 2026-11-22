<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MachineService
{
    /**
     * Create a new printer machine and log activity.
     */
    public function createMachine(array $data, User $actor): Machine
    {
        try {
            return DB::transaction(function () use ($data, $actor): Machine {
                $machine = Machine::create([
                    'branch_id' => $data['branch_id'] ?? null,
                    'name' => $data['name'],
                    'cost' => (float) ($data['cost'] ?? 0.0),
                ]);

                ActivityLog::create([
                    'branch_id' => $actor->branch_id,
                    'user_id' => $actor->id,
                    'username' => $actor->username,
                    'role' => $actor->role,
                    'action' => 'CREATE',
                    'description' => "Membuat mesin cetak: {$machine->name} di cabang ID: ".($machine->branch_id ?? 'Global'),
                    'model_type' => Machine::class,
                    'model_id' => $machine->id,
                    'payload' => $machine->toArray(),
                ]);

                return $machine;
            });
        } catch (\Throwable $e) {
            Log::error('Machine creation failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing printer machine and log activity.
     */
    public function updateMachine(Machine $machine, array $data, User $actor): Machine
    {
        try {
            return DB::transaction(function () use ($machine, $data, $actor): Machine {
                $oldData = $machine->toArray();

                $machine->update([
                    'branch_id' => $data['branch_id'] ?? $machine->branch_id,
                    'name' => $data['name'] ?? $machine->name,
                    'cost' => isset($data['cost']) ? (float) $data['cost'] : (float) $machine->cost,
                ]);

                $newData = $machine->fresh()->toArray();

                ActivityLog::create([
                    'branch_id' => $actor->branch_id,
                    'user_id' => $actor->id,
                    'username' => $actor->username,
                    'role' => $actor->role,
                    'action' => 'UPDATE',
                    'description' => "Memperbarui mesin cetak: {$machine->name}",
                    'model_type' => Machine::class,
                    'model_id' => $machine->id,
                    'payload' => [
                        'before' => $oldData,
                        'after' => $newData,
                    ],
                ]);

                return $machine;
            });
        } catch (\Throwable $e) {
            Log::error('Machine update failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a printer machine (soft delete) and log activity.
     */
    public function deleteMachine(Machine $machine, User $actor): void
    {
        try {
            DB::transaction(function () use ($machine, $actor): void {
                ActivityLog::create([
                    'branch_id' => $actor->branch_id,
                    'user_id' => $actor->id,
                    'username' => $actor->username,
                    'role' => $actor->role,
                    'action' => 'DELETE',
                    'description' => "Menghapus sementara mesin cetak: {$machine->name}",
                    'model_type' => Machine::class,
                    'model_id' => $machine->id,
                    'payload' => $machine->toArray(),
                ]);

                $machine->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Machine deletion failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Restore a deleted printer machine and log activity.
     */
    public function restoreMachine(Machine $machine, User $actor): void
    {
        try {
            DB::transaction(function () use ($machine, $actor): void {
                $machine->restore();

                ActivityLog::create([
                    'branch_id' => $actor->branch_id,
                    'user_id' => $actor->id,
                    'username' => $actor->username,
                    'role' => $actor->role,
                    'action' => 'RESTORE',
                    'description' => "Memulihkan mesin cetak: {$machine->name}",
                    'model_type' => Machine::class,
                    'model_id' => $machine->id,
                    'payload' => $machine->toArray(),
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Machine restoration failed.', [
                'user_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
