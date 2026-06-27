<?php

namespace App\Livewire\Dashboards;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Pricing;
use App\Models\StockLedger;
use App\Models\User;
use App\Services\InventoryService;
use Livewire\Attributes\Url;
use Livewire\Component;

class Manager extends Component
{
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    // Active tab
    #[Url(as: 'tab')]
    public string $activeTab = 'inventory';

    // Add stock form properties
    public string $selectedMaterialId = '';

    public string $addStockQuantity = '';

    public string $addStockNotes = '';

    // Alert feedback
    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetErrorBag();
        $this->showSuccessAlert = false;
    }

    public function addMaterialStock(): void
    {
        $this->validate([
            'selectedMaterialId' => 'required|string|uuid|exists:materials,id',
            'addStockQuantity' => 'required|numeric|min:0.01',
            'addStockNotes' => 'nullable|string|max:255',
        ]);

        $material = Material::findOrFail($this->selectedMaterialId);

        if (auth()->user()->branch_id && $material->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $inventoryService = new InventoryService;
        $inventoryService->addStock($material, (float) $this->addStockQuantity, $this->addStockNotes);

        $this->reset(['selectedMaterialId', 'addStockQuantity', 'addStockNotes']);

        $this->successMessage = 'Inventory stock restocked successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $branchId = auth()->user()->branch_id ?: Branch::value('id');

        // 1. Dynamic Quick Stat Counters
        $materialCount = Material::where('branch_id', $branchId)->count();
        $machineCount = Machine::where('branch_id', $branchId)->count();
        $staffCount = User::where('branch_id', $branchId)->count();
        $pricingRulesCount = Pricing::where('branch_id', $branchId)->count();

        // 2. Load stock ledger log history
        $stockLogs = StockLedger::with('material')
            ->whereHas('material', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // 3. Load entire material inventory list
        $materialsList = Material::where('branch_id', $branchId)
            ->orderBy('current_stock', 'asc') // prioritize low stock
            ->limit(20)
            ->get();

        // 4. Load all materials for the dropdown selection
        $allMaterials = Material::where('branch_id', $branchId)->orderBy('name')->get();

        // 5. Recent Expenses
        $expenses = Expense::where('branch_id', $branchId)->orderBy('created_at', 'desc')->limit(8)->get();

        return view('livewire.dashboards.manager', compact(
            'materialCount',
            'machineCount',
            'staffCount',
            'pricingRulesCount',
            'stockLogs',
            'materialsList',
            'allMaterials',
            'expenses'
        ));
    }
}
