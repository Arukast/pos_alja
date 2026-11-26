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
        Schema::create('material_product', function (Blueprint $table): void {
            $table->foreignUuid('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignUuid('material_id')
                ->constrained('materials')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['product_id', 'material_id']);
        });

        $pairs = DB::table('pricing')
            ->join('products', 'pricing.product_id', '=', 'products.id')
            ->join('materials', 'pricing.material_id', '=', 'materials.id')
            ->select('pricing.product_id', 'pricing.material_id')
            ->distinct()
            ->get();

        if ($pairs->isNotEmpty()) {
            $now = now();
            $rows = $pairs->map(static fn (object $pair): array => [
                'product_id' => $pair->product_id,
                'material_id' => $pair->material_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('material_product')->insertOrIgnore($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('material_product');
    }
};
