<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('selling_unit', 16)->default('pcs');
        });

        // Unscoped so NULL and soft-deleted rows are backfilled too.
        DB::table('products')->update(['selling_unit' => 'pcs']);
        DB::table('products')->where('pricing_type', 'area')->update(['selling_unit' => 'm2']);

        // Separate closure: SQLite rebuilds the table when a column is dropped.
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('pricing_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('pricing_type', 20)->default('unit');
        });

        DB::table('products')->update(['pricing_type' => 'unit']);
        DB::table('products')->where('selling_unit', 'm2')->update(['pricing_type' => 'area']);

        // Separate closure: SQLite rebuilds the table when a column is dropped.
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('selling_unit');
        });
    }
};
