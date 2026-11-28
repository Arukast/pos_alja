<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Backup\DatabaseDumper;
use App\Services\Backup\PgDumpDatabaseDumper;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Tests\TestCase;

class PgDumpDatabaseDumperTest extends TestCase
{
    public function test_dump_passes_credentials_in_environment_and_uses_custom_compressed_format(): void
    {
        $this->usePostgresConnection();
        config(['backup.timeout' => 600]);
        Process::fake();

        (new PgDumpDatabaseDumper)->dump('/srv/backups/pos_alja.dump');

        Process::assertRan(function (PendingProcess $process, ProcessResult $result): bool {
            $this->assertSame([
                'pg_dump',
                '--format=custom',
                '--compress=9',
                '--no-owner',
                '--no-privileges',
                '--no-password',
                '--host', 'db.example.test',
                '--port', '5544',
                '--username', 'backup_user',
                '--dbname', 'pos_alja',
                '--file', '/srv/backups/pos_alja.dump',
            ], $process->command);
            $this->assertSame(['PGPASSWORD' => 'p@ss-secret'], $process->environment);
            $this->assertStringNotContainsString('p@ss-secret', $result->command());
            $this->assertSame(600, $process->timeout);

            return true;
        });
    }

    public function test_non_zero_exit_throws_with_stderr(): void
    {
        $this->usePostgresConnection();
        Process::fake(['*' => Process::result(errorOutput: 'boom', exitCode: 1)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        (new PgDumpDatabaseDumper)->dump('/srv/backups/pos_alja.dump');
    }

    public function test_failure_message_never_contains_the_password(): void
    {
        $this->usePostgresConnection();
        Process::fake(['*' => Process::result(errorOutput: 'pg_dump: error: query failed', exitCode: 1)]);

        try {
            (new PgDumpDatabaseDumper)->dump('/srv/backups/pos_alja.dump');
            $this->fail('Expected a RuntimeException for a failed dump.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('query failed', $exception->getMessage());
            $this->assertStringNotContainsString('p@ss-secret', $exception->getMessage());
        }
    }

    public function test_non_postgresql_connection_is_refused(): void
    {
        config(['database.default' => 'sqlite']);
        Process::fake();

        try {
            (new PgDumpDatabaseDumper)->dump('/srv/backups/pos_alja.dump');
            $this->fail('Expected a RuntimeException for a non-PostgreSQL connection.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('sqlite', $exception->getMessage());
            $this->assertStringContainsString('pgsql', $exception->getMessage());
        }

        Process::assertNothingRan();
    }

    public function test_database_dumper_interface_is_bound_to_pg_dump_implementation(): void
    {
        $this->assertInstanceOf(PgDumpDatabaseDumper::class, $this->app->make(DatabaseDumper::class));
    }

    private function usePostgresConnection(): void
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => 'db.example.test',
            'database.connections.pgsql.port' => '5544',
            'database.connections.pgsql.database' => 'pos_alja',
            'database.connections.pgsql.username' => 'backup_user',
            'database.connections.pgsql.password' => 'p@ss-secret',
        ]);
    }
}
