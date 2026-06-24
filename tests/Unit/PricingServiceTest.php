<?php

namespace Tests\Unit;

use App\Exceptions\PricingMatrixNotFoundException;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new PricingService();

        // Setup base branch
        DB::table('branches')->insert([
            'id' => 1,
            'name' => 'Test Branch',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test calculation for standard products (e.g. Merchandise, subtotal = price * qty).
     */
    public function test_merchandise_pricing_calculation(): void
    {
        // 1. Seed product, material, machine, finishing using DB::table to force exact IDs
        DB::table('products')->insert(['id' => 1, 'name' => 'Merchandise', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('materials')->insert([
            'id' => 10,
            'name' => 'Mug Putih',
            'category' => 'pcs',
            'type' => 'Merchandise',
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('machines')->insert([
            'id' => 20,
            'name' => 'Mug Press Machine',
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('finishings')->insert([
            'id' => 30,
            'name' => 'No Finishing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Seed pricing matrix
        DB::table('pricing_matrices')->insert([
            'id' => 100,
            'branch_id' => 1,
            'product_id' => 1,
            'material_id' => 10,
            'machine_id' => 20,
            'finishing_id' => 30,
            'cut_id' => 0,
            'display_id' => 0,
            'sides' => 1,
            'finishing_sides' => 0,
            'price' => 15000,
            'cost' => 8000,
            'profit' => 7000,
            'min_qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Test dynamic calculation
        $result = $this->pricingService->calculateQuote([
            'product_id' => 1,
            'material_id' => 10,
            'machine_id' => 20,
            'finishing_id' => 30,
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
        // Force Product ID to 5 (Large Format)
        DB::table('products')->insert(['id' => 5, 'name' => 'Large Format', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('materials')->insert([
            'id' => 11,
            'name' => 'Flexy 340gr',
            'category' => 'meter',
            'type' => 'outdoor',
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('machines')->insert([
            'id' => 21,
            'name' => 'Flora Outdoor',
            'branch_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('finishings')->insert([
            'id' => 31,
            'name' => 'Mata Ayam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pricing_matrices')->insert([
            'id' => 101,
            'branch_id' => 1,
            'product_id' => 5,
            'material_id' => 11,
            'machine_id' => 21,
            'finishing_id' => 31,
            'cut_id' => 0,
            'display_id' => 0,
            'sides' => 1,
            'finishing_sides' => 0,
            'price' => 25000, // Rp. 25,000 per square meter
            'cost' => 12000,  // Rp. 12,000 HPP cost per sq meter
            'profit' => 13000,
            'min_qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Calculate quote for 3 banners of dimensions 2.0m x 1.5m
        $result = $this->pricingService->calculateQuote([
            'product_id' => 5,
            'material_id' => 11,
            'machine_id' => 21,
            'finishing_id' => 31,
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
        $this->expectException(PricingMatrixNotFoundException::class);

        $this->pricingService->calculateQuote([
            'product_id' => 999, // Non-existent product category
            'material_id' => 1,
            'quantity' => 10,
        ]);
    }
}
