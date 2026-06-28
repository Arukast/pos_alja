<?php

namespace App\Livewire\Dashboards;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class Manager extends Component
{
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    // Active tab
    #[Url(as: 'tab')]
    public string $activeTab = 'dashboard';

    // Add stock form properties
    public string $selectedMaterialId = '';

    public string $addStockQuantity = '';

    public string $addStockNotes = '';

    // Display & Finishing Attribute CRUD properties
    public ?string $attributeId = null;

    public string $attributeName = '';

    public string $attributeType = 'finishing'; // finishing, cut, display

    // Pricing CRUD properties
    public ?string $pricingId = null;

    public string $productId = '';

    public string $materialId = '';

    public string $customerType = 'Reguler'; // Reguler, Mitra

    public int $minQty = 1;

    public string $price = '';

    public string $cost = '';

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

    // Attribute CRUD Methods
    public function saveAttribute(): void
    {
        $this->validate([
            'attributeName' => 'required|string|max:255',
            'attributeType' => 'required|string|in:finishing,cut,display',
        ]);

        $branchId = auth()->user()->branch_id ?: Branch::value('id');

        if ($this->attributeId) {
            $attribute = Attribute::findOrFail($this->attributeId);
            if ($attribute->branch_id && $attribute->branch_id !== auth()->user()->branch_id) {
                abort(403, 'Unauthorized branch access.');
            }
            $attribute->update([
                'name' => $this->attributeName,
                'type' => $this->attributeType,
            ]);
            $this->successMessage = 'Attribute updated successfully!';
        } else {
            Attribute::create([
                'branch_id' => $branchId,
                'name' => $this->attributeName,
                'type' => $this->attributeType,
            ]);
            $this->successMessage = 'Attribute created successfully!';
        }

        $this->resetAttributeForm();
        $this->showSuccessAlert = true;
    }

