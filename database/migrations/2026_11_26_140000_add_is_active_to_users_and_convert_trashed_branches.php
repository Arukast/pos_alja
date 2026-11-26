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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true);
        });

        DB::table('branches')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null, 'is_active' => false]);
    }

    /**
     * Reverse the migrations.
     *
     * The branch conversion above is one-way: a trashed branch cannot be told
     * apart from one that was already inactive before this migration.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
