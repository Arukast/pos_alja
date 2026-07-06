<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributePricing;
use App\Models\AttributeType;
use App\Models\Branch;
use App\Models\Material;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttributeTypeEngineTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private ProductCategory $category;

    private Product $product;

    private $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'name' => 'Branch A',
            'code' => 'BRA',
        ]);

        $this->category = ProductCategory::create([
            'id' => (string) Str::uuid(),
            'name' => 'Custom Category',
            'calculation_archetype' => 'per_piece',
        ]);

        $this->product = Product::create([
            'id' => (string) Str::uuid(),
            'product_category_id' => $this->category->id,
            'name' => 'Test Product',
            'pricing_type' => 'unit',
        ]);

        // Create a material manually because we need category reference
        $this->material = Material::create([
            'id' => (string) Str::uuid(),
            'product_category_id' => $this->category->id,
            'name' => 'Standard Material',
            'cost' => 1000,
            'price' => 2000,
        ]);
    }

    public function test_pricing_evaluates_custom_attribute_types_dynamically(): void
    {
        // 1. Create a dynamic AttributeType
        $customType = AttributeType::create([
            'id' => (string) Str::uuid(),
            'name' => 'emboss',
            'calculation_archetype' => 'per_piece',
        ]);

        // 2. Create Attribute of this type
        $attribute = Attribute::create([
            'id' => (string) Str::uuid(),
            'branch_id' => $this->branch->id,
            'name' => 'Gold Emboss',
            'type' => 'emboss',
        ]);

        // 3. Create Pricing
        $pricing = Pricing::create([
            'id' => (string) Str::uuid(),
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'customer_type' => 'Reguler',
            'min_qty' => 1,
            'price' => 5000,
            'cost' => 3000,
        ]);

        // 4. Create Attribute Pricing Modifier
        AttributePricing::create([
            'id' => (string) Str::uuid(),
            'branch_id' => $this->branch->id,
            'pricing_id' => $pricing->id,
            'attribute_id' => $attribute->id,
            'price' => 1500,
            'cost' => 500,
        ]);

        // 5. Invoke PricingService
        $service = new PricingService;
        $result = $service->calculateQuote([
            'product_id' => $this->product->id,
            'material_id' => $this->material->id,
            'selected_attributes' => [$attribute->id],
            'quantity' => 2,
            'branch_id' => $this->branch->id,
        ]);

        // Unit Price: Base (5000) + Emboss (1500) = 6500
        // Unit Cost: Base (3000) + Emboss (500) = 3500
        // Subtotal: 6500 * 2 = 13000
        // Cost: 3500 * 2 = 7000
        $this->assertEquals(6500.0, $result['unit_price']);
        $this->assertEquals(3500.0, $result['unit_cost']);
        $this->assertEquals(13000.0, $result['subtotal']);
        $this->assertEquals(7000.0, $result['cost']);
    }
}
