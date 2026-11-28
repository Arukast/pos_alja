<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Material;
use App\Models\ProductCategory;
use App\Support\MaterialUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialUnitBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_name_m2_keywords_win_over_a_lembar_category(): void
    {
        $material = $this->material('Vinyl Sticker', 'Kertas', 'per_piece');

        MaterialUnit::backfill();

        $this->assertSame('m2', $material->fresh()?->unit);
    }

    public function test_material_name_lembar_and_liter_keywords_backfill(): void
    {
        $lembar = $this->material('Art Paper', 'Lainnya', 'per_piece');
        $liter = $this->material('Tinta Solvent', 'Lainnya', 'per_piece');

        MaterialUnit::backfill();

        $this->assertSame('lembar', $lembar->fresh()?->unit);
        $this->assertSame('liter', $liter->fresh()?->unit);
    }

    public function test_category_name_keywords_apply_when_the_material_name_has_none(): void
    {
        $m2 = $this->material('Bahan A', 'Spanduk', 'per_piece');
        $lembar = $this->material('Bahan B', 'Kertas', 'per_piece');
        $liter = $this->material('Bahan C', 'Tinta', 'per_piece');

        MaterialUnit::backfill();

        $this->assertSame('m2', $m2->fresh()?->unit);
        $this->assertSame('lembar', $lembar->fresh()?->unit);
        $this->assertSame('liter', $liter->fresh()?->unit);
    }

    public function test_category_name_beats_the_surface_area_archetype(): void
    {
        $material = $this->material('Bahan A', 'Kertas', 'surface_area');

        MaterialUnit::backfill();

        $this->assertSame('lembar', $material->fresh()?->unit);
    }

    public function test_material_name_beats_the_category_name(): void
    {
        $material = $this->material('Tinta', 'Spanduk', 'per_piece');

        MaterialUnit::backfill();

        $this->assertSame('liter', $material->fresh()?->unit);
    }

    public function test_surface_area_archetype_backfills_to_m2(): void
    {
        $material = $this->material('Bahan A', 'Lainnya', 'surface_area');

        MaterialUnit::backfill();

        $this->assertSame('m2', $material->fresh()?->unit);
    }

    public function test_unmatched_materials_backfill_to_pcs(): void
    {
        $material = $this->material('Bahan A', 'Lainnya', 'per_piece');

        MaterialUnit::backfill();

        $this->assertSame('pcs', $material->fresh()?->unit);
    }

    private function material(string $name, string $categoryName, string $archetype): Material
    {
        $category = ProductCategory::create([
            'name' => $categoryName,
            'calculation_archetype' => $archetype,
        ]);

        return Material::create([
            'name' => $name,
            'product_category_id' => $category->id,
            'cost' => 0,
            'price' => 0,
        ]);
    }
}
