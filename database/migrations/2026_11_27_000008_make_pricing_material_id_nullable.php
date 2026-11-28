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
        Schema::table('pricing', function (Blueprint $table): void {
            $table->foreignUuid('material_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('pricing')->whereNull('material_id')->exists()) {
            throw new RuntimeException(
                'Tidak dapat mengembalikan pricing.material_id menjadi NOT NULL selama masih ada baris tanpa bahan. '
                .'Hapus atau isi material_id terlebih dahulu.'
            );
        }

        Schema::table('pricing', function (Blueprint $table): void {
            $table->foreignUuid('material_id')->nullable(false)->change();
        });
    }
};
