<?php

namespace App\Livewire\Dashboards;

use App\Events\OrderUpdated;
use App\Exceptions\PricingMatrixNotFoundException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Cut;
use App\Models\Display;
use App\Models\Finishing;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Order;
use App\Models\Product;
use App\Services\PricingService;
use Illuminate\Support\Facades\DB;
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

    // Active calculations
    public int $calcPrice = 0;

    public int $calcCost = 0;

    public int $calcSubtotal = 0;

    public int $calcMatrixId = 0;

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

    public function selectCustomer(int $id): void
    {
        $this->customerId = (string) $id;
        $this->customerSearch = Customer::find($id)->name ?? '';
    }

    public function updatedCustomerSearch(): void
    {
        // Reset the selected customer ID if the user manually edits the search field
        // after a customer was already selected — prevents ghost/stale selection
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

        $customer = Customer::create([
            'name' => $this->customerName,
            'phone' => $this->customerPhone,
            'address' => $this->customerAddress,
            'email' => $this->customerEmail,
            'type' => $this->customerType,
        ]);

        $this->customerId = (string) $customer->id;
        $this->customerSearch = $customer->name;
        $this->showNewCustomerForm = false;

        $this->reset(['customerName', 'customerPhone', 'customerAddress', 'customerEmail']);
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, [
            'productId', 'materialId', 'machineId', 'finishingId',
            'cutId', 'displayId', 'sides', 'finishingSides',
            'quantity', 'width', 'length',
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

        try {
            $pricingService = new PricingService;
            $quote = $pricingService->calculateQuote([
                'product_id' => $this->productId,
                'material_id' => $this->materialId,
                'machine_id' => $this->machineId ?: 0,
                'finishing_id' => $this->finishingId ?: 0,
                'cut_id' => $this->cutId ?: 0,
                'display_id' => $this->displayId ?: 0,
                'sides' => $this->sides ?: 1,
                'finishing_sides' => $this->finishingSides ?: 0,
                'quantity' => $this->quantity ?: 1,
                'width' => $this->width ?: 0,
                'length' => $this->length ?: 0,
            ]);

            $this->calcPrice = $quote['unit_price'];
            $this->calcCost = $quote['unit_cost'];
            $this->calcSubtotal = $quote['subtotal'];
            $this->calcMatrixId = $quote['pricing_matrix_id'];
            $this->isCalculated = true;
        } catch (PricingMatrixNotFoundException $e) {
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

        $productName = Product::find($this->productId)->name;
        $materialName = Material::find($this->materialId)->name;
        $machineName = $this->machineId ? Machine::find($this->machineId)->name : 'N/A';
        $finishingName = $this->finishingId ? Finishing::find($this->finishingId)->name : 'N/A';
        $cutName = $this->cutId ? Cut::find($this->cutId)->name : 'N/A';
        $displayName = $this->displayId ? Display::find($this->displayId)->name : 'N/A';

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
        if ($this->productId == 5) {
            $specDetails[] = "Size: {$this->length}m x {$this->width}m";
        }

        $this->items[] = [
            'id' => uniqid(),
            'product_id' => $this->productId,
            'product_name' => $productName,
            'material_id' => $this->materialId,
            'machine_id' => $this->machineId ?: 0,
            'finishing_id' => $this->finishingId ?: 0,
            'cut_id' => $this->cutId ?: 0,
            'display_id' => $this->displayId ?: 0,
            'sides' => $this->sides,
            'finishing_sides' => $this->finishingSides,
            'quantity' => $this->quantity,
            'width' => $this->width,
            'length' => $this->length,
            'file_name' => $this->fileName ?: 'Unnamed_File',
            'notes' => $this->notes ?: '',
            'pricing_matrix_id' => $this->calcMatrixId,
            'unit_price' => $this->calcPrice,
            'unit_cost' => $this->calcCost,
            'subtotal' => $this->calcSubtotal,
            'cost' => $this->calcCost * $this->quantity * ($this->productId == 5 ? ($this->width * $this->length) : 1),
            'profit' => $this->calcSubtotal - ($this->calcCost * $this->quantity * ($this->productId == 5 ? ($this->width * $this->length) : 1)),
            'details_string' => implode(', ', $specDetails),
        ];

        $this->reset([
            'productId', 'materialId', 'machineId', 'finishingId', 'cutId', 'displayId',
            'sides', 'finishingSides', 'fileName', 'notes',
            'calcPrice', 'calcCost', 'calcSubtotal', 'calcMatrixId', 'isCalculated', 'calcError',
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

        DB::transaction(function () {
            $grandTotal = 0;
            $totalCost = 0;
            $totalProfit = 0;

            foreach ($this->items as $item) {
                $grandTotal += $item['subtotal'];
                $totalCost += $item['cost'];
                $totalProfit += $item['profit'];
            }

            $discountAmount = (int) ($this->discount ?: 0);
            $grandTotal = max(0, $grandTotal - $discountAmount);
            $totalProfit = $grandTotal - $totalCost;

            $invoice = Invoice::create([
                'branch_id' => auth()->user()->branch_id ?: (Branch::first()->id ?? 1),
                'created_date' => now()->toDateString(),
                'customer_id' => $this->customerId,
                'status' => 0,
                'discount' => $discountAmount,
                'dp_amount' => 0,
                'accounts_receivable' => $grandTotal,
                'dp_paid' => 0,
                'settlement_amount' => 0,
                'grand_total' => $grandTotal,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'customer_service' => auth()->user()->name,
                'deadline' => $this->deadline,
                'pickup_status' => 0,
                'source' => $this->source === 'Offline' ? 0 : 1,
            ]);

            foreach ($this->items as $item) {
                Order::create([
                    'invoice_id' => $invoice->id,
                    'pricing_matrix_id' => $item['pricing_matrix_id'] ?? 0,
                    'file_name' => $item['file_name'],
                    'notes' => $item['notes'] ?: $item['details_string'],
                    'area' => $item['product_id'] == 5 ? ($item['width'] * $item['length']) : 0.0,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'cost' => $item['cost'],
                    'profit' => $item['profit'],
                    'ordered_at' => now()->toDateString(),
                    'status' => 0,
                    'pickup_status' => 0,
                ]);
            }
        });

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

        $branchId = auth()->user()->branch_id ?: (Branch::first()->id ?? 1);

        if ($this->productId) {
            $matrices = PricingMatrix::where('product_id', $this->productId)->where('branch_id', $branchId)->get();

            $materialIds = $matrices->pluck('material_id')->unique();
            $materials = Material::whereIn('id', $materialIds)->where('branch_id', $branchId)->get();

            $machineIds = $matrices->pluck('machine_id')->unique()->filter();
            if ($machineIds->isNotEmpty()) {
                $machines = Machine::whereIn('id', $machineIds)->where('branch_id', $branchId)->get();
                $hasMachines = $matrices->contains(fn ($m) => $m->machine_id > 0);
            }

            $finishingIds = $matrices->pluck('finishing_id')->unique()->filter();
            if ($finishingIds->isNotEmpty()) {
                $finishings = Finishing::whereIn('id', $finishingIds)->get();
                $hasFinishings = $matrices->contains(fn ($m) => $m->finishing_id > 0);
            }

            $cutIds = $matrices->pluck('cut_id')->unique()->filter();
            if ($cutIds->isNotEmpty()) {
                $cuts = Cut::whereIn('id', $cutIds)->get();
                $hasCuts = $matrices->contains(fn ($m) => $m->cut_id > 0);
            }

            $displayIds = $matrices->pluck('display_id')->unique()->filter();
            if ($displayIds->isNotEmpty()) {
                $displays = Display::whereIn('id', $displayIds)->get();
                $hasDisplays = $matrices->contains(fn ($m) => $m->display_id > 0);
            }

            $hasSides = $matrices->contains(fn ($m) => $m->sides > 1)
                || in_array((int) $this->productId, [1, 4, 6, 7, 8, 10]);

            $hasFinishingSides = $matrices->contains(fn ($m) => $m->finishing_sides > 0);
        }

        $activeInvoices = Invoice::with('customer')
            ->where('branch_id', $branchId)
            ->orderBy('id', 'desc')
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
