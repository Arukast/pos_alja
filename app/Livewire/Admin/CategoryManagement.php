<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Events\OrderUpdated;
use App\Livewire\Forms\CategoryForm;
use App\Models\AttributeType;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use Livewire\Component;

class CategoryManagement extends Component
{
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    public CategoryForm $categoryForm;

    public bool $isCreatingCategory = false;

    public ?string $editingCategoryId = null;

    public bool $isCreatingType = false;

    public string $typeName = '';

    public string $typeCalculationArchetype = 'per_piece';

    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    public function mount(): void
    {
        $role = auth()->user()->role ?? '';
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function openCategoryModal(): void
    {
        $this->editingCategoryId = null;
        $this->categoryForm->resetFields();
        $this->resetErrorBag();
        $this->isCreatingCategory = true;
        $this->showSuccessAlert = false;
    }

    public function editCategory(string $id): void
    {
        $category = ProductCategory::findOrFail($id);

        $this->editingCategoryId = $category->id;
        $this->categoryForm->name = $category->name;
        $this->categoryForm->calculation_archetype = $category->calculation_archetype;
        $this->categoryForm->allowed_attribute_types = $category->allowed_attribute_types ?? [];

        $this->resetErrorBag();
        $this->isCreatingCategory = true;
        $this->showSuccessAlert = false;
    }

    public function saveCategory(ProductCategoryService $categoryService): void
    {
        $role = auth()->user()->role ?? '';
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        $payload = $this->categoryForm->validateAndGetPayload();

        if ($this->editingCategoryId) {
            $categoryService->updateCategory($this->editingCategoryId, $payload);
            $this->successMessage = 'Product category updated successfully!';
        } else {
            $categoryService->createCategory($payload);
            $this->successMessage = 'Product category registered successfully!';
        }

        $this->editingCategoryId = null;
        $this->categoryForm->resetFields();
        $this->isCreatingCategory = false;

        event(new OrderUpdated);

        $this->showSuccessAlert = true;
    }

    public function deleteCategory(string $id, ProductCategoryService $categoryService): void
    {
        $role = auth()->user()->role ?? '';
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        $categoryService->deleteCategory($id);

        event(new OrderUpdated);

        $this->successMessage = 'Product category deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function closeCategoryModal(): void
    {
        $this->editingCategoryId = null;
        $this->categoryForm->resetFields();
        $this->resetErrorBag();
        $this->isCreatingCategory = false;
    }

    public function openTypeModal(): void
    {
        $this->typeName = '';
        $this->typeCalculationArchetype = 'per_piece';
        $this->resetErrorBag();
        $this->isCreatingType = true;
        $this->showSuccessAlert = false;
    }

    public function closeTypeModal(): void
    {
        $this->typeName = '';
        $this->typeCalculationArchetype = 'per_piece';
        $this->resetErrorBag();
        $this->isCreatingType = false;
    }

    public function saveType(): void
    {
        $role = auth()->user()->role ?? '';
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate([
            'typeName' => 'required|string|max:255|unique:attribute_types,name',
            'typeCalculationArchetype' => 'required|string|in:per_piece,surface_area,linear_dimension,tiered_volume',
        ]);

        AttributeType::create([
            'name' => strtolower(trim($this->typeName)),
            'calculation_archetype' => $this->typeCalculationArchetype,
        ]);

        $this->isCreatingType = false;
        $this->typeName = '';
        $this->typeCalculationArchetype = 'per_piece';

        event(new OrderUpdated);

        $this->successMessage = 'Attribute type created successfully!';
        $this->showSuccessAlert = true;
    }

    public function deleteType(string $id): void
    {
        $role = auth()->user()->role ?? '';
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        $type = AttributeType::findOrFail($id);
        $type->delete();

        event(new OrderUpdated);

        $this->successMessage = 'Attribute type deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $categoriesList = ProductCategory::orderBy('name')->get();
        $attributeTypesList = AttributeType::orderBy('name')->get();

        return view('livewire.admin.category-management', compact('categoriesList', 'attributeTypesList'));
    }
}
