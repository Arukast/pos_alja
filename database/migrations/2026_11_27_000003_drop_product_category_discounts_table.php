<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No FK points to this table and no catalog row is deleted.
     */
    public function up(): void
    {
        Schema::dropIfExists('product_category_discounts');
    }

    /**
     * Recreate the table empty; dropped discount rows are unrecoverable.
     */
    public function down(): void
    {
        Schema::create('product_category_discounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_category_id')
                ->constrained('product_categories')
                ->cascadeOnDelete();
            $table->unsignedInteger('min_qty');
            $table->decimal('discount_percent', 5, 2);
            $table->timestamps();

            $table->unique(['product_category_id', 'min_qty']);
        });
    }
};
