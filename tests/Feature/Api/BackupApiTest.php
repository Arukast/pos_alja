<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\Branch;
use App\Models\User;
use App\Modules\Audit\Jobs\RunBackup;
use App\Services\Backup\BackupDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class BackupApiTest extends TestCase
{
    use RefreshDatabase;

    private const REMOTE_CONFIG = [
        'bucket' => 'test-bucket',
        'endpoint' => 'https://example.r2.cloudflarestorage.com',
        'key' => 'test-key',
        'secret' => 'test-secret',
    ];

    private User $owner;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);

        $this->owner = User::factory()->create(['role' => 'owner', 'branch_id' => null]);
        $this->manager = User::factory()->create(['role' => 'manager', 'branch_id' => $branch->id]);

        Storage::fake('backups-local');
        Storage::fake('backups-s3');
    }

    public function test_guest_and_non_owner_cannot_access_backups(): void
    {
        $this->getJson('/api/v1/backups')->assertUnauthorized();
        $this->postJson('/api/v1/backups')->assertUnauthorized();

        $this->actingAs($this->manager)->getJson('/api/v1/backups')->assertForbidden();
        $this->actingAs($this->manager)->postJson('/api/v1/backups')->assertForbidden();

        $this->assertDatabaseCount('backups', 0);
        $this->assertDatabaseCount('backup_settings', 0);
    }

    public function test_owner_lists_backups_with_default_and_max_pagination(): void
    {
        $backups = collect(range(1, 12))->map(function (int $offset): Backup {
            return Backup::query()->forceCreate([
                'trigger' => 'scheduled',
                'destination' => 'local',
                'status' => 'success',
                'created_at' => now()->subMinutes(13 - $offset),
                'updated_at' => now()->subMinutes(13 - $offset),
            ]);
        });

        $default = $this->actingAs($this->owner)
            ->getJson('/api/v1/backups')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12);

        $this->assertSame($backups[11]->id, $default->json('data.0.id'));
        $this->assertSame($backups[2]->id, $default->json('data.9.id'));

        $this->actingAs($this->owner)
            ->getJson('/api/v1/backups?per_page=50')
            ->assertOk()
            ->assertJsonCount(12, 'data');

        $this->actingAs($this->owner)
            ->getJson('/api/v1/backups?per_page=51')
            ->assertStatus(422);
    }

    public function test_manual_backup_returns_202_and_dispatches_job_when_remote_is_configured(): void
    {
        Queue::fake();
        config(['filesystems.disks.backups-s3' => array_merge(
            ['driver' => 'local'],
            self::REMOTE_CONFIG,
        )]);
        $this->settings(['remote_enabled' => true]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/backups')
            ->assertStatus(202)
            ->assertJsonPath('message', 'Backup dimulai.')
            ->assertJsonPath('backup.trigger', 'manual')
            ->assertJsonPath('backup.destination', 'both')
            ->assertJsonPath('backup.status', 'queued')
            ->assertJsonPath('backup.filename', null)
            ->assertJsonPath('backup.is_pinned', false)
            ->assertJsonPath('backup.user.id', $this->owner->id);

        $backup = Backup::query()->sole();

        $this->assertSame($backup->id, $response->json('backup.id'));
        $this->assertSame('manual', $backup->trigger);
        $this->assertSame('both', $backup->destination);
        $this->assertSame('queued', $backup->status);
        $this->assertSame($this->owner->id, $backup->user_id);
        $this->assertNull($backup->filename);

        Queue::assertPushed(
            RunBackup::class,
            fn (RunBackup $job): bool => $job->backupId === $backup->id,
        );
    }

    public function test_manual_backup_uses_local_destination_when_remote_is_disabled(): void
    {
        Queue::fake();
        $this->settings(['remote_enabled' => false]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/backups')
            ->assertStatus(202)
            ->assertJsonPath('backup.destination', 'local');

        $backup = Backup::query()->sole();

        $this->assertSame('local', $backup->destination);
        Queue::assertPushed(RunBackup::class, 1);
        $this->assertSame([], Storage::disk('backups-s3')->allFiles());
    }

    public function test_manual_backup_returns_422_when_remote_is_enabled_but_unconfigured(): void
    {
        Queue::fake();
        $this->settings(['remote_enabled' => true]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/backups')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Backup remote belum dikonfigurasi.');

        $this->assertDatabaseCount('backups', 0);
        Queue::assertNothingPushed();
    }

    public function test_recent_manual_run_returns_409_and_stale_run_ages_out(): void
    {
        Queue::fake();
        $this->settings(['remote_enabled' => false]);

        $existing = Backup::query()->forceCreate([
            'trigger' => 'manual',
            'destination' => 'local',
            'status' => 'queued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $post = fn () => $this->actingAs($this->owner)->postJson('/api/v1/backups');

        $post()
            ->assertStatus(409)
            ->assertJsonPath('message', 'Backup manual sedang berjalan.');
        $this->assertDatabaseCount('backups', 1);

        $existing->forceFill(['status' => 'running'])->save();

        $post()->assertStatus(409);

        $this->travel(30)->minutes();

        $post()->assertStatus(409);

        $this->travel(1)->seconds();

        $post()->assertStatus(202);
        $this->assertDatabaseCount('backups', 2);
        $this->assertSame('queued', Backup::query()->orderByDesc('created_at')->first()?->status);
    }

    public function test_scheduled_rows_do_not_block_a_manual_run(): void
    {
        Queue::fake();
        $this->settings(['remote_enabled' => false]);

        foreach (['queued', 'running'] as $status) {
            Backup::query()->forceCreate([
                'trigger' => 'scheduled',
                'destination' => 'local',
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($this->owner)
            ->postJson('/api/v1/backups')
            ->assertStatus(202);

        Queue::assertPushed(RunBackup::class, 1);
    }

    public function test_dispatch_failure_marks_row_failed_and_writes_audit(): void
    {
        $this->settings(['remote_enabled' => false]);
        $this->app->instance(BackupDispatcher::class, new class implements BackupDispatcher
        {
            public function dispatch(string $backupId): void
            {
                throw new RuntimeException('Queue tidak tersedia.');
            }
        });

        $this->actingAs($this->owner)
            ->postJson('/api/v1/backups')
            ->assertStatus(500)
            ->assertJsonPath('message', 'Backup gagal dimulai.');

        $backup = Backup::query()->sole();

        $this->assertSame('failed', $backup->status);
        $this->assertNotNull($backup->finished_at);
        $this->assertSame('Queue tidak tersedia.', $backup->error);
        $this->assertNull($backup->local_path);

        $this->assertSame(1, ActivityLog::query()->where('action', 'BACKUP')->count());

        $log = ActivityLog::query()->sole();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(Backup::class, $log->model_type);
        $this->assertSame($backup->id, $log->model_id);
        $this->assertSame('failed', $log->payload['status']);
    }

    public function test_resource_reports_local_and_remote_availability(): void
    {
        Storage::disk('backups-local')->put('2026/11/26/kept.dump', 'dump-data');

        $available = Backup::query()->forceCreate([
            'trigger' => 'manual',
            'destination' => 'both',
            'status' => 'success',
            'filename' => 'kept.dump',
            'local_path' => '2026/11/26/kept.dump',
            'remote_path' => '2026/11/26/kept.dump',
            'size_bytes' => 9,
            'is_pinned' => true,
            'user_id' => $this->owner->id,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
            'meta' => [
                'pending_local' => '2026/11/26/kept.dump',
                'pending_temp' => '/tmp/removed.dump',
                'remote_skipped' => true,
                'duration_ms' => 50,
                'pruned' => ['local' => 1],
            ],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $missing = Backup::query()->forceCreate([
            'trigger' => 'scheduled',
            'destination' => 'remote',
            'status' => 'success',
            'filename' => 'gone.dump',
            'local_path' => '2026/11/26/gone.dump',
            'remote_path' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/backups')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $rows = collect($response->json('data'))->keyBy('id');

        $kept = $rows[$available->id];

        $this->assertTrue($kept['local_available']);
        $this->assertTrue($kept['remote_available']);
        $this->assertSame('kept.dump', $kept['filename']);
        $this->assertSame(9, $kept['size_bytes']);
        $this->assertTrue($kept['is_pinned']);
        $this->assertNull($kept['error']);
        $this->assertSame(['id' => $this->owner->id, 'name' => $this->owner->name], $kept['user']);
        $this->assertNotNull($kept['started_at']);
        $this->assertNotNull($kept['finished_at']);
        $this->assertNotNull($kept['created_at']);
        $this->assertArrayNotHasKey('pending_local', $kept['meta']);
        $this->assertArrayNotHasKey('pending_temp', $kept['meta']);
        $this->assertTrue($kept['meta']['remote_skipped']);
        $this->assertSame(50, $kept['meta']['duration_ms']);
        $this->assertSame(['local' => 1], $kept['meta']['pruned']);

        $gone = $rows[$missing->id];

        $this->assertFalse($gone['local_available']);
        $this->assertFalse($gone['remote_available']);
        $this->assertNull($gone['user']);
        $this->assertSame('gone.dump', $gone['filename']);
        $this->assertNull($gone['meta']);
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
}
