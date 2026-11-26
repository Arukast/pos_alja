<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MaterialRetirementApiTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchOne;

    private Branch $branchTwo;

    private User $owner;

    private User $manager;

    private ProductCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchOne = $this->branch('Branch One');
        $this->branchTwo = $this->branch('Branch Two');

        $this->owner = $this->user('owner', null);
        $this->manager = $this->user('manager', $this->branchOne);

        $this->category = ProductCategory::create([
            'name' => 'Large Format',
        ]);
    }

    public function test_material_and_branch_stock_active_flags_default_true(): void
    {
        $this->assertTrue(Schema::hasColumn('materials', 'is_active'));
        $this->assertTrue(Schema::hasColumn('material_branch_stock', 'is_active'));

        $material = $this->material();
        $stock = $this->stock($material, $this->branchOne);

        $freshMaterial = $material->fresh();
        $freshStock = $stock->fresh();

        $this->assertIsBool($freshMaterial->is_active);
        $this->assertTrue($freshMaterial->is_active);
        $this->assertIsBool($freshStock->is_active);
        $this->assertTrue($freshStock->is_active);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'is_active' => true]);
        $this->assertDatabaseHas('material_branch_stock', ['id' => $stock->id, 'is_active' => true]);
    }

    public function test_material_list_exposes_global_and_branch_active_flags(): void
    {
        $material = $this->material();
        $this->stock($material, $this->branchOne);

        $this->actingAs($this->owner)
            ->getJson('/api/v1/materials?branch_id=all')
            ->assertOk()
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.stocks.0.is_active', true);
    }

    public function test_manager_material_list_scopes_stocks_to_the_own_branch(): void
    {
        $material = $this->material();
        $this->stock($material, $this->branchOne);
        $this->stock($material, $this->branchTwo);

        $this->actingAs($this->manager)
            ->getJson('/api/v1/materials')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(1, 'data.0.stocks')
            ->assertJsonPath('data.0.stocks.0.branch_id', $this->branchOne->id)
            ->assertJsonPath('data.0.stocks.0.is_active', true);
    }

    public function test_owner_toggles_global_material_active_and_writes_one_update_audit(): void
    {
        $material = $this->material();

        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/active", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('material.is_active', false);

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'is_active' => false]);
        $log = ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', Material::class)
            ->where('model_id', $material->id)
            ->sole();
        $this->assertSame('Memperbarui bahan: Vinyl Roll', $log->description);
        $this->assertSame(['is_active' => true], $log->payload['before']);
        $this->assertSame(['is_active' => false], $log->payload['after']);
        $this->assertNull($log->branch_id);
    }

    public function test_manager_toggles_own_branch_material_availability_without_audit_or_ledger(): void
    {
        $material = $this->material();
        $stock = $this->stock($material, $this->branchOne, 5, 1);

        $this->actingAs($this->manager)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('material.stocks.0.is_active', false);

        $this->assertDatabaseHas('material_branch_stock', [
            'id' => $stock->id,
            'current_stock' => 5,
            'is_active' => false,
        ]);
        $this->assertDatabaseCount('stock_ledgers', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_global_material_active_toggle_enforces_auth_and_roles(): void
    {
        $material = $this->material();

        $this->patchJson("/api/v1/materials/{$material->id}/active", ['is_active' => false])
            ->assertUnauthorized();

        foreach (['manager', 'cashier', 'designer', 'production'] as $role) {
            $this->actingAs($this->user($role, $this->branchOne))
                ->patchJson("/api/v1/materials/{$material->id}/active", ['is_active' => false])
                ->assertForbidden();
        }

        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/active", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/active", ['is_active' => 'not-a-bool'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->actingAs($this->owner)
            ->patchJson('/api/v1/materials/'.Str::uuid().'/active', ['is_active' => false])
            ->assertNotFound();
    }

    public function test_owner_toggles_any_branch_and_must_name_the_branch(): void
    {
        $material = $this->material();
        $branchOneStock = $this->stock($material, $this->branchOne);
        $branchTwoStock = $this->stock($material, $this->branchTwo);

        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", [
                'branch_id' => $this->branchTwo->id,
                'is_active' => false,
            ])
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'id' => $branchTwoStock->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('material_branch_stock', [
            'id' => $branchOneStock->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", ['is_active' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');

        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", [
                'branch_id' => Str::uuid()->toString(),
                'is_active' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');

        $branchThree = $this->branch('Branch Three');
        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", [
                'branch_id' => $branchThree->id,
                'is_active' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');
    }

    public function test_branch_availability_denies_cross_branch_and_branchless_managers(): void
    {
        $material = $this->material();
        $this->stock($material, $this->branchOne);
        $this->stock($material, $this->branchTwo);

        $this->patchJson("/api/v1/materials/{$material->id}/branch-availability", ['is_active' => false])
            ->assertUnauthorized();

        $this->actingAs($this->manager)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", [
                'branch_id' => $this->branchTwo->id,
                'is_active' => false,
            ])
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", [
                'branch_id' => $this->branchOne->id,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('material.stocks.0.is_active', false);

        $this->actingAs($this->user('manager', null))
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", ['is_active' => false])
            ->assertForbidden();

        foreach (['cashier', 'designer', 'production'] as $role) {
            $this->actingAs($this->user($role, $this->branchOne))
                ->patchJson("/api/v1/materials/{$material->id}/branch-availability", ['is_active' => false])
                ->assertForbidden();
        }

        $this->actingAs($this->manager)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", [
                'branch_id' => 'not-a-uuid',
                'is_active' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');

        $this->actingAs($this->manager)
            ->patchJson("/api/v1/materials/{$material->id}/branch-availability", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $this->actingAs($this->manager)
            ->patchJson('/api/v1/materials/'.Str::uuid().'/branch-availability', ['is_active' => false])
            ->assertNotFound();
    }

    public function test_activate_reactivates_an_existing_branch_stock_row(): void
    {
        $material = $this->material();
        $stock = $this->stock($material, $this->branchOne, 5, 1);
        $stock->update(['is_active' => false]);

        $this->actingAs($this->owner)
            ->postJson("/api/v1/materials/{$material->id}/activate", [
                'branch_id' => $this->branchOne->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'id' => $stock->id,
            'is_active' => true,
            'current_stock' => 5,
        ]);
        $this->assertSame(1, ActivityLog::query()->where('action', 'ACTIVATE')->count());
        $this->assertDatabaseCount('stock_ledgers', 0);
    }

    public function test_global_material_toggle_does_not_change_stock_or_write_a_ledger(): void
    {
        $material = $this->material();
        $stock = $this->stock($material, $this->branchOne, 5, 1);

        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/active", ['is_active' => false])
            ->assertOk();
        $this->actingAs($this->owner)
            ->patchJson("/api/v1/materials/{$material->id}/active", ['is_active' => true])
            ->assertOk();

        $this->assertDatabaseHas('material_branch_stock', [
            'id' => $stock->id,
            'current_stock' => 5,
            'min_stock' => 1,
            'is_active' => true,
        ]);
        $this->assertSame(2, ActivityLog::query()
            ->where('action', 'UPDATE')
            ->where('model_type', Material::class)
            ->where('model_id', $material->id)
            ->count());
        $this->assertDatabaseCount('stock_ledgers', 0);
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function material(array $overrides = []): Material
    {
        return Material::create(array_merge([
            'name' => 'Vinyl Roll',
            'product_category_id' => $this->category->id,
            'type' => 'roll',
            'cost' => 1000,
            'price' => 2000,
        ], $overrides));
    }

    private function stock(
        Material $material,
        Branch $branch,
        float $current = 5,
        float $min = 1,
    ): MaterialBranchStock {
        return $material->branchStocks()->create([
            'branch_id' => $branch->id,
            'current_stock' => $current,
            'min_stock' => $min,
        ]);
    }
}
