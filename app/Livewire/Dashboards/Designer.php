<?php

namespace App\Livewire\Dashboards;

use App\Events\OrderUpdated;
use App\Exceptions\PricingNotFoundException;
use App\Models\Attribute;
use App\Models\AttributePricing;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\MachinePricing;
use App\Models\Material;
use App\Models\Order;
use App\Models\Pricing;
use App\Models\Product;
use App\Services\CustomerService;
use App\Services\OrderService;
use App\Services\PricingService;
use Livewire\Component;

class Designer extends Component
{
    // Order info
    public string $customerSearch = '';

    public string $customerId = '';

    public string $deadline = '';

    public string $source = 'Offline';

    // New customer form
    public bool $showNewCustomerForm = false;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $customerAddress = '';

    public string $customerEmail = '';

    public string $customerType = 'Reguler';

    // Calculator inputs
    public string $productId = '';

    public string $materialId = '';

    public string $machineId = '';

    public string $finishingId = '';

    public string $cutId = '';

    public string $displayId = '';

    public int $sides = 1;

    public int $finishingSides = 0;

    public int $quantity = 1;

    public float $width = 1.0;

    public float $length = 1.0;

    public string $fileName = '';

    public string $notes = '';

    // Manual price overrides
    public bool $isManualPrice = false;

    public int $manualPrice = 0;

    public int $manualCost = 0;

    // Active calculations
    public int $calcPrice = 0;

    public int $calcCost = 0;

