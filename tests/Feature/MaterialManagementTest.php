<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\MaterialManagement;
use App\Models\Branch;
use App\Models\Material;
use App\Models\ProductCategory;
use App\Models\StockLedger;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialManagementTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branchA;

    private Branch $branchB;

    private User $owner;

    private User $managerA;

    private User $managerB;

    private User $cashier;

    private ProductCategory $meterCategory;

    private ProductCategory $lembarCategory;

    private ProductCategory $pcsCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branchA = Branch::create([
            'id' => (string) Str::uuid(),
            'name' => 'Branch A',
            'code' => 'BRA',
        ]);

        $this->branchB = Branch::create([
            'id' => (string) Str::uuid(),
            'name' => 'Branch B',
            'code' => 'BRB',
        ]);

        $this->owner = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'username' => 'testowner',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'branch_id' => $this->branchA->id,
        ]);

        $this->managerA = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Manager A',
            'email' => 'managera@example.com',
            'username' => 'managera',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'branch_id' => $this->branchA->id,
        ]);

        $this->managerB = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Manager B',
            'email' => 'managerb@example.com',
            'username' => 'managerb',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'branch_id' => $this->branchB->id,
        ]);

        $this->cashier = User::create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Cashier',
            'email' => 'cashier@example.com',
            'username' => 'testcashier',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'branch_id' => $this->branchA->id,
        ]);

        $this->meterCategory = ProductCategory::create([
            'name' => 'meter',
            'calculation_archetype' => 'linear_dimension',
        ]);

        $this->lembarCategory = ProductCategory::create([
            'name' => 'lembar',
            'calculation_archetype' => 'per_piece',
        ]);

        $this->pcsCategory = ProductCategory::create([
            'name' => 'pcs',
            'calculation_archetype' => 'per_piece',
        ]);
    }

    /**
     * Cashier role cannot access material registration.
     */
    public function test_cashier_role_cannot_create_material(): void
    {
        $this->actingAs($this->cashier);

        Livewire::test(MaterialManagement::class)
            ->call('openMaterialModal')
            ->set('materialForm.name', 'Vinyl Banner')
            ->set('materialForm.productCategoryId', $this->meterCategory->id)
            ->set('materialForm.type', 'Outdoor')
            ->set('materialForm.dimensions', '3.2m')
            ->set('materialForm.cost', 15000)
            ->set('materialForm.price', 25000)
            ->set('materialForm.current_stock', 10)
            ->set('materialForm.min_stock', 2)
            ->call('saveMaterial')
            ->assertStatus(403);
    }

    /**
     * Manager role can register a new material.
     */
    public function test_manager_can_register_new_material(): void
    {
        $this->actingAs($this->managerA);

        Livewire::test(MaterialManagement::class)
            ->call('openMaterialModal')
            ->set('materialForm.name', 'Vinyl Banner')
            ->set('materialForm.productCategoryId', $this->meterCategory->id)
            ->set('materialForm.type', 'Outdoor')
            ->set('materialForm.dimensions', '3.2m')
            ->set('materialForm.cost', 15000)
            ->set('materialForm.price', 25000)
            ->set('materialForm.current_stock', 50)
            ->set('materialForm.min_stock', 5)
            ->call('saveMaterial')
            ->assertHasNoErrors()
            ->assertSet('isCreatingMaterial', false);

        $this->assertDatabaseHas('materials', [
            'name' => 'Vinyl Banner',
            'product_category_id' => $this->meterCategory->id,
            'type' => 'Outdoor',
            'dimensions' => '3.2m',
            'cost' => 15000.00,
            'price' => 25000.00,
        ]);

        $material = Material::where('name', 'Vinyl Banner')->first();
        $this->assertNotNull($material);

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $material->id,
            'branch_id' => $this->branchA->id,
            'current_stock' => 50.00,
            'min_stock' => 5.00,
        ]);

        $this->assertDatabaseHas('stock_ledgers', [
            'branch_id' => $this->branchA->id,
            'material_id' => $material->id,
            'initial_stock' => 0.0,
            'added_stock' => 50.00,
            'remaining_stock' => 50.00,
        ]);
    }

    /**
     * Tenancy isolation: name uniqueness is global and case-insensitive.
     */
    public function test_case_insensitive_name_uniqueness_is_global_and_triggers_activation_wizard(): void
    {
        // Create a material
        $material = Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Albatros',
            'product_category_id' => $this->lembarCategory->id,
            'cost' => 5000,
            'price' => 10000,
        ]);

        // Manager A tries to create 'albatros' (case-insensitive) -> triggers wizard
        $this->actingAs($this->managerA);
        Livewire::test(MaterialManagement::class)
            ->call('openMaterialModal')
            ->set('materialForm.name', 'albatros')
            ->set('materialForm.productCategoryId', $this->lembarCategory->id)
            ->set('materialForm.min_stock', 5)
            ->call('saveMaterial')
            ->assertHasNoErrors()
            ->assertSet('showActivationWizard', true)
            ->assertSet('duplicateGlobalMaterialId', $material->id)
            ->call('activateExistingMaterial')
            ->assertHasNoErrors()
            ->assertSet('showActivationWizard', false);

        // Verify the junction is active with stock = 0
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $material->id,
            'branch_id' => $this->branchA->id,
            'current_stock' => 0.0,
            'min_stock' => 5.0,
        ]);

        // Manager A tries to create 'albatros' again -> fails validation immediately because junction now exists
        Livewire::test(MaterialManagement::class)
            ->call('openMaterialModal')
            ->set('materialForm.name', 'albatros')
            ->set('materialForm.productCategoryId', $this->lembarCategory->id)
            ->call('saveMaterial')
            ->assertHasErrors(['materialForm.name']);
    }

    public function test_owner_creation_auto_populates_all_branches(): void
    {
        $this->actingAs($this->owner);

        Livewire::test(MaterialManagement::class)
            ->call('openMaterialModal')
            ->set('materialForm.name', 'Global Roll Out')
            ->set('materialForm.productCategoryId', $this->meterCategory->id)
            ->set('materialForm.type', 'Premium')
            ->set('materialForm.cost', 8000)
            ->set('materialForm.price', 12000)
            ->set('materialForm.current_stock', 100) // starting stock input
            ->set('materialForm.min_stock', 10)
            ->call('saveMaterial')
            ->assertHasNoErrors();

        $material = Material::where('name', 'Global Roll Out')->first();
        $this->assertNotNull($material);

        // Automatically populated for branchA and branchB with stock 0
        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $material->id,
            'branch_id' => $this->branchA->id,
            'current_stock' => 0.0,
            'min_stock' => 10.0,
        ]);

        $this->assertDatabaseHas('material_branch_stock', [
            'material_id' => $material->id,
            'branch_id' => $this->branchB->id,
            'current_stock' => 0.0,
            'min_stock' => 10.0,
        ]);
    }

    public function test_fuzzy_similarity_matching_triggers_warning_and_can_be_bypassed(): void
    {
        $this->actingAs($this->managerA);

        // Create a material: 'Albatros'
        Material::create([
            'id' => (string) Str::uuid(),
            'name' => 'Albatros',
            'product_category_id' => $this->lembarCategory->id,
            'cost' => 5000,
            'price' => 10000,
        ]);

        // Manager A tries to create 'Albatross' (very similar) -> triggers similarity warning
        Livewire::test(MaterialManagement::class)
            ->call('openMaterialModal')
            ->set('materialForm.name', 'Albatross')
            ->set('materialForm.productCategoryId', $this->lembarCategory->id)
            ->set('materialForm.min_stock', 5)
            ->call('saveMaterial')
            ->assertHasNoErrors()
            ->assertSet('showSimilarityWarning', true)
            ->assertSet('similarGlobalMaterialName', 'Albatros')
            // Bypass warning and force save
            ->call('bypassSimilarityAndSave')
            ->assertHasNoErrors()
            ->assertSet('showSimilarityWarning', false)
            ->assertSet('forceSaveSimilar', false);

        $this->assertDatabaseHas('materials', [
            'name' => 'Albatross',
        ]);
    }

    /**
     * Database transaction rollback verification on creation failure midway.
     */
    public function test_material_creation_rolls_back_entirely_on_ledger_failure(): void
    {
        $this->actingAs($this->managerA);

        // We listen to the saving event on StockLedger and throw an exception to simulate database crash midway
        StockLedger::saving(function () {
            throw new \RuntimeException('Mock Database Exception midway');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mock Database Exception midway');

        $inventoryService = new InventoryService;
        $material = $inventoryService->createGlobalMaterial([
            'name' => 'Failing Material',
            'product_category_id' => $this->pcsCategory->id,
            'cost' => 1000.0,
            'price' => 2000.0,
        ]);

        $inventoryService->initializeBranchStock($material->id, $this->branchA->id, 10.0, 2.0);

        // Ensure database did not persist the material record or the stock junction footprint
        $this->assertDatabaseMissing('materials', [
            'name' => 'Failing Material',
        ]);
        $this->assertDatabaseMissing('material_branch_stock', [
            'branch_id' => $this->branchA->id,
        ]);
    }
}
