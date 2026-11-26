<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class BackupSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_backups_table_has_the_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('backups'));
        $this->assertTrue(Schema::hasColumns('backups', [
            'id',
            'trigger',
            'destination',
            'status',
            'filename',
            'local_path',
            'remote_path',
            'size_bytes',
            'error',
            'is_pinned',
            'meta',
            'user_id',
            'started_at',
            'finished_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_backups_table_has_created_at_status_and_is_pinned_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('backups'));

        foreach (['created_at', 'status', 'is_pinned'] as $column) {
            $this->assertNotNull(
                $indexes->first(fn (array $index): bool => $index['columns'] === [$column]),
                "Expected an index on backups.{$column}.",
            );
        }
    }

    public function test_deleting_a_user_nulls_the_backup_user_id(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $backupId = (string) Str::uuid();

        DB::table('backups')->insert([
            'id' => $backupId,
            'trigger' => 'manual',
            'destination' => 'local',
            'status' => 'success',
            'filename' => 'pos_alja_20261126_020000_abcdef.dump',
            'is_pinned' => false,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->forceDelete();

        $this->assertNull(DB::table('backups')->where('id', $backupId)->value('user_id'));
    }

    public function test_backup_settings_table_has_the_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('backup_settings'));
        $this->assertTrue(Schema::hasColumns('backup_settings', [
            'id',
            'local_enabled',
            'local_time',
            'remote_enabled',
            'remote_time',
            'local_daily_days',
            'local_weekly_weeks',
            'local_monthly_months',
            'local_yearly_years',
            'remote_daily_days',
            'remote_weekly_weeks',
            'remote_monthly_months',
            'remote_yearly_years',
            'last_local_date',
            'last_remote_date',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_backup_settings_defaults_match_the_schedule_and_retention_defaults(): void
    {
        DB::table('backup_settings')->insert(['id' => (string) Str::uuid()]);

        $row = DB::table('backup_settings')->first();

        $this->assertTrue((bool) $row->local_enabled);
        $this->assertSame('02:00', $row->local_time);
        $this->assertFalse((bool) $row->remote_enabled);
        $this->assertSame('02:30', $row->remote_time);
        $this->assertSame(30, (int) $row->local_daily_days);
        $this->assertSame(12, (int) $row->local_weekly_weeks);
        $this->assertSame(12, (int) $row->local_monthly_months);
        $this->assertSame(3, (int) $row->local_yearly_years);
        $this->assertSame(7, (int) $row->remote_daily_days);
        $this->assertSame(4, (int) $row->remote_weekly_weeks);
        $this->assertSame(12, (int) $row->remote_monthly_months);
        $this->assertSame(3, (int) $row->remote_yearly_years);
        $this->assertNull($row->last_local_date);
        $this->assertNull($row->last_remote_date);
    }
}
