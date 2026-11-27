<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SellingUnitBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_replaces_pricing_type_with_selling_unit_and_defaults_to_pcs(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'selling_unit'));
        $this->assertFalse(Schema::hasColumn('products', 'pricing_type'));

        $category = ProductCategory::create(['name' => 'Kertas']);
        $product = Product::create([
            'name' => 'Kartu Nama',
            'product_category_id' => $category->id,
        ]);

        $this->assertSame('pcs', $product->fresh()->selling_unit);
    }

    public function test_migration_backfills_area_and_unit_rows_including_soft_deleted(): void
    {
        $category = ProductCategory::create(['name' => 'Large Format']);
        $migration = require database_path(
            'migrations/2026_11_27_000008_z_replace_product_pricing_type_with_selling_unit.php'
        );

        $migration->down();
        $this->assertTrue(Schema::hasColumn('products', 'pricing_type'));

        // The production down() restores NOT NULL; allow the legacy NULL fixture here only.
        Schema::table('products', function (Blueprint $table): void {
            $table->string('pricing_type', 20)->nullable()->change();
        });

        $base = [
            'product_category_id' => $category->id,
            'is_active' => true,
            'requires_material' => true,
            'uses_attributes' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ([
            ['name' => 'Banner', 'pricing_type' => 'area', 'deleted_at' => null],
            ['name' => 'Sticker', 'pricing_type' => 'unit', 'deleted_at' => null],
            ['name' => 'Legacy Null', 'pricing_type' => null, 'deleted_at' => null],
            ['name' => 'Deleted Banner', 'pricing_type' => 'area', 'deleted_at' => now()],
        ] as $row) {
            DB::table('products')->insert(array_merge(['id' => (string) Str::uuid()], $base, $row));
        }

        $migration->up();

        $this->assertSame('m2', DB::table('products')->where('name', 'Banner')->value('selling_unit'));
        $this->assertSame('pcs', DB::table('products')->where('name', 'Sticker')->value('selling_unit'));
        $this->assertSame('pcs', DB::table('products')->where('name', 'Legacy Null')->value('selling_unit'));
        $this->assertSame('m2', DB::table('products')->where('name', 'Deleted Banner')->value('selling_unit'));
        $this->assertFalse(Schema::hasColumn('products', 'pricing_type'));
    }
}
