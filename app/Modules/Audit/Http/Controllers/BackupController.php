<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\User;
use App\Modules\Audit\Http\Requests\PinBackupRequest;
use App\Modules\Audit\Http\Resources\BackupResource;
use App\Services\AuditLogger;
use App\Services\Backup\BackupDispatcher;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class BackupController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Backup::class);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 10);

        return BackupResource::collection(
            Backup::query()
                ->with('user')
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString(),
        );
    }

    public function store(
        Request $request,
        BackupService $service,
        BackupDispatcher $dispatcher,
    ): JsonResponse {
        Gate::authorize('create', Backup::class);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $manualActive = Backup::query()
            ->where('trigger', 'manual')
            ->whereIn('status', ['queued', 'running'])
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($manualActive) {
            return response()->json([
                'message' => 'Backup manual sedang berjalan.',
            ], 409);
        }

        $settings = BackupSetting::current();

        if ($settings->remote_enabled && ! $settings->remoteConfigured()) {
            return response()->json([
                'message' => 'Backup remote belum dikonfigurasi.',
            ], 422);
        }

        $backup = Backup::query()->create([
            'trigger' => 'manual',
            'destination' => $settings->remote_enabled ? 'both' : 'local',
            'status' => 'queued',
            'user_id' => $actor->id,
        ]);

        try {
            $dispatcher->dispatch($backup->id);
        } catch (Throwable $e) {
            Log::error('Manual backup dispatch failed.', [
                'backup_id' => $backup->id,
                'trigger' => $backup->trigger,
                'destination' => $backup->destination,
                'user_id' => $actor->id,
                'error' => $e->getMessage(),
            ]);

            $service->fail($backup->id, $e);

            return response()->json([
                'message' => 'Backup gagal dimulai.',
            ], 500);
        }

        return response()->json([
            'message' => 'Backup dimulai.',
            'backup' => new BackupResource($backup->loadMissing('user')),
        ], 202);
    }

    public function download(
        Request $request,
        Backup $backup,
        AuditLogger $audit,
    ): StreamedResponse|JsonResponse {
        Gate::authorize('download', $backup);

        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $source = null;
        $disk = null;
        $path = null;

        foreach (['local' => 'local_path', 'remote' => 'remote_path'] as $candidate => $column) {
            $candidatePath = $backup->{$column};

            if (
                is_string($candidatePath)
                && $candidatePath !== ''
                && Storage::disk((string) config('backup.disks.'.$candidate))->exists($candidatePath)
            ) {
                $source = $candidate;
                $disk = (string) config('backup.disks.'.$candidate);
                $path = $candidatePath;

                break;
            }
        }

        if ($source === null || $disk === null || $path === null) {
            return response()->json([
                'message' => 'Berkas backup tidak ditemukan.',
            ], 410);
        }

        $audit->record(
            $actor,
            'DOWNLOAD',
            $source === 'local'
                ? 'Mengunduh berkas backup dari penyimpanan lokal.'
                : 'Mengunduh berkas backup dari penyimpanan R2.',
            $backup,
            [
                'backup_id' => $backup->id,
                'filename' => $backup->filename,
                'source' => $source,
            ],
        );

        $filename = $backup->filename;

        return Storage::disk($disk)->download(
            $path,
            is_string($filename) ? $filename : null,
        );
    }

    public function pin(
        PinBackupRequest $request,
        Backup $backup,
        AuditLogger $audit,
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof User) {
            abort(401);
        }

        $pinned = (bool) $request->validated('pinned');

        $backup = DB::transaction(function () use ($backup, $pinned, $actor, $audit): Backup {
            $locked = Backup::query()
                ->whereKey($backup->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = (bool) $locked->is_pinned;

            $locked->forceFill(['is_pinned' => $pinned])->save();

            $audit->record(
                $actor,
                $pinned ? 'PIN' : 'UNPIN',
                ($pinned ? 'Menyematkan' : 'Melepas sematan').' backup: '.((string) ($locked->filename ?? '-')),
                $locked,
                [
                    'backup_id' => $locked->id,
                    'is_pinned_before' => $before,
                    'is_pinned_after' => $pinned,
                ],
            );

            return $locked;
        });

        return response()->json([
            'backup' => new BackupResource($backup->loadMissing('user')),
        ]);
    }
}
