<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Events\OrderUpdated;
use App\Livewire\Forms\MaterialForm;
use App\Livewire\Forms\RestockForm;
use App\Models\Branch;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MaterialManagement extends Component
{
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
        'branchSelected' => 'handleBranchSelected',
    ];

    public string $branchId = '';

    public RestockForm $restockForm;

    public MaterialForm $materialForm;

    public bool $isCreatingMaterial = false;

    public ?string $editingMaterialId = null;

    public bool $showActivationWizard = false;

    public ?string $duplicateGlobalMaterialId = null;

    public bool $showSimilarityWarning = false;

    public ?string $similarGlobalMaterialId = null;

    public string $similarGlobalMaterialName = '';

    public bool $forceSaveSimilar = false;

    public bool $showDeleted = false;

    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    public function mount(?string $branchId = null): void
    {
        $this->branchId = $branchId ?: (auth()->user()->branch_id ?? Branch::value('id') ?? 'all');
    }

    public function handleBranchSelected(string $branchId): void
    {
        $this->branchId = $branchId;
        $this->resetErrorBag();
        $this->showSuccessAlert = false;
    }

    public function addMaterialStock(): void
    {
        $this->restockForm->validate();

        $material = Material::findOrFail($this->restockForm->selectedMaterialId);

        $targetBranchId = $this->branchId === 'all' ? Branch::value('id') : $this->branchId;

        $hasStock = MaterialBranchStock::where('material_id', $material->id)
            ->where('branch_id', $targetBranchId)
            ->exists();

        if (auth()->user()->branch_id && ! $hasStock) {
            abort(403, 'Unauthorized branch access.');
        }

        $inventoryService = new InventoryService;
        $inventoryService->addStock(
            $material,
            (float) $this->restockForm->addStockQuantity,
            $this->restockForm->addStockNotes,
            $targetBranchId
        );

        $this->restockForm->reset();

        $this->successMessage = 'Inventory stock restocked successfully!';
        $this->showSuccessAlert = true;
    }

    public function openMaterialModal(): void
    {
        $this->editingMaterialId = null;
        $this->showActivationWizard = false;
        $this->duplicateGlobalMaterialId = null;
        $this->showSimilarityWarning = false;
        $this->similarGlobalMaterialId = null;
        $this->similarGlobalMaterialName = '';
        $this->forceSaveSimilar = false;
        $this->materialForm->resetFields();
        $this->resetErrorBag();
        $this->isCreatingMaterial = true;
        $this->showSuccessAlert = false;
    }

    public function editMaterial(string $id): void
    {
        $material = Material::findOrFail($id);

        $targetBranchId = $this->branchId === 'all' ? Branch::value('id') : $this->branchId;

        $stock = MaterialBranchStock::where('material_id', $id)
            ->where('branch_id', $targetBranchId)
            ->first();

        $this->editingMaterialId = $material->id;
        $this->showActivationWizard = false;
        $this->duplicateGlobalMaterialId = null;
        $this->showSimilarityWarning = false;
        $this->similarGlobalMaterialId = null;
        $this->similarGlobalMaterialName = '';
        $this->forceSaveSimilar = false;

        $this->materialForm->name = $material->name;
        $this->materialForm->category = $material->category;
        $this->materialForm->type = $material->type ?? '';
        $this->materialForm->dimensions = $material->dimensions ?? '';
        $this->materialForm->cost = (float) $material->cost;
        $this->materialForm->price = (float) $material->price;
        $this->materialForm->current_stock = (float) ($stock->current_stock ?? 0.0);
        $this->materialForm->min_stock = (float) ($stock->min_stock ?? 0.0);

        $this->resetErrorBag();
        $this->isCreatingMaterial = true;
        $this->showSuccessAlert = false;
    }

    public function deleteMaterial(string $id): void
    {
        $role = auth()->user()->role;
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        $inventoryService = new InventoryService;
        $inventoryService->deleteGlobalMaterial($id);

        event(new OrderUpdated);

        $this->successMessage = 'Material deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function closeMaterialModal(): void
    {
        $this->editingMaterialId = null;
        $this->showActivationWizard = false;
        $this->duplicateGlobalMaterialId = null;
        $this->showSimilarityWarning = false;
        $this->similarGlobalMaterialId = null;
        $this->similarGlobalMaterialName = '';
        $this->forceSaveSimilar = false;
        $this->materialForm->resetFields();
        $this->resetErrorBag();
        $this->isCreatingMaterial = false;
    }

    public function activateExistingMaterial(): void
    {
        $role = auth()->user()->role;
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        if (! $this->duplicateGlobalMaterialId) {
            return;
        }

        $targetBranchId = $this->branchId === 'all' ? Branch::value('id') : $this->branchId;

        $inventoryService = new InventoryService;
        $inventoryService->initializeBranchStock(
            $this->duplicateGlobalMaterialId,
            $targetBranchId,
            0.0,
            (float) $this->materialForm->min_stock
        );

        $this->isCreatingMaterial = false;
        $this->showActivationWizard = false;
        $this->duplicateGlobalMaterialId = null;
        $this->materialForm->resetFields();

        event(new OrderUpdated);

        $this->successMessage = 'Material activated successfully for your branch!';
        $this->showSuccessAlert = true;
    }

    public function acceptSimilarMaterial(): void
    {
        $role = auth()->user()->role;
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        if (! $this->similarGlobalMaterialId) {
            return;
        }

        $targetBranchId = $this->branchId === 'all' ? Branch::value('id') : $this->branchId;

        if ($role === 'owner') {
            $this->editMaterial($this->similarGlobalMaterialId);
            $this->showSimilarityWarning = false;
            $this->similarGlobalMaterialId = null;
            $this->similarGlobalMaterialName = '';

            return;
        }

        $hasJunction = MaterialBranchStock::where('material_id', $this->similarGlobalMaterialId)
            ->where('branch_id', $targetBranchId)
            ->exists();

        if ($hasJunction) {
            $this->addError('materialForm.name', 'This material is already registered and active in your branch.');
            $this->showSimilarityWarning = false;
            $this->similarGlobalMaterialId = null;
            $this->similarGlobalMaterialName = '';

            return;
        }

        $inventoryService = new InventoryService;
        $inventoryService->initializeBranchStock(
            $this->similarGlobalMaterialId,
            $targetBranchId,
            0.0,
            (float) $this->materialForm->min_stock
        );

        $this->isCreatingMaterial = false;
        $this->showSimilarityWarning = false;
        $this->similarGlobalMaterialId = null;
        $this->similarGlobalMaterialName = '';
        $this->materialForm->resetFields();

        event(new OrderUpdated);

        $this->successMessage = 'Material activated successfully for your branch!';
        $this->showSuccessAlert = true;
    }

    public function bypassSimilarityAndSave(): void
    {
        $this->forceSaveSimilar = true;
        $this->showSimilarityWarning = false;
        $this->saveMaterial();
    }

    public function saveMaterial(): void
    {
        $role = auth()->user()->role;
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        $payload = $this->materialForm->validateAndGetPayload();

        $inventoryService = new InventoryService;
        $targetBranchId = $this->branchId === 'all' ? Branch::value('id') : $this->branchId;

        $existing = Material::whereRaw('LOWER(name) = ?', [strtolower($payload['name'])])
            ->whereNull('deleted_at')
            ->first();

        if ($this->editingMaterialId) {
            if ($existing && $existing->id !== $this->editingMaterialId) {
                $this->addError('materialForm.name', 'This material name is already registered to another material.');

                return;
            }

            $material = Material::findOrFail($this->editingMaterialId);
            $material->update([
                'name' => $payload['name'],
                'category' => $payload['category'],
                'type' => $payload['type'],
                'dimensions' => $payload['dimensions'],
                'cost' => $payload['cost'],
                'price' => $payload['price'],
            ]);

            MaterialBranchStock::updateOrCreate(
                ['material_id' => $material->id, 'branch_id' => $targetBranchId],
                ['current_stock' => $payload['current_stock'], 'min_stock' => $payload['min_stock']]
            );

            $this->successMessage = 'Material updated successfully!';
        } else {
            if ($existing) {
                if ($role === 'owner') {
                    $this->addError('materialForm.name', 'This material name already exists in the global catalog.');

                    return;
                }

                $hasJunction = MaterialBranchStock::where('material_id', $existing->id)
                    ->where('branch_id', $targetBranchId)
                    ->exists();

                if ($hasJunction) {
                    $this->addError('materialForm.name', 'This material is already registered and active in your branch.');

                    return;
                }

                $this->showActivationWizard = true;
                $this->duplicateGlobalMaterialId = $existing->id;

                return;
            }

            if (! $this->forceSaveSimilar) {
                $inputName = strtolower(trim($payload['name']));
                $allGlobal = Material::whereNull('deleted_at')->get();
                foreach ($allGlobal as $mat) {
                    $dbName = strtolower(trim($mat->name));
                    $distance = levenshtein($inputName, $dbName);
                    $maxLength = max(strlen($inputName), strlen($dbName));
                    $similarity = $maxLength > 0 ? (1 - ($distance / $maxLength)) * 100 : 100;

                    if ($similarity >= 75.0) {
                        $this->showSimilarityWarning = true;
                        $this->similarGlobalMaterialId = $mat->id;
                        $this->similarGlobalMaterialName = $mat->name;

                        return;
                    }
                }
            }

            if ($role === 'owner') {
                $material = $inventoryService->createGlobalMaterial($payload);
                $branches = Branch::all();
                foreach ($branches as $branch) {
                    $inventoryService->initializeBranchStock($material->id, $branch->id, 0.0, $payload['min_stock']);
                }
                $this->successMessage = 'Material registered globally across all branches!';
            } else {
                $material = $inventoryService->createGlobalMaterial($payload);
                $inventoryService->initializeBranchStock($material->id, $targetBranchId, $payload['current_stock'], $payload['min_stock']);
                $this->successMessage = 'Material registered successfully!';
            }
        }

        $this->editingMaterialId = null;
        $this->showSimilarityWarning = false;
        $this->similarGlobalMaterialId = null;
        $this->similarGlobalMaterialName = '';
        $this->forceSaveSimilar = false;
        $this->materialForm->resetFields();
        $this->isCreatingMaterial = false;

        event(new OrderUpdated);

        $this->showSuccessAlert = true;
    }

    public function restoreMaterial(string $id): void
    {
        $role = auth()->user()->role;
        if (! in_array($role, ['manager', 'owner'])) {
            abort(403, 'Unauthorized action.');
        }

        $material = Material::withTrashed()->findOrFail($id);
        $material->restore();

        $targetBranchId = $this->branchId === 'all' ? Branch::value('id') : $this->branchId;

        if ($role === 'owner') {
            $branches = Branch::all();
            foreach ($branches as $branch) {
                MaterialBranchStock::firstOrCreate(
                    ['material_id' => $material->id, 'branch_id' => $branch->id],
                    ['current_stock' => 0.0, 'min_stock' => 5.0]
                );
            }
        } else {
            MaterialBranchStock::firstOrCreate(
                ['material_id' => $material->id, 'branch_id' => $targetBranchId],
                ['current_stock' => 0.0, 'min_stock' => 5.0]
            );
        }

        event(new OrderUpdated);

        $this->successMessage = 'Material restored successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $targetBranchId = $this->branchId === 'all' ? Branch::value('id') : $this->branchId;

        // Query active or deleted materials
        $materialsQuery = Material::query();

        if ($this->showDeleted) {
            $materialsQuery->onlyTrashed();
        }

        if ($this->branchId === 'all') {
            $materialsList = $materialsQuery->leftJoin('material_branch_stock', 'materials.id', '=', 'material_branch_stock.material_id')
                ->leftJoin('branches', 'material_branch_stock.branch_id', '=', 'branches.id')
                ->select('materials.*', 'material_branch_stock.current_stock', 'material_branch_stock.min_stock', 'branches.name as branch_name')
                ->orderBy('materials.name')
                ->get();
        } else {
            $materialsList = $materialsQuery->join('material_branch_stock', 'materials.id', '=', 'material_branch_stock.material_id')
                ->where('material_branch_stock.branch_id', $this->branchId)
                ->select('materials.*', 'material_branch_stock.current_stock', 'material_branch_stock.min_stock')
                ->orderBy('material_branch_stock.current_stock', 'asc')
                ->limit(20)
                ->get();
        }

        $allMaterials = Material::join('material_branch_stock', 'materials.id', '=', 'material_branch_stock.material_id')
            ->where('material_branch_stock.branch_id', $targetBranchId)
            ->select('materials.*', 'material_branch_stock.current_stock', 'material_branch_stock.min_stock')
            ->orderBy('materials.name')
            ->get();

        // Query inactive global profiles that can be activated
        $inactiveMaterials = Material::whereNull('deleted_at')
            ->whereNotExists(function ($query) use ($targetBranchId) {
                $query->select(DB::raw(1))
                    ->from('material_branch_stock')
                    ->whereColumn('material_branch_stock.material_id', 'materials.id')
                    ->where('material_branch_stock.branch_id', $targetBranchId);
            })
            ->orderBy('name')
            ->get();

        return view('livewire.admin.material-management', compact('materialsList', 'allMaterials', 'inactiveMaterials'));
    }
}
