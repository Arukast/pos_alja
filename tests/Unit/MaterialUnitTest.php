<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\MaterialUnit;
use PHPUnit\Framework\TestCase;

class MaterialUnitTest extends TestCase
{
    public function test_material_name_m2_keywords_win_over_lembar(): void
    {
        $this->assertSame('m2', MaterialUnit::guess('Vinyl Sticker', 'Kertas', 'per_piece'));
        $this->assertSame('m2', MaterialUnit::guess('Banner', 'Tinta', 'per_piece'));
    }

    public function test_material_name_keywords_follow_m2_lembar_liter_order(): void
    {
        $this->assertSame('m2', MaterialUnit::guess('Kertas Vinyl', null, null));
        $this->assertSame('lembar', MaterialUnit::guess('Tinta Kertas', null, null));
        $this->assertSame('liter', MaterialUnit::guess('Tinta Solvent', null, null));
    }

    public function test_category_name_keywords_apply_when_the_material_name_has_none(): void
    {
        $this->assertSame('m2', MaterialUnit::guess('Bahan A', 'Spanduk', 'per_piece'));
        $this->assertSame('lembar', MaterialUnit::guess('Bahan B', 'Kertas', 'per_piece'));
        $this->assertSame('liter', MaterialUnit::guess('Bahan C', 'Tinta', 'per_piece'));
    }

    public function test_surface_area_archetype_maps_to_m2_and_unmatched_inputs_to_pcs(): void
    {
        $this->assertSame('m2', MaterialUnit::guess('Bahan A', 'Lainnya', 'surface_area'));
        $this->assertSame('pcs', MaterialUnit::guess('Bahan A', 'Lainnya', 'per_piece'));
        $this->assertSame('pcs', MaterialUnit::guess(null, null, null));
    }

    public function test_values_are_the_fixed_unit_set(): void
    {
        $this->assertSame(
            ['m2', 'pcs', 'lembar', 'roll', 'kg', 'liter', 'batang'],
            MaterialUnit::VALUES
        );
    }
}
