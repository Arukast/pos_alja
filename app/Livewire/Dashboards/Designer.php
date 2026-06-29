<?php

declare(strict_types=1);

namespace App\Livewire\Dashboards;

use App\Events\OrderUpdated;
use App\Exceptions\PricingNotFoundException;
use App\Livewire\Forms\CalculatorForm;
use App\Livewire\Forms\CustomerForm;
use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\MachinePricing;
use App\Models\Material;
use App\Models\Order;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CustomerService;
use App\Services\OrderService;
use App\Services\PricingService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Designer extends Component
{
    use WithPagination;

    #[Url(as: 'tab')]
    public string $activeTab = 'dashboard';

    public string $invoiceSearch = '';

    // Form Objects
    public CustomerForm $customerForm;

    public CalculatorForm $calculatorForm;

    // Properties accessed inside @island views
    public $productCategories;

    public $products;

    public $materials;

    public $machines;

    public $finishings;

    public $cuts;

    public $displays;

    public $availableAttributes;

    public bool $hasMachines = false;

    public bool $hasFinishings = false;

    public bool $hasCuts = false;

    public bool $hasDisplays = false;

    public bool $hasSides = false;

    public bool $hasFinishingSides = false;

    public bool $hasLength = false;

    public bool $hasWidth = false;

    // Remaining basic properties
    public string $customerId = '';

    public string $deadline = '';

    public string $source = 'Offline';

    public bool $showNewCustomerForm = false;

    // Manual price overrides
    public bool $isManualPrice = false;

    public int $manualPrice = 0;

    public int $manualCost = 0;

    // Active calculations
    public float $calcPrice = 0.0;

    public float $calcCost = 0.0;

    public float $calcSubtotal = 0.0;

    public ?string $calcPricingId = null;

    public bool $isCalculated = false;

    public string $calcError = '';

    // Order summary
    public array $items = [];

    public int $discount = 0;

    // Alert feedback
    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    public function mount(): void
    {
        $this->deadline = now()->addDay()->format('Y-m-d\TH:i');
        $this->productCategories = collect();
        $this->products = collect();
        $this->materials = collect();
        $this->machines = collect();
        $this->finishings = collect();
        $this->cuts = collect();
        $this->displays = collect();
    }

    public function selectCustomer(string $id): void
    {
        $this->customerId = $id;
        $this->customerForm->search = Customer::find($id)->name ?? '';
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, [
            'calculatorForm.productCategoryId', 'calculatorForm.materialId', 'calculatorForm.machineId',
            'calculatorForm.finishingId', 'calculatorForm.cutId', 'calculatorForm.displayId',
            'calculatorForm.sides', 'calculatorForm.finishingSides', 'calculatorForm.quantity',
            'calculatorForm.width', 'calculatorForm.length', 'isManualPrice', 'manualPrice', 'manualCost',
        ])) {
            $this->calculateQuote();
        }
    }

    public function updatedCalculatorFormProductCategoryId(): void
    {
        $this->calculatorForm->materialId = '';
        $this->reset(['isCalculated', 'calcPrice', 'calcCost', 'calcSubtotal', 'calcPricingId', 'calcError']);
        $this->calculateQuote();
    }

    public function createCustomer(): void
    {
        $this->customerForm->validate();

        $customerService = new CustomerService;
        $customer = $customerService->createCustomer([
            'name' => $this->customerForm->name,
            'phone' => $this->customerForm->phone,
            'address' => $this->customerForm->address,
            'email' => $this->customerForm->email,
            'type' => $this->customerForm->type,
        ], auth()->user());

        $this->customerId = $customer->id;
        $this->customerForm->search = $customer->name;
        $this->showNewCustomerForm = false;

        $this->customerForm->reset(['name', 'phone', 'address', 'email']);
    }

    public function calculateQuote(): void
    {
        $this->calcError = '';
        $this->isCalculated = false;

        if (! $this->calculatorForm->productCategoryId || ! $this->calculatorForm->materialId) {
            return;
        }

        $product = Product::where('product_category_id', $this->calculatorForm->productCategoryId)->first();
        if (! $product) {
            $this->calcError = 'No product associated with this category.';

            return;
        }

        if ($this->isManualPrice) {
            // Manual pricing override bypasses the matrix lookups
            $this->calcPrice = $this->manualPrice;
            $this->calcCost = $this->manualCost;

            if ($product->pricing_type === 'area') {
                $area = ($this->calculatorForm->width > 0 && $this->calculatorForm->length > 0) ? ($this->calculatorForm->width * $this->calculatorForm->length) : 1.0;
                $this->calcSubtotal = (int) round($this->calcPrice * $this->calculatorForm->quantity * $area);
            } else {
                $this->calcSubtotal = $this->calcPrice * $this->calculatorForm->quantity;
            }

            $this->calcPricingId = null;
            $this->isCalculated = true;

            return;
        }

        try {
            // Fetch customer type if selected to apply tiered pricing
            $custType = 'Reguler';
            if ($this->customerId) {
                $custType = Customer::find($this->customerId)->type ?? 'Reguler';
            }

            $finishingId = $this->calculatorForm->finishingId ?: null;
            $cutId = $this->calculatorForm->cutId ?: null;
            $displayId = $this->calculatorForm->displayId ?: null;

            $branchId = auth()->user()->branch_id ?: (Branch::first()->id ?? null);

            $pricingService = new PricingService;
            $quote = $pricingService->calculateQuote([
                'product_id' => $product->id,
                'material_id' => $this->calculatorForm->materialId,
                'machine_id' => $this->calculatorForm->machineId ?: null,
                'finishing_id' => $finishingId,
                'cut_id' => $cutId,
                'display_id' => $displayId,
                'sides' => $this->calculatorForm->sides ?: 1,
                'finishing_sides' => $this->calculatorForm->finishingSides ?: 0,
                'quantity' => $this->calculatorForm->quantity ?: 1,
                'width' => $this->calculatorForm->width ?: 0,
                'length' => $this->calculatorForm->length ?: 0,
                'customer_type' => $custType,
                'branch_id' => $branchId,
            ]);

            $this->calcPrice = $quote['unit_price'];
            $this->calcCost = $quote['unit_cost'];
            $this->calcSubtotal = $quote['subtotal'];
            $this->calcPricingId = $quote['pricing_id'];
            $this->isCalculated = true;
        } catch (PricingNotFoundException $e) {
            $this->calcError = 'Pricing configuration not found for these parameters: '.json_encode($e->getParams());
        } catch (\Throwable $e) {
            $this->calcError = $e->getMessage();
        }
    }

    public function addItem(): void
    {
        $this->calculateQuote();

        if (! $this->isCalculated) {
            return;
        }

        $product = Product::where('product_category_id', $this->calculatorForm->productCategoryId)->first();
        if (! $product) {
            return;
        }
        $finishingId = $this->calculatorForm->finishingId ?: null;
        $cutId = $this->calculatorForm->cutId ?: null;
        $displayId = $this->calculatorForm->displayId ?: null;

        $productName = $product->name;
        $materialName = Material::find($this->calculatorForm->materialId)->name;
        $machineName = $this->calculatorForm->machineId ? Machine::find($this->calculatorForm->machineId)->name : 'N/A';
        $finishingName = $finishingId ? Attribute::find($finishingId)->name : 'N/A';
        $cutName = $cutId ? Attribute::find($cutId)->name : 'N/A';
        $displayName = $displayId ? Attribute::find($displayId)->name : 'N/A';

        $specDetails = ["Material: {$materialName}"];
        if ($this->calculatorForm->machineId) {
            $specDetails[] = "Machine: {$machineName}";
        }
        if ($finishingId) {
            $specDetails[] = "Finishing: {$finishingName}";
        }
        if ($cutId) {
            $specDetails[] = "Cutting: {$cutName}";
        }
        if ($displayId) {
            $specDetails[] = "Display: {$displayName}";
        }
        if ($product->pricing_type === 'area') {
            $specDetails[] = "Size: {$this->calculatorForm->length}m x {$this->calculatorForm->width}m";
        }
        if ($this->isManualPrice) {
            $specDetails[] = 'Manual Price Override';
        }

        $areaMultiplier = $product->pricing_type === 'area' ? ($this->calculatorForm->width * $this->calculatorForm->length) : 1;
        $totalItemCost = (int) round($this->calcCost * $this->calculatorForm->quantity * $areaMultiplier);

        $this->items[] = [
            'id' => uniqid(),
            'product_id' => $product->id,
            'product_name' => $productName,
            'material_id' => $this->calculatorForm->materialId,
            'machine_id' => $this->calculatorForm->machineId ?: null,
            'finishing_id' => $finishingId,
            'cut_id' => $cutId,
            'display_id' => $displayId,
            'sides' => $this->calculatorForm->sides,
            'finishing_sides' => $this->calculatorForm->finishingSides,
            'quantity' => $this->calculatorForm->quantity,
            'width' => $this->calculatorForm->width,
            'length' => $this->calculatorForm->length,
            'file_name' => $this->calculatorForm->fileName ?: 'Unnamed_File',
            'notes' => $this->calculatorForm->notes ?: '',
            'pricing_id' => $this->calcPricingId,
            'unit_price' => $this->calcPrice,
            'unit_cost' => $this->calcCost,
            'subtotal' => $this->calcSubtotal,
            'cost' => $totalItemCost,
            'profit' => $this->calcSubtotal - $totalItemCost,
            'details_string' => implode(', ', $specDetails),
            'is_manual_price' => $this->isManualPrice,
        ];

        $this->calculatorForm->reset([
            'productCategoryId', 'materialId', 'machineId', 'finishingId', 'cutId', 'displayId',
            'sides', 'finishingSides', 'fileName', 'notes',
        ]);

        $this->reset([
            'isManualPrice', 'manualPrice', 'manualCost',
            'calcPrice', 'calcCost', 'calcSubtotal', 'calcPricingId', 'isCalculated', 'calcError',
        ]);

        $this->calculatorForm->quantity = 1;
        $this->calculatorForm->width = 1.0;
        $this->calculatorForm->length = 1.0;
    }

    public function removeItem(string $id): void
    {
        $this->items = array_values(array_filter($this->items, fn ($item) => $item['id'] !== $id));
    }

    public function submitOrder(): void
    {
        if (! $this->customerId) {
            $this->addError('customerId', 'Please select or create a customer.');

            return;
        }

        if (empty($this->items)) {
            $this->addError('items', 'Please add at least one item.');

            return;
        }

        $activeBranchId = auth()->user()->branch_id ?: (Branch::first()->id ?? null);

        $orderService = new OrderService;
        $orderService->createInvoice([
            'customer_id' => $this->customerId,
            'items' => $this->items,
            'discount' => $this->discount,
            'deadline' => $this->deadline,
            'source' => $this->source,
            'user_id' => auth()->user()->id,
            'branch_id' => $activeBranchId,
        ]);

        event(new OrderUpdated);

        $this->reset(['items', 'customerId', 'discount']);
        $this->customerForm->reset();
        $this->deadline = now()->addDay()->format('Y-m-d\TH:i');

        $this->successMessage = 'Order placed successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $customers = Customer::query()
            ->when($this->customerForm->search, function ($q) {
                $q->where('name', 'like', '%'.$this->customerForm->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->customerForm->search.'%');
            })
            ->orderBy('name')
            ->limit(8)
            ->get();

        $this->productCategories = ProductCategory::all();
        $this->products = Product::all();

        $this->materials = collect();
        $this->machines = collect();
        $this->finishings = collect();
        $this->cuts = collect();
        $this->displays = collect();
        $this->availableAttributes = collect();

        $this->hasMachines = false;
        $this->hasFinishings = false;
        $this->hasCuts = false;
        $this->hasDisplays = false;
        $this->hasSides = false;
        $this->hasFinishingSides = false;
        $this->hasLength = false;
        $this->hasWidth = false;

        $branchId = auth()->user()->branch_id ?: (Branch::first()->id ?? null);

        if ($this->calculatorForm->productCategoryId && $branchId) {
            $category = ProductCategory::find($this->calculatorForm->productCategoryId);
            if ($category) {
                $this->hasLength = in_array($category->calculation_archetype, ['surface_area', 'linear_dimension']);
                $this->hasWidth = $category->calculation_archetype === 'surface_area';
                $this->hasSides = in_array($category->name, ['Merchandise', 'Print Kertas', 'ID Card', 'Stempel', 'Kartu Nama', 'NOTA']);
            }

            $productIds = Product::where('product_category_id', $this->calculatorForm->productCategoryId)->pluck('id');
            $pricings = Pricing::whereIn('product_id', $productIds)->where('branch_id', $branchId)->get();
            $pricingIds = $pricings->pluck('id');

            $materialIds = $pricings->pluck('material_id')->unique();

            // Load only materials registered under the selected product category that have pricing configured for this branch
            $this->materials = Material::where('product_category_id', $this->calculatorForm->productCategoryId)
                ->whereIn('id', $materialIds)
                ->get();

            $machineIds = MachinePricing::whereIn('pricing_id', $pricingIds)->pluck('machine_id')->unique()->filter();
            if ($machineIds->isNotEmpty()) {
                $this->machines = Machine::whereIn('id', $machineIds)->where('branch_id', $branchId)->get();
                $this->hasMachines = true;
            }

            // Load all attributes registered to the active branch
            $this->availableAttributes = Attribute::where('branch_id', $branchId)->get();
            $this->finishings = $this->availableAttributes->where('type', 'finishing');
            $this->hasFinishings = $this->finishings->isNotEmpty();

            $this->cuts = $this->availableAttributes->where('type', 'cut');
            $this->hasCuts = $this->cuts->isNotEmpty();

            $this->displays = $this->availableAttributes->where('type', 'display');
            $this->hasDisplays = $this->displays->isNotEmpty();

            $this->hasFinishingSides = $this->hasFinishings;
        }

        $placedTodayCount = 0;
        $grandTotalToday = 0;
        $cartItemsCount = count($this->items);

        if ($this->activeTab === 'dashboard') {
            $placedTodayCount = Invoice::where('branch_id', $branchId)
                ->where('designer_id', auth()->user()->id)
                ->whereDate('created_at', now()->toDateString())
                ->count();

            $grandTotalToday = Invoice::where('branch_id', $branchId)
                ->where('designer_id', auth()->user()->id)
                ->whereDate('created_at', now()->toDateString())
                ->sum('grand_total');

            $activeInvoices = Invoice::with('customer')
                ->where('branch_id', $branchId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } elseif ($this->activeTab === 'order_list') {
            $activeInvoices = Invoice::with('customer')
                ->where('branch_id', $branchId)
                ->when($this->invoiceSearch, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('id', 'like', '%'.$this->invoiceSearch.'%')
                            ->orWhereHas('customer', function ($cust) {
                                $cust->where('name', 'like', '%'.$this->invoiceSearch.'%')
                                    ->orWhere('phone', 'like', '%'.$this->invoiceSearch.'%');
                            });
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            $activeInvoices = collect();
        }

        return view('livewire.dashboards.designer', compact(
            'customers',
            'activeInvoices',
            'placedTodayCount',
            'grandTotalToday',
            'cartItemsCount'
        ));
    }

    public function updatingInvoiceSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActiveTab(): void
    {
        $this->resetPage();
    }
}
