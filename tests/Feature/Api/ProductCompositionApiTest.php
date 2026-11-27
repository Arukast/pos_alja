<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Branch;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductCompositionApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $owner;

    private User $designer;

    private ProductCategory $category;

    private Product $product;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'name' => 'Branch One',
            'code' => Str::upper(Str::random(6)),
        ]);

        $this->owner = $this->user('owner', null);
        $this->designer = $this->user('designer', $this->branch);

        $this->category = ProductCategory::create([
            'name' => 'Large Format',
        ]);

        $this->product = Product::create([
            'name' => 'Vinyl Banner',
            'pricing_type' => 'area',
            'product_category_id' => $this->category->id,
        ]);

        $this->material = Material::create([
            'name' => 'Vinyl Roll',
            'product_category_id' => $this->category->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
        ]);
    }

    public function test_product_composition_schema_and_defaults(): void
    {
        $product = Product::create([
            'name' => 'Banner Tanpa Bahan',
            'pricing_type' => 'unit',
            'product_category_id' => $this->category->id,
        ]);

        $this->assertTrue($product->fresh()->requires_material);
        $this->assertTrue($product->fresh()->uses_attributes);
        $this->assertDatabaseCount('attribute_type_product', 0);
        $this->assertDatabaseCount('attribute_product', 0);
    }

    public function test_product_composition_relations_use_explicit_pivot_tables(): void
    {
        $this->assertSame('attribute_type_product', $this->product->attributeTypes()->getTable());
        $this->assertSame('attribute_product', $this->product->compatibleAttributes()->getTable());
        $this->assertFalse(method_exists($this->product, 'attributes'));
    }

    public function test_product_resource_serializes_flags_ids_and_name_arrays(): void
    {
        $type = AttributeType::query()->where('slug', 'finishing')->firstOrFail();
        $attribute = Attribute::create([
            'name' => 'Glossy',
            'type' => 'finishing',
            'is_active' => true,
        ]);

        $this->product->attributeTypes()->sync([$type->id]);
        $this->product->compatibleAttributes()->sync([$attribute->id]);
        $this->product->materials()->sync([$this->material->id]);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.requires_material', true)
            ->assertJsonPath('data.0.uses_attributes', true)
            ->assertJsonPath('data.0.attribute_type_ids.0', $type->id)
            ->assertJsonPath('data.0.attribute_types.0.name', 'Finishing')
            ->assertJsonPath('data.0.attribute_ids.0', $attribute->id)
            ->assertJsonPath('data.0.compatible_attributes.0.name', 'Glossy')
            ->assertJsonPath('data.0.compatible_attributes.0.type_name', 'Finishing');
    }

    public function test_quote_options_include_product_composition(): void
    {
        $type = AttributeType::query()->where('slug', 'finishing')->firstOrFail();
        $attribute = Attribute::create([
            'name' => 'Glossy',
            'type' => 'finishing',
            'is_active' => true,
        ]);

        $this->product->attributeTypes()->sync([$type->id]);
        $this->product->compatibleAttributes()->sync([$attribute->id]);

        $this->actingAs($this->designer)
            ->getJson('/api/v1/quotes/options')
            ->assertOk()
            ->assertJsonPath('products.0.requires_material', true)
            ->assertJsonPath('products.0.uses_attributes', true)
            ->assertJsonPath('products.0.attribute_type_ids.0', $type->id)
            ->assertJsonPath('products.0.attribute_ids.0', $attribute->id)
            ->assertJsonPath('products.0.compatible_attributes.0.type_name', 'Finishing');
    }

    public function test_loaded_empty_pivots_serialize_as_empty_arrays(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.attribute_type_ids', [])
            ->assertJsonPath('data.0.attribute_types', [])
            ->assertJsonPath('data.0.attribute_ids', [])
            ->assertJsonPath('data.0.compatible_attributes', []);
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
