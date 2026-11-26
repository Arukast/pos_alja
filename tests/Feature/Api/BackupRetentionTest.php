<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Backup;
use App\Services\Backup\BackupRetention;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class BackupRetentionTest extends TestCase
{
    use RefreshDatabase;

    private const DISK = 'backups-local';

    private const REMOTE_DISK = 'backups-s3';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(self::DISK);
        Storage::fake(self::REMOTE_DISK);

        $this->travelTo(CarbonImmutable::parse('2026-11-26 12:00:00', 'Asia/Jakarta'));
    }

    public function test_keeps_the_newest_file_per_day_week_month_and_year(): void
    {
        $disk = Storage::disk(self::DISK);

        $kept = [
            '2026/11/26/b.dump',
            '2026/11/25/c.dump',
            '2026/11/24/d.dump',
            '2026/11/20/f.dump',
            '2026/10/20/i.dump',
            '2025/12/31/j.dump',
        ];

        $pruned = [
            '2026/11/26/a.dump',
            '2026/11/23/e.dump',
            '2026/11/17/g.dump',
            '2026/10/15/h.dump',
            '2025/06/01/k.dump',
            '2024/05/05/l.dump',
        ];

        foreach ([...$kept, ...$pruned] as $path) {
            $disk->put($path, 'dump');
        }

        $deleted = (new BackupRetention)->prune(self::DISK, $this->windows(3, 2, 2, 2));

        $this->assertSame(6, $deleted);
        $this->assertEqualsCanonicalizing($kept, $disk->allFiles());
    }

    public function test_always_keeps_the_newest_file_overall_even_outside_windows(): void
    {
        $disk = Storage::disk(self::DISK);

        $disk->put('2025/06/01/newest.dump', 'dump');
        $disk->put('2025/05/10/older.dump', 'dump');
        $disk->put('2024/01/01/oldest.dump', 'dump');

        $deleted = (new BackupRetention)->prune(self::DISK, $this->windows(1, 1, 1, 1));

        $this->assertSame(2, $deleted);
        $this->assertTrue($disk->exists('2025/06/01/newest.dump'));
        $this->assertFalse($disk->exists('2025/05/10/older.dump'));
        $this->assertFalse($disk->exists('2024/01/01/oldest.dump'));
    }

    public function test_pinned_files_survive_and_unpinned_files_become_eligible(): void
    {
        $disk = Storage::disk(self::DISK);

        $disk->put('2026/11/05/pinned.dump', 'dump');
        $disk->put('2026/11/10/other.dump', 'dump');
        $disk->put('2026/10/01/october.dump', 'dump');

        $backup = $this->backup([
            'filename' => 'pinned.dump',
            'local_path' => '2026/11/05/pinned.dump',
            'is_pinned' => true,
        ]);

        $retention = new BackupRetention;
        $windows = $this->windows(1, 1, 1, 1);

        $this->assertSame(1, $retention->prune(self::DISK, $windows));
        $this->assertTrue($disk->exists('2026/11/05/pinned.dump'));
        $this->assertTrue($disk->exists('2026/11/10/other.dump'));
        $this->assertFalse($disk->exists('2026/10/01/october.dump'));

        $backup->update(['is_pinned' => false]);

        $this->assertSame(1, $retention->prune(self::DISK, $windows));
        $this->assertFalse($disk->exists('2026/11/05/pinned.dump'));
        $this->assertNull($backup->fresh()->local_path);
    }

    public function test_deleting_a_file_nulls_the_matching_local_and_remote_paths(): void
    {
        $path = '2026/10/01/pos_alja_20261001_000000_abcdef.dump';

        $backup = $this->backup([
            'filename' => 'pos_alja_20261001_000000_abcdef.dump',
            'local_path' => $path,
            'remote_path' => $path,
        ]);

        foreach ([self::DISK, self::REMOTE_DISK] as $disk) {
            Storage::disk($disk)->put($path, 'dump');
            Storage::disk($disk)->put('2026/11/26/keep.dump', 'dump');
        }

        $retention = new BackupRetention;
        $windows = $this->windows(1, 1, 1, 1);

        $this->assertSame(1, $retention->prune(self::DISK, $windows));
        $this->assertNull($backup->fresh()->local_path);
        $this->assertSame($path, $backup->fresh()->remote_path);

        $this->assertSame(1, $retention->prune(self::REMOTE_DISK, $windows));
        $this->assertNull($backup->fresh()->remote_path);
        $this->assertDatabaseCount('backups', 1);
    }

    public function test_lowering_windows_prunes_immediately_on_the_next_run(): void
    {
        $disk = Storage::disk(self::DISK);

        $disk->put('2026/11/26/a.dump', 'dump');
        $disk->put('2026/11/25/b.dump', 'dump');
        $disk->put('2026/11/24/c.dump', 'dump');

        $retention = new BackupRetention;

        $this->assertSame(0, $retention->prune(self::DISK, $this->windows(3, 3, 3, 3)));
        $this->assertCount(3, $disk->allFiles());

        $this->assertSame(2, $retention->prune(self::DISK, $this->windows(1, 1, 1, 1)));
        $this->assertSame(['2026/11/26/a.dump'], $disk->allFiles());
    }

    public function test_retention_ignores_files_outside_the_dated_tree(): void
    {
        $disk = Storage::disk(self::DISK);

        $disk->put('manual.dump', 'dump');
        $disk->put('exports/2026/11/26/nested.dump', 'dump');
        $disk->put('2026/11/26/readme.txt', 'dump');
        $disk->put('2026/11/26/keep.dump', 'dump');
        $disk->put('2026/10/01/prune.dump', 'dump');

        $deleted = (new BackupRetention)->prune(self::DISK, $this->windows(1, 1, 1, 1));

        $this->assertSame(1, $deleted);
        $this->assertTrue($disk->exists('manual.dump'));
        $this->assertTrue($disk->exists('exports/2026/11/26/nested.dump'));
        $this->assertTrue($disk->exists('2026/11/26/readme.txt'));
        $this->assertTrue($disk->exists('2026/11/26/keep.dump'));
        $this->assertFalse($disk->exists('2026/10/01/prune.dump'));
    }

    public function test_month_end_keeps_the_newest_file_for_each_allowed_month(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-01-31 12:00:00', 'Asia/Jakarta'));

        $disk = Storage::disk(self::DISK);

        $kept = [
            '2026/01/31/january.dump',
            '2025/12/31/december.dump',
        ];

        $pruned = [
            '2026/01/05/january-old.dump',
            '2025/12/15/december-old.dump',
            '2025/11/30/november.dump',
            '2025/10/31/october.dump',
        ];

        foreach ([...$kept, ...$pruned] as $path) {
            $disk->put($path, 'dump');
        }

        $deleted = (new BackupRetention)->prune(self::DISK, $this->windows(1, 1, 2, 1));

        $this->assertSame(4, $deleted);
        $this->assertEqualsCanonicalizing($kept, $disk->allFiles());
    }

    public function test_month_end_does_not_skip_the_previous_month(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-31 12:00:00', 'Asia/Jakarta'));

        $disk = Storage::disk(self::DISK);

        $kept = [
            '2026/03/31/march.dump',
            '2026/02/28/february.dump',
        ];

        $pruned = [
            '2026/03/15/march-old.dump',
            '2026/01/31/january.dump',
        ];

        foreach ([...$kept, ...$pruned] as $path) {
            $disk->put($path, 'dump');
        }

        $deleted = (new BackupRetention)->prune(self::DISK, $this->windows(1, 1, 2, 1));

        $this->assertSame(2, $deleted);
        $this->assertEqualsCanonicalizing($kept, $disk->allFiles());
    }

    public function test_iso_week_boundary_on_january_first_spans_the_iso_year(): void
    {
        $this->travelTo(CarbonImmutable::parse('2027-01-01 12:00:00', 'Asia/Jakarta'));

        $this->putIsoWeekBoundaryFiles();

        $deleted = (new BackupRetention)->prune(self::DISK, $this->windows(1, 2, 1, 1));

        $this->assertSame(2, $deleted);
        $this->assertEqualsCanonicalizing([
            '2027/01/01/current-iso-week.dump',
            '2026/12/21/previous-iso-week.dump',
            '2027/01/04/newer-overall.dump',
        ], Storage::disk(self::DISK)->allFiles());
    }

    public function test_iso_week_boundary_on_december_31_spans_the_iso_year(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-12-31 12:00:00', 'Asia/Jakarta'));

        $this->putIsoWeekBoundaryFiles();

        $deleted = (new BackupRetention)->prune(self::DISK, $this->windows(1, 2, 1, 1));

        $this->assertSame(1, $deleted);
        $this->assertEqualsCanonicalizing([
            '2027/01/01/current-iso-week.dump',
            '2026/12/31/older-in-current-iso-week.dump',
            '2026/12/21/previous-iso-week.dump',
            '2027/01/04/newer-overall.dump',
        ], Storage::disk(self::DISK)->allFiles());
    }

    public function test_zero_or_negative_windows_throw_before_deleting_anything(): void
    {
        $disk = Storage::disk(self::DISK);

        $disk->put('2026/10/01/old.dump', 'dump');
        $disk->put('2026/11/26/new.dump', 'dump');

        $backup = $this->backup([
            'filename' => 'old.dump',
            'local_path' => '2026/10/01/old.dump',
        ]);

        $retention = new BackupRetention;

        foreach ([
            $this->windows(0, 1, 1, 1),
            $this->windows(1, 1, -1, 1),
        ] as $windows) {
            try {
                $retention->prune(self::DISK, $windows);
                $this->fail('Expected an InvalidArgumentException for a retention window below 1.');
            } catch (InvalidArgumentException) {
                // Expected: fail closed before any file or row path is touched.
            }

            $this->assertTrue($disk->exists('2026/10/01/old.dump'));
            $this->assertTrue($disk->exists('2026/11/26/new.dump'));
            $this->assertSame('2026/10/01/old.dump', $backup->fresh()->local_path);
        }
    }

    private function putIsoWeekBoundaryFiles(): void
    {
        $disk = Storage::disk(self::DISK);

        foreach ([
            '2027/01/01/current-iso-week.dump',
            '2026/12/31/older-in-current-iso-week.dump',
            '2026/12/21/previous-iso-week.dump',
            '2027/01/04/newer-overall.dump',
            '2026/12/20/outside-weeks.dump',
        ] as $path) {
            $disk->put($path, 'dump');
        }
    }

    /**
     * @return array{daily:int, weekly:int, monthly:int, yearly:int}
     */
    private function windows(int $daily, int $weekly, int $monthly, int $yearly): array
    {
        return [
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'yearly' => $yearly,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function backup(array $attributes = []): Backup
    {
        return Backup::query()->create(array_merge([
            'trigger' => 'scheduled',
            'destination' => 'local',
            'status' => 'success',
            'filename' => 'pos_alja_20261126_020000_abcdef.dump',
        ], $attributes));
    }
}
