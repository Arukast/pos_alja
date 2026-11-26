<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\BackupSetting;
use App\Models\User;
use App\Modules\Audit\Http\Requests\UpdateBackupSettingsRequest;
use App\Modules\Audit\Http\Resources\BackupSettingResource;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class BackupSettingController extends Controller
{
    public function show(): JsonResponse
    {
        Gate::authorize('view', BackupSetting::class);

        return response()->json([
            'settings' => new BackupSettingResource(BackupSetting::current()),
        ]);
    }

    public function update(
        UpdateBackupSettingsRequest $request,
        AuditLogger $audit,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $validated = $request->validated();

        // Outside the transaction: current() may create the row and catches a unique violation.
        $initial = BackupSetting::current();

        $settings = DB::transaction(function () use ($initial, $validated, $actor, $audit): BackupSetting {
            $locked = BackupSetting::query()
                ->whereKey($initial->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $before = $locked->only(array_keys($validated));

            $locked->update($validated);
            $locked->refresh();

            $audit->record(
                $actor,
                'UPDATE',
                'Memperbarui pengaturan backup.',
                $locked,
                [
                    'before' => $before,
                    'after' => $locked->only(array_keys($validated)),
                ],
            );

            return $locked;
        });

        return response()->json([
            'settings' => new BackupSettingResource($settings),
        ]);
    }
}
