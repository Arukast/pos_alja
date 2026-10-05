<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Dashboards\Manager;
use App\Models\Branch;
use App\Models\Material;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SimplifiedPricingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $manager;

    private Branch $branch;

    private ProductCategory $category;

    private Product $product;

    private Material $materialA;

    private Material $materialB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Simplified Branch',
            'code' => 'SMP',
            'is_active' => true,
            'settings' => [
                'tax_rate' => 10.0,
                'reseller_discount_percent' => 15.0, // 15% discount for resellers
            ],
        ]);

        $this->owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'username' => 'owner',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'branch_id' => $this->branch->id,
        ]);

        $this->manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'username' => 'manager',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);

        $this->category = ProductCategory::create([
            'name' => 'Merchandise',
            'calculation_archetype' => 'per_piece',
        ]);

        $this->product = Product::create([
            'product_category_id' => $this->category->id,
            'name' => 'Custom Mug',
            'is_active' => true,
        ]);

        $this->materialA = Material::create([
            'name' => 'Standard Mug White',
            'product_category_id' => $this->category->id,
            'cost' => 5000.00,
            'price' => 10000.00,
        ]);

        $this->materialB = Material::create([
            'name' => 'Premium Mug Black',
            'product_category_id' => $this->category->id,
            'cost' => 6000.00,
            'price' => 12000.00,
        ]);
    }

    /**
     * Reseller pricing falls back to Reguler price minus the branch reseller discount.
     */
    public function test_reseller_pricing_falls_back_with_branch_discount(): void
    {
        // Define only Reguler pricing in matrix
        Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'material_id' => $this->materialA->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 10000.00,
            'cost' => 0.0,
            'profit' => 10000.00,
        ]);

        $pricingService = new PricingService;

        // Query quote for a Reseller customer
        $quote = $pricingService->calculateQuote([
            'product_id' => $this->product->id,
            'material_id' => $this->materialA->id,
            'customer_type' => 'Reseller',
            'quantity' => 1,
            'branch_id' => $this->branch->id,
        ]);

        // Expect: 15% discount off Rp 10.000 = Rp 8.500
        $this->assertEquals(8500.00, $quote['unit_price']);

        // HPP/cost is retrieved directly from materialA->cost (Rp 5.000)
        $this->assertEquals(5000.00, $quote['unit_cost']);
    }

    /**
     * Explicit Reseller rules in matrix override the branch fallback discount.
     */
    public function test_explicit_reseller_rules_override_fallback(): void
    {
        // Define Reguler pricing
        Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'material_id' => $this->materialA->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 10000.00,
            'cost' => 0.0,
            'profit' => 10000.00,
        ]);

        // Explicit Reseller pricing (Rp 9.000 instead of fallback Rp 8.500)
        Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'material_id' => $this->materialA->id,
            'customer_type' => 'Reseller',
            'min_qty' => 1,
            'price' => 9000.00,
            'cost' => 0.0,
            'profit' => 9000.00,
        ]);

        $pricingService = new PricingService;

        $quote = $pricingService->calculateQuote([
            'product_id' => $this->product->id,
            'material_id' => $this->materialA->id,
            'customer_type' => 'Reseller',
            'quantity' => 1,
            'branch_id' => $this->branch->id,
        ]);

        $this->assertEquals(9000.00, $quote['unit_price']);
        $this->assertEquals(5000.00, $quote['unit_cost']);
    }

    /**
     * Managers can bulk clone pricing matrix tiers with custom adjustment rates.
     */
    public function test_manager_can_bulk_clone_pricing_tiers(): void
    {
        // Define source pricing tiers for Material A
        Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'material_id' => $this->materialA->id,
            'customer_type' => 'Reguler',
            'min_qty' => 10,
            'price' => 9000.00,
            'cost' => 0.0,
            'profit' => 9000.00,
        ]);

        Pricing::create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'material_id' => $this->materialA->id,
            'customer_type' => 'Reguler',
            'min_qty' => 100,
            'price' => 8000.00,
            'cost' => 0.0,
            'profit' => 8000.00,
        ]);

        $this->actingAs($this->manager);

        // Perform cloning with +10% price markup
        Livewire::test(Manager::class)
            ->set('activeTab', 'pricing')
            ->set('cloneSourceMaterialId', $this->materialA->id)
            ->set('cloneSourceCustomerType', 'Reguler')
            ->set('cloneTargetMaterialId', $this->materialB->id)
            ->set('cloneTargetCustomerType', 'Reseller')
            ->set('cloneAdjustmentPercent', 10.0)
            ->call('clonePricingTiers')
            ->assertHasNoErrors();

        // Verify Target Tiers created successfully
        $this->assertDatabaseHas('pricing', [
            'material_id' => $this->materialB->id,
            'customer_type' => 'Reseller',
            'min_qty' => 10,
            'price' => 9900.00, // Rp 9.000 + 10%
            'cost' => 6000.00,  // Target material B cost is 6000
        ]);

        $this->assertDatabaseHas('pricing', [
            'material_id' => $this->materialB->id,
            'customer_type' => 'Reseller',
            'min_qty' => 100,
            'price' => 8800.00, // Rp 8.000 + 10%
            'cost' => 6000.00,
        ]);
    }
}
