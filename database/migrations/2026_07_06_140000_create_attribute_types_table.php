<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attribute_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('calculation_archetype'); // e.g. per_piece, surface_area
            $table->timestamps();
        });

        // Insert default initial attribute types
        $defaults = [
            ['id' => (string) Str::uuid(), 'name' => 'finishing', 'calculation_archetype' => 'per_piece', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'name' => 'cut', 'calculation_archetype' => 'per_piece', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'name' => 'display', 'calculation_archetype' => 'per_piece', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('attribute_types')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_types');
    }
};
