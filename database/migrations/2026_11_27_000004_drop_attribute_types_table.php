<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No FK points here; attributes.type keeps its stored strings, including legacy `cut`.
     */
    public function up(): void
    {
        Schema::dropIfExists('attribute_types');
    }

    /**
     * Recreate the table empty; the seeded default rows are unrecoverable.
     */
    public function down(): void
    {
        Schema::create('attribute_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('calculation_archetype');
            $table->timestamps();
        });
    }
};
