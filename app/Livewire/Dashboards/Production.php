<?php

namespace App\Livewire\Dashboards;

use App\Models\Machine;
use App\Models\Material;
use App\Models\Order;
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
    public string $designerName = '';
    public string $operatorName = '';
    public string $notes = '';

    // Alert feedback
    public bool $showSuccessAlert = false;
    public string $successMessage = '';

    public function mount(): void
    {
        $this->operatorName = auth()->user()->name;
    }

    public function startOrderProduction(int $orderId): void
    {
        $order = Order::with('invoice')->findOrFail($orderId);

        if (auth()->user()->branch_id && $order->invoice->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $order->status = 1; // Processing/Printing
        $order->save();

        // Proactively deduct stock when production starts if a pricing matrix is linked
        if ($order->pricing_matrix_id > 0) {
            $matrix = DB::table('pricing_matrices')->find($order->pricing_matrix_id);
            if ($matrix && $matrix->material_id) {
                // If it is large format (BANNER, product_id 5) deduct by printed area
                $qtyToDeduct = (float) $order->quantity;
                if ($matrix->product_id == 5) {
                    $qtyToDeduct = (float) ($order->area * $order->quantity);
                }

                $inventoryService = new InventoryService();
                $inventoryService->deductStockOnPrint($matrix->material_id, $qtyToDeduct);
            }
        }

        $this->successMessage = 'Order production started and inventory adjusted!';
        $this->showSuccessAlert = true;

        event(new \App\Events\OrderUpdated());
    }

    public function completeOrderProduction(int $orderId): void
    {
        $order = Order::with('invoice')->findOrFail($orderId);

        if (auth()->user()->branch_id && $order->invoice->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $order->status = 2; // Completed / Done
        $order->save();

        $this->successMessage = 'Order marked as completed!';
        $this->showSuccessAlert = true;

        event(new \App\Events\OrderUpdated());
    }

    public function logWaste(): void
    {
        $this->validate([
            'materialId'   => 'required|integer|exists:materials,id',
            'machineId'    => 'nullable|integer',
            'quantity'     => 'required|numeric|min:0.01',
            'dimensions'   => 'nullable|string|max:50',
            'designerName' => 'nullable|string|max:255',
            'operatorName' => 'required|string|max:255',
            'notes'        => 'required|string|max:500',
        ]);

        $inventoryService = new InventoryService();
        $inventoryService->logWaste([
            'material_id'   => (int) $this->materialId,
            'machine_id'    => (int) ($this->machineId ?: 0),
            'quantity'      => (float) $this->quantity,
            'dimensions'    => $this->dimensions,
            'designer_name' => $this->designerName,
            'operator_name' => $this->operatorName,
            'notes'         => $this->notes,
        ]);

        $this->reset(['materialId', 'machineId', 'quantity', 'dimensions', 'designerName', 'notes']);
        $this->operatorName = auth()->user()->name;

        $this->successMessage = 'Failed print/waste logged successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $branchId = auth()->user()->branch_id ?: 1;

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
            ->orderBy('id', 'asc')
            ->limit(10)
            ->get();

        // 3. Load materials and machines options
        $allMaterials = Material::where('branch_id', $branchId)->orderBy('name')->get();
        $allMachines = Machine::where('branch_id', $branchId)->orderBy('name')->get();

        // Load designers list for dropdown
        $designersList = User::where('role', 'designer')->where('branch_id', $branchId)->get();

        // 4. Load recent wasted logs
        $recentWaste = WastedMaterial::with(['material', 'machine'])
            ->where('branch_id', $branchId)
            ->orderBy('id', 'desc')
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
