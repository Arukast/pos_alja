<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class BackupDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const LOCAL_DISK = 'backups-local';

    private const REMOTE_DISK = 'backups-s3';

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

        Storage::fake(self::LOCAL_DISK);
        Storage::fake(self::REMOTE_DISK);
    }

    public function test_owner_downloads_the_local_file_first_and_audits_the_source(): void
    {
        Storage::disk(self::LOCAL_DISK)->put('2026/11/26/local.dump', 'local-dump');
        Storage::disk(self::REMOTE_DISK)->put('2026/11/26/local.dump', 'remote-dump');

        $backup = $this->backup([
            'filename' => 'pos_alja_local.dump',
            'local_path' => '2026/11/26/local.dump',
            'remote_path' => '2026/11/26/local.dump',
        ]);

        $this->actingAs($this->owner)
            ->get('/api/v1/backups/'.$backup->id.'/download')
            ->assertOk()
            ->assertDownload('pos_alja_local.dump')
            ->assertStreamedContent('local-dump');

        $log = ActivityLog::query()->where('action', 'DOWNLOAD')->sole();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(Backup::class, $log->model_type);
        $this->assertSame($backup->id, $log->model_id);
        $this->assertSame($backup->id, $log->payload['backup_id']);
        $this->assertSame('pos_alja_local.dump', $log->payload['filename']);
        $this->assertSame('local', $log->payload['source']);
    }

    public function test_download_falls_back_to_remote_when_local_path_is_null(): void
    {
        Storage::disk(self::REMOTE_DISK)->put('2026/11/26/remote.dump', 'remote-dump');

        $backup = $this->backup([
            'filename' => 'pos_alja_remote.dump',
            'local_path' => null,
            'remote_path' => '2026/11/26/remote.dump',
        ]);

        $this->actingAs($this->owner)
            ->get('/api/v1/backups/'.$backup->id.'/download')
            ->assertOk()
            ->assertDownload('pos_alja_remote.dump')
            ->assertStreamedContent('remote-dump');

        $log = ActivityLog::query()->where('action', 'DOWNLOAD')->sole();

        $this->assertSame('remote', $log->payload['source']);
        $this->assertSame('pos_alja_remote.dump', $log->payload['filename']);
    }

    public function test_download_falls_back_to_remote_when_the_local_file_is_missing(): void
    {
        Storage::disk(self::REMOTE_DISK)->put('2026/11/26/stale.dump', 'remote-dump');

        $backup = $this->backup([
            'filename' => 'pos_alja_stale.dump',
            'local_path' => '2026/11/26/stale.dump',
            'remote_path' => '2026/11/26/stale.dump',
        ]);

        $this->actingAs($this->owner)
            ->get('/api/v1/backups/'.$backup->id.'/download')
            ->assertOk()
            ->assertDownload('pos_alja_stale.dump')
            ->assertStreamedContent('remote-dump');

        $this->assertSame(
            'remote',
            ActivityLog::query()->where('action', 'DOWNLOAD')->sole()->payload['source'],
        );
    }

    public function test_download_returns_410_when_neither_file_exists(): void
    {
        $missing = $this->backup([
            'filename' => 'pos_alja_missing.dump',
            'local_path' => '2026/11/26/missing.dump',
            'remote_path' => '2026/11/26/missing.dump',
        ]);

        $unwritten = $this->backup([
            'filename' => null,
            'local_path' => null,
            'remote_path' => null,
        ]);

        foreach ([$missing, $unwritten] as $backup) {
            $this->actingAs($this->owner)
                ->getJson('/api/v1/backups/'.$backup->id.'/download')
                ->assertStatus(410)
                ->assertExactJson(['message' => 'Berkas backup tidak ditemukan.']);
        }

        $this->assertDatabaseMissing('activity_logs', ['action' => 'DOWNLOAD']);
    }

    public function test_download_returns_500_when_the_disk_check_fails(): void
    {
        $disk = Storage::disk(self::REMOTE_DISK);

        Storage::set(self::REMOTE_DISK, new class($disk->getDriver(), $disk->getAdapter()) extends FilesystemAdapter
        {
            public function exists($path): bool
            {
                throw new RuntimeException('R2 tidak tersedia.');
            }
        });

        $backup = $this->backup([
            'filename' => 'pos_alja_outage.dump',
            'local_path' => null,
            'remote_path' => '2026/11/26/outage.dump',
        ]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/backups/'.$backup->id.'/download')
            ->assertStatus(500);

        $this->assertDatabaseMissing('activity_logs', ['action' => 'DOWNLOAD']);
    }

    public function test_download_ignores_a_path_from_the_request(): void
    {
        Storage::disk(self::LOCAL_DISK)->put('2026/11/26/real.dump', 'real-dump');
        Storage::disk(self::LOCAL_DISK)->put('other.dump', 'other-dump');

        $backup = $this->backup([
            'filename' => 'pos_alja_real.dump',
            'local_path' => '2026/11/26/real.dump',
            'remote_path' => null,
        ]);

        $this->actingAs($this->owner)
            ->get('/api/v1/backups/'.$backup->id.'/download?path=other.dump')
            ->assertOk()
            ->assertDownload('pos_alja_real.dump')
            ->assertStreamedContent('real-dump');
    }

    public function test_guest_cannot_download_a_backup(): void
    {
        $backup = $this->backup(['local_path' => null]);

        $this->getJson('/api/v1/backups/'.$backup->id.'/download')->assertUnauthorized();

        $this->assertDatabaseMissing('activity_logs', ['action' => 'DOWNLOAD']);
    }

    public function test_non_owner_cannot_download_a_backup(): void
    {
        Storage::disk(self::LOCAL_DISK)->put('2026/11/26/forbidden.dump', 'forbidden-dump');

        $backup = $this->backup([
            'filename' => 'pos_alja_forbidden.dump',
            'local_path' => '2026/11/26/forbidden.dump',
            'remote_path' => null,
        ]);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/backups/'.$backup->id.'/download')
            ->assertForbidden();

        $this->assertDatabaseMissing('activity_logs', ['action' => 'DOWNLOAD']);
    }

    public function test_unknown_backup_download_returns_404(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/backups/'.Str::uuid().'/download')
            ->assertNotFound();

        $this->assertDatabaseMissing('activity_logs', ['action' => 'DOWNLOAD']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function backup(array $overrides = []): Backup
    {
        return Backup::query()->forceCreate(array_merge([
            'trigger' => 'manual',
            'destination' => 'local',
            'status' => 'success',
            'filename' => 'pos_alja.dump',
            'local_path' => null,
            'remote_path' => null,
            'size_bytes' => 9,
            'user_id' => $this->owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
