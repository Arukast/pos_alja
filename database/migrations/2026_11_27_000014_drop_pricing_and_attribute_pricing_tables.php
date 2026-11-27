<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('attribute_pricing');
        Schema::dropIfExists('pricing');
    }

    public function down(): void
    {
        // Rows are not restored; the drop was an accepted, irreversible user decision.
        Schema::create('pricing', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('material_id')->nullable()->constrained('materials')->cascadeOnDelete();
            $table->string('customer_type', 50)->default('Reguler');
            $table->integer('min_qty')->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('attribute_pricing', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('pricing_id')->constrained('pricing')->cascadeOnDelete();
            $table->foreignUuid('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->timestamps();
        });
    }
};
