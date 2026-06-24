<?php

use Livewire\Volt\Component;
use App\Models\Order;
use App\Models\Material;
use App\Models\Machine;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

new class extends Component {
    // Waste logging form properties
    public $materialId = '';
    public $machineId = '';
    public $quantity = '';
    public $dimensions = '';
    public $designerName = '';
    public $operatorName = '';
    public $notes = '';

    // Alert feedback
    public $showSuccessAlert = false;
    public $successMessage = '';

    public function mount()
    {
        $this->operatorName = auth()->user()->name;
    }

    public function startOrderProduction($orderId)
    {
        $order = Order::findOrFail($orderId);
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
    }

    public function completeOrderProduction($orderId)
    {
        $order = Order::findOrFail($orderId);
        $order->status = 2; // Completed / Done
        $order->save();

        $this->successMessage = 'Order marked as completed!';
        $this->showSuccessAlert = true;
    }

    public function logWaste()
    {
        $this->validate([
            'materialId' => 'required|integer|exists:materials,id',
            'machineId' => 'nullable|integer',
            'quantity' => 'required|numeric|min:0.01',
            'dimensions' => 'nullable|string|max:50',
            'designerName' => 'nullable|string|max:255',
            'operatorName' => 'required|string|max:255',
            'notes' => 'required|string|max:500',
        ]);

        $inventoryService = new InventoryService();
        $inventoryService->logWaste([
            'material_id' => (int) $this->materialId,
            'machine_id' => (int) ($this->machineId ?: 0),
            'quantity' => (float) $this->quantity,
            'dimensions' => $this->dimensions,
            'designer_name' => $this->designerName,
            'operator_name' => $this->operatorName,
            'notes' => $this->notes,
        ]);

        $this->reset(['materialId', 'machineId', 'quantity', 'dimensions', 'designerName', 'notes']);
        $this->operatorName = auth()->user()->name;

        $this->successMessage = 'Failed print/waste logged successfully!';
        $this->showSuccessAlert = true;
    }

    public function with(): array
    {
        // 1. Dynamic Quick Stat Counters
        $queueCount = Order::where('status', 0)->count();
        $printingCount = Order::where('status', 1)->count();
        $completedToday = Order::where('status', 2)->whereDate('updated_at', now()->toDateString())->count();
        $wasteToday = WastedMaterial::whereDate('logged_at', now()->toDateString())->sum('quantity');

        // 2. Load active queue (status 0: pending, status 1: printing)
        $activeOrders = Order::with('invoice.customer')
            ->whereIn('status', [0, 1])
            ->orderBy('id', 'asc')
            ->limit(10)
            ->get();

        // 3. Load materials and machines options
        $allMaterials = Material::orderBy('name')->get();
        $allMachines = Machine::orderBy('name')->get();
        
        // Load designers list for dropdown
        $designersList = User::where('role', 'designer')->get();

        // 4. Load recent wasted logs
        $recentWaste = WastedMaterial::with(['material', 'machine'])
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        return compact(
            'queueCount',
            'printingCount',
            'completedToday',
            'wasteToday',
            'activeOrders',
            'allMaterials',
            'allMachines',
            'designersList',
            'recentWaste'
        );
    }
}; ?>

