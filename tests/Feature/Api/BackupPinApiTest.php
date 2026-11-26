<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BackupPinApiTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_owner_pins_a_backup_and_writes_a_pin_audit(): void
    {
        $backup = $this->backup(['is_pinned' => false]);

        $this->actingAs($this->owner)
            ->patchJson('/api/v1/backups/'.$backup->id.'/pin', ['pinned' => true])
            ->assertOk()
            ->assertJsonPath('backup.id', $backup->id)
            ->assertJsonPath('backup.is_pinned', true);

        $this->assertTrue($backup->fresh()?->is_pinned);

        $log = ActivityLog::query()->where('action', 'PIN')->sole();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(Backup::class, $log->model_type);
        $this->assertSame($backup->id, $log->model_id);
        $this->assertSame($backup->id, $log->payload['backup_id']);
        $this->assertFalse($log->payload['is_pinned_before']);
        $this->assertTrue($log->payload['is_pinned_after']);
    }

    public function test_owner_unpins_a_backup_and_writes_an_unpin_audit(): void
    {
        $backup = $this->backup(['is_pinned' => true]);

        $this->actingAs($this->owner)
            ->patchJson('/api/v1/backups/'.$backup->id.'/pin', ['pinned' => false])
            ->assertOk()
            ->assertJsonPath('backup.is_pinned', false);

        $this->assertFalse($backup->fresh()?->is_pinned);

        $log = ActivityLog::query()->where('action', 'UNPIN')->sole();

        $this->assertTrue($log->payload['is_pinned_before']);
        $this->assertFalse($log->payload['is_pinned_after']);
        $this->assertSame($backup->id, $log->payload['backup_id']);
    }

    public function test_pin_requires_a_boolean(): void
    {
        $backup = $this->backup(['is_pinned' => false]);

        $this->actingAs($this->owner)
            ->patchJson('/api/v1/backups/'.$backup->id.'/pin')
            ->assertStatus(422)
            ->assertJsonValidationErrors('pinned');

        $this->actingAs($this->owner)
            ->patchJson('/api/v1/backups/'.$backup->id.'/pin', ['pinned' => 'nope'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pinned');

        $this->assertFalse($backup->fresh()?->is_pinned);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_guest_cannot_pin_a_backup(): void
    {
        $backup = $this->backup(['is_pinned' => false]);

        $this->patchJson('/api/v1/backups/'.$backup->id.'/pin', ['pinned' => true])
            ->assertUnauthorized();

        $this->assertFalse($backup->fresh()?->is_pinned);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_non_owner_cannot_pin_a_backup(): void
    {
        $backup = $this->backup(['is_pinned' => false]);

        $this->actingAs($this->manager)
            ->patchJson('/api/v1/backups/'.$backup->id.'/pin', ['pinned' => true])
            ->assertForbidden();

        $this->assertFalse($backup->fresh()?->is_pinned);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_unknown_backup_pin_returns_404(): void
    {
        $this->actingAs($this->owner)
            ->patchJson('/api/v1/backups/'.Str::uuid().'/pin', ['pinned' => true])
            ->assertNotFound();
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
            'local_path' => '2026/11/26/pos_alja.dump',
            'remote_path' => null,
            'size_bytes' => 9,
            'is_pinned' => false,
            'user_id' => $this->owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
