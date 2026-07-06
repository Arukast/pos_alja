<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\CategoryManagement;
use App\Models\Attribute;
use App\Models\AttributeType;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\ProductCategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerUser;

    private User $managerUser;

    private User $cashierUser;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::create([
            'id' => (string) Str::uuid(),
            'name' => 'Branch A',
            'code' => 'BRA',
        ]);

        $this->ownerUser = User::factory()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'role' => 'owner',
            'branch_id' => $this->branch->id,
        ]);

        $this->managerUser = User::factory()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Manager',
            'email' => 'manager@example.com',
            'role' => 'manager',
            'branch_id' => $this->branch->id,
        ]);

        $this->cashierUser = User::factory()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Test Cashier',
            'email' => 'cashier@example.com',
            'role' => 'cashier',
            'branch_id' => $this->branch->id,
        ]);
    }

    /**
     * Test ProductCategory model's constraint validation method.
     */
    public function test_category_attribute_allowed_check(): void
    {
        $category = ProductCategory::create([
            'id' => (string) Str::uuid(),
            'name' => 'Category A',
            'calculation_archetype' => 'per_piece',
            'allowed_attribute_types' => ['cut'],
        ]);

        $cutAttribute = new Attribute(['type' => 'cut', 'name' => 'Seaming']);
        $finishingAttribute = new Attribute(['type' => 'finishing', 'name' => 'Glossy']);

        $this->assertTrue($category->isAttributeAllowed($cutAttribute));
        $this->assertFalse($category->isAttributeAllowed($finishingAttribute));

        // Test with string input
        $this->assertTrue($category->isAttributeAllowed('cut'));
        $this->assertFalse($category->isAttributeAllowed('finishing'));

        // Category with empty constraints allows all
        $openCategory = ProductCategory::create([
            'id' => (string) Str::uuid(),
            'name' => 'Category B',
            'calculation_archetype' => 'per_piece',
            'allowed_attribute_types' => null,
        ]);

        $this->assertTrue($openCategory->isAttributeAllowed($cutAttribute));
        $this->assertTrue($openCategory->isAttributeAllowed($finishingAttribute));
    }

    /**
     * Test Service layer CRUD.
     */
    public function test_service_can_create_update_delete_category(): void
    {
        $this->actingAs($this->ownerUser);
        $service = new ProductCategoryService;

        // 1. Create
        $category = $service->createCategory([
            'name' => 'Service Category',
            'calculation_archetype' => 'tiered_volume',
            'allowed_attribute_types' => ['finishing', 'display'],
        ]);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Service Category',
            'calculation_archetype' => 'tiered_volume',
        ]);
        $this->assertEquals(['finishing', 'display'], $category->fresh()->allowed_attribute_types);

        // 2. Update
        $service->updateCategory($category->id, [
            'name' => 'Updated Service Category',
            'calculation_archetype' => 'surface_area',
            'allowed_attribute_types' => ['cut'],
        ]);

        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Updated Service Category',
            'calculation_archetype' => 'surface_area',
        ]);
        $this->assertEquals(['cut'], $category->fresh()->allowed_attribute_types);

        // 3. Delete
        $service->deleteCategory($category->id);
        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    /**
     * Test authorization bounds.
     */
    public function test_cashier_cannot_access_category_management(): void
    {
        $this->actingAs($this->cashierUser);

        Livewire::test(CategoryManagement::class)
            ->assertStatus(403);
    }

    /**
     * Test Livewire component integration.
     */
    public function test_manager_can_manage_categories_via_livewire(): void
    {
        $this->actingAs($this->managerUser);

        $component = Livewire::test(CategoryManagement::class)
            ->assertViewHas('categoriesList')
            ->call('openCategoryModal')
            ->set('categoryForm.name', 'Livewire Category')
            ->set('categoryForm.calculation_archetype', 'surface_area')
            ->set('categoryForm.allowed_attribute_types', ['cut', 'display'])
            ->call('saveCategory');

        $category = ProductCategory::where('name', 'Livewire Category')->first();
        $this->assertNotNull($category);
        $this->assertEquals('surface_area', $category->calculation_archetype);
        $this->assertEquals(['cut', 'display'], $category->allowed_attribute_types);

        // Update
        Livewire::test(CategoryManagement::class)
            ->call('editCategory', $category->id)
            ->set('categoryForm.name', 'Updated Livewire Category')
            ->call('saveCategory');

        $this->assertEquals('Updated Livewire Category', $category->fresh()->name);

        // Delete
        Livewire::test(CategoryManagement::class)
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    /**
     * Test managing attribute types dynamically via Livewire component.
     */
    public function test_manager_can_manage_attribute_types_via_livewire(): void
    {
        $this->actingAs($this->managerUser);

        // 1. Create a custom attribute type
        Livewire::test(CategoryManagement::class)
            ->call('openTypeModal')
            ->set('typeName', 'emboss')
            ->set('typeCalculationArchetype', 'per_piece')
            ->call('saveType');

        $this->assertDatabaseHas('attribute_types', [
            'name' => 'emboss',
            'calculation_archetype' => 'per_piece',
        ]);

        $type = AttributeType::where('name', 'emboss')->first();
        $this->assertNotNull($type);

        // 2. Delete custom attribute type
        Livewire::test(CategoryManagement::class)
            ->call('deleteType', $type->id);

        $this->assertDatabaseMissing('attribute_types', [
            'id' => $type->id,
        ]);
    }
}
