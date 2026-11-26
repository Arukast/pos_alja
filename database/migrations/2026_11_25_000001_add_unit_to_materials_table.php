<?php

declare(strict_types=1);

use App\Support\MaterialUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('unit', 16)->default('pcs');
        });

        // Do not remove or move App\Support\MaterialUnit: this migration's backfill depends on it.
        MaterialUnit::backfill();
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
