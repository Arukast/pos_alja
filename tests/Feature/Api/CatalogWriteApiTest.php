<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLedger;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogWriteApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private User $cashier;

    private ProductCategory $category;

    private Product $product;

    private Material $material;

    private Material $otherMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);
        $this->cashier = $this->user('cashier', $this->branchOne);

        $this->category = ProductCategory::create([
            'name' => 'Large Format',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['finishing'],
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

        $this->otherMaterial = Material::create([
            'name' => 'Paper Roll',
            'product_category_id' => $this->category->id,
            'type' => 'roll',
            'cost' => 500,
            'price' => 1000,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 5,
            'min_stock' => 1,
        ]);

        MaterialBranchStock::create([
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 7,
            'min_stock' => 1,
        ]);
    }

    public function test_owner_creates_material_and_initializes_all_branch_stock(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/v1/materials', [
                'name' => 'New Vinyl',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'roll',
                'cost' => 1000,
                'price' => 2000,
                'initial_stock' => 10,
                'min_stock' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('material.is_active', true);

        $this->assertDatabaseCount('material_branch_stock', 4);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $response->json('material.id'),
            'branch_id' => $this->branchOne->id,
            'current_stock' => 10,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $response->json('material.id'),
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 10,
        ]);

        $log = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', Material::class)
            ->where('model_id', $response->json('material.id'))
            ->firstOrFail();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame('Membuat bahan: New Vinyl', $log->description);
        $this->assertSame([
            'name' => 'New Vinyl',
            'product_category_id' => $this->category->id,
            'type' => 'roll',
            'dimensions' => null,
            'unit' => 'roll',
            'cost' => '1000.00',
            'price' => '2000.00',
        ], $log->payload['after']);
    }

    public function test_material_create_requires_a_unit_from_the_fixed_set(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/materials', [
                'name' => 'Unitless',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/materials', [
                'name' => 'Unknown Unit',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'sheet',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/materials', [
                'name' => 'Measured Vinyl',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'm2',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('material.unit', 'm2');

        $this->assertDatabaseHas('materials', [
            'name' => 'Measured Vinyl',
            'unit' => 'm2',
        ]);
    }

    public function test_material_update_accepts_an_optional_unit_and_rejects_unknown_units(): void
    {
        $this->actingAs($this->owner)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Vinyl Roll',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertOk()
            ->assertJsonPath('material.unit', 'pcs');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Vinyl Roll',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'kg',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertOk()
            ->assertJsonPath('material.unit', 'kg');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Vinyl Roll',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'sheet',
                'cost' => 1000,
                'price' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('unit');

        $this->assertDatabaseHas('materials', [
            'id' => $this->material->id,
            'unit' => 'kg',
        ]);

        $logs = ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', Material::class)
            ->where('model_id', $this->material->id)
            ->get();

        $this->assertCount(2, $logs);

        $kilograms = $logs->first(
            fn (ActivityLog $log): bool => $log->payload['after']['unit'] === 'kg'
        );

        $this->assertNotNull($kilograms);
        $this->assertSame('pcs', $kilograms->payload['before']['unit']);
        $this->assertSame('kg', $kilograms->payload['after']['unit']);
        $this->assertSame('1000.00', $kilograms->payload['before']['cost']);
        $this->assertSame('2000.00', $kilograms->payload['after']['price']);
    }

    public function test_manager_creates_material_only_for_own_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/materials', [
                'name' => 'Manager Vinyl',
                'product_category_id' => $this->category->id,
                'type' => 'roll',
                'unit' => 'roll',
                'cost' => 1000,
                'price' => 2000,
                'initial_stock' => 4,
                'min_stock' => 1,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $response->json('material.id'),
            'branch_id' => $this->branchOne->id,
        ]);
        $this->assertDatabaseMissing('material_branch_stock', [
            'material_id' => $response->json('material.id'),
            'branch_id' => $this->branchTwo->id,
        ]);

        $this->actingAs($this->cashier)
            ->postJson('/api/v1/materials', [
                'name' => 'Nope',
                'product_category_id' => $this->category->id,
                'unit' => 'pcs',
            ])
            ->assertForbidden();
    }

    public function test_similar_material_lookup_returns_matches(): void
    {
        $this->actingAs($this->manager)
            ->getJson('/api/v1/materials/similar?name=Vinyl')
            ->assertOk()
            ->assertJsonFragment(['id' => $this->material->id]);
    }

    public function test_restock_is_forced_to_the_manager_branch(): void
    {
        $this->actingAs($this->manager)
            ->postJson("/api/v1/materials/{$this->material->id}/stock", [
                'quantity' => 3,
                'notes' => 'Restock',
                'branch_id' => $this->branchTwo->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 8,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchTwo->id,
            'current_stock' => 7,
        ]);
        $this->assertDatabaseHas('stock_ledgers', [
            'material_id' => $this->material->id,
            'added_stock' => 3,
        ]);

        $ledger = StockLedger::query()
            ->where('material_id', $this->material->id)
            ->where('branch_id', $this->branchOne->id)
            ->where('type', 'restock')
            ->firstOrFail();

        $log = ActivityLog::query()
            ->where('action', 'STOCK')
            ->where('model_type', Material::class)
            ->where('model_id', $this->material->id)
            ->firstOrFail();

        $this->assertSame($this->manager->id, $log->user_id);
        $this->assertSame($this->branchOne->id, $log->branch_id);
        $this->assertSame(3.0, (float) $log->payload['quantity']);
        $this->assertSame(5.0, (float) $log->payload['stock_before']);
        $this->assertSame(8.0, (float) $log->payload['stock_after']);
        $this->assertSame(3.0, (float) $log->payload['delta']);
        $this->assertSame('Restock', $log->payload['notes']);
        $this->assertSame($this->branchOne->id, $log->payload['branch_id']);
        $this->assertSame($ledger->id, $log->payload['ledger_id']);
    }

    public function test_global_material_delete_is_owner_only(): void
    {
        $this->actingAs($this->manager)
            ->deleteJson("/api/v1/materials/{$this->material->id}")
            ->assertForbidden();

        $this->assertDatabaseCount('activity_logs', 0);

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/materials/{$this->material->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('materials', ['id' => $this->material->id]);

        $log = ActivityLog::query()
            ->where('action', 'DELETE')
            ->where('model_type', Material::class)
            ->where('model_id', $this->material->id)
            ->firstOrFail();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame('Menghapus bahan: Vinyl Roll', $log->description);
        $this->assertSame('Vinyl Roll', $log->payload['before']['name']);
        $this->assertSame('roll', $log->payload['before']['type']);
        $this->assertSame('1000.00', $log->payload['before']['cost']);
        $this->assertSame('2000.00', $log->payload['before']['price']);
        $this->assertNull($log->payload['after']);
    }

    public function test_manager_cannot_update_global_material_master(): void
    {
        $this->actingAs($this->manager)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Manager Rename',
                'product_category_id' => $this->category->id,
                'cost' => 1,
                'price' => 2,
            ])
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->putJson("/api/v1/materials/{$this->material->id}", [
                'name' => 'Owner Rename',
                'product_category_id' => $this->category->id,
                'cost' => 1200,
                'price' => 2400,
            ])
            ->assertOk()
            ->assertJsonPath('material.cost', '1200.00')
            ->assertJsonPath('material.price', '2400.00');
    }

    public function test_material_restore_keeps_branch_stock(): void
    {
        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/materials/{$this->material->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('materials', ['id' => $this->material->id]);
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 5,
        ]);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/materials/{$this->material->id}/restore")
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->material->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 5,
        ]);

        $log = ActivityLog::query()
            ->where('action', 'RESTORE')
            ->where('model_type', Material::class)
            ->where('model_id', $this->material->id)
            ->firstOrFail();

        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame('Memulihkan bahan: Vinyl Roll', $log->description);
        $this->assertSame('Vinyl Roll', $log->payload['after']['name']);
        $this->assertSame('1000.00', $log->payload['after']['cost']);
        $this->assertSame('2000.00', $log->payload['after']['price']);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/materials/{$this->material->id}/restore")
            ->assertOk();

        $this->assertSame(1, ActivityLog::query()
            ->where('action', 'RESTORE')
            ->where('model_type', Material::class)
            ->where('model_id', $this->material->id)
            ->count());
    }

    public function test_activate_material_writes_activate_audit(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/v1/materials/{$this->otherMaterial->id}/activate", [
                'branch_id' => $this->branchOne->id,
                'initial_stock' => 4,
                'min_stock' => 2,
            ])
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $this->otherMaterial->id,
            'branch_id' => $this->branchOne->id,
            'current_stock' => 4,
            'min_stock' => 2,
        ]);

        $first = ActivityLog::query()
            ->where('action', 'ACTIVATE')
            ->where('model_type', Material::class)
            ->where('model_id', $this->otherMaterial->id)
            ->firstOrFail();

        $this->assertSame($this->owner->id, $first->user_id);
        $this->assertSame($this->branchOne->id, $first->payload['branch_id']);
        $this->assertTrue($first->payload['created_stock_row']);
        $this->assertSame(4.0, (float) $first->payload['initial_stock']);
        $this->assertSame(2.0, (float) $first->payload['min_stock']);
        $this->assertSame(0.0, (float) $first->payload['stock_before']);
        $this->assertSame(4.0, (float) $first->payload['stock_after']);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/materials/{$this->otherMaterial->id}/activate", [
                'branch_id' => $this->branchOne->id,
                'initial_stock' => 9,
                'min_stock' => 3,
            ])
            ->assertOk();

        $logs = ActivityLog::query()
            ->where('action', 'ACTIVATE')
            ->where('model_type', Material::class)
            ->where('model_id', $this->otherMaterial->id)
            ->get();

        $this->assertCount(2, $logs);

        $second = $logs->first(
            fn (ActivityLog $log): bool => $log->payload['created_stock_row'] === false
        );

        $this->assertNotNull($second);
        $this->assertSame(4.0, (float) $second->payload['stock_before']);
        $this->assertSame(4.0, (float) $second->payload['stock_after']);
        $this->assertSame(0.0, (float) $second->payload['initial_stock']);
        $this->assertSame(2.0, (float) $second->payload['min_stock']);
    }

    public function test_manager_material_list_hides_other_branch_stock(): void
    {
        $managerResponse = $this->actingAs($this->manager)
            ->getJson('/api/v1/materials')
            ->assertOk();

        $managerRow = collect($managerResponse->json('data'))->firstWhere('id', $this->material->id);

        $this->assertCount(1, $managerRow['stocks']);
        $this->assertSame($this->branchOne->id, $managerRow['stocks'][0]['branch_id']);

        $ownerResponse = $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?branch_id=all')
            ->assertOk();

        $ownerRow = collect($ownerResponse->json('data'))->firstWhere('id', $this->material->id);

        $this->assertCount(2, $ownerRow['stocks']);
    }

    public function test_owner_creates_global_and_branch_attributes(): void
    {
        $global = $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy',
                'type' => 'finishing',
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.branch_id', null)
            ->json('attribute');

        $this->assertDatabaseHas('attributes', [
            'id' => $global['id'],
            'branch_id' => null,
        ]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Matte',
                'type' => 'display',
                'branch_id' => $this->branchTwo->id,
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.branch_id', $this->branchTwo->id);
    }

    public function test_manager_attribute_create_is_forced_or_rejected_by_branch(): void
    {
        $this->actingAs($this->manager)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy',
                'type' => 'finishing',
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.branch_id', $this->branchOne->id);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy Own',
                'type' => 'finishing',
                'branch_id' => $this->branchOne->id,
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.branch_id', $this->branchOne->id);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy Null Branch',
                'type' => 'finishing',
                'branch_id' => null,
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.branch_id', $this->branchOne->id);

        $this->assertDatabaseMissing('attributes', [
            'name' => 'Glossy Null Branch',
            'branch_id' => null,
        ]);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy Other',
                'type' => 'finishing',
                'branch_id' => $this->branchTwo->id,
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attributes', ['name' => 'Glossy Other']);

        $this->actingAs($this->manager)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy Unknown',
                'type' => 'finishing',
                'branch_id' => (string) Str::uuid(),
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('branch_id');

        $branchlessManager = $this->user('manager', null);

        $this->actingAs($branchlessManager)
            ->postJson('/api/v1/attributes', [
                'name' => 'Glossy Branchless',
                'type' => 'finishing',
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attributes', ['name' => 'Glossy Branchless']);
    }

    public function test_manager_updates_only_own_branch_attributes(): void
    {
        $own = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ]);
        $other = Attribute::create([
            'branch_id' => $this->branchTwo->id,
            'name' => 'Matte',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ]);
        $global = Attribute::create([
            'branch_id' => null,
            'name' => 'Shared',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $this->actingAs($this->manager)
            ->putJson("/api/v1/attributes/{$own->id}", [
                'name' => 'Glossy Updated',
                'type' => 'display',
                'price' => 6000,
                'cost' => 2500,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('attribute.type', 'display')
            ->assertJsonPath('attribute.price', '6000.00')
            ->assertJsonPath('attribute.cost', '2500.00')
            ->assertJsonPath('attribute.is_active', false);

        $branchlessManager = $this->user('manager', null);

        $this->actingAs($branchlessManager)
            ->putJson("/api/v1/attributes/{$own->id}", [
                'name' => 'Glossy Branchless',
                'type' => 'display',
                'price' => 6000,
                'cost' => 2500,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('attributes', [
            'id' => $own->id,
            'name' => 'Glossy Updated',
        ]);

        $this->actingAs($this->manager)
            ->putJson("/api/v1/attributes/{$global->id}", [
                'name' => 'Shared Updated',
                'type' => 'display',
                'price' => 6000,
                'cost' => 2500,
            ])
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->putJson("/api/v1/attributes/{$other->id}", [
                'name' => 'Matte Updated',
                'type' => 'display',
                'price' => 6000,
                'cost' => 2500,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('attributes', ['id' => $global->id, 'name' => 'Shared']);
        $this->assertDatabaseHas('attributes', ['id' => $other->id, 'name' => 'Matte']);
    }

    public function test_manager_cannot_move_an_attribute_to_another_branch(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $this->actingAs($this->manager)
            ->putJson("/api/v1/attributes/{$attribute->id}", [
                'name' => 'Glossy Moved',
                'type' => 'display',
                'branch_id' => $this->branchTwo->id,
                'price' => 6000,
                'cost' => 2500,
            ])
            ->assertOk()
            ->assertJsonPath('attribute.branch_id', $this->branchOne->id);

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'branch_id' => $this->branchOne->id,
        ]);
    }

    public function test_attribute_delete_endpoint_is_removed(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $this->deleteJson("/api/v1/attributes/{$attribute->id}")
            ->assertNotFound();

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/attributes/{$attribute->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('attributes', ['id' => $attribute->id]);
    }

    public function test_attribute_writes_are_owner_or_manager_only(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $designer = $this->user('designer', $this->branchOne);
        $production = $this->user('production', $this->branchOne);

        foreach ([$this->cashier, $designer, $production] as $actor) {
            $this->actingAs($actor)
                ->postJson('/api/v1/attributes', [
                    'name' => 'Glossy Blocked',
                    'type' => 'finishing',
                    'price' => 5000,
                    'cost' => 2000,
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->putJson("/api/v1/attributes/{$attribute->id}", [
                    'name' => 'Glossy Blocked',
                    'type' => 'finishing',
                    'price' => 5000,
                    'cost' => 2000,
                ])
                ->assertForbidden();
        }
    }

    public function test_attribute_write_endpoints_require_authentication(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Glossy',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $this->postJson('/api/v1/attributes', [
            'name' => 'Guest',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ])->assertUnauthorized();

        $this->putJson("/api/v1/attributes/{$attribute->id}", [
            'name' => 'Guest',
            'type' => 'finishing',
            'price' => 5000,
            'cost' => 2000,
        ])->assertUnauthorized();
    }

    public function test_attribute_create_requires_an_active_type(): void
    {
        $type = AttributeType::create([
            'name' => 'Packaging',
            'slug' => 'packaging',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Box',
                'type' => 'packaging',
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.type_name', 'Packaging');

        $type->update(['is_active' => false]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Box Inactive',
                'type' => 'packaging',
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Box Unknown',
                'type' => 'unknown-type',
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

        $this->assertDatabaseMissing('attributes', ['name' => 'Box Inactive']);
        $this->assertDatabaseMissing('attributes', ['name' => 'Box Unknown']);
    }

    public function test_attribute_update_may_keep_its_current_inactive_type(): void
    {
        $type = AttributeType::create([
            'name' => 'Packaging',
            'slug' => 'packaging',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Box',
            'type' => 'packaging',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $type->update(['is_active' => false]);

        $this->actingAs($this->manager)
            ->putJson("/api/v1/attributes/{$attribute->id}", [
                'name' => 'Box Updated',
                'type' => 'packaging',
                'price' => 6000,
                'cost' => 2500,
            ])
            ->assertOk()
            ->assertJsonPath('attribute.type', 'packaging')
            ->assertJsonPath('attribute.type_name', 'Packaging')
            ->assertJsonPath('attribute.price', '6000.00');

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'name' => 'Box Updated',
            'type' => 'packaging',
        ]);
    }

    public function test_attribute_update_rejects_a_different_inactive_type(): void
    {
        AttributeType::create([
            'name' => 'Packaging',
            'slug' => 'packaging',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        AttributeType::create([
            'name' => 'Emboss',
            'slug' => 'emboss',
            'sort_order' => 11,
            'is_active' => false,
        ]);

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Box',
            'type' => 'packaging',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $this->actingAs($this->manager)
            ->putJson("/api/v1/attributes/{$attribute->id}", [
                'name' => 'Box Emboss',
                'type' => 'emboss',
                'price' => 6000,
                'cost' => 2500,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'name' => 'Box',
            'type' => 'packaging',
        ]);
    }

    public function test_attribute_update_can_change_to_another_active_type(): void
    {
        AttributeType::create([
            'name' => 'Packaging',
            'slug' => 'packaging',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Box',
            'type' => 'packaging',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $this->actingAs($this->manager)
            ->putJson("/api/v1/attributes/{$attribute->id}", [
                'name' => 'Box Display',
                'type' => 'display',
                'price' => 6000,
                'cost' => 2500,
            ])
            ->assertOk()
            ->assertJsonPath('attribute.type', 'display')
            ->assertJsonPath('attribute.type_name', 'Display');

        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->id,
            'type' => 'display',
        ]);
    }

    public function test_cut_attribute_round_trips_and_reports_potong(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Half Cut',
            'type' => 'cut',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $rows = $this->actingAs($this->owner)
            ->getJson('/api/v1/attributes')
            ->assertOk()
            ->json('data');

        $row = collect($rows)->firstWhere('id', $attribute->id);

        $this->assertNotNull($row);
        $this->assertSame('Potong', $row['type_name']);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/attributes/{$attribute->id}", [
                'name' => 'Half Cut Updated',
                'type' => 'cut',
                'price' => 5500,
                'cost' => 2200,
            ])
            ->assertOk()
            ->assertJsonPath('attribute.type_name', 'Potong');
    }

    public function test_custom_type_attribute_reports_type_name(): void
    {
        AttributeType::create([
            'name' => 'Packaging',
            'slug' => 'packaging',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $created = $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Box',
                'type' => 'packaging',
                'price' => 5000,
                'cost' => 2000,
            ])
            ->assertCreated()
            ->assertJsonPath('attribute.type_name', 'Packaging')
            ->json('attribute');

        $rows = $this->actingAs($this->owner)
            ->getJson('/api/v1/attributes')
            ->assertOk()
            ->json('data');

        $row = collect($rows)->firstWhere('id', $created['id']);

        $this->assertNotNull($row);
        $this->assertSame('Packaging', $row['type_name']);
    }

    public function test_attribute_type_name_falls_back_to_the_raw_slug(): void
    {
        $attribute = Attribute::create([
            'branch_id' => $this->branchOne->id,
            'name' => 'Orphan',
            'type' => 'orphan',
            'price' => 5000,
            'cost' => 2000,
        ]);

        $rows = $this->actingAs($this->owner)
            ->getJson('/api/v1/attributes')
            ->assertOk()
            ->json('data');

        $row = collect($rows)->firstWhere('id', $attribute->id);

        $this->assertNotNull($row);
        $this->assertSame('orphan', $row['type_name']);
    }

    public function test_pricing_crud_and_clone_tiers(): void
    {
        $pricing = $this->actingAs($this->owner)
            ->postJson('/api/v1/pricing', [
                'branch_id' => $this->branchOne->id,
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'customer_type' => 'Reguler',
                'min_qty' => 1,
                'price' => 50000,
                'cost' => 20000,
            ])
            ->assertCreated()
            ->json('pricing');

        $this->actingAs($this->manager)
            ->putJson("/api/v1/pricing/{$pricing['id']}", [
                'min_qty' => 1,
                'price' => 55000,
                'cost' => 20000,
            ])
            ->assertOk()
            ->assertJsonPath('pricing.price', '55000.00');

        $this->actingAs($this->manager)
            ->postJson('/api/v1/pricing/clone-tiers', [
                'from_material_id' => $this->material->id,
                'to_material_id' => $this->otherMaterial->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('pricing', [
            'branch_id' => $this->branchOne->id,
            'material_id' => $this->otherMaterial->id,
            'customer_type' => 'Reguler',
        ]);

        $this->actingAs($this->manager)
            ->deleteJson("/api/v1/pricing/{$pricing['id']}")
            ->assertNoContent();

        $this->assertSoftDeleted('pricing', ['id' => $pricing['id']]);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/pricing')
            ->assertOk()
            ->assertJsonMissing(['id' => $pricing['id']]);
    }

    public function test_catalog_write_endpoints_require_authentication(): void
    {
        foreach (['/materials', '/categories', '/attributes', '/pricing'] as $path) {
            $this->postJson('/api/v1'.$path, [])->assertUnauthorized();
        }

        $this->getJson('/api/v1/categories')->assertUnauthorized();
        $this->putJson("/api/v1/categories/{$this->category->id}", ['name' => 'Guest'])
            ->assertUnauthorized();
    }

    public function test_catalog_write_validation_errors_are_422(): void
    {
        $actor = $this->manager;

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Ignored Legacy Fields',
                'calculation_archetype' => 'not-real',
                'allowed_attribute_types' => ['finishing'],
            ])
            ->assertCreated()
            ->assertJsonPath('category.name', 'Ignored Legacy Fields')
            ->assertJsonMissingPath('category.calculation_archetype')
            ->assertJsonMissingPath('category.allowed_attribute_types');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Invalid',
                'type' => 'not-real',
                'price' => 1000,
                'cost' => 500,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Negative Price',
                'type' => 'finishing',
                'price' => -1,
                'cost' => 500,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('price');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/attributes', [
                'name' => 'Missing Cost',
                'type' => 'display',
                'price' => 1000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cost');

        $this->actingAs($actor)
            ->postJson('/api/v1/pricing', [
                'product_id' => $this->product->id,
                'material_id' => $this->material->id,
                'customer_type' => 'Reguler',
                'min_qty' => 1,
                'cost' => 1000,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('price');
    }

    public function test_product_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/products')->assertUnauthorized();
        $this->postJson('/api/v1/products', [])->assertUnauthorized();
        $this->putJson("/api/v1/products/{$this->product->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/v1/products/{$this->product->id}")->assertUnauthorized();
        $this->postJson("/api/v1/products/{$this->product->id}/restore")->assertUnauthorized();
    }

    public function test_owner_can_create_update_delete_and_restore_products(): void
    {
        $created = $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Banner Baru',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'area',
                'material_ids' => [$this->material->id],
            ])
            ->assertCreated()
            ->assertJsonPath('product.is_active', true)
            ->assertJsonPath('product.material_ids.0', $this->material->id)
            ->assertJsonPath('product.materials.0.id', $this->material->id)
            ->json('product');

        $this->assertDatabaseHas('material_product', [
            'product_id' => $created['id'],
            'material_id' => $this->material->id,
        ]);

        $createLog = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', Product::class)
            ->where('model_id', $created['id'])
            ->first();

        $this->assertNotNull($createLog);
        $this->assertSame([$this->material->id], $createLog->payload['material_ids']);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$created['id']}", [
                'name' => 'Banner Baru Updated',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'is_active' => false,
                'material_ids' => [$this->otherMaterial->id],
            ])
            ->assertOk()
            ->assertJsonPath('product.is_active', false)
            ->assertJsonPath('product.pricing_type', 'unit')
            ->assertJsonPath('product.material_ids.0', $this->otherMaterial->id);

        $this->assertDatabaseMissing('material_product', [
            'product_id' => $created['id'],
            'material_id' => $this->material->id,
        ]);
        $this->assertDatabaseHas('material_product', [
            'product_id' => $created['id'],
            'material_id' => $this->otherMaterial->id,
        ]);

        $updateLog = ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', Product::class)
            ->where('model_id', $created['id'])
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertTrue((bool) $updateLog->payload['before']['is_active']);
        $this->assertFalse((bool) $updateLog->payload['after']['is_active']);
        $this->assertSame([$this->material->id], $updateLog->payload['before']['material_ids']);
        $this->assertSame([$this->otherMaterial->id], $updateLog->payload['after']['material_ids']);

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/products/{$created['id']}")
            ->assertNoContent();

        $this->assertSoftDeleted('products', ['id' => $created['id']]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'DELETE',
            'model_type' => Product::class,
            'model_id' => $created['id'],
        ]);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/products/{$created['id']}/restore")
            ->assertOk()
            ->assertJsonPath('product.deleted_at', null);

        $this->assertNotSoftDeleted('products', ['id' => $created['id']]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'RESTORE',
            'model_type' => Product::class,
            'model_id' => $created['id'],
        ]);
    }

    public function test_unauthorized_roles_and_branchless_manager_cannot_manage_products(): void
    {
        $designer = $this->user('designer', $this->branchOne);
        $production = $this->user('production', $this->branchOne);
        $branchlessManager = $this->user('manager', null);

        $payload = [
            'name' => 'Blocked Product',
            'product_category_id' => $this->category->id,
            'pricing_type' => 'unit',
        ];

        foreach ([$this->manager, $this->cashier, $designer, $production, $branchlessManager] as $actor) {
            $this->actingAs($actor)->postJson('/api/v1/products', $payload)->assertForbidden();
            $this->actingAs($actor)->putJson("/api/v1/products/{$this->product->id}", $payload)->assertForbidden();
            $this->actingAs($actor)->deleteJson("/api/v1/products/{$this->product->id}")->assertForbidden();
            $this->actingAs($actor)->postJson("/api/v1/products/{$this->product->id}/restore")->assertForbidden();
        }
    }

    public function test_product_write_validation_errors_are_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Invalid Pricing Type',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'weight',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pricing_type');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Missing Category',
                'product_category_id' => (string) Str::uuid(),
                'pricing_type' => 'unit',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_category_id');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => 'Invalid Active Flag',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'is_active' => 'not-a-bool',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_active');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Invalid Material Mapping',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'material_ids' => [(string) Str::uuid()],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_ids.0');

        $deletedMaterial = Material::create([
            'name' => 'Deleted Material',
            'product_category_id' => $this->category->id,
            'unit' => 'pcs',
            'cost' => 1000,
            'price' => 2000,
        ]);
        $deletedMaterial->delete();

        $this->actingAs($this->owner)
            ->putJson("/api/v1/products/{$this->product->id}", [
                'name' => 'Deleted Material Mapping',
                'product_category_id' => $this->category->id,
                'pricing_type' => 'unit',
                'material_ids' => [$deletedMaterial->id],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('material_ids.0');
    }

    public function test_owner_creates_and_updates_a_category_with_description_and_active_state(): void
    {
        $category = $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Indoor',
                'description' => 'Bahan indoor',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('category.description', 'Bahan indoor')
            ->assertJsonPath('category.is_active', true)
            ->assertJsonMissingPath('category.products_count')
            ->assertJsonMissingPath('category.discount_tiers')
            ->json('category');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Updated',
                'description' => null,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('category.description', null)
            ->assertJsonPath('category.is_active', false);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category['id'],
            'description' => null,
            'is_active' => false,
        ]);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Kept',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('category.description', null)
            ->assertJsonPath('category.is_active', true);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Kept',
                'description' => 'Bahan indoor lagi',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('category.description', 'Bahan indoor lagi');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$category['id']}", [
                'name' => 'Indoor Kept',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('category.description', 'Bahan indoor lagi');
    }

    public function test_category_delete_endpoint_is_removed(): void
    {
        $this->deleteJson("/api/v1/categories/{$this->category->id}")
            ->assertNotFound();

        $this->actingAs($this->owner)
            ->deleteJson("/api/v1/categories/{$this->category->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('product_categories', ['id' => $this->category->id]);
    }

    public function test_category_write_validation_errors_are_422(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'description' => 'Missing name',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Long Description',
                'description' => str_repeat('a', 256),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Array Description',
                'description' => ['nope'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('description');

        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Invalid Active Flag',
                'is_active' => 'not-a-bool',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('is_active');
    }

    public function test_legacy_discount_tiers_are_ignored(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Legacy',
                'discount_tiers' => [
                    ['min_qty' => 10, 'discount_percent' => 5],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('category.name', 'Legacy');

        $this->assertFalse(Schema::hasTable('product_category_discounts'));
    }

    public function test_product_can_be_created_under_an_inactive_category(): void
    {
        $inactiveCategory = ProductCategory::create([
            'name' => 'Retired',
            'is_active' => false,
        ]);

        $this->actingAs($this->owner)
            ->postJson('/api/v1/products', [
                'name' => 'Retired Banner',
                'product_category_id' => $inactiveCategory->id,
                'pricing_type' => 'unit',
            ])
            ->assertCreated()
            ->assertJsonPath('product.is_active', true)
            ->assertJsonPath('product.category.is_active', false);
    }

    public function test_restore_product_is_inactive_when_its_category_is_inactive(): void
    {
        $inactiveCategory = ProductCategory::create([
            'name' => 'Retired',
            'is_active' => false,
        ]);

        $inactiveProduct = Product::create([
            'name' => 'Retired Banner',
            'pricing_type' => 'unit',
            'product_category_id' => $inactiveCategory->id,
            'is_active' => true,
        ]);
        $inactiveProduct->delete();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/products/{$inactiveProduct->id}/restore")
            ->assertOk()
            ->assertJsonPath('product.is_active', false);

        $this->assertDatabaseHas('products', [
            'id' => $inactiveProduct->id,
            'is_active' => false,
        ]);

        $this->product->delete();

        $this->actingAs($this->owner)
            ->postJson("/api/v1/products/{$this->product->id}/restore")
            ->assertOk()
            ->assertJsonPath('product.is_active', true);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'is_active' => true,
        ]);
    }

    public function test_category_writes_record_audit(): void
    {
        $created = $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', [
                'name' => 'Print Lembaran',
                'description' => 'Bahan outdoor',
                'is_active' => true,
            ])
            ->assertCreated()
            ->json('category');

        $createLog = ActivityLog::query()
            ->where('action', 'CREATE')
            ->where('model_type', ProductCategory::class)
            ->where('model_id', $created['id'])
            ->first();

        $this->assertNotNull($createLog);
        $this->assertSame([
            'name' => 'Print Lembaran',
            'description' => 'Bahan outdoor',
            'is_active' => true,
        ], $createLog->payload['after']);

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$created['id']}", [
                'name' => 'Print Lembaran Updated',
                'is_active' => false,
            ])
            ->assertOk();

        $updateLog = ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', ProductCategory::class)
            ->where('model_id', $created['id'])
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertSame('Print Lembaran', $updateLog->payload['before']['name']);
        $this->assertSame('Print Lembaran Updated', $updateLog->payload['after']['name']);
        $this->assertTrue($updateLog->payload['before']['is_active']);
        $this->assertFalse($updateLog->payload['after']['is_active']);
        $this->assertSame('Bahan outdoor', $updateLog->payload['after']['description']);
    }

    public function test_category_writes_are_owner_only(): void
    {
        $designer = $this->user('designer', $this->branchOne);
        $production = $this->user('production', $this->branchOne);
        $payload = ['name' => 'Blocked Category'];

        foreach ([$this->manager, $this->cashier, $designer, $production] as $actor) {
            $this->actingAs($actor)->postJson('/api/v1/categories', $payload)->assertForbidden();
            $this->actingAs($actor)
                ->putJson("/api/v1/categories/{$this->category->id}", $payload)
                ->assertForbidden();
        }

        $created = $this->actingAs($this->owner)
            ->postJson('/api/v1/categories', ['name' => 'Owner Category'])
            ->assertCreated()
            ->json('category');

        $this->actingAs($this->owner)
            ->putJson("/api/v1/categories/{$created['id']}", [
                'name' => 'Owner Category Updated',
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('category.name', 'Owner Category Updated')
            ->assertJsonPath('category.is_active', false);

        $this->assertFalse(Schema::hasTable('product_category_discounts'));
    }

    private function branch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'code' => Str::upper(Str::random(6)),
        ]);
    }

    private function user(string $role, ?Branch $branch): User
    {
        return User::factory()->create([
            'role' => $role,
            'branch_id' => $branch?->id,
        ]);
    }
}
