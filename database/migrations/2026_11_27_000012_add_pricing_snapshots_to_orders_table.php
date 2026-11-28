<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignUuid('material_id')->nullable()->constrained('materials')->nullOnDelete();
            $table->string('customer_type', 50)->default('Reguler');
            $table->decimal('reseller_discount_percent', 5, 2)->default(0);
            $table->decimal('quantity_discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['material_id']);
            $table->dropColumn([
                'discount_amount',
                'quantity_discount_percent',
                'reseller_discount_percent',
                'customer_type',
                'material_id',
                'product_id',
            ]);
        });
    }
};
