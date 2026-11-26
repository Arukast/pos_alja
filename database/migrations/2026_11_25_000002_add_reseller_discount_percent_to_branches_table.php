<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->decimal('reseller_discount_percent', 5, 2)->default(0)->after('is_active');
        });

        DB::table('branches')
            ->select(['id', 'settings'])
            ->whereNotNull('settings')
            ->orderBy('id')
            ->each(function (object $row): void {
                $settings = is_string($row->settings) ? json_decode($row->settings, true) : null;

                if (! is_array($settings) || ! isset($settings['reseller_discount_percent'])) {
                    return;
                }

                $percent = (float) $settings['reseller_discount_percent'];

                if ($percent >= 0 && $percent <= 100) {
                    DB::table('branches')
                        ->where('id', $row->id)
                        ->update(['reseller_discount_percent' => number_format($percent, 2, '.', '')]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn('reseller_discount_percent');
        });
    }
};
