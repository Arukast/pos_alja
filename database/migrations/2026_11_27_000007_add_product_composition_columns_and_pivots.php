<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->boolean('requires_material')->default(true);
            $table->boolean('uses_attributes')->default(true);
        });

        Schema::create('attribute_type_product', function (Blueprint $table): void {
            $table->foreignUuid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignUuid('attribute_type_id')
                ->constrained('attribute_types')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_id', 'attribute_type_id']);
        });

        Schema::create('attribute_product', function (Blueprint $table): void {
            $table->foreignUuid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignUuid('attribute_id')
                ->constrained('attributes')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_id', 'attribute_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_product');
        Schema::dropIfExists('attribute_type_product');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['requires_material', 'uses_attributes']);
        });
    }
};
