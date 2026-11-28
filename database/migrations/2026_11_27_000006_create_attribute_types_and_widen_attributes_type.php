<?php

declare(strict_types=1);

use App\Support\AttributeTypeBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attributes', function (Blueprint $table): void {
            $table->string('type', 100)->change();
        });

        Schema::create('attribute_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('sort_order');
            $table->index('is_active');
        });

        // Do not remove or move App\Support\AttributeTypeBackfill: this migration and its tests depend on it.
        AttributeTypeBackfill::run();
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_types');

        // attributes.type intentionally stays at 100 to avoid truncating stored values.
    }
};
