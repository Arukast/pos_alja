<?php

namespace App\Livewire\Dashboards;

use App\Events\OrderUpdated;
use App\Exceptions\PricingMatrixNotFoundException;
use App\Models\ActivityLog;
use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderStatusLog;
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

    // Manual price overrides
    public bool $isManualPrice = false;

    public int $manualPrice = 0;

    public int $manualCost = 0;

    // Active calculations
    public int $calcPrice = 0;

    public int $calcCost = 0;

    public int $calcSubtotal = 0;

    public ?string $calcMatrixId = null;

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

        $customer = Customer::create([
            'name' => $this->customerName,
            'phone' => $this->customerPhone,
            'address' => $this->customerAddress,
            'email' => $this->customerEmail,
            'type' => $this->customerType,
        ]);

        // Audit Log for customer creation
        ActivityLog::create([
            'branch_id' => auth()->user()->branch_id,
            'user_id' => auth()->user()->id,
            'username' => auth()->user()->username,
            'role' => auth()->user()->role,
            'action' => 'CREATE',
            'description' => "Created customer: {$customer->name} ({$customer->type})",
            'model_type' => Customer::class,
            'model_id' => $customer->id,
            'payload' => $customer->toArray(),
        ]);

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

            $this->calcMatrixId = null;
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
            'pricing_matrix_id' => $this->calcMatrixId,
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

            $activeBranchId = auth()->user()->branch_id ?: (Branch::first()->id ?? null);
            $branch = Branch::find($activeBranchId);

            // Generate clean prefix: e.g. "Main Branch (Jakarta)" -> "MBJ" or "MN"
            $branchPrefix = 'BR';
            if ($branch) {
                $cleanedName = preg_replace('/[^A-Za-z0-9 ]/', '', $branch->name);
                $words = explode(' ', $cleanedName);
                if (count($words) >= 2) {
                    $branchPrefix = strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
                } else {
                    $branchPrefix = strtoupper(substr($cleanedName, 0, 2));
                }
            }

            // Generate branch-prefixed structured invoice number
            $todayDate = now()->format('Ymd');
            $count = Invoice::where('created_date', now()->toDateString())
                ->where('branch_id', $activeBranchId)
                ->count();
            $sequence = str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
            $invoiceNumber = "INV-{$branchPrefix}-{$todayDate}-{$sequence}";

            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,
                'branch_id' => $activeBranchId,
                'created_date' => now()->toDateString(),
                'customer_id' => $this->customerId,
                'designer_id' => auth()->user()->id, // Relational assignment
                'status' => 0, // Unpaid
                'discount' => $discountAmount,
                'dp_amount' => 0,
                'accounts_receivable' => $grandTotal,
                'grand_total' => $grandTotal,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'deadline' => $this->deadline,
                'pickup_status' => 0,
                'source' => $this->source === 'Offline' ? 0 : 1,
            ]);

            foreach ($this->items as $item) {
                $order = Order::create([
                    'invoice_id' => $invoice->id,
                    'pricing_matrix_id' => $item['pricing_matrix_id'],
                    'operator_id' => null,
                    'file_name' => $item['file_name'],
                    'notes' => $item['notes'] ?: $item['details_string'],
                    'area' => $item['width'] * $item['length'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['subtotal'],
                    'cost' => $item['cost'],
                    'profit' => $item['profit'],
                    'ordered_at' => now()->toDateString(),
                    'status' => 0, // Pending
                    'pickup_status' => 0,
                    'is_manual_price' => $item['is_manual_price'],
                ]);

                // Create initial Status log for each order item
                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => 0, // Pending
                    'user_id' => auth()->user()->id,
                    'user_name' => auth()->user()->name,
                    'notes' => 'Order created by designer',
                ]);
            }

            // Create global Activity Log
            ActivityLog::create([
                'branch_id' => $activeBranchId,
                'user_id' => auth()->user()->id,
                'username' => auth()->user()->username,
                'role' => auth()->user()->role,
                'action' => 'CREATE',
                'description' => "Placed order invoice: {$invoice->invoice_number} (Grand Total: {$invoice->grand_total})",
                'model_type' => Invoice::class,
                'model_id' => $invoice->id,
                'payload' => $invoice->toArray(),
            ]);
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

        $branchId = auth()->user()->branch_id ?: (Branch::first()->id ?? null);

        if ($this->productId && $branchId) {
            $matrices = PricingMatrix::where('product_id', $this->productId)->where('branch_id', $branchId)->get();

            $materialIds = $matrices->pluck('material_id')->unique();
            $materials = Material::whereIn('id', $materialIds)->where('branch_id', $branchId)->get();

            $machineIds = $matrices->pluck('machine_id')->unique()->filter();
            if ($machineIds->isNotEmpty()) {
                $machines = Machine::whereIn('id', $machineIds)->where('branch_id', $branchId)->get();
                $hasMachines = $matrices->contains(fn ($m) => ! empty($m->machine_id));
            }

            $finishingIds = $matrices->pluck('finishing_id')->unique()->filter();
            if ($finishingIds->isNotEmpty()) {
                $finishings = Attribute::whereIn('id', $finishingIds)->where('type', 'finishing')->get();
                $hasFinishings = $matrices->contains(fn ($m) => ! empty($m->finishing_id));
            }

            $cutIds = $matrices->pluck('cut_id')->unique()->filter();
            if ($cutIds->isNotEmpty()) {
                $cuts = Attribute::whereIn('id', $cutIds)->where('type', 'cut')->get();
                $hasCuts = $matrices->contains(fn ($m) => ! empty($m->cut_id));
            }

            $displayIds = $matrices->pluck('display_id')->unique()->filter();
            if ($displayIds->isNotEmpty()) {
                $displays = Attribute::whereIn('id', $displayIds)->where('type', 'display')->get();
                $hasDisplays = $matrices->contains(fn ($m) => ! empty($m->display_id));
            }

            $hasSides = $matrices->contains(fn ($m) => $m->sides > 1)
                || in_array(Product::find($this->productId)->name ?? '', ['Merchandise', 'Print Kertas', 'ID Card', 'Stempel', 'Kartu Nama', 'NOTA']);

            $hasFinishingSides = $matrices->contains(fn ($m) => $m->finishing_sides > 0);
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