<div class="space-y-6 text-gray-900 dark:text-gray-100">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-500 to-amber-600 p-6 shadow-lg dark:from-orange-600 dark:to-amber-700">
        <div class="relative z-10 text-white">
            <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/20 rounded-full backdrop-blur-sm">
                Print Shop Production Line
            </span>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Active Print Queue</h1>
            <p class="mt-1 text-orange-100 max-w-xl text-sm">
                Track print jobs dispatched from designers, log printer machine usage, report failed prints (wasted stock), and execute completed work tickets.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
    </div>

    <!-- Alert Feedback -->
    @if ($showSuccessAlert)
        <div class="rounded-xl border border-emerald-500/20 bg-emerald-50/50 dark:bg-emerald-950/20 p-4 backdrop-blur-sm flex justify-between items-center transition" x-data="{ show: true }" x-show="show">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ $successMessage }}</span>
            </div>
            <button type="button" @click="show = false; $wire.set('showSuccessAlert', false)" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    @endif

    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat 1 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Jobs in Queue</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $queueCount }} Jobs</h3>
                </div>
                <div class="rounded-lg bg-orange-50 dark:bg-orange-900/20 p-3 text-orange-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-orange-500 font-semibold">
                <span class="h-2 w-2 rounded-full bg-orange-500 animate-ping mr-2"></span>
                <span>Ready to assign to printers</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Currently Printing</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $printingCount }} active</h3>
                </div>
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3 text-amber-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3 3L22 4"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-amber-500 font-semibold">
                <span>Active print machine runs</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Jobs Completed (Today)</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $completedToday }} jobs</h3>
                </div>
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-emerald-500 font-semibold">
                <span>Finished today</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Failed / Waste logged</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($wasteToday, 2) }} units</h3>
                </div>
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-red-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-red-500 font-semibold">
                <span>Loss registered today</span>
            </div>
        </div>
    </div>

    <!-- Active print queue board & Wasted logs -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Live print queue board -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">Active Print Tickets</h3>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Work orders prioritized by job deadline.</p>
                </div>
                <span class="rounded-full bg-orange-50 dark:bg-orange-950/30 px-3 py-1 text-[10px] font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider">Queue: {{ $queueCount + $printingCount }} active</span>
            </div>

            <div class="space-y-3.5">
                @forelse ($activeOrders as $oItem)
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between rounded-lg border border-gray-100 dark:border-gray-700/30 p-4 gap-3 bg-gray-50/20 dark:bg-gray-900/10 hover:shadow-md transition">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">#{{ str_pad($oItem->invoice_id, 5, '0', STR_PAD_LEFT) }}-{{ $oItem->id }}</span>
                                <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-cyan-50 dark:bg-cyan-950/30 text-cyan-600 dark:text-cyan-400 uppercase tracking-wider">
                                    CS: {{ $oItem->invoice->customer_service }}
                                </span>
                            </div>
                            <h4 class="mt-1 text-sm font-bold text-gray-900 dark:text-white">{{ $oItem->file_name }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Specs: {{ $oItem->notes }}</p>
                            <p class="text-[10px] text-gray-400 font-semibold mt-0.5">Quantity: <span class="text-gray-800 dark:text-gray-200">{{ $oItem->quantity }}</span> &bull; Client: {{ $oItem->invoice->customer->name }}</p>
                        </div>
                        <div class="flex items-center gap-2 self-start sm:self-center">
                            @if ($oItem->status == 0)
                                <button wire:click="startOrderProduction({{ $oItem->id }})" class="rounded border border-orange-500 text-orange-500 hover:bg-orange-500 hover:text-white px-3 py-1.5 text-xs font-bold transition">Start Printing</button>
                            @else
                                <span class="inline-flex items-center text-[10px] text-amber-500 font-bold mr-2 animate-pulse">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                    Printing...
                                </span>
                                <button wire:click="completeOrderProduction({{ $oItem->id }})" class="rounded bg-emerald-500 hover:bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition">Mark Done</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-xs text-gray-400 dark:text-gray-500 py-6">No jobs currently in the print queue.</div>
                @endforelse
            </div>
        </div>

        <!-- Log failed prints / quick actions -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm lg:col-span-1">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Log Print Waste</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 mb-4">Report failed runs to adjust inventory.</p>

            <form wire:submit.prevent="logWaste" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Select Material *</label>
                    <select wire:model="materialId" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-red-500 text-gray-900 dark:text-white" required>
                        <option value="">-- Choose Material --</option>
                        @foreach ($allMaterials as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} {{ $m->dimensions ? "({$m->dimensions})" : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Printer Machine</label>
                    <select wire:model="machineId" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-red-500 text-gray-900 dark:text-white">
                        <option value="">-- Choose Machine --</option>
                        @foreach ($allMachines as $mac)
                            <option value="{{ $mac->id }}">{{ $mac->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Wasted Qty *</label>
                        <input type="number" step="any" min="0.01" wire:model="quantity" placeholder="e.g. 1.5 or 5" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-red-500 text-gray-900 dark:text-white" required>
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Dimensions (m)</label>
                        <input type="text" wire:model="dimensions" placeholder="e.g. 2x1" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-red-500 text-gray-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Responsible Designer</label>
                    <select wire:model="designerName" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-red-500 text-gray-900 dark:text-white">
                        <option value="">-- Choose Designer --</option>
                        @foreach ($designersList as $des)
                            <option value="{{ $des->name }}">{{ $des->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Failure Reason *</label>
                    <textarea wire:model="notes" placeholder="e.g. Paper jam, head clog, color mismatch..." class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-red-500 text-gray-900 dark:text-white h-16 resize-none" required></textarea>
                </div>

                <button type="submit" class="w-full rounded-lg bg-red-500 hover:bg-red-600 text-white font-bold py-2 transition shadow-sm">
                    Submit Material Waste Log
                </button>
            </form>
        </div>
    </div>

    <!-- LOG OF RECENT FAILURES/WASTED MATERIALS -->
    <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm">
        <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-4">Recent Logged Print Failures</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3">Material</th>
                        <th class="py-2.5 px-3">Printer</th>
                        <th class="py-2.5 px-3">Wasted Qty</th>
                        <th class="py-2.5 px-3">Designer</th>
                        <th class="py-2.5 px-3">Operator</th>
                        <th class="py-2.5 px-3">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                    @forelse ($recentWaste as $wItem)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all text-red-600">
                            <td class="py-3 px-3">{{ $wItem->logged_at }}</td>
                            <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">
                                {{ $wItem->material->name }} {{ $wItem->material->dimensions ? "({$wItem->material->dimensions})" : '' }}
                            </td>
                            <td class="py-3 px-3 text-gray-700 dark:text-gray-300 font-medium">{{ $wItem->machine ? $wItem->machine->name : 'N/A' }}</td>
                            <td class="py-3 px-3 font-bold">{{ $wItem->quantity }} {{ $wItem->material->category }}</td>
                            <td class="py-3 px-3 text-gray-600 dark:text-gray-400">{{ $wItem->designer_name ?: '-' }}</td>
                            <td class="py-3 px-3 text-gray-600 dark:text-gray-400 font-semibold">{{ $wItem->operator_name }}</td>
                            <td class="py-3 px-3 italic font-medium">{{ $wItem->notes }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-gray-400 dark:text-gray-500">No print failures logged.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
