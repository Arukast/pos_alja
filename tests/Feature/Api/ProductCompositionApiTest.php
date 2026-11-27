<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
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

    private User $manager;

    private AttributeType $type;

    private AttributeType $finishingType;

    private AttributeType $displayType;

    private Attribute $attribute;

    private Attribute $displayAttribute;

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
        $this->manager = $this->user('manager', $this->branch);

        $this->type = AttributeType::query()->where('slug', 'finishing')->firstOrFail();
        $this->finishingType = $this->type;
        $this->displayType = AttributeType::query()->where('slug', 'display')->firstOrFail();
        $this->attribute = Attribute::create([
            'name' => 'Glossy',
            'type' => 'finishing',
            'is_active' => true,
        ]);
        $this->displayAttribute = Attribute::create([
            'name' => 'X-Banner Stand',
            'type' => 'display',
            'is_active' => true,
        ]);

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

    public function test_owner_creates_product_with_flags_and_composition(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Banner Tanpa Bahan',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'requires_material' => false,
                'uses_attributes' => true,
                'material_ids' => [],
                'attribute_type_ids' => [$this->type->id],
                'attribute_ids' => [$this->attribute->id],
            ])
            ->assertCreated()
            ->assertJsonPath('product.requires_material', false)
            ->assertJsonPath('product.attribute_type_ids.0', $this->type->id)
            ->assertJsonPath('product.attribute_ids.0', $this->attribute->id);

        $log = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', Product::class)
            ->where('model_id', $response->json('product.id'))
            ->firstOrFail();

        $this->assertFalse((bool) $log->payload['requires_material']);
        $this->assertTrue((bool) $log->payload['uses_attributes']);
        $this->assertSame([], $log->payload['material_ids']);
        $this->assertSame([$this->type->id], $log->payload['attribute_type_ids']);
        $this->assertSame([$this->attribute->id], $log->payload['attribute_ids']);
    }

    public function test_update_audits_before_and_after_composition(): void
    {
        $this->product->materials()->sync([$this->material->id]);
        $this->product->attributeTypes()->sync([$this->type->id]);
        $this->product->compatibleAttributes()->sync([$this->attribute->id]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => $this->product->name,
                'product_category_id' => $this->category->id,
                'pricing_type' => $this->product->pricing_type,
                'requires_material' => false,
                'uses_attributes' => false,
                'material_ids' => [],
                'attribute_type_ids' => [$this->displayType->id],
                'attribute_ids' => [$this->displayAttribute->id],
            ])
            ->assertOk();

        $log = ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', Product::class)
            ->where('model_id', $this->product->id)
            ->latest()
            ->firstOrFail();

        $payload = $log->payload;

        foreach (['before', 'after'] as $side) {
            $this->assertArrayHasKey('requires_material', $payload[$side]);
            $this->assertArrayHasKey('uses_attributes', $payload[$side]);
            $this->assertArrayHasKey('material_ids', $payload[$side]);
            $this->assertArrayHasKey('attribute_type_ids', $payload[$side]);
            $this->assertArrayHasKey('attribute_ids', $payload[$side]);
        }

        $this->assertTrue((bool) $payload['before']['requires_material']);
        $this->assertSame([$this->material->id], $payload['before']['material_ids']);
        $this->assertSame([$this->type->id], $payload['before']['attribute_type_ids']);
        $this->assertSame([$this->attribute->id], $payload['before']['attribute_ids']);
        $this->assertFalse((bool) $payload['after']['requires_material']);
        $this->assertFalse((bool) $payload['after']['uses_attributes']);
        $this->assertSame([], $payload['after']['material_ids']);
        $this->assertSame([$this->displayType->id], $payload['after']['attribute_type_ids']);
        $this->assertSame([$this->displayAttribute->id], $payload['after']['attribute_ids']);
    }

    public function test_new_attribute_outside_allowed_types_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => $this->product->name,
                'product_category_id' => $this->category->id,
                'pricing_type' => $this->product->pricing_type,
                'attribute_type_ids' => [$this->finishingType->id],
                'attribute_ids' => [$this->displayAttribute->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('attribute_ids');
    }

    public function test_existing_inactive_membership_is_kept_on_update(): void
    {
        $this->product->attributeTypes()->sync([$this->type->id]);
        $this->product->compatibleAttributes()->sync([$this->attribute->id]);

        $this->type->update(['is_active' => false]);
        $this->attribute->update(['is_active' => false]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => $this->product->name,
                'product_category_id' => $this->category->id,
                'pricing_type' => $this->product->pricing_type,
                'attribute_type_ids' => [$this->type->id],
                'attribute_ids' => [$this->attribute->id],
            ])
            ->assertOk()
            ->assertJsonPath('product.requires_material', true)
            ->assertJsonPath('product.uses_attributes', true);

        $this->assertDatabaseHas('attribute_type_product', [
            'product_id' => $this->product->id,
            'attribute_type_id' => $this->type->id,
        ]);
        $this->assertDatabaseHas('attribute_product', [
            'product_id' => $this->product->id,
            'attribute_id' => $this->attribute->id,
        ]);
    }

    public function test_new_inactive_type_is_rejected_on_update(): void
    {
        $inactiveType = AttributeType::create([
            'name' => 'Stempel',
            'slug' => 'stempel',
            'is_active' => false,
        ]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => $this->product->name,
                'product_category_id' => $this->category->id,
                'pricing_type' => $this->product->pricing_type,
                'attribute_type_ids' => [$inactiveType->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('attribute_type_ids');
    }

    public function test_uses_attributes_false_keeps_pivots(): void
    {
        $this->product->attributeTypes()->sync([$this->type->id]);
        $this->product->compatibleAttributes()->sync([$this->attribute->id]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => $this->product->name,
                'product_category_id' => $this->category->id,
                'pricing_type' => $this->product->pricing_type,
                'uses_attributes' => false,
            ])
            ->assertOk()
            ->assertJsonPath('product.uses_attributes', false)
            ->assertJsonPath('product.attribute_type_ids.0', $this->type->id)
            ->assertJsonPath('product.attribute_ids.0', $this->attribute->id);

        $this->assertDatabaseHas('attribute_type_product', [
            'product_id' => $this->product->id,
            'attribute_type_id' => $this->type->id,
        ]);
        $this->assertDatabaseHas('attribute_product', [
            'product_id' => $this->product->id,
            'attribute_id' => $this->attribute->id,
        ]);
    }

    public function test_manager_cannot_write_product_composition(): void
    {
        $payload = [
            'name' => 'Manager Product',
            'product_category_id' => $this->category->id,
            'pricing_type' => 'unit',
            'attribute_type_ids' => [$this->type->id],
            'attribute_ids' => [$this->attribute->id],
        ];

        $this->actingAs($this->manager)
            ->postJson('/api/v1/products', $payload)
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->putJson("/api/v1/products/{$this->product->id}", $payload)
            ->assertForbidden();
    }

    public function test_restore_returns_composition(): void
    {
        $this->product->attributeTypes()->sync([$this->type->id]);
        $this->product->compatibleAttributes()->sync([$this->attribute->id]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/products/{$this->product->id}")
            ->assertNoContent();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/products/{$this->product->id}/restore")
            ->assertOk()
            ->assertJsonPath('product.attribute_type_ids.0', $this->type->id)
            ->assertJsonPath('product.attribute_ids.0', $this->attribute->id)
            ->assertJsonPath('product.compatible_attributes.0.type_name', 'Finishing');
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
