<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\User;
use App\Services\Backup\BackupRetention;
use App\Services\Backup\DatabaseDumper;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Execute one queued backup row against its requested destination(s).
 */
final class BackupService
{
    private const FINAL_STATUSES = ['success', 'partial', 'failed'];

    public function __construct(
        private readonly DatabaseDumper $dumper,
        private readonly BackupRetention $retention,
        private readonly AuditLogger $audit,
    ) {}

    public function run(string $backupId): void
    {
        $backup = Backup::query()->find($backupId);

        if (! $backup instanceof Backup || $this->isFinal($backup)) {
            return;
        }

        $startedAt = now();
        $filename = 'pos_alja_'.$startedAt->format('Ymd_His').'_'.Str::lower(Str::random(6)).'.dump';
        $relativePath = $startedAt->format('Y/m/d').'/'.$filename;

        $backup->forceFill([
            'status' => 'running',
            'started_at' => $startedAt,
            'filename' => $filename,
        ])->save();

        $actor = $this->actor($backup);
        $settings = BackupSetting::current();

        $localDisk = (string) config('backup.disks.local');
        $remoteDisk = (string) config('backup.disks.remote');

        $wantsLocal = in_array($backup->destination, ['local', 'both'], true);
        $remoteRequired = in_array($backup->destination, ['remote', 'both'], true);

        if (! $wantsLocal && ! $remoteRequired) {
            $this->finalize($backup, 'failed', null, null, null, 'Tujuan backup tidak dikenal.', $this->meta($startedAt), $actor);

            return;
        }

        $remoteSkipped = false;
        $remoteError = null;

        if ($remoteRequired && ! $settings->remote_enabled) {
            if (! $wantsLocal) {
                $this->finalize($backup, 'failed', null, null, null, 'Backup remote dinonaktifkan, backup remote tidak dijalankan.', $this->meta($startedAt), $actor);

                return;
            }

            $remoteRequired = false;
            $remoteSkipped = true;
        } elseif ($remoteRequired && ! $settings->remoteConfigured()) {
            if (! $wantsLocal) {
                $this->finalize($backup, 'failed', null, null, null, 'Backup remote belum dikonfigurasi.', $this->meta($startedAt), $actor);

                return;
            }

            $remoteError = 'Backup remote belum dikonfigurasi.';
        }

        $localPath = null;
        $remotePath = null;
        $sizeBytes = null;

        if ($wantsLocal) {
            $this->rememberPending($backup, ['pending_local' => $relativePath]);

            try {
                Storage::disk($localDisk)->makeDirectory(dirname($relativePath));
                $source = Storage::disk($localDisk)->path($relativePath);
                $this->dumper->dump($source);
                $localPath = $relativePath;
                $sizeBytes = $this->size($source);
            } catch (Throwable $e) {
                Log::error('Backup local dump failed.', [
                    'backup_id' => $backup->id,
                    'user_id' => $actor?->id,
                    'branch_id' => $actor?->branch_id,
                    'error' => $e->getMessage(),
                ]);

                $this->deleteQuietly($localDisk, $relativePath);

                $this->finalize($backup, 'failed', null, null, null, $e->getMessage(), $this->meta($startedAt), $actor);

                return;
            }
        }

        if ($remoteRequired && $settings->remoteConfigured()) {
            $tempPath = $localPath === null
                ? rtrim((string) config('backup.tmp_directory'), '/').'/'.$filename
                : null;

            if ($tempPath !== null) {
                $this->rememberPending($backup, ['pending_temp' => $tempPath]);
            }

            try {
                if ($tempPath !== null) {
                    File::ensureDirectoryExists(dirname($tempPath));
                    $this->dumper->dump($tempPath);
                    $sizeBytes = $this->size($tempPath);
                }

                $source = $tempPath ?? Storage::disk($localDisk)->path($relativePath);
                $stream = fopen($source, 'rb');

                if ($stream === false) {
                    throw new RuntimeException('Berkas dump tidak dapat dibuka untuk diunggah.');
                }

                try {
                    Storage::disk($remoteDisk)->writeStream($relativePath, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                $remotePath = $relativePath;
            } catch (Throwable $e) {
                Log::error('Backup remote dump or upload failed.', [
                    'backup_id' => $backup->id,
                    'user_id' => $actor?->id,
                    'branch_id' => $actor?->branch_id,
                    'error' => $e->getMessage(),
                ]);

                $remoteError = $e->getMessage();
            } finally {
                if ($tempPath !== null) {
                    File::delete($tempPath);
                }
            }
        }

        if ($localPath === null && $remotePath === null) {
            $status = 'failed';
            $error = $remoteError ?? 'Backup gagal.';
        } elseif ($remoteRequired && $remotePath === null) {
            $status = 'partial';
            $error = $remoteError;
        } else {
            $status = 'success';
            $error = null;
        }

        $meta = $this->meta($startedAt);

        if ($remoteSkipped) {
            $meta['remote_skipped'] = true;
        }

        $written = ['local' => $localDisk, 'remote' => $remoteDisk];

        foreach ($written as $destination => $disk) {
            $path = $destination === 'local' ? $localPath : $remotePath;

            if ($path === null) {
                continue;
            }

            try {
                $meta['pruned'][$destination] = $this->retention->prune($disk, $this->windows($settings, $destination));
            } catch (Throwable $e) {
                Log::error('Backup retention failed.', [
                    'backup_id' => $backup->id,
                    'user_id' => $actor?->id,
                    'branch_id' => $actor?->branch_id,
                    'destination' => $destination,
                    'error' => $e->getMessage(),
                ]);

                $meta['retention_error'] = isset($meta['retention_error'])
                    ? $meta['retention_error'].' '.$e->getMessage()
                    : $e->getMessage();
            }
        }

        $meta['duration_ms'] = $this->duration($startedAt);

        $this->finalize($backup, $status, $localPath, $remotePath, $sizeBytes, $error, $meta, $actor);
    }

    /**
     * Mark a queued or running backup failed without duplicating its audit row.
     */
    public function fail(string $backupId, Throwable $e): void
    {
        $backup = Backup::query()->find($backupId);

        if (! $backup instanceof Backup || $this->isFinal($backup)) {
            return;
        }

        $meta = $backup->meta ?? [];

        $pendingLocal = $meta['pending_local'] ?? null;

        if (is_string($pendingLocal)) {
            $this->deleteQuietly((string) config('backup.disks.local'), $pendingLocal);
        }

        $pendingTemp = $meta['pending_temp'] ?? null;

        if (is_string($pendingTemp) && $this->insideTempDirectory($pendingTemp)) {
            File::delete($pendingTemp);
        }

        unset($meta['pending_local'], $meta['pending_temp']);

        if ($backup->local_path !== null) {
            $this->deleteQuietly((string) config('backup.disks.local'), $backup->local_path);
        }

        $meta['duration_ms'] = $backup->started_at === null
            ? 0
            : (int) $backup->started_at->diffInMilliseconds(now());

        $backup->forceFill([
            'status' => 'failed',
            'local_path' => null,
            'error' => $e->getMessage(),
            'meta' => $meta,
            'finished_at' => now(),
        ])->save();

        $this->writeAudit($backup, $this->actor($backup));
    }

    private function finalize(
        Backup $backup,
        string $status,
        ?string $localPath,
        ?string $remotePath,
        ?int $sizeBytes,
        ?string $error,
        array $meta,
        ?User $actor,
    ): void {
        $backup->forceFill([
            'status' => $status,
            'local_path' => $localPath,
            'remote_path' => $remotePath,
            'size_bytes' => $sizeBytes,
            'error' => $error,
            'meta' => $meta,
            'finished_at' => now(),
        ])->save();

        $this->writeAudit($backup, $actor);
    }

    private function actor(Backup $backup): ?User
    {
        if ($backup->user_id === null) {
            return null;
        }

        return User::query()->withTrashed()->find($backup->user_id);
    }

    private function isFinal(Backup $backup): bool
    {
        return in_array($backup->status, self::FINAL_STATUSES, true);
    }

    /**
     * Record cleanup targets before any file exists, so failed() can remove them.
     *
     * @param  array<string, string>  $pending
     */
    private function rememberPending(Backup $backup, array $pending): void
    {
        $backup->forceFill([
            'meta' => array_merge($backup->meta ?? [], $pending),
        ])->save();
    }

    private function insideTempDirectory(string $path): bool
    {
        $directory = rtrim((string) config('backup.tmp_directory'), '/');

        return $directory !== '' && str_starts_with($path, $directory.'/');
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(CarbonInterface $startedAt): array
    {
        return [
            'pruned' => [],
            'duration_ms' => $this->duration($startedAt),
        ];
    }

    /**
     * @return array{daily: int, weekly: int, monthly: int, yearly: int}
     */
    private function windows(BackupSetting $settings, string $destination): array
    {
        return [
            'daily' => (int) $settings->getAttribute($destination.'_daily_days'),
            'weekly' => (int) $settings->getAttribute($destination.'_weekly_weeks'),
            'monthly' => (int) $settings->getAttribute($destination.'_monthly_months'),
            'yearly' => (int) $settings->getAttribute($destination.'_yearly_years'),
        ];
    }

    private function duration(CarbonInterface $startedAt): int
    {
        return (int) $startedAt->diffInMilliseconds(now());
    }

    private function size(string $absolutePath): ?int
    {
        $size = filesize($absolutePath);

        return $size === false ? null : $size;
    }

    private function deleteQuietly(string $disk, string $path): void
    {
        try {
            Storage::disk($disk)->delete($path);
        } catch (Throwable) {
            // Cleanup is best effort; the recorded status already reflects the failure.
        }
    }

    private function writeAudit(Backup $backup, ?User $actor): void
    {
        try {
            $exists = ActivityLog::query()
                ->where('action', 'BACKUP')
                ->where('model_type', Backup::class)
                ->where('model_id', $backup->id)
                ->exists();

            if ($exists) {
                return;
            }

            $this->audit->record(
                $actor,
                'BACKUP',
                sprintf(
                    'Backup database %s (%s) selesai: %s.',
                    $backup->trigger,
                    $backup->destination,
                    $backup->status,
                ),
                $backup,
                [
                    'backup_id' => $backup->id,
                    'trigger' => $backup->trigger,
                    'destination' => $backup->destination,
                    'status' => $backup->status,
                    'filename' => $backup->filename,
                    'local_path' => $backup->local_path,
                    'remote_path' => $backup->remote_path,
                    'size' => $backup->size_bytes,
                    'error' => $backup->error,
                    'meta' => $backup->meta,
                ],
            );
        } catch (Throwable $e) {
            Log::error('Backup audit row could not be written.', [
                'backup_id' => $backup->id,
                'user_id' => $actor?->id,
                'branch_id' => $actor?->branch_id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
