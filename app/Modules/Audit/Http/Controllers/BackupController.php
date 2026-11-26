<?php

declare(strict_types=1);

namespace App\Modules\Audit\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\User;
use App\Modules\Audit\Http\Resources\BackupResource;
use App\Services\Backup\BackupDispatcher;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
}