    public int $calcSubtotal = 0;

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
    }

    public function selectCustomer(string $id): void
    {
        $this->customerId = $id;
        $this->customerSearch = Customer::find($id)->name ?? '';
    }

    public function updatedCustomerSearch(): void
    {
        $this->customerId = '';
    }

    public function createCustomer(): void
    {
        $this->validate([
            'customerName' => 'required|string|max:255',
            'customerPhone' => 'nullable|string|max:20',
            'customerAddress' => 'nullable|string',
            'customerEmail' => 'nullable|email|max:255',
            'customerType' => 'required|string|in:Reguler,Mitra',
        ]);

        $customerService = new CustomerService;
        $customer = $customerService->createCustomer([
            'name' => $this->customerName,
            'phone' => $this->customerPhone,
            'address' => $this->customerAddress,
            'email' => $this->customerEmail,
            'type' => $this->customerType,
        ], auth()->user());

        $this->customerId = $customer->id;
        $this->customerSearch = $customer->name;
        $this->showNewCustomerForm = false;

        $this->reset(['customerName', 'customerPhone', 'customerAddress', 'customerEmail']);
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, [
            'productId', 'materialId', 'machineId', 'finishingId',
            'cutId', 'displayId', 'sides', 'finishingSides',
            'quantity', 'width', 'length', 'isManualPrice', 'manualPrice', 'manualCost',
        ])) {
            $this->calculateQuote();
        }
    }

    public function calculateQuote(): void
    {
        $this->calcError = '';
        $this->isCalculated = false;

        if (! $this->productId || ! $this->materialId) {
            return;
        }

        if ($this->isManualPrice) {
            // Manual pricing override bypasses the matrix lookups
            $this->calcPrice = $this->manualPrice;
            $this->calcCost = $this->manualCost;
            $product = Product::findOrFail($this->productId);

            if ($product->pricing_type === 'area') {
                $area = ($this->width > 0 && $this->length > 0) ? ($this->width * $this->length) : 1.0;
                $this->calcSubtotal = (int) round($this->calcPrice * $this->quantity * $area);
            } else {
                $this->calcSubtotal = $this->calcPrice * $this->quantity;
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

            $pricingService = new PricingService;
            $quote = $pricingService->calculateQuote([
                'product_id' => $this->productId,
                'material_id' => $this->materialId,
                'machine_id' => $this->machineId ?: null,
                'finishing_id' => $this->finishingId ?: null,
                'cut_id' => $this->cutId ?: null,
                'display_id' => $this->displayId ?: null,
                'sides' => $this->sides ?: 1,
                'finishing_sides' => $this->finishingSides ?: 0,
                'quantity' => $this->quantity ?: 1,
                'width' => $this->width ?: 0,
                'length' => $this->length ?: 0,
                'customer_type' => $custType,
            ]);

            $this->calcPrice = $quote['unit_price'];
            $this->calcCost = $quote['unit_cost'];
            $this->calcSubtotal = $quote['subtotal'];
            $this->calcPricingId = $quote['pricing_id'];
            $this->isCalculated = true;
        } catch (PricingNotFoundException $e) {
            $this->calcError = 'Pricing configuration not found for these parameters.';
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

        $product = Product::find($this->productId);
        $productName = $product->name;
        $materialName = Material::find($this->materialId)->name;
        $machineName = $this->machineId ? Machine::find($this->machineId)->name : 'N/A';
        $finishingName = $this->finishingId ? Attribute::find($this->finishingId)->name : 'N/A';
        $cutName = $this->cutId ? Attribute::find($this->cutId)->name : 'N/A';
        $displayName = $this->displayId ? Attribute::find($this->displayId)->name : 'N/A';

        $specDetails = ["Material: {$materialName}"];
        if ($this->machineId) {
            $specDetails[] = "Machine: {$machineName}";
        }
        if ($this->finishingId) {
            $specDetails[] = "Finishing: {$finishingName}";
        }
        if ($this->cutId) {
            $specDetails[] = "Cutting: {$cutName}";
        }
        if ($this->displayId) {
            $specDetails[] = "Display: {$displayName}";
        }
        if ($product->pricing_type === 'area') {
            $specDetails[] = "Size: {$this->length}m x {$this->width}m";
        }
        if ($this->isManualPrice) {
            $specDetails[] = 'Manual Price Override';
        }

        $areaMultiplier = $product->pricing_type === 'area' ? ($this->width * $this->length) : 1;
        $totalItemCost = (int) round($this->calcCost * $this->quantity * $areaMultiplier);

        $this->items[] = [
            'id' => uniqid(),
            'product_id' => $this->productId,
            'product_name' => $productName,
            'material_id' => $this->materialId,
            'machine_id' => $this->machineId ?: null,
            'finishing_id' => $this->finishingId ?: null,
            'cut_id' => $this->cutId ?: null,
            'display_id' => $this->displayId ?: null,
            'sides' => $this->sides,
            'finishing_sides' => $this->finishingSides,
            'quantity' => $this->quantity,
            'width' => $this->width,
            'length' => $this->length,
            'file_name' => $this->fileName ?: 'Unnamed_File',
            'notes' => $this->notes ?: '',
            'pricing_id' => $this->calcPricingId,
            'unit_price' => $this->calcPrice,
            'unit_cost' => $this->calcCost,
            'subtotal' => $this->calcSubtotal,
            'cost' => $totalItemCost,
            'profit' => $this->calcSubtotal - $totalItemCost,
            'details_string' => implode(', ', $specDetails),
            'is_manual_price' => $this->isManualPrice,
        ];

        $this->reset([
            'productId', 'materialId', 'machineId', 'finishingId', 'cutId', 'displayId',
            'sides', 'finishingSides', 'fileName', 'notes', 'isManualPrice', 'manualPrice', 'manualCost',
            'calcPrice', 'calcCost', 'calcSubtotal', 'calcPricingId', 'isCalculated', 'calcError',
        ]);

        $this->quantity = 1;
        $this->width = 1.0;
        $this->length = 1.0;
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

        $this->reset(['items', 'customerId', 'customerSearch', 'discount']);
        $this->deadline = now()->addDay()->format('Y-m-d\TH:i');

        $this->successMessage = 'Order placed successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $customers = Customer::query()
            ->when($this->customerSearch, function ($q) {
                $q->where('name', 'like', '%'.$this->customerSearch.'%')
                    ->orWhere('phone', 'like', '%'.$this->customerSearch.'%');
            })
            ->orderBy('name')
            ->limit(8)
            ->get();

        $products = Product::all();

        $materials = collect();
        $machines = collect();
        $finishings = collect();
        $cuts = collect();
        $displays = collect();

        $hasMachines = false;
        $hasFinishings = false;
        $hasCuts = false;
        $hasDisplays = false;
        $hasSides = false;
        $hasFinishingSides = false;

        $branchId = auth()->user()->branch_id ?: (Branch::first()->id ?? null);

        if ($this->productId && $branchId) {
            $pricings = Pricing::where('product_id', $this->productId)->where('branch_id', $branchId)->get();
            $pricingIds = $pricings->pluck('id');

            $materialIds = $pricings->pluck('material_id')->unique();
            $materials = Material::whereIn('id', $materialIds)->where('branch_id', $branchId)->get();

            $machineIds = MachinePricing::whereIn('pricing_id', $pricingIds)->pluck('machine_id')->unique()->filter();
            if ($machineIds->isNotEmpty()) {
                $machines = Machine::whereIn('id', $machineIds)->where('branch_id', $branchId)->get();
                $hasMachines = true;
            }

            $attributeIds = AttributePricing::whereIn('pricing_id', $pricingIds)->pluck('attribute_id')->unique()->filter();
            if ($attributeIds->isNotEmpty()) {
                $finishings = Attribute::whereIn('id', $attributeIds)->where('type', 'finishing')->get();
                $hasFinishings = $finishings->isNotEmpty();

                $cuts = Attribute::whereIn('id', $attributeIds)->where('type', 'cut')->get();
                $hasCuts = $cuts->isNotEmpty();

                $displays = Attribute::whereIn('id', $attributeIds)->where('type', 'display')->get();
                $hasDisplays = $displays->isNotEmpty();
            }

            $hasSides = in_array(Product::find($this->productId)->name ?? '', ['Merchandise', 'Print Kertas', 'ID Card', 'Stempel', 'Kartu Nama', 'NOTA']);

            $hasFinishingSides = $hasFinishings;
        }

        $activeInvoices = Invoice::with('customer')
            ->where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('livewire.dashboards.designer', compact(
            'customers',
            'products',
            'materials',
            'machines',
            'finishings',
            'cuts',
            'displays',
            'hasMachines',
            'hasFinishings',
            'hasCuts',
            'hasDisplays',
            'hasSides',
            'hasFinishingSides',
            'activeInvoices'
        ));
    }
}
