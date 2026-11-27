<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add per-customer institution labels while keeping the primary label mirrored. */
    public function up(): void
    {
        // Add nullable first so existing rows can be backfilled from their primary label;
        // a column-level default would otherwise mask every legacy row.
        Schema::table('customers', function (Blueprint $table): void {
            $table->json('institutions')->nullable();
        });

        DB::table('customers')
            ->whereNull('institutions')
            ->chunkById(200, static function (Collection $customers): void {
                foreach ($customers as $customer) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'institutions' => json_encode([$customer->institution], JSON_THROW_ON_ERROR),
                        ]);
                }
            });

        Schema::table('customers', function (Blueprint $table): void {
            $table->json('institutions')
                ->nullable()
                ->default(json_encode(['Masyarakat/Retail'], JSON_THROW_ON_ERROR))
                ->change();
        });
    }

    /** Remove the per-customer institution labels column. */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('institutions');
        });
    }
};
