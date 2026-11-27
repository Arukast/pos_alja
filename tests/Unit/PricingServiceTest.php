<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Exceptions\PricingNotFoundException;
use App\Models\Attribute;
use App\Models\AttributePricing;
use App\Models\Branch;
use App\Models\Material;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricingService;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new PricingService;

        // Setup base branch
        $this->branch = Branch::create([
            'name' => 'Test Branch',
        ]);
    }

    /**
     * Test calculation for standard products (e.g. Merchandise, subtotal = price * qty).
     */
    public function test_merchandise_pricing_calculation(): void
    {
        $category = ProductCategory::create([
            'name' => 'Merchandise',
        ]);

        $product = Product::create([
            'name' => 'Merchandise',
            'selling_unit' => 'pcs',
            'product_category_id' => $category->id,
        ]);

        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mug Putih',
            'product_category_id' => $category->id,
            'type' => 'Merchandise',
        ]);

        $finishing = Attribute::create([
            'branch_id' => $this->branch->id,
            'name' => 'No Finishing',
            'type' => 'finishing',
        ]);

        $pricing = Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'material_id' => $material->id,
            'price' => 15000,
            'cost' => 8000,
            'profit' => 7000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        AttributePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $finishing->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        $result = $this->pricingService->calculateQuote([
            'product_id' => $product->id,
            'material_id' => $material->id,
            'finishing_id' => $finishing->id,
            'sides' => 1,
            'finishing_sides' => 0,
            'quantity' => 5,
        ]);

        $this->assertEquals(15000, $result['unit_price']);
        $this->assertEquals(8000, $result['unit_cost']);
        $this->assertEquals(5, $result['quantity']);
        $this->assertEquals(75000, $result['subtotal']); // 15000 * 5
        $this->assertEquals(40000, $result['cost']);     // 8000 * 5
        $this->assertEquals(35000, $result['profit']);   // 75000 - 40000
    }

    /**
     * Test calculation for Large Format banners (uses area = width * length).
     */
    public function test_large_format_banner_pricing_calculation(): void
    {
        $category = ProductCategory::create([
            'name' => 'Large Format',
        ]);

        $product = Product::create([
            'name' => 'Large Format',
            'selling_unit' => 'm2',
            'product_category_id' => $category->id,
        ]);

        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Flexy 340gr',
            'product_category_id' => $category->id,
            'type' => 'outdoor',
        ]);

        $finishing = Attribute::create([
            'branch_id' => $this->branch->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);

        $pricing = Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'material_id' => $material->id,
            'price' => 25000, // Rp. 25,000 per square meter
            'cost' => 12000,  // Rp. 12,000 HPP cost per sq meter
            'profit' => 13000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        AttributePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $finishing->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        // Calculate quote for 3 banners of dimensions 2.0m x 1.5m
        $result = $this->pricingService->calculateQuote([
            'product_id' => $product->id,
            'material_id' => $material->id,
            'finishing_id' => $finishing->id,
            'quantity' => 3,
            'width' => 1.5,
            'length' => 2.0,
        ]);

        // Area = 1.5 * 2.0 = 3.0 sq meters
        $this->assertEquals(25000, $result['unit_price']);
        $this->assertEquals(3.0, $result['area']);
        $this->assertEquals(225000, $result['subtotal']); // 25000 * 3 * 3.0
        $this->assertEquals(108000, $result['cost']);     // 12000 * 3 * 3.0
        $this->assertEquals(117000, $result['profit']);
    }

    public function test_non_m2_selling_units_ignore_dimensions(): void
    {
        $category = ProductCategory::create([
            'name' => 'Large Format',
        ]);

        $product = Product::create([
            'name' => 'Large Format',
            'selling_unit' => 'lembar',
            'product_category_id' => $category->id,
        ]);

        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Flexy 340gr',
            'product_category_id' => $category->id,
            'type' => 'outdoor',
        ]);

        $finishing = Attribute::create([
            'branch_id' => $this->branch->id,
            'name' => 'Mata Ayam',
            'type' => 'finishing',
        ]);

        $pricing = Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'material_id' => $material->id,
            'price' => 25000,
            'cost' => 12000,
            'profit' => 13000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        AttributePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $finishing->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        $result = $this->pricingService->calculateQuote([
            'product_id' => $product->id,
            'material_id' => $material->id,
            'finishing_id' => $finishing->id,
            'quantity' => 3,
            'width' => 1.5,
            'length' => 2.0,
        ]);

        $this->assertSame(0.0, $result['area']);
        $this->assertEquals(25000, $result['unit_price']);
        $this->assertEquals(75000, $result['subtotal']); // 25000 * 3, no area factor
        $this->assertEquals(36000, $result['cost']);     // 12000 * 3, no area factor
    }

    /**
     * Test validation exception when geometric parameters are missing for surface_area archetype.
     */
    public function test_throws_exception_when_geometric_parameters_missing_for_surface_area(): void
    {
        $category = ProductCategory::create([
            'name' => 'Large Format',
        ]);

        $product = Product::create([
            'name' => 'Large Format Banner',
            'selling_unit' => 'm2',
            'product_category_id' => $category->id,
        ]);

        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Flexy 340gr',
            'product_category_id' => $category->id,
            'type' => 'outdoor',
        ]);

        Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'material_id' => $material->id,
            'price' => 25000,
            'cost' => 12000,
            'profit' => 13000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->pricingService->calculateQuote([
            'product_id' => $product->id,
            'material_id' => $material->id,
            'quantity' => 1,
            'width' => 0, // Missing geometric metric
            'length' => 2.0,
        ]);
    }

    /**
     * Test that an exception is thrown when pricing parameters do not match any matrix record.
     */
    public function test_throws_exception_when_pricing_matrix_not_found(): void
    {
        $category = ProductCategory::create([
            'name' => 'Merchandise',
        ]);

        $product = Product::create([
            'name' => 'Merchandise',
            'selling_unit' => 'pcs',
            'product_category_id' => $category->id,
        ]);

        $this->expectException(PricingNotFoundException::class);
        $this->expectExceptionMessage('Data harga untuk produk: Merchandise dengan parameter ini tidak ditemukan.');

        $this->pricingService->calculateQuote([
            'product_id' => $product->id,
            'material_id' => (string) Str::uuid(), // Non-existent material UUID
            'quantity' => 10,
        ]);
    }

    public function test_attribute_price_and_cost_are_the_fallback_without_a_modifier(): void
    {
        $pricing = $this->makeUnitPricing();
        $attribute = $this->makeAttribute('finishing', 5000, 2000);

        $result = $this->quoteFor($pricing, ['selected_attributes' => [$attribute->id]]);

        $this->assertEquals(55000, $result['unit_price']); // base 50000 + 5000
        $this->assertEquals(22000, $result['unit_cost']);  // base 20000 + 2000
    }

    public function test_cut_display_and_custom_types_use_the_same_fallback(): void
    {
        $pricing = $this->makeUnitPricing();

        foreach (['cut' => [1000, 400], 'display' => [2000, 500], 'custom' => [3000, 600]] as $type => [$price, $cost]) {
            $attribute = $this->makeAttribute($type, $price, $cost);

            $result = $this->quoteFor($pricing, ['selected_attributes' => [$attribute->id]]);

            $this->assertEquals(50000 + $price, $result['unit_price'], "unit_price for {$type}");
            $this->assertEquals(20000 + $cost, $result['unit_cost'], "unit_cost for {$type}");
        }
    }

    public function test_uppercase_selected_attribute_id_still_uses_the_fallback(): void
    {
        $pricing = $this->makeUnitPricing();
        $attribute = $this->makeAttribute('finishing', 5000, 2000);

        $result = $this->quoteFor($pricing, ['selected_attributes' => [strtoupper($attribute->id)]]);

        $this->assertEquals(55000, $result['unit_price']);
        $this->assertEquals(22000, $result['unit_cost']);
    }

    public function test_modifier_and_attribute_price_mix_in_one_selection(): void
    {
        $pricing = $this->makeUnitPricing();
        $withModifier = $this->makeAttribute('finishing', 5000, 2000);
        $withAttributePrice = $this->makeAttribute('display', 3000, 1000);

        AttributePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $withModifier->id,
            'price' => 2000,
            'cost' => 900,
            'profit' => 1100,
        ]);

        $result = $this->quoteFor($pricing, [
            'selected_attributes' => [$withModifier->id, $withAttributePrice->id],
        ]);

        $this->assertEquals(55000, $result['unit_price']); // base 50000 + modifier 2000 + attribute 3000
        $this->assertEquals(21900, $result['unit_cost']);  // base 20000 + modifier 900 + attribute 1000
    }

    public function test_modifier_row_wins_over_attribute_price(): void
    {
        $pricing = $this->makeUnitPricing();
        $attribute = $this->makeAttribute('finishing', 5000, 2000);

        AttributePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $attribute->id,
            'price' => 2000,
            'cost' => 900,
            'profit' => 1100,
        ]);

        $result = $this->quoteFor($pricing, ['selected_attributes' => [$attribute->id]]);

        $this->assertEquals(52000, $result['unit_price']); // base 50000 + modifier 2000
        $this->assertEquals(20900, $result['unit_cost']);  // base 20000 + modifier 900
    }

    public function test_zero_modifier_row_still_wins(): void
    {
        $pricing = $this->makeUnitPricing();
        $attribute = $this->makeAttribute('finishing', 5000, 2000);

        AttributePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $attribute->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        $result = $this->quoteFor($pricing, ['selected_attributes' => [$attribute->id]]);

        $this->assertEquals(50000, $result['unit_price']);
        $this->assertEquals(20000, $result['unit_cost']);
    }

    public function test_legacy_finishing_id_uses_the_attribute_price_fallback(): void
    {
        $pricing = $this->makeUnitPricing();
        $attribute = $this->makeAttribute('finishing', 3000, 1200);

        $result = $this->quoteFor($pricing, ['finishing_id' => $attribute->id]);

        $this->assertEquals(53000, $result['unit_price']);
        $this->assertEquals(21200, $result['unit_cost']);
    }

    public function test_legacy_cut_and_display_ids_still_work(): void
    {
        $pricing = $this->makeUnitPricing();
        $cut = $this->makeAttribute('cut', 1000, 400);
        $display = $this->makeAttribute('display', 2000, 500);

        $result = $this->quoteFor($pricing, [
            'cut_id' => $cut->id,
            'display_id' => $display->id,
        ]);

        $this->assertEquals(53000, $result['unit_price']);
        $this->assertEquals(20900, $result['unit_cost']);
    }

    public function test_selected_attributes_take_precedence_over_legacy_ids(): void
    {
        $pricing = $this->makeUnitPricing();
        $selected = $this->makeAttribute('display', 3000, 1000);
        $legacy = $this->makeAttribute('finishing', 9000, 4000);

        AttributePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $legacy->id,
            'price' => 1000,
            'cost' => 300,
            'profit' => 700,
        ]);

        $result = $this->quoteFor($pricing, [
            'finishing_id' => $legacy->id,
            'selected_attributes' => [$selected->id],
        ]);

        $this->assertEquals(53000, $result['unit_price']); // only the selected attribute counts
        $this->assertEquals(21000, $result['unit_cost']);
    }

    public function test_missing_selected_attribute_contributes_zero(): void
    {
        $pricing = $this->makeUnitPricing();

        $result = $this->quoteFor($pricing, ['selected_attributes' => [(string) Str::uuid()]]);

        $this->assertEquals(50000, $result['unit_price']);
        $this->assertEquals(20000, $result['unit_cost']);
    }

    public function test_no_material_pricing_uses_the_null_material_row(): void
    {
        $category = ProductCategory::create([
            'name' => 'No Material Pricing Fixture',
        ]);

        $product = Product::create([
            'name' => 'Jasa Desain',
            'selling_unit' => 'pcs',
            'product_category_id' => $category->id,
            'requires_material' => false,
        ]);

        Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'material_id' => null,
            'price' => 30000,
            'cost' => 12000,
            'profit' => 18000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        $params = [
            'product_id' => $product->id,
            'material_id' => null,
            'quantity' => 2,
            'branch_id' => $this->branch->id,
        ];

        $result = $this->pricingService->calculateQuote($params);

        $this->assertEquals(30000, $result['unit_price']);
        $this->assertEquals(60000, $result['subtotal']);
        $this->assertEquals(24000, $result['cost']);

        $fallback = $this->pricingService->calculateQuote([
            ...$params,
            'customer_type' => 'Reseller',
        ]);

        $this->assertEquals(30000, $fallback['unit_price']);
        $this->assertEquals(60000, $fallback['subtotal']);
        $this->assertEquals(24000, $fallback['cost']);
    }

    private function makeUnitPricing(float $price = 50000, float $cost = 20000): Pricing
    {
        $category = ProductCategory::create([
            'name' => 'Pricing Fixture',
        ]);

        $product = Product::create([
            'name' => 'Pricing Fixture',
            'selling_unit' => 'pcs',
            'product_category_id' => $category->id,
        ]);

        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Pricing Fixture Material',
            'product_category_id' => $category->id,
            'type' => 'Merchandise',
        ]);

        return Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'material_id' => $material->id,
            'price' => $price,
            'cost' => $cost,
            'profit' => $price - $cost,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);
    }

    private function makeAttribute(string $type, float $price, float $cost): Attribute
    {
        return Attribute::create([
            'branch_id' => $this->branch->id,
            'name' => ucfirst($type).' Fixture',
            'type' => $type,
            'price' => $price,
            'cost' => $cost,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function quoteFor(Pricing $pricing, array $overrides): array
    {
        return $this->pricingService->calculateQuote(array_merge([
            'product_id' => $pricing->product_id,
            'material_id' => $pricing->material_id,
            'quantity' => 1,
        ], $overrides));
    }
}
