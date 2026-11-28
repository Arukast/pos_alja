<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Modules\Audit\Jobs\RunBackup;
use App\Services\Backup\BackupDispatcher;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

final class BackupTickTest extends TestCase
{
    use RefreshDatabase;

    private BackupSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('backups-local');
        Storage::fake('backups-s3');

        Queue::fake();

        $this->settings = BackupSetting::current();
        $this->settings->forceFill([
            'local_enabled' => true,
            'local_time' => '02:00',
            'remote_enabled' => false,
            'remote_time' => '02:30',
            'last_local_date' => null,
            'last_remote_date' => null,
        ])->save();
    }

    public function test_due_local_run_dispatches_once_and_records_the_date(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-26 02:00:00', 'Asia/Jakarta'));

        $this->tick();

        $backup = Backup::query()->sole();

        $this->assertSame('scheduled', $backup->trigger);
        $this->assertSame('local', $backup->destination);
        $this->assertSame('queued', $backup->status);
        $this->assertNull($backup->user_id);
        $this->assertNull($backup->filename);

        Queue::assertPushed(
            RunBackup::class,
            fn (RunBackup $job): bool => $job->backupId === $backup->id,
        );

        $this->assertSame('2026-11-26', $this->settings->refresh()->last_local_date?->toDateString());
    }

    public function test_same_minute_does_not_dispatch_twice(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-26 02:00:00', 'Asia/Jakarta'));

        $this->tick();
        $this->tick();

        $this->assertDatabaseCount('backups', 1);
        Queue::assertPushed(RunBackup::class, 1);
        $this->assertSame('2026-11-26', $this->settings->refresh()->last_local_date?->toDateString());
    }

    public function test_next_day_dispatches_again(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-26 02:00:00', 'Asia/Jakarta'));
        $this->tick();

        $this->travelTo(CarbonImmutable::parse('2026-11-27 02:00:00', 'Asia/Jakarta'));
        $this->tick();

        $this->assertDatabaseCount('backups', 2);
        Queue::assertPushed(RunBackup::class, 2);
        $this->assertSame('2026-11-27', $this->settings->refresh()->last_local_date?->toDateString());
    }

    public function test_before_the_scheduled_time_does_nothing(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-26 01:59:00', 'Asia/Jakarta'));

        $this->tick();

        $this->assertDatabaseCount('backups', 0);
        Queue::assertNothingPushed();
        $this->assertNull($this->settings->refresh()->last_local_date);
    }

    public function test_disabled_destinations_do_nothing(): void
    {
        $this->settings->forceFill([
            'local_enabled' => false,
            'remote_enabled' => false,
        ])->save();

        $this->travelTo(CarbonImmutable::parse('2026-11-26 03:00:00', 'Asia/Jakarta'));

        $this->tick();

        $this->assertDatabaseCount('backups', 0);
        Queue::assertNothingPushed();
        $this->assertNull($this->settings->refresh()->last_local_date);
        $this->assertNull($this->settings->refresh()->last_remote_date);
    }

    public function test_changed_time_applies_on_the_next_tick(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-26 01:00:00', 'Asia/Jakarta'));

        $this->tick();

        $this->assertDatabaseCount('backups', 0);

        $this->settings->forceFill(['local_time' => '00:30'])->save();

        $this->tick();

        $this->assertDatabaseCount('backups', 1);
        $this->assertSame('local', Backup::query()->sole()->destination);
        $this->assertSame('2026-11-26', $this->settings->refresh()->last_local_date?->toDateString());
    }

    public function test_missed_time_catches_up_after_downtime(): void
    {
        $this->settings->forceFill(['last_local_date' => '2026-11-25'])->save();

        $this->travelTo(CarbonImmutable::parse('2026-11-26 15:00:00', 'Asia/Jakarta'));

        $this->tick();

        $this->assertDatabaseCount('backups', 1);
        $this->assertSame('local', Backup::query()->sole()->destination);
        Queue::assertPushed(RunBackup::class, 1);
        $this->assertSame('2026-11-26', $this->settings->refresh()->last_local_date?->toDateString());
    }

    public function test_dispatch_failure_marks_the_row_failed_writes_audit_and_still_records_the_date(): void
    {
        $this->app->instance(BackupDispatcher::class, new class implements BackupDispatcher
        {
            public function dispatch(string $backupId): void
            {
                throw new RuntimeException('Queue tidak tersedia.');
            }
        });

        $this->travelTo(CarbonImmutable::parse('2026-11-26 02:00:00', 'Asia/Jakarta'));

        $this->tick();

        $backup = Backup::query()->sole();

        $this->assertSame('failed', $backup->status);
        $this->assertSame('Queue tidak tersedia.', $backup->error);
        $this->assertNotNull($backup->finished_at);

        $log = ActivityLog::query()->sole();

        $this->assertSame('BACKUP', $log->action);
        $this->assertSame(Backup::class, $log->model_type);
        $this->assertSame($backup->id, $log->model_id);
        $this->assertSame('failed', $log->payload['status']);

        $this->assertSame('2026-11-26', $this->settings->refresh()->last_local_date?->toDateString());

        $this->tick();

        $this->assertDatabaseCount('backups', 1);
    }

    public function test_remote_is_not_dispatched_when_disabled(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-26 03:00:00', 'Asia/Jakarta'));

        $this->tick();

        $this->assertDatabaseCount('backups', 1);
        $this->assertSame('local', Backup::query()->sole()->destination);
        Queue::assertPushed(RunBackup::class, 1);
        $this->assertNull($this->settings->refresh()->last_remote_date);
    }

    public function test_enabled_remote_destination_dispatches_independently(): void
    {
        $this->settings->forceFill([
            'local_enabled' => false,
            'remote_enabled' => true,
        ])->save();

        $this->travelTo(CarbonImmutable::parse('2026-11-26 02:30:00', 'Asia/Jakarta'));

        $this->tick();

        $backup = Backup::query()->sole();

        $this->assertSame('remote', $backup->destination);
        $this->assertSame('queued', $backup->status);
        Queue::assertPushed(RunBackup::class, 1);
        $this->assertSame('2026-11-26', $this->settings->refresh()->last_remote_date?->toDateString());
        $this->assertNull($this->settings->refresh()->last_local_date);
    }

    public function test_remote_dispatch_failure_marks_the_row_failed_writes_one_audit_row_and_records_the_remote_date(): void
    {
        $this->settings->forceFill([
            'local_enabled' => false,
            'remote_enabled' => true,
        ])->save();

        $this->app->instance(BackupDispatcher::class, new class implements BackupDispatcher
        {
            public function dispatch(string $backupId): void
            {
                throw new RuntimeException('Queue tidak tersedia.');
            }
        });

        $this->travelTo(CarbonImmutable::parse('2026-11-26 02:30:00', 'Asia/Jakarta'));

        $this->tick();

        $backup = Backup::query()->sole();

        $this->assertSame('remote', $backup->destination);
        $this->assertSame('failed', $backup->status);
        $this->assertSame('Queue tidak tersedia.', $backup->error);

        $log = ActivityLog::query()->sole();

        $this->assertSame('BACKUP', $log->action);
        $this->assertSame(Backup::class, $log->model_type);
        $this->assertSame($backup->id, $log->model_id);
        $this->assertSame('failed', $log->payload['status']);

        $this->assertSame('2026-11-26', $this->settings->refresh()->last_remote_date?->toDateString());
    }

    public function test_catch_up_straddling_midnight_records_the_starting_date(): void
    {
        $this->app->instance(BackupDispatcher::class, new class implements BackupDispatcher
        {
            public function dispatch(string $backupId): void
            {
                CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-27 00:00:01', 'Asia/Jakarta'));
            }
        });

        $this->travelTo(CarbonImmutable::parse('2026-11-26 23:59:59', 'Asia/Jakarta'));

        $this->tick();

        $this->assertSame('2026-11-26', $this->settings->refresh()->last_local_date?->toDateString());
    }

    private function tick(): void
    {
        $this->artisan('backup:tick')->assertSuccessful();
    }
}
