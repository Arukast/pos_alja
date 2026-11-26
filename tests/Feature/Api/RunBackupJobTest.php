<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\User;
use App\Modules\Audit\Jobs\RunBackup;
use App\Services\Backup\BackupDispatcher;
use App\Services\Backup\BackupRetention;
use App\Services\Backup\DatabaseDumper;
use App\Services\Backup\QueueBackupDispatcher;
use App\Services\BackupService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class RunBackupJobTest extends TestCase
{
    use RefreshDatabase;

    private const LOCAL_DISK = 'backups-local';

    private const REMOTE_DISK = 'backups-s3';

    private const REMOTE_CONFIG = [
        'bucket' => 'test-bucket',
        'endpoint' => 'https://example.r2.cloudflarestorage.com',
        'key' => 'test-key',
        'secret' => 'test-secret',
    ];

    private string $tempDirectory;

    private string $backupTempDirectory;

    private FakeDatabaseDumper $dumper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-11-26 10:15:30', 'Asia/Jakarta'));

        Storage::fake(self::LOCAL_DISK);
        Storage::fake(self::REMOTE_DISK);
        config(['filesystems.disks.'.self::REMOTE_DISK => array_merge(
            ['driver' => 'local'],
            self::REMOTE_CONFIG,
        )]);

        $this->tempDirectory = storage_path('framework/testing/backup-tmp-'.Str::uuid());
        $this->backupTempDirectory = $this->tempDirectory.'/dumps';
        config(['backup.tmp_directory' => $this->backupTempDirectory]);

        $this->dumper = new FakeDatabaseDumper;
        $this->app->instance(DatabaseDumper::class, $this->dumper);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempDirectory);

        parent::tearDown();
    }

    public function test_manual_both_writes_local_and_remote_and_records_one_audit_row(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);
        $this->settings(['remote_enabled' => true]);

        $backup = $this->makeBackup([
            'trigger' => 'manual',
            'destination' => 'both',
            'user_id' => $owner->id,
        ]);

        RunBackup::dispatchSync($backup->id);

        $backup->refresh();

        $this->assertSame('success', $backup->status);
        $this->assertSame(9, $backup->size_bytes);
        $this->assertMatchesRegularExpression(
            '#^\d{4}/\d{2}/\d{2}/pos_alja_\d{8}_\d{6}_[a-z0-9]{6}\.dump$#',
            (string) $backup->local_path,
        );
        $this->assertStringStartsWith('2026/11/26/pos_alja_20261126_101530_', (string) $backup->local_path);
        $this->assertSame(basename((string) $backup->local_path), $backup->filename);
        $this->assertSame($backup->local_path, $backup->remote_path);
        $this->assertNull($backup->error);
        $this->assertNotNull($backup->started_at);
        $this->assertNotNull($backup->finished_at);
        $this->assertSame(0, $backup->meta['pruned']['local']);
        $this->assertSame(0, $backup->meta['pruned']['remote']);
        $this->assertArrayNotHasKey('remote_skipped', $backup->meta);
        $this->assertArrayNotHasKey('pending_local', $backup->meta);
        $this->assertArrayNotHasKey('pending_temp', $backup->meta);
        $this->assertIsInt($backup->meta['duration_ms']);

        Storage::disk(self::LOCAL_DISK)->assertExists((string) $backup->local_path);
        Storage::disk(self::REMOTE_DISK)->assertExists((string) $backup->remote_path);

        $this->assertDatabaseCount('activity_logs', 1);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame($owner->username, $log->username);
        $this->assertSame('owner', $log->role);
        $this->assertSame($owner->id, $log->user_id);
        $this->assertSame(Backup::class, $log->model_type);
        $this->assertSame($backup->id, $log->model_id);
        $this->assertSame($backup->id, $log->payload['backup_id']);
        $this->assertSame('manual', $log->payload['trigger']);
        $this->assertSame('both', $log->payload['destination']);
        $this->assertSame('success', $log->payload['status']);
        $this->assertSame($backup->filename, $log->payload['filename']);
        $this->assertSame($backup->local_path, $log->payload['local_path']);
        $this->assertSame($backup->remote_path, $log->payload['remote_path']);
        $this->assertSame(9, $log->payload['size']);
        $this->assertNull($log->payload['error']);
        $this->assertSame($backup->meta, $log->payload['meta']);
    }

    public function test_scheduled_local_writes_only_the_local_file_and_uses_the_system_actor(): void
    {
        $this->settings(['remote_enabled' => true]);

        $backup = $this->makeBackup([
            'trigger' => 'scheduled',
            'destination' => 'local',
        ]);

        $backup = $this->runBackup($backup);

        $this->assertSame('success', $backup->status);
        $this->assertSame(9, $backup->size_bytes);
        $this->assertNotNull($backup->local_path);
        $this->assertNull($backup->remote_path);
        Storage::disk(self::LOCAL_DISK)->assertExists((string) $backup->local_path);
        $this->assertSame([], Storage::disk(self::REMOTE_DISK)->allFiles());
        $this->assertArrayNotHasKey('pending_local', $backup->meta);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame('system', $log->username);
        $this->assertSame('system', $log->role);
        $this->assertNull($log->user_id);
        $this->assertNull($log->branch_id);
        $this->assertSame('scheduled', $log->payload['trigger']);
        $this->assertSame('success', $log->payload['status']);
    }

    public function test_scheduled_remote_writes_only_the_remote_file(): void
    {
        $this->settings(['remote_enabled' => true]);

        $backup = $this->makeBackup([
            'trigger' => 'scheduled',
            'destination' => 'remote',
        ]);

        $backup = $this->runBackup($backup);

        $this->assertSame('success', $backup->status);
        $this->assertSame(9, $backup->size_bytes);
        $this->assertNull($backup->local_path);
        $this->assertNotNull($backup->remote_path);
        Storage::disk(self::REMOTE_DISK)->assertExists((string) $backup->remote_path);
        $this->assertSame([], Storage::disk(self::LOCAL_DISK)->allFiles());
        $this->assertSame([], $this->tempFiles());
        $this->assertArrayNotHasKey('pending_temp', $backup->meta);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame('system', $log->username);
        $this->assertSame('remote', $log->payload['destination']);
    }

    public function test_both_keeps_the_local_file_and_is_partial_when_the_remote_upload_fails(): void
    {
        $this->settings(['remote_enabled' => true]);
        $this->breakRemoteDisk();

        $backup = $this->makeBackup(['destination' => 'both']);

        $backup = $this->runBackup($backup);

        $this->assertSame('partial', $backup->status);
        $this->assertNotNull($backup->local_path);
        $this->assertNull($backup->remote_path);
        $this->assertNotNull($backup->error);
        $this->assertStringNotContainsString('belum dikonfigurasi', (string) $backup->error);
        $this->assertSame(9, $backup->size_bytes);
        Storage::disk(self::LOCAL_DISK)->assertExists((string) $backup->local_path);
        $this->assertArrayNotHasKey('pending_local', $backup->meta);
        $this->assertArrayNotHasKey('pending_temp', $backup->meta);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame('partial', $log->payload['status']);
        $this->assertNotNull($log->payload['error']);
    }

    public function test_both_skips_remote_and_succeeds_when_remote_is_disabled(): void
    {
        $this->settings(['remote_enabled' => false]);

        $backup = $this->makeBackup(['destination' => 'both']);

        $backup = $this->runBackup($backup);

        $this->assertSame('success', $backup->status);
        $this->assertTrue($backup->meta['remote_skipped']);
        $this->assertNull($backup->remote_path);
        $this->assertNotNull($backup->local_path);
        $this->assertNull($backup->error);
        Storage::disk(self::LOCAL_DISK)->assertExists((string) $backup->local_path);
        $this->assertSame([], Storage::disk(self::REMOTE_DISK)->allFiles());
    }

    public function test_remote_destination_fails_when_remote_is_disabled(): void
    {
        $this->settings(['remote_enabled' => false]);

        $backup = $this->makeBackup(['destination' => 'remote']);

        $backup = $this->runBackup($backup);

        $this->assertSame('failed', $backup->status);
        $this->assertNotNull($backup->error);
        $this->assertStringContainsString('remote', strtolower($backup->error));
        $this->assertNull($backup->local_path);
        $this->assertNull($backup->remote_path);
        $this->assertSame([], Storage::disk(self::LOCAL_DISK)->allFiles());
        $this->assertSame([], Storage::disk(self::REMOTE_DISK)->allFiles());
        $this->assertSame([], $this->tempFiles());
        $this->assertDatabaseHas('activity_logs', ['action' => 'BACKUP', 'model_id' => $backup->id]);
    }

    public function test_both_is_partial_when_remote_is_enabled_but_unconfigured(): void
    {
        $this->forgetRemoteConfiguration();
        $this->settings(['remote_enabled' => true]);

        $backup = $this->makeBackup(['destination' => 'both']);

        $backup = $this->runBackup($backup);

        $this->assertSame('partial', $backup->status);
        $this->assertNotNull($backup->error);
        $this->assertStringContainsString('remote', strtolower($backup->error));
        $this->assertNotNull($backup->local_path);
        $this->assertNull($backup->remote_path);
        $this->assertArrayNotHasKey('remote_skipped', $backup->meta);
        Storage::disk(self::LOCAL_DISK)->assertExists((string) $backup->local_path);
        $this->assertSame([], Storage::disk(self::REMOTE_DISK)->allFiles());
    }

    public function test_remote_destination_fails_when_remote_is_enabled_but_unconfigured(): void
    {
        $this->forgetRemoteConfiguration();
        $this->settings(['remote_enabled' => true]);

        $backup = $this->makeBackup(['destination' => 'remote']);

        $backup = $this->runBackup($backup);

        $this->assertSame('failed', $backup->status);
        $this->assertNotNull($backup->error);
        $this->assertStringContainsString('remote', strtolower($backup->error));
        $this->assertSame([], Storage::disk(self::LOCAL_DISK)->allFiles());
        $this->assertSame([], Storage::disk(self::REMOTE_DISK)->allFiles());
        $this->assertSame([], $this->tempFiles());
    }

    public function test_local_dump_failure_marks_the_run_failed_and_removes_the_partial_file(): void
    {
        $this->dumper->fail = true;

        $backup = $this->makeBackup(['destination' => 'local']);

        $backup = $this->runBackup($backup);

        $this->assertSame('failed', $backup->status);
        $this->assertStringContainsString('pg_dump failed', (string) $backup->error);
        $this->assertNull($backup->local_path);
        $this->assertNull($backup->size_bytes);
        $this->assertSame([], Storage::disk(self::LOCAL_DISK)->allFiles());
        $this->assertSame([], $this->tempFiles());
        $this->assertArrayNotHasKey('pending_local', $backup->meta);
        $this->assertArrayNotHasKey('pending_temp', $backup->meta);
        $this->assertDatabaseHas('activity_logs', ['action' => 'BACKUP', 'model_id' => $backup->id]);
    }

    public function test_remote_dump_failure_marks_the_run_failed_and_removes_the_temp_file(): void
    {
        $this->settings(['remote_enabled' => true]);
        $this->dumper->fail = true;

        $backup = $this->makeBackup(['destination' => 'remote']);

        $backup = $this->runBackup($backup);

        $this->assertSame('failed', $backup->status);
        $this->assertStringContainsString('pg_dump failed', (string) $backup->error);
        $this->assertNull($backup->local_path);
        $this->assertNull($backup->remote_path);
        $this->assertSame([], Storage::disk(self::LOCAL_DISK)->allFiles());
        $this->assertSame([], Storage::disk(self::REMOTE_DISK)->allFiles());
        $this->assertSame([], $this->tempFiles());
        $this->assertArrayNotHasKey('pending_temp', $backup->meta);
    }

    public function test_remote_upload_failure_leaves_no_temp_file_and_writes_one_failed_audit_row(): void
    {
        $this->settings(['remote_enabled' => true]);
        $this->breakRemoteDisk();

        $backup = $this->makeBackup(['destination' => 'remote']);
        $pendingTempDuringDump = null;

        $this->dumper->onDump = function (string $absolutePath) use ($backup, &$pendingTempDuringDump): void {
            $row = Backup::query()->find($backup->id);

            $pendingTempDuringDump = $row?->meta['pending_temp'] ?? null;

            $this->assertNull($row?->local_path);
            $this->assertNull($row?->remote_path);
            $this->assertSame($absolutePath, $pendingTempDuringDump);
        };

        $backup = $this->runBackup($backup);

        $this->assertIsString($pendingTempDuringDump);
        $this->assertStringStartsWith($this->backupTempDirectory.'/', $pendingTempDuringDump);
        $this->assertSame('failed', $backup->status);
        $this->assertNotNull($backup->error);
        $this->assertNull($backup->local_path);
        $this->assertNull($backup->remote_path);
        $this->assertSame([], $this->tempFiles());
        $this->assertArrayNotHasKey('pending_temp', $backup->meta);
        $this->assertArrayNotHasKey('pending_local', $backup->meta);
        $this->assertDatabaseCount('activity_logs', 1);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame('failed', $log->payload['status']);
        $this->assertNotNull($log->payload['error']);
    }

    public function test_retention_failure_is_recorded_without_downgrading_the_status(): void
    {
        $this->app->instance(BackupRetention::class, new class extends BackupRetention
        {
            public function prune(string $disk, array $windows, ?CarbonInterface $now = null): int
            {
                throw new RuntimeException('retention failed');
            }
        });

        $backup = $this->makeBackup(['destination' => 'local']);

        $backup = $this->runBackup($backup);

        $this->assertSame('success', $backup->status);
        $this->assertSame('retention failed', $backup->meta['retention_error']);
        Storage::disk(self::LOCAL_DISK)->assertExists((string) $backup->local_path);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame('success', $log->payload['status']);
        $this->assertSame('retention failed', $log->payload['meta']['retention_error']);
    }

    public function test_retention_runs_only_for_written_destinations_with_their_windows(): void
    {
        $retention = new class extends BackupRetention
        {
            /** @var list<array{disk: string, windows: array<string, int>}> */
            public array $calls = [];

            public function prune(string $disk, array $windows, ?CarbonInterface $now = null): int
            {
                $this->calls[] = ['disk' => $disk, 'windows' => $windows];

                return 0;
            }
        };

        $this->app->instance(BackupRetention::class, $retention);
        $this->settings([
            'remote_enabled' => true,
            'remote_daily_days' => 5,
            'remote_weekly_weeks' => 6,
            'remote_monthly_months' => 7,
            'remote_yearly_years' => 8,
        ]);

        Storage::disk(self::LOCAL_DISK)->put('2020/01/01/local-old.dump', 'old');
        Storage::disk(self::LOCAL_DISK)->put('2019/01/01/local-older.dump', 'old');

        $backup = $this->makeBackup(['destination' => 'remote']);

        $this->runBackup($backup);

        $this->assertSame([[
            'disk' => self::REMOTE_DISK,
            'windows' => ['daily' => 5, 'weekly' => 6, 'monthly' => 7, 'yearly' => 8],
        ]], $retention->calls);

        Storage::disk(self::LOCAL_DISK)->assertExists('2020/01/01/local-old.dump');
        Storage::disk(self::LOCAL_DISK)->assertExists('2019/01/01/local-older.dump');
    }

    public function test_manual_run_resolves_a_soft_deleted_actor(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);
        $owner->delete();

        $backup = $this->makeBackup(['user_id' => $owner->id]);

        $this->runBackup($backup);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame($owner->username, $log->username);
        $this->assertSame('owner', $log->role);
        $this->assertSame($owner->id, $log->user_id);
    }

    public function test_queued_backup_without_filename_succeeds_and_the_service_fills_it_at_run_start(): void
    {
        $backup = Backup::query()->create([
            'trigger' => 'manual',
            'destination' => 'local',
            'status' => 'queued',
        ]);

        $this->assertNull($backup->filename);

        $filenameAtRunStart = null;

        $this->dumper->onDump = function () use ($backup, &$filenameAtRunStart): void {
            $filenameAtRunStart = Backup::query()->find($backup->id)?->filename;
        };

        $backup = $this->runBackup($backup);

        $this->assertMatchesRegularExpression(
            '#^pos_alja_\d{8}_\d{6}_[a-z0-9]{6}\.dump$#',
            (string) $filenameAtRunStart,
        );
        $this->assertSame($filenameAtRunStart, $backup->filename);
        $this->assertSame('success', $backup->status);
        $this->assertSame(basename((string) $backup->local_path), $backup->filename);
    }

    public function test_running_row_keeps_pending_paths_out_of_the_downloadable_columns_until_finalize(): void
    {
        $backup = $this->makeBackup(['destination' => 'local']);
        $seen = null;

        $this->dumper->onDump = function () use ($backup, &$seen): void {
            $row = Backup::query()->find($backup->id);

            $seen = [
                'local_path' => $row?->local_path,
                'remote_path' => $row?->remote_path,
                'meta' => $row?->meta,
            ];
        };

        $backup = $this->runBackup($backup);

        $this->assertNull($seen['local_path']);
        $this->assertNull($seen['remote_path']);
        $this->assertArrayHasKey('pending_local', $seen['meta']);
        $this->assertArrayNotHasKey('pending_temp', $seen['meta']);
        $this->assertMatchesRegularExpression(
            '#^\d{4}/\d{2}/\d{2}/pos_alja_\d{8}_\d{6}_[a-z0-9]{6}\.dump$#',
            $seen['meta']['pending_local'],
        );
        $this->assertSame($seen['meta']['pending_local'], $backup->local_path);
        $this->assertArrayNotHasKey('pending_local', $backup->meta);
        $this->assertArrayNotHasKey('pending_temp', $backup->meta);
    }

    public function test_run_is_idempotent_for_a_final_backup(): void
    {
        $backup = $this->makeBackup([
            'status' => 'success',
            'filename' => 'pos_alja_20261126_101530_abcdef.dump',
            'finished_at' => now(),
        ]);

        $backup = $this->runBackup($backup);

        $this->assertSame('success', $backup->status);
        $this->assertSame(0, ActivityLog::query()->count());
        $this->assertSame([], $this->dumper->paths);
    }

    public function test_job_failure_marks_a_queued_backup_failed_and_writes_one_audit_row(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);
        $backup = $this->makeBackup(['user_id' => $owner->id]);
        $job = new RunBackup($backup->id);

        $job->failed(new RuntimeException('worker failed'));

        $backup->refresh();

        $this->assertSame('failed', $backup->status);
        $this->assertStringContainsString('worker failed', (string) $backup->error);
        $this->assertNotNull($backup->finished_at);
        $this->assertDatabaseCount('activity_logs', 1);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame('failed', $log->payload['status']);
        $this->assertSame($owner->username, $log->username);

        $job->failed(new RuntimeException('a second failure'));

        $backup->refresh();

        $this->assertStringContainsString('worker failed', (string) $backup->error);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_job_failure_without_an_exception_uses_a_fallback_error(): void
    {
        $backup = $this->makeBackup();

        (new RunBackup($backup->id))->failed(null);

        $backup->refresh();

        $this->assertSame('failed', $backup->status);
        $this->assertNotNull($backup->error);
    }

    public function test_job_failure_deletes_pending_files_and_writes_one_audit_row(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);

        $pendingLocal = '2026/11/26/pos_alja_20261126_101530_abcdef.dump';
        Storage::disk(self::LOCAL_DISK)->put($pendingLocal, 'partial');

        File::ensureDirectoryExists($this->backupTempDirectory);
        $pendingTemp = $this->backupTempDirectory.'/pos_alja_20261126_101530_abcdef.dump';
        file_put_contents($pendingTemp, 'partial');

        $backup = $this->makeBackup([
            'status' => 'running',
            'started_at' => now(),
            'user_id' => $owner->id,
            'meta' => [
                'pending_local' => $pendingLocal,
                'pending_temp' => $pendingTemp,
            ],
        ]);

        (new RunBackup($backup->id))->failed(new RuntimeException('backup timed out'));

        $backup->refresh();

        $this->assertSame('failed', $backup->status);
        $this->assertNotNull($backup->finished_at);
        $this->assertStringContainsString('backup timed out', (string) $backup->error);
        $this->assertNull($backup->local_path);
        $this->assertNull($backup->remote_path);
        Storage::disk(self::LOCAL_DISK)->assertMissing($pendingLocal);
        $this->assertFileDoesNotExist($pendingTemp);
        $this->assertArrayNotHasKey('pending_local', $backup->meta);
        $this->assertArrayNotHasKey('pending_temp', $backup->meta);
        $this->assertDatabaseCount('activity_logs', 1);

        $log = ActivityLog::query()->where('action', 'BACKUP')->sole();

        $this->assertSame('failed', $log->payload['status']);
        $this->assertSame($owner->username, $log->username);
    }

    public function test_job_is_configured_for_one_attempt_and_the_configured_timeout(): void
    {
        config(['backup.timeout' => 900]);

        $backupId = (string) Str::uuid();
        $job = new RunBackup($backupId);

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertSame(1, $job->tries);
        $this->assertSame(900, $job->timeout);
        $this->assertSame($backupId, $job->backupId);
    }

    public function test_queue_dispatcher_pushes_a_run_backup_job(): void
    {
        Queue::fake();

        $dispatcher = app(BackupDispatcher::class);
        $backupId = (string) Str::uuid();

        $this->assertInstanceOf(QueueBackupDispatcher::class, $dispatcher);

        $dispatcher->dispatch($backupId);

        Queue::assertPushed(
            RunBackup::class,
            fn (RunBackup $job): bool => $job->backupId === $backupId,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function settings(array $overrides = []): BackupSetting
    {
        $settings = BackupSetting::current();
        $settings->forceFill($overrides)->save();

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeBackup(array $attributes = []): Backup
    {
        return Backup::query()->create(array_merge([
            'trigger' => 'manual',
            'destination' => 'local',
            'status' => 'queued',
            'filename' => 'pending',
        ], $attributes));
    }

    private function runBackup(Backup $backup): Backup
    {
        app(BackupService::class)->run($backup->id);

        return $backup->refresh();
    }

    /**
     * Remove the remote credentials while keeping the faked local driver.
     */
    private function forgetRemoteConfiguration(): void
    {
        Storage::fake(self::REMOTE_DISK);
        config(['filesystems.disks.'.self::REMOTE_DISK => ['driver' => 'local']]);
    }

    /**
     * Point the remote disk at a regular file so every write fails.
     */
    private function breakRemoteDisk(): void
    {
        $root = $this->tempDirectory.'/broken-remote-root';

        File::ensureDirectoryExists($this->tempDirectory);
        file_put_contents($root, 'not-a-directory');

        config(['filesystems.disks.'.self::REMOTE_DISK => array_merge(self::REMOTE_CONFIG, [
            'driver' => 'local',
            'root' => $root,
            'throw' => true,
            'lazy_root_creation' => true,
        ])]);

        Storage::forgetDisk(self::REMOTE_DISK);
    }

    /**
     * @return list<string>
     */
    private function tempFiles(): array
    {
        if (! File::isDirectory($this->backupTempDirectory)) {
            return [];
        }

        return array_map(
            static fn ($file): string => $file->getPathname(),
            File::allFiles($this->backupTempDirectory),
        );
    }
}

final class FakeDatabaseDumper implements DatabaseDumper
{
    public bool $fail = false;

    public ?Closure $onDump = null;

    /** @var list<string> */
    public array $paths = [];

    public function dump(string $absolutePath): void
    {
        $this->paths[] = $absolutePath;

        if ($this->onDump !== null) {
            ($this->onDump)($absolutePath);
        }

        if (file_put_contents($absolutePath, 'fake-dump') === false) {
            throw new RuntimeException('The fake dumper could not write '.$absolutePath);
        }

        if ($this->fail) {
            throw new RuntimeException('pg_dump failed');
        }
    }
}
