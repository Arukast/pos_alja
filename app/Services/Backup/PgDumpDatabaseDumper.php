<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use RuntimeException;

final class PgDumpDatabaseDumper implements DatabaseDumper
{
    public function dump(string $absolutePath): void
    {
        $connection = DB::getDefaultConnection();
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'pgsql') {
            throw new RuntimeException(sprintf(
                'Database backup requires a "pgsql" connection; the "%s" connection uses "%s".',
                $connection,
                $driver,
            ));
        }

        $config = (array) config("database.connections.{$connection}", []);

        $command = [
            (string) config('backup.pg_dump', 'pg_dump'),
            '--format=custom',
            '--compress=9',
            '--no-owner',
            '--no-privileges',
            '--no-password',
            '--host', (string) ($config['host'] ?? ''),
            '--port', (string) ($config['port'] ?? ''),
            '--username', (string) ($config['username'] ?? ''),
            '--dbname', (string) ($config['database'] ?? ''),
            '--file', $absolutePath,
        ];

        // PGPASSWORD stays in the process environment, never in argv.
        $result = Process::timeout((int) config('backup.timeout'))
            ->env(['PGPASSWORD' => (string) ($config['password'] ?? '')])
            ->run($command);

        if ($result->failed()) {
            $stderr = trim($result->errorOutput());

            throw new RuntimeException(sprintf(
                'pg_dump failed with exit code %s%s',
                (string) $result->exitCode(),
                $stderr === '' ? '.' : ': '.$stderr,
            ));
        }
    }
}
