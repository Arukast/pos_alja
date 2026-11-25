<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wasted_materials', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
        });

        Schema::table('wasted_materials', function (Blueprint $table) {
            $table->dropColumn('machine_id');
        });
    }

    public function down(): void
    {
        Schema::table('wasted_materials', function (Blueprint $table) {
            $table->foreignUuid('machine_id')->nullable()->constrained('machines')->nullOnDelete();
        });
    }
};