    public function editAttribute(string $id): void
    {
        $attribute = Attribute::findOrFail($id);
        if ($attribute->branch_id && $attribute->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $this->attributeId = $attribute->id;
        $this->attributeName = $attribute->name;
        $this->attributeType = $attribute->type;
        $this->showSuccessAlert = false;
    }

    public function deleteAttribute(string $id): void
    {
        $attribute = Attribute::findOrFail($id);
        if ($attribute->branch_id && $attribute->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $attribute->delete();
        $this->successMessage = 'Attribute deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function resetAttributeForm(): void
    {
        $this->reset(['attributeId', 'attributeName', 'attributeType']);
        $this->attributeType = 'finishing';
        $this->resetErrorBag();
    }

    // Pricing CRUD Methods
    public function savePricing(): void
    {
        $this->validate([
            'productId' => 'required|string|uuid|exists:products,id',
            'materialId' => 'required|string|uuid|exists:materials,id',
            'customerType' => 'required|string|max:50',
            'minQty' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
        ]);

        $branchId = auth()->user()->branch_id ?: Branch::value('id');
        $priceVal = (float) $this->price;
        $costVal = (float) $this->cost;
        $profitVal = max(0.0, $priceVal - $costVal);

        if ($this->pricingId) {
            $pricing = Pricing::findOrFail($this->pricingId);
            if ($pricing->branch_id && $pricing->branch_id !== auth()->user()->branch_id) {
                abort(403, 'Unauthorized branch access.');
            }
            $pricing->update([
                'product_id' => $this->productId,
                'material_id' => $this->materialId,
                'customer_type' => $this->customerType,
                'min_qty' => $this->minQty,
                'price' => $priceVal,
                'cost' => $costVal,
                'profit' => $profitVal,
            ]);
            $this->successMessage = 'Pricing matrix rule updated successfully!';
        } else {
            Pricing::create([
                'branch_id' => $branchId,
                'product_id' => $this->productId,
                'material_id' => $this->materialId,
                'customer_type' => $this->customerType,
                'min_qty' => $this->minQty,
                'price' => $priceVal,
                'cost' => $costVal,
                'profit' => $profitVal,
            ]);
            $this->successMessage = 'Pricing matrix rule created successfully!';
        }

        $this->resetPricingForm();
        $this->showSuccessAlert = true;
    }

    public function editPricing(string $id): void
    {
        $pricing = Pricing::findOrFail($id);
        if ($pricing->branch_id && $pricing->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $this->pricingId = $pricing->id;
        $this->productId = $pricing->product_id;
        $this->materialId = $pricing->material_id;
        $this->customerType = $pricing->customer_type;
        $this->minQty = $pricing->min_qty;
        $this->price = (string) $pricing->price;
        $this->cost = (string) $pricing->cost;
        $this->showSuccessAlert = false;
    }

    public function deletePricing(string $id): void
    {
        $pricing = Pricing::findOrFail($id);
        if ($pricing->branch_id && $pricing->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $pricing->delete();
        $this->successMessage = 'Pricing matrix rule deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function resetPricingForm(): void
    {
        $this->reset(['pricingId', 'productId', 'materialId', 'customerType', 'minQty', 'price', 'cost']);
        $this->customerType = 'Reguler';
        $this->minQty = 1;
        $this->resetErrorBag();
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

        // 6. Load attributes (finishing, cut, display)
        $allAttributes = Attribute::where('branch_id', $branchId)->orderBy('name')->get();

        // 7. Load pricing matrices
        $pricingMatrices = Pricing::with(['product', 'material'])
            ->where('branch_id', $branchId)
            ->orderBy('min_qty')
            ->get();

        // 8. Products for pricing matrix builder
        $productsList = Product::orderBy('name')->get();

        // 9. Designer Performance Leaderboard (scoped to current branch and current month)
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $leaderQuery = Invoice::query()
            ->join('users', 'invoices.designer_id', '=', 'users.id')
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->select(
                'users.id as designer_id',
                'users.name',
                'branches.name as branch_name',
                DB::raw('count(invoices.id) as orders_count'),
                DB::raw('sum(invoices.grand_total) as total_value')
            )
            ->where('users.branch_id', $branchId)
            ->where('invoices.branch_id', $branchId)
            ->whereBetween('invoices.created_date', [$startDate, $endDate]);

        $leaders = $leaderQuery->groupBy('users.id', 'users.name', 'branches.name')
            ->orderBy('total_value', 'desc')
            ->get();

        // Get wasted material logs count for each designer
        $wasteQuery = WastedMaterial::query()
            ->select('designer_id', DB::raw('count(*) as waste_count'))
            ->where('branch_id', $branchId)
            ->whereBetween(DB::raw('DATE(logged_at)'), [$startDate, $endDate]);

        $wastes = $wasteQuery->groupBy('designer_id')
            ->pluck('waste_count', 'designer_id')
            ->toArray();

        // Map wastes, average order value (AOV) into the leaderboard collection
        $leaders = $leaders->map(function ($leader) use ($wastes) {
            $designerId = (string) $leader->getAttribute('designer_id');
            $ordersCount = (int) $leader->getAttribute('orders_count');
            $totalValue = (float) $leader->getAttribute('total_value');

            $leaderObj = new \stdClass;
            $leaderObj->designer_id = $designerId;
            $leaderObj->name = (string) $leader->getAttribute('name');
            $leaderObj->branch_name = $leader->getAttribute('branch_name') ? (string) $leader->getAttribute('branch_name') : null;
            $leaderObj->orders_count = $ordersCount;
            $leaderObj->total_value = $totalValue;
            $leaderObj->waste_count = (int) ($wastes[$designerId] ?? 0);
            $leaderObj->aov = $ordersCount > 0 ? (float) ($totalValue / $ordersCount) : 0.0;

            return $leaderObj;
        });

        // Compute performance highlight cards
        $topRevenue = $leaders->first();
        $orderChampion = $leaders->sortByDesc('orders_count')->first();
        $topAov = $leaders->sortByDesc('aov')->first();
        $accuracyLeader = $leaders->filter(fn ($l) => $l->orders_count > 0)
            ->sortBy([
                ['waste_count', 'asc'],
                ['orders_count', 'desc'],
            ])->first();

        return view('livewire.dashboards.manager', compact(
            'materialCount',
            'machineCount',
            'staffCount',
            'pricingRulesCount',
            'stockLogs',
            'materialsList',
            'allMaterials',
            'expenses',
            'allAttributes',
            'pricingMatrices',
            'productsList',
            'leaders',
            'topRevenue',
            'orderChampion',
            'topAov',
            'accuracyLeader'
        ));
    }
}
