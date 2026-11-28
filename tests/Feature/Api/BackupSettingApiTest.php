<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class BackupSettingApiTest extends TestCase
{
    use RefreshDatabase;

    private const REMOTE_CONFIG = [
        'bucket' => 'test-bucket',
        'endpoint' => 'https://example.r2.cloudflarestorage.com',
        'key' => 'test-key',
        'secret' => 'test-secret',
    ];

    /**
     * The 12 fields a full-replace PUT must receive.
     *
     * @var list<string>
     */
    private const REQUIRED_KEYS = [
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
    }

    public function test_guest_cannot_access_backup_settings(): void
    {
        $this->getJson('/api/v1/backup-settings')->assertUnauthorized();
        $this->putJson('/api/v1/backup-settings', $this->payload())->assertUnauthorized();

        $this->assertDatabaseCount('backup_settings', 0);
    }

    public function test_non_owner_cannot_access_backup_settings(): void
    {
        $this->actingAs($this->manager)
            ->getJson('/api/v1/backup-settings')
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->putJson('/api/v1/backup-settings', [])
            ->assertForbidden();

        $this->assertDatabaseCount('backup_settings', 0);
    }

    public function test_owner_reads_defaults_with_usage_and_quota_fields(): void
    {
        config([
            'filesystems.disks.backups-s3' => [],
            'backup.remote_quota_bytes' => 104857600,
            'backup.schedule.local.enabled' => true,
            'backup.schedule.local.at' => '01:15',
            'backup.schedule.remote.enabled' => false,
            'backup.schedule.remote.at' => '03:45',
            'backup.schedule.local.daily' => 30,
            'backup.schedule.local.weekly' => 12,
            'backup.schedule.local.monthly' => 11,
            'backup.schedule.local.yearly' => 3,
            'backup.schedule.remote.daily' => 7,
            'backup.schedule.remote.weekly' => 4,
            'backup.schedule.remote.monthly' => 10,
            'backup.schedule.remote.yearly' => 2,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/backup-settings')
            ->assertOk()
            ->assertJsonPath('settings.local_enabled', true)
            ->assertJsonPath('settings.local_time', '01:15')
            ->assertJsonPath('settings.remote_enabled', false)
            ->assertJsonPath('settings.remote_time', '03:45')
            ->assertJsonPath('settings.local_daily_days', 30)
            ->assertJsonPath('settings.local_weekly_weeks', 12)
            ->assertJsonPath('settings.local_monthly_months', 11)
            ->assertJsonPath('settings.local_yearly_years', 3)
            ->assertJsonPath('settings.remote_daily_days', 7)
            ->assertJsonPath('settings.remote_weekly_weeks', 4)
            ->assertJsonPath('settings.remote_monthly_months', 10)
            ->assertJsonPath('settings.remote_yearly_years', 2)
            ->assertJsonPath('settings.local_usage_bytes', 0)
            ->assertJsonPath('settings.remote_usage_bytes', 0)
            ->assertJsonPath('settings.remote_quota_bytes', 104857600)
            ->assertJsonPath('settings.remote_configured', false)
            ->assertJsonPath('settings.pinned_count', 0);

        $this->assertSame(0.0, (float) $response->json('settings.remote_usage_percent'));
        $this->assertSame(self::SETTING_KEYS, array_keys($response->json('settings')));
        $this->assertDatabaseCount('backup_settings', 1);
    }

    public function test_remote_configured_is_true_with_credentials_and_values_are_not_exposed(): void
    {
        config([
            'filesystems.disks.backups-s3' => array_merge(
                ['driver' => 'local'],
                self::REMOTE_CONFIG,
            ),
            'backup.remote_quota_bytes' => 1024,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/backup-settings')
            ->assertOk()
            ->assertJsonPath('settings.remote_configured', true);

        $this->assertStringNotContainsString('test-secret', $response->getContent());
        $this->assertStringNotContainsString('test-key', $response->getContent());
        $this->assertStringNotContainsString('example.r2.cloudflarestorage.com', $response->getContent());
        $this->assertStringNotContainsString('test-bucket', $response->getContent());
    }

    public function test_usage_sums_only_rows_with_a_path_and_counts_pins(): void
    {
        config(['backup.remote_quota_bytes' => 1000]);

        $this->backupRow([
            'size_bytes' => 100,
            'local_path' => '2026/11/26/a.dump',
            'remote_path' => '2026/11/26/a.dump',
            'is_pinned' => true,
        ]);
        $this->backupRow([
            'size_bytes' => 50,
            'local_path' => null,
            'remote_path' => '2026/11/26/b.dump',
        ]);
        $this->backupRow([
            'size_bytes' => 30,
            'local_path' => '2026/11/26/c.dump',
            'remote_path' => null,
        ]);
        $this->backupRow([
            'size_bytes' => 500,
            'local_path' => null,
            'remote_path' => null,
            'is_pinned' => true,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/backup-settings')
            ->assertOk()
            ->assertJsonPath('settings.local_usage_bytes', 130)
            ->assertJsonPath('settings.remote_usage_bytes', 150)
            ->assertJsonPath('settings.remote_quota_bytes', 1000)
            ->assertJsonPath('settings.pinned_count', 2);

        $this->assertSame(15.0, (float) $response->json('settings.remote_usage_percent'));
    }

    public function test_remote_usage_percent_is_zero_when_quota_is_zero(): void
    {
        config(['backup.remote_quota_bytes' => 0]);

        $this->backupRow([
            'size_bytes' => 50,
            'local_path' => null,
            'remote_path' => '2026/11/26/b.dump',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/v1/backup-settings')
            ->assertOk()
            ->assertJsonPath('settings.remote_usage_bytes', 50)
            ->assertJsonPath('settings.remote_quota_bytes', 0);

        $this->assertSame(0.0, (float) $response->json('settings.remote_usage_percent'));
    }

    public function test_owner_can_replace_every_setting_and_one_update_audit_row_is_written(): void
    {
        config([
            'backup.schedule.local.enabled' => true,
            'backup.schedule.local.at' => '02:00',
            'backup.schedule.remote.enabled' => false,
            'backup.schedule.remote.at' => '02:30',
            'backup.schedule.local.daily' => 30,
            'backup.schedule.local.weekly' => 12,
            'backup.schedule.local.monthly' => 12,
            'backup.schedule.local.yearly' => 3,
            'backup.schedule.remote.daily' => 7,
            'backup.schedule.remote.weekly' => 4,
            'backup.schedule.remote.monthly' => 12,
            'backup.schedule.remote.yearly' => 3,
        ]);

        $payload = [
            'local_enabled' => true,
            'local_time' => '04:15',
            'remote_enabled' => true,
            'remote_time' => '04:45',
            'local_daily_days' => 45,
            'local_weekly_weeks' => 20,
            'local_monthly_months' => 18,
            'local_yearly_years' => 8,
            'remote_daily_days' => 14,
            'remote_weekly_weeks' => 8,
            'remote_monthly_months' => 20,
            'remote_yearly_years' => 10,
        ];

        $response = $this->actingAs($this->owner)
            ->putJson('/api/v1/backup-settings', $payload)
            ->assertOk();

        foreach ($payload as $key => $value) {
            $response->assertJsonPath('settings.'.$key, $value);
        }

        $settings = BackupSetting::query()->sole();

        foreach ($payload as $key => $value) {
            $this->assertSame($value, $settings->{$key}, "Expected {$key} to persist.");
        }

        $log = ActivityLog::query()->sole();

        $this->assertSame('UPDATE', $log->action);
        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(BackupSetting::class, $log->model_type);
        $this->assertSame($settings->id, $log->model_id);
        $this->assertSame(30, $log->payload['before']['local_daily_days']);
        $this->assertSame(45, $log->payload['after']['local_daily_days']);
        $this->assertSame('02:00', $log->payload['before']['local_time']);
        $this->assertSame('04:15', $log->payload['after']['local_time']);
        $this->assertFalse($log->payload['before']['remote_enabled']);
        $this->assertTrue($log->payload['after']['remote_enabled']);
    }

    public function test_update_requires_every_field(): void
    {
        $this->actingAs($this->owner)
            ->putJson('/api/v1/backup-settings', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(self::REQUIRED_KEYS);

        $this->assertDatabaseCount('backup_settings', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_update_rejects_invalid_times_and_booleans(): void
    {
        $cases = [
            ['local_time', '25:00'],
            ['remote_time', '02:60'],
            ['local_time', 'ab:cd'],
            ['local_enabled', 'yes'],
            ['remote_enabled', 'yes'],
        ];

        foreach ($cases as [$field, $value]) {
            $this->actingAs($this->owner)
                ->putJson('/api/v1/backup-settings', $this->payload([$field => $value]))
                ->assertStatus(422)
                ->assertJsonValidationErrors($field);
        }

        $this->assertDatabaseCount('backup_settings', 0);
    }

    public function test_update_rejects_every_out_of_range_retention_boundary(): void
    {
        $cases = [
            'local_daily_days' => [6, 91],
            'local_weekly_weeks' => [3, 53],
            'local_monthly_months' => [5, 25],
            'local_yearly_years' => [0, 11],
            'remote_daily_days' => [6, 91],
            'remote_weekly_weeks' => [3, 53],
            'remote_monthly_months' => [5, 25],
            'remote_yearly_years' => [0, 11],
        ];

        foreach ($cases as $field => $values) {
            foreach ($values as $value) {
                $this->actingAs($this->owner)
                    ->putJson('/api/v1/backup-settings', $this->payload([$field => $value]))
                    ->assertStatus(422)
                    ->assertJsonValidationErrors($field);
            }
        }

        $this->actingAs($this->owner)
            ->putJson('/api/v1/backup-settings', $this->payload(['local_daily_days' => 'abc']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('local_daily_days');

        $this->assertDatabaseCount('backup_settings', 0);
    }

    public function test_retention_boundaries_are_inclusive(): void
    {
        $minimums = [
            'local_daily_days' => 7,
            'local_weekly_weeks' => 4,
            'local_monthly_months' => 6,
            'local_yearly_years' => 1,
            'remote_daily_days' => 7,
            'remote_weekly_weeks' => 4,
            'remote_monthly_months' => 6,
            'remote_yearly_years' => 1,
        ];

        $maximums = [
            'local_daily_days' => 90,
            'local_weekly_weeks' => 52,
            'local_monthly_months' => 24,
            'local_yearly_years' => 10,
            'remote_daily_days' => 90,
            'remote_weekly_weeks' => 52,
            'remote_monthly_months' => 24,
            'remote_yearly_years' => 10,
        ];

        $this->actingAs($this->owner)
            ->putJson('/api/v1/backup-settings', $this->payload($minimums))
            ->assertOk();

        $this->actingAs($this->owner)
            ->putJson('/api/v1/backup-settings', $this->payload($maximums))
            ->assertOk();

        $this->assertSame(2, ActivityLog::query()->where('action', 'UPDATE')->count());
    }

    public function test_remote_can_be_enabled_without_credentials(): void
    {
        config(['filesystems.disks.backups-s3' => []]);

        $this->actingAs($this->owner)
            ->putJson('/api/v1/backup-settings', $this->payload(['remote_enabled' => true]))
            ->assertOk()
            ->assertJsonPath('settings.remote_enabled', true)
            ->assertJsonPath('settings.remote_configured', false);

        $this->assertTrue(BackupSetting::query()->sole()->remote_enabled);
    }

    /**
     * @var list<string>
     */
    private const SETTING_KEYS = [
        ...self::REQUIRED_KEYS,
        'local_usage_bytes',
        'remote_usage_bytes',
        'remote_quota_bytes',
        'remote_usage_percent',
        'remote_configured',
        'pinned_count',
    ];

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'local_enabled' => true,
            'local_time' => '02:00',
            'remote_enabled' => false,
            'remote_time' => '02:30',
            'local_daily_days' => 30,
            'local_weekly_weeks' => 12,
            'local_monthly_months' => 12,
            'local_yearly_years' => 3,
            'remote_daily_days' => 7,
            'remote_weekly_weeks' => 4,
            'remote_monthly_months' => 12,
            'remote_yearly_years' => 3,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function backupRow(array $overrides = []): Backup
    {
        return Backup::query()->forceCreate(array_merge([
            'trigger' => 'scheduled',
            'destination' => 'local',
            'status' => 'success',
            'is_pinned' => false,
        ], $overrides));
    }
}
