<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductQuantityDiscount;
use App\Services\QuotePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class QuotePricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuotePricingService $service;

    private Product $product;

    private Material $material;

    private Attribute $attributeA;

    private Attribute $attributeB;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new QuotePricingService;

        $category = ProductCategory::create(['name' => 'Pricing Fixture']);

        $this->product = Product::create([
            'name' => 'Pricing Fixture',
            'selling_unit' => 'pcs',
            'product_category_id' => $category->id,
            'is_active' => true,
        ]);

        $this->material = Material::create([
            'name' => 'Pricing Fixture Material',
            'product_category_id' => $category->id,
            'price' => 100000,
            'cost' => 60000,
            'is_active' => true,
        ]);

        $this->attributeA = Attribute::create([
            'branch_id' => null,
            'name' => 'Finishing A',
            'type' => 'finishing',
            'price' => 20000,
            'cost' => 10000,
            'is_active' => true,
        ]);

        $this->attributeB = Attribute::create([
            'branch_id' => null,
            'name' => 'Cut B',
            'type' => 'cut',
            'price' => 5000,
            'cost' => 2000,
            'is_active' => true,
        ]);

        $this->branch = Branch::create([
            'name' => 'Reseller Branch',
            'reseller_discount_percent' => 10,
        ]);

        ProductQuantityDiscount::create([
            'product_id' => $this->product->id,
            'min_qty' => 10,
            'discount_percent' => 5,
        ]);

        ProductQuantityDiscount::create([
            'product_id' => $this->product->id,
            'min_qty' => 50,
            'discount_percent' => 12,
        ]);
    }

    public function test_base_price_is_material_plus_every_selected_active_attribute(): void
    {
        $this->product->update(['selling_unit' => 'm2']);

        $result = $this->quote(['width' => 2, 'length' => 3]);

        $this->assertSame('125000.00', $result['unit_price']);
        $this->assertSame('72000.00', $result['unit_cost']);
    }

    public function test_reseller_discount_applies_to_base_before_area(): void
    {
        $result = $this->quote(['customer_type' => 'Reseller']);

        $this->assertSame('112500.00', $result['unit_price']);
        $this->assertSame('10.00', $result['reseller_discount_percent']);
    }

    public function test_reseller_without_a_branch_gets_no_discount(): void
    {
        $result = $this->quote(['customer_type' => 'Reseller', 'branch_id' => null]);

        $this->assertSame('0.00', $result['reseller_discount_percent']);
        $this->assertSame('125000.00', $result['unit_price']);
    }

    public function test_highest_eligible_tier_wins_with_inclusive_boundary(): void
    {
        $this->assertSame('5.00', $this->quote(['quantity' => 10])['quantity_discount_percent']);
        $this->assertSame('12.00', $this->quote(['quantity' => 50])['quantity_discount_percent']);
        $this->assertSame('0.00', $this->quote(['quantity' => 9])['quantity_discount_percent']);
    }

    public function test_reseller_discount_applies_before_tier_discount(): void
    {
        $result = $this->quote(['customer_type' => 'Reseller', 'quantity' => 10]);

        $this->assertSame('1068750.00', $result['subtotal']);
        $this->assertSame('181250.00', $result['discount_amount']);
    }

    public function test_area_factor_applies_only_when_selling_unit_is_m2(): void
    {
        $pcsProduct = Product::create([
            'name' => 'Piece Fixture',
            'selling_unit' => 'pcs',
            'product_category_id' => $this->material->product_category_id,
            'is_active' => true,
        ]);

        $this->product->update(['selling_unit' => 'm2']);

        $result = $this->quote(['width' => 2, 'length' => 3]);

        $this->assertSame(6.0, $result['area']);
        $this->assertSame('750000.00', $result['subtotal']);
        $this->assertSame(0.0, $this->quote(['product_id' => $pcsProduct->id])['area']);
    }

    public function test_cost_and_profit_follow_the_chain_without_discounts(): void
    {
        $result = $this->quote(['customer_type' => 'Reseller', 'quantity' => 10]);

        $this->assertSame('720000.00', $result['cost']);
        $this->assertSame('348750.00', $result['profit']);
    }

    public function test_discount_amount_is_undiscounted_minus_final_subtotal(): void
    {
        $this->product->update(['selling_unit' => 'm2']);

        $result = $this->quote([
            'customer_type' => 'Reseller',
            'quantity' => 50,
            'width' => 2,
            'length' => 3,
        ]);

        $this->assertSame('29700000.00', $result['subtotal']);
        $this->assertSame('7800000.00', $result['discount_amount']);
    }

    public function test_result_has_no_legacy_identity_keys(): void
    {
        $result = $this->quote();

        $this->assertArrayNotHasKey('pricing_id', $result);
        $this->assertArrayNotHasKey('finishing_id', $result);
        $this->assertArrayNotHasKey('cut_id', $result);
        $this->assertArrayNotHasKey('display_id', $result);
        $this->assertArrayNotHasKey('gross_subtotal', $result);
    }

    public function test_zero_or_negative_quantity_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->quote(['quantity' => 0]);
    }

    public function test_tier_percent_above_one_hundred_is_rejected(): void
    {
        ProductQuantityDiscount::create([
            'product_id' => $this->product->id,
            'min_qty' => 100,
            'discount_percent' => 101,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->quote(['quantity' => 100]);
    }

    public function test_missing_dimensions_for_m2_are_rejected(): void
    {
        $this->product->update(['selling_unit' => 'm2']);

        $this->expectException(InvalidArgumentException::class);

        $this->quote();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function quote(array $overrides = []): array
    {
        return $this->service->calculate(array_merge([
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'selected_attributes' => [$this->attributeA->id, $this->attributeB->id],
            'quantity' => 1,
            'width' => 0,
            'length' => 0,
            'customer_type' => 'Reguler',
            'branch_id' => $this->branch->id,
        ], $overrides));
    }
}
