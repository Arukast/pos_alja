<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LegacyMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test legacy data migration command.
     */
    public function test_legacy_data_migration_command_runs_successfully(): void
    {
        // Skip the test if mysql_migration connection is not available (e.g. in CI)
        try {
            DB::connection('mysql_migration')->getPdo();
        } catch (\Exception $e) {
            $this->markTestSkipped('Legacy MariaDB connection is not available.');
        }

        // Assert postgres/test tables are empty first
        $this->assertEquals(0, DB::table('branches')->count());
        $this->assertEquals(0, DB::table('users')->count());

        // Run the migration command
        $this->artisan('db:migrate-legacy')
            ->expectsOutput('Starting migration from legacy MariaDB to PostgreSQL...')
            ->expectsOutput('All legacy data successfully migrated to PostgreSQL!')
            ->assertExitCode(0);

        // Assert tables are populated
        $this->assertGreaterThan(0, DB::table('branches')->count());
        $this->assertEquals('Main Branch', DB::table('branches')->first()->name);

        $this->assertGreaterThan(0, DB::table('users')->count());
        $this->assertGreaterThan(0, DB::table('customers')->count());
        $this->assertGreaterThan(0, DB::table('products')->count());
        $this->assertGreaterThan(0, DB::table('materials')->count());

        // Assert pricing matrices are populated
        $this->assertGreaterThan(0, DB::table('pricing_matrices')->count());

        // Assert invoices & orders are populated
        $this->assertGreaterThan(0, DB::table('invoices')->count());
        $this->assertGreaterThan(0, DB::table('orders')->count());

        // Assert wasted materials and stock ledgers are populated
        $this->assertGreaterThan(0, DB::table('wasted_materials')->count());
        $this->assertGreaterThan(0, DB::table('stock_ledgers')->count());

        // Assert passwords were set to temporary default secure hash
        $user = DB::table('users')->first();
        $this->assertNotEmpty($user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }
}
