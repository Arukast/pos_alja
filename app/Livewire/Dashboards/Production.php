<?php

namespace App\Livewire\Dashboards;

use App\Events\OrderUpdated;
use App\Models\Branch;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Production extends Component
{
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    // Waste logging form properties
    public string $materialId = '';

    public string $machineId = '';

    public string $quantity = '';

    public string $dimensions = '';

    public string $designerId = '';

    public string $operatorName = '';

    public string $notes = '';

    // Alert feedback
    public bool $showSuccessAlert = false;

    public string $successMessage = '';

    public function mount(): void
    {
        $this->operatorName = auth()->user()->name;
    }

    public function startOrderProduction(string $orderId): void
    {
        $order = Order::with('invoice')->findOrFail($orderId);

        if (auth()->user()->branch_id && $order->invoice->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        DB::transaction(function () use ($order) {
            $order->status = 1; // Processing/Printing
            $order->operator_id = auth()->user()->id;
            $order->save();

            // Proactively deduct stock when production starts if a pricing tier is linked
            if ($order->pricing_id) {
                $pricing = DB::table('pricing')->where('id', $order->pricing_id)->first();
                if ($pricing && $pricing->material_id) {
                    $product = DB::table('products')->where('id', $pricing->product_id)->first();
                    $isArea = $product && $product->pricing_type === 'area';

                    $qtyToDeduct = $isArea
                        ? (float) ($order->area * $order->quantity)
                        : (float) $order->quantity;

                    $inventoryService = new InventoryService;
                    $inventoryService->deductStockOnPrint($pricing->material_id, $qtyToDeduct);
                }
            }

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 1,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'notes' => 'Production started',
            ]);
        });

        $this->successMessage = 'Order production started and inventory adjusted!';
        $this->showSuccessAlert = true;

        event(new OrderUpdated);
    }

    public function completeOrderProduction(string $orderId): void
    {
        $order = Order::with('invoice')->findOrFail($orderId);

        if (auth()->user()->branch_id && $order->invoice->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        DB::transaction(function () use ($order) {
            $order->status = 2; // Completed / Done
            $order->operator_id = auth()->user()->id;
            $order->save();

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => 2,
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'notes' => 'Production completed',
            ]);
        });

        $this->successMessage = 'Order marked as completed!';
        $this->showSuccessAlert = true;

        event(new OrderUpdated);
    }

    public function logWaste(): void
    {
        $this->validate([
            'materialId' => 'required|string|exists:materials,id',
            'machineId' => 'nullable|string|exists:machines,id',
            'quantity' => 'required|numeric|min:0.01',
            'dimensions' => 'nullable|string|max:50',
            'designerId' => 'nullable|string|exists:users,id',
            'notes' => 'required|string|max:500',
        ]);

        $inventoryService = new InventoryService;
        $inventoryService->logWaste([
            'material_id' => $this->materialId,
            'machine_id' => $this->machineId ?: null,
            'quantity' => (float) $this->quantity,
            'dimensions' => $this->dimensions,
            'designer_id' => $this->designerId ?: null,
            'operator_id' => auth()->user()->id,
            'notes' => $this->notes,
        ]);

        $this->reset(['materialId', 'machineId', 'quantity', 'dimensions', 'designerId', 'notes']);
        $this->operatorName = auth()->user()->name;

        $this->successMessage = 'Failed print/waste logged successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $branchId = auth()->user()->branch_id ?: Branch::value('id');

        // 1. Dynamic Quick Stat Counters
        $queueCount = Order::where('status', 0)
            ->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId))
            ->count();
        $printingCount = Order::where('status', 1)
            ->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId))
            ->count();
        $completedToday = Order::where('status', 2)
            ->whereDate('updated_at', now()->toDateString())
            ->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId))
            ->count();
        $wasteToday = WastedMaterial::where('branch_id', $branchId)
            ->whereDate('logged_at', now()->toDateString())
            ->sum('quantity');

        // 2. Load active queue (status 0: pending, status 1: printing)
        $activeOrders = Order::with('invoice.customer')
            ->whereIn('status', [0, 1])
            ->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        // 3. Load materials and machines options
        $allMaterials = Material::where('branch_id', $branchId)->orderBy('name')->get();
        $allMachines = Machine::where('branch_id', $branchId)->orderBy('name')->get();

        // Load designers list for dropdown
        $designersList = User::where('role', 'designer')->where('branch_id', $branchId)->get();

        // 4. Load recent wasted logs
        $recentWaste = WastedMaterial::with(['material', 'machine', 'designer', 'operator'])
            ->where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        return view('livewire.dashboards.production', compact(
            'queueCount',
            'printingCount',
            'completedToday',
            'wasteToday',
            'activeOrders',
            'allMaterials',
            'allMachines',
            'designersList',
            'recentWaste'
        ));
    }
}
