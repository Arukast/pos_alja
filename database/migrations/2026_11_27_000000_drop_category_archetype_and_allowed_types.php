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
        $surfaceAreaCategoryIds = DB::table('product_categories')
            ->where('calculation_archetype', 'surface_area')
            ->pluck('id');

        if ($surfaceAreaCategoryIds->isNotEmpty()) {
            DB::table('products')
                ->whereIn('product_category_id', $surfaceAreaCategoryIds->all())
                ->where('pricing_type', '!=', 'area')
                ->update(['pricing_type' => 'area']);
        }

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->string('calculation_archetype')->nullable()->default('per_piece')->change();
            $table->json('allowed_attribute_types')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('product_categories')
            ->whereNull('calculation_archetype')
            ->update(['calculation_archetype' => 'per_piece']);

        Schema::table('product_categories', function (Blueprint $table): void {
            $table->string('calculation_archetype')->nullable(false)->default(null)->change();
            $table->json('allowed_attribute_types')->nullable()->change();
        });
    }
};
