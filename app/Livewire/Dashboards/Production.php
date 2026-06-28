<?php

namespace App\Livewire\Dashboards;

use App\Events\OrderUpdated;
use App\Livewire\Forms\WasteLogForm;
use App\Models\Branch;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Order;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Services\InventoryService;
use App\Services\OrderService;
use Livewire\Attributes\Url;
use Livewire\Component;

class Production extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'dashboard';

    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    public int $queueCount = 0;

    public int $printingCount = 0;

    public int $completedToday = 0;

    public float $wasteToday = 0.0;

    public $activeOrders;

    // Waste logging form
    public WasteLogForm $form;

    public string $operatorName = '';

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

        $orderService = new OrderService;
        $orderService->startProduction($order, auth()->user());

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

        $orderService = new OrderService;
        $orderService->completeProduction($order, auth()->user());

        $this->successMessage = 'Order marked as completed!';
        $this->showSuccessAlert = true;

        event(new OrderUpdated);
    }

    public function logWaste(): void
    {
        $this->form->validate();

        $inventoryService = new InventoryService;
        $inventoryService->logWaste([
            'material_id' => $this->form->materialId,
            'machine_id' => $this->form->machineId ?: null,
            'quantity' => (float) $this->form->quantity,
            'dimensions' => $this->form->dimensions,
            'designer_id' => $this->form->designerId ?: null,
            'operator_id' => (string) auth()->user()->id,
            'notes' => $this->form->notes,
        ]);

        $this->form->reset();
        $this->operatorName = auth()->user()->name;

        $this->successMessage = 'Failed print/waste logged successfully!';
        $this->showSuccessAlert = true;
    }

    public function render()
    {
        $branchId = auth()->user()->branch_id ?: Branch::value('id');

        // Initialize variables to default empty/null states
        $this->queueCount = 0;
        $this->printingCount = 0;
        $this->completedToday = 0;
        $this->wasteToday = 0.0;
        $this->activeOrders = collect();
        $allMaterials = collect();
        $allMachines = collect();
        $designersList = collect();
        $recentWaste = collect();

        // 1. Load metrics & lists conditionally based on activeTab
        if ($this->activeTab === 'dashboard' || $this->activeTab === 'print_queue') {
            $this->queueCount = Order::where('status', 0)
                ->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId))
                ->count();
            $this->printingCount = Order::where('status', 1)
                ->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId))
                ->count();
            $this->completedToday = Order::where('status', 2)
                ->whereDate('updated_at', now()->toDateString())
                ->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId))
                ->count();
            $this->wasteToday = (float) WastedMaterial::where('branch_id', $branchId)
                ->whereDate('logged_at', now()->toDateString())
                ->sum('quantity');

            // Load active queue (status 0: pending, status 1: printing)
            $this->activeOrders = Order::with('invoice.customer')
                ->whereIn('status', [0, 1])
                ->whereHas('invoice', fn ($q) => $q->where('branch_id', $branchId))
                ->orderBy('created_at', 'asc')
                ->limit(10)
                ->get();
        }

        if ($this->activeTab === 'waste') {
            // Load materials and machines options
            $allMaterials = Material::where('branch_id', $branchId)->orderBy('name')->get();
            $allMachines = Machine::where('branch_id', $branchId)->orderBy('name')->get();

            // Load designers list for dropdown
            $designersList = User::where('role', 'designer')->where('branch_id', $branchId)->get();

            // Load recent wasted logs
            $recentWaste = WastedMaterial::with(['material', 'machine', 'designer', 'operator'])
                ->where('branch_id', $branchId)
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        }

        return view('livewire.dashboards.production', [
            'allMaterials' => $allMaterials,
            'allMachines' => $allMachines,
            'designersList' => $designersList,
            'recentWaste' => $recentWaste,
        ]);
    }
}
