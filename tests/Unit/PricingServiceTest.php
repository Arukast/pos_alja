<?php

namespace Tests\Unit;

use App\Exceptions\PricingNotFoundException;
use App\Models\Attribute;
use App\Models\AttributePricing;
use App\Models\Branch;
use App\Models\Machine;
use App\Models\MachinePricing;
use App\Models\Material;
use App\Models\Pricing;
use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
        // 1. Seed product, material, machine, finishing using models
        $product = Product::create([
            'name' => 'Merchandise',
            'pricing_type' => 'unit',
        ]);

        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Mug Putih',
            'category' => 'pcs',
            'type' => 'Merchandise',
        ]);

        $machine = Machine::create([
            'branch_id' => $this->branch->id,
            'name' => 'Mug Press Machine',
        ]);

        $finishing = Attribute::create([
            'branch_id' => $this->branch->id,
            'name' => 'No Finishing',
            'type' => 'finishing',
        ]);

        // 2. Seed pricing
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

        MachinePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'machine_id' => $machine->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        AttributePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $finishing->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        // 3. Test dynamic calculation
        $result = $this->pricingService->calculateQuote([
            'product_id' => $product->id,
            'material_id' => $material->id,
            'machine_id' => $machine->id,
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
        $product = Product::create([
            'name' => 'Large Format',
            'pricing_type' => 'area',
        ]);

        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Flexy 340gr',
            'category' => 'meter',
            'type' => 'outdoor',
        ]);

        $machine = Machine::create([
            'branch_id' => $this->branch->id,
            'name' => 'Flora Outdoor',
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

        MachinePricing::create([
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'machine_id' => $machine->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
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
            'machine_id' => $machine->id,
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

    /**
     * Test that an exception is thrown when pricing parameters do not match any matrix record.
     */
    public function test_throws_exception_when_pricing_matrix_not_found(): void
    {
        $product = Product::create([
            'name' => 'Merchandise',
            'pricing_type' => 'unit',
        ]);

        $this->expectException(PricingNotFoundException::class);

        $this->pricingService->calculateQuote([
            'product_id' => $product->id,
            'material_id' => (string) Str::uuid(), // Non-existent material UUID
            'quantity' => 10,
        ]);
    }
}
