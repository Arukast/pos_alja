<?php

use Livewire\Volt\Component;
use App\Models\Material;
use App\Models\Machine;
use App\Models\User;
use App\Models\PricingMatrix;
use App\Models\Expense;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

new class extends Component {
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    // Active tab
    public $activeTab = 'inventory';

    // Add stock form properties
    public $selectedMaterialId = '';
    public $addStockQuantity = '';
    public $addStockNotes = '';

    // User Management properties
    public $userSearch = '';
    public $showUserModal = false;
    public $userIdBeingEdited = null;
    public $formName = '';
    public $formUsername = '';
    public $formEmail = '';
    public $formPhone = '';
    public $formAddress = '';
    public $formPassword = '';
    public $formRole = 'cashier';

    // Alert feedback
    public $showSuccessAlert = false;
    public $successMessage = '';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetErrorBag();
        $this->showSuccessAlert = false;
    }

    public function addMaterialStock()
    {
        $this->validate([
            'selectedMaterialId' => 'required|integer|exists:materials,id',
            'addStockQuantity' => 'required|numeric|min:0.01',
            'addStockNotes' => 'nullable|string|max:255',
        ]);

        $material = Material::findOrFail($this->selectedMaterialId);

        if (auth()->user()->branch_id && $material->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $inventoryService = new InventoryService();
        $inventoryService->addStock($material, (float) $this->addStockQuantity, $this->addStockNotes);

        $this->reset(['selectedMaterialId', 'addStockQuantity', 'addStockNotes']);

        $this->successMessage = 'Inventory stock restocked successfully!';
        $this->showSuccessAlert = true;
    }

    public function openCreateModal()
    {
        $this->resetErrorBag();
        $this->userIdBeingEdited = null;
        $this->formName = '';
        $this->formUsername = '';
        $this->formEmail = '';
        $this->formPhone = '';
        $this->formAddress = '';
        $this->formPassword = '';
        $this->formRole = 'cashier';
        $this->showUserModal = true;
    }

    public function openEditModal($userId)
    {
        $this->resetErrorBag();
        $user = User::findOrFail($userId);

        // Restriction Guard: Manager cannot edit owners or other managers
        if (in_array($user->role, ['owner', 'manager'])) {
            abort(403, 'Unauthorized.');
        }
        // Branch scope guard
        if (auth()->user()->branch_id && $user->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized.');
        }

        $this->userIdBeingEdited = $user->id;
        $this->formName = $user->name;
        $this->formUsername = $user->username;
        $this->formEmail = $user->email;
        $this->formPhone = $user->phone;
        $this->formAddress = $user->address;
        $this->formPassword = '';
        $this->formRole = $user->role;
        $this->showUserModal = true;
    }

    public function saveUser()
    {
        $rules = [
            'formName' => 'required|string|max:255',
            'formUsername' => 'required|string|max:255|unique:users,username,' . ($this->userIdBeingEdited ?: 'NULL') . ',id,deleted_at,NULL',
            'formEmail' => 'required|string|email|max:255|unique:users,email,' . ($this->userIdBeingEdited ?: 'NULL') . ',id,deleted_at,NULL',
            'formPhone' => 'nullable|string|max:20',
            'formAddress' => 'nullable|string|max:255',
            'formRole' => 'required|string|in:cashier,designer,production',
        ];

        if (!$this->userIdBeingEdited) {
            $rules['formPassword'] = 'required|string|min:6';
        } else {
            $rules['formPassword'] = 'nullable|string|min:6';
        }

        $this->validate($rules);

        $branchId = auth()->user()->branch_id ?: 1;

        if ($this->userIdBeingEdited) {
            $user = User::findOrFail($this->userIdBeingEdited);

            // Restriction Guards
            if (in_array($user->role, ['owner', 'manager'])) {
                abort(403, 'Unauthorized.');
            }
            if (auth()->user()->branch_id && $user->branch_id !== auth()->user()->branch_id) {
                abort(403, 'Unauthorized.');
            }

            $data = [
                'name' => $this->formName,
                'username' => $this->formUsername,
                'email' => $this->formEmail,
                'phone' => $this->formPhone,
                'address' => $this->formAddress,
                'role' => $this->formRole,
            ];

            if (!empty($this->formPassword)) {
                $data['password'] = Hash::make($this->formPassword);
            }

            $user->update($data);
            $this->successMessage = 'User updated successfully!';
        } else {
            User::create([
                'name' => $this->formName,
                'username' => $this->formUsername,
                'email' => $this->formEmail,
                'phone' => $this->formPhone,
                'address' => $this->formAddress,
                'role' => $this->formRole,
                'branch_id' => $branchId,
                'password' => Hash::make($this->formPassword),
            ]);
            $this->successMessage = 'User created successfully!';
        }

        $this->showUserModal = false;
        $this->showSuccessAlert = true;
    }

    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);

        // Restriction Guards
        if (in_array($user->role, ['owner', 'manager'])) {
            abort(403, 'Unauthorized.');
        }
        if (auth()->user()->branch_id && $user->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized.');
        }

        $user->delete();

        $this->successMessage = 'User deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function with(): array
    {
        $branchId = auth()->user()->branch_id ?: 1;

        // 1. Dynamic Quick Stat Counters
        $materialCount = Material::where('branch_id', $branchId)->count();
        $machineCount = Machine::where('branch_id', $branchId)->count();
        $staffCount = User::where('branch_id', $branchId)->count();
        $pricingRulesCount = PricingMatrix::where('branch_id', $branchId)->count();

        // 2. Load stock ledger log history
        $stockLogs = DB::table('stock_ledgers')
            ->join('materials', 'stock_ledgers.material_id', '=', 'materials.id')
            ->where('materials.branch_id', $branchId)
            ->select('stock_ledgers.*', 'materials.name as material_name', 'materials.category as material_category')
            ->orderBy('stock_ledgers.id', 'desc')
            ->limit(8)
            ->get();

        // 3. Load entire material inventory list
        $materialsList = Material::where('branch_id', $branchId)
            ->orderBy('remaining_stock', 'asc') // prioritize low stock
            ->limit(20)
            ->get();

        // 4. Load all materials for the dropdown selection
        $allMaterials = Material::where('branch_id', $branchId)->orderBy('name')->get();

        // 5. Recent Expenses
        $expenses = Expense::where('branch_id', $branchId)->orderBy('id', 'desc')->limit(8)->get();

        // 6. User Management list (if active)
        $usersList = [];
        if ($this->activeTab === 'users') {
            $query = User::where('branch_id', $branchId);
            if (!empty($this->userSearch)) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->userSearch . '%')
                      ->orWhere('username', 'like', '%' . $this->userSearch . '%');
                });
            }
            $usersList = $query->orderBy('name')->get();
        }

        return compact(
            'materialCount',
            'machineCount',
            'staffCount',
            'pricingRulesCount',
            'stockLogs',
            'materialsList',
            'allMaterials',
            'expenses',
            'usersList'
        );
    }
}; ?>

<div class="space-y-6 text-gray-200">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-alja-cyan via-[#0088BB] to-surface-700 p-6 shadow-lg">
        <div class="relative z-10 text-white">
            <div class="flex items-center gap-3 mb-2">
                <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/15 rounded-full backdrop-blur-sm">
                    Branch Manager Portal
                </span>
                <div class="flex gap-1">
                    <div class="w-2 h-2 rounded-sm bg-alja-magenta"></div>
                    <div class="w-2 h-2 rounded-sm bg-white/80"></div>
                    <div class="w-2 h-2 rounded-sm bg-alja-yellow"></div>
                </div>
            </div>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Main Branch Overview</h1>
            <p class="mt-1 text-white/70 max-w-xl text-sm">
                Manage your shop inventory, adjust pricing engine parameters, oversee printing machinery, and coordinate staff.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-alja-magenta/10 blur-2xl"></div>
        <div class="absolute -left-8 -top-8 h-32 w-32 rounded-full bg-alja-yellow/10 blur-2xl"></div>
    </div>

    <!-- Feedback alert -->
    @if ($showSuccessAlert)
        <div class="rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4 backdrop-blur-sm flex justify-between items-center transition" x-data="{ show: true }" x-show="show">
            <div class="flex items-center">
                <svg class="mr-2 h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-sm font-semibold text-emerald-300">{{ $successMessage }}</span>
            </div>
            <button type="button" @click="show = false; $wire.set('showSuccessAlert', false)" class="text-emerald-400 hover:text-emerald-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    @endif

    <!-- Tabs Menu -->
    <div class="flex border-b border-gray-200 dark:border-surface-300/30 gap-2 mb-2">
        <button wire:click="setTab('inventory')" class="py-2.5 px-4 text-xs font-semibold border-b-2 transition-all duration-200 {{ $activeTab === 'inventory' ? 'border-alja-magenta text-alja-magenta' : 'border-transparent text-gray-400 hover:text-gray-200' }}">
            Overview & Inventory
        </button>
        <button wire:click="setTab('users')" class="py-2.5 px-4 text-xs font-semibold border-b-2 transition-all duration-200 {{ $activeTab === 'users' ? 'border-alja-magenta text-alja-magenta' : 'border-transparent text-gray-400 hover:text-gray-200' }}">
            User Management
        </button>
    </div>

    @if ($activeTab === 'inventory')
        <!-- Quick stats grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Stat card 1 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Stock Materials</p>
                        <h3 class="mt-1 text-2xl font-bold text-white">{{ $materialCount }}</h3>
                    </div>
                    <div class="rounded-xl bg-alja-magenta/10 p-3 text-alja-magenta">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-emerald-400 font-semibold">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Active stock tracking enabled</span>
                </div>
            </div>

            <!-- Stat card 2 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Printers & Machinery</p>
                        <h3 class="mt-1 text-2xl font-bold text-white">{{ $machineCount }}</h3>
                    </div>
                    <div class="rounded-xl bg-alja-cyan/10 p-3 text-alja-cyan">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-alja-cyan font-semibold">
                    <span class="h-2 w-2 rounded-full bg-alja-cyan animate-ping mr-2"></span>
                    <span>All machines online</span>
                </div>
            </div>

            <!-- Stat card 3 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Active Staff</p>
                        <h3 class="mt-1 text-2xl font-bold text-white">{{ $staffCount }}</h3>
                    </div>
                    <div class="rounded-xl bg-alja-yellow/10 p-3 text-alja-yellow">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-alja-yellow/70 font-semibold">
                    <span>Managed across 5 roles</span>
                </div>
            </div>

            <!-- Stat card 4 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Pricing Engine Rows</p>
                        <h3 class="mt-1 text-2xl font-bold text-white">{{ $pricingRulesCount }}</h3>
                    </div>
                    <div class="rounded-xl bg-emerald-500/10 p-3 text-emerald-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-emerald-400 font-semibold">
                    <span>Active price rule matrix</span>
                </div>
            </div>
        </div>

        <!-- Management actions and recent stock ledger -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Stock Restock Payout form -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card">
                <h3 class="text-lg font-bold text-white">Material Restocking</h3>
                <p class="mt-1 text-xs text-gray-500 mb-4">Replenish material sheet or banner rolls inventory levels.</p>
                
                <form wire:submit.prevent="addMaterialStock" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">Select Material</label>
                        <select wire:model="selectedMaterialId" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 focus:outline-none focus:border-alja-magenta focus:ring-1 focus:ring-alja-magenta/30 text-white transition-colors duration-200" required>
                            <option value="">-- Choose Material to Restock --</option>
                            @foreach ($allMaterials as $mat)
                                <option value="{{ $mat->id }}">{{ $mat->name }} {{ $mat->dimensions ? "({$mat->dimensions})" : '' }} [{{ $mat->category }}]</option>
                            @endforeach
                        </select>
                        @error('selectedMaterialId') <span class="text-red-400 text-[10px] font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">Added Restock Qty</label>
                        <input type="number" step="any" min="0.01" wire:model="addStockQuantity" placeholder="e.g. 500 or 12.5" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 focus:outline-none focus:border-alja-magenta focus:ring-1 focus:ring-alja-magenta/30 text-white transition-colors duration-200" required>
                        @error('addStockQuantity') <span class="text-red-400 text-[10px] font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-500 mb-1">Restocking Notes</label>
                        <textarea wire:model="addStockNotes" placeholder="Restock batch invoice number, vendor name, etc..." class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 focus:outline-none focus:border-alja-magenta focus:ring-1 focus:ring-alja-magenta/30 text-white h-20 resize-none transition-colors duration-200"></textarea>
                        @error('addStockNotes') <span class="text-red-400 text-[10px] font-semibold">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-alja-magenta hover:bg-alja-magenta-hover text-white font-bold py-2.5 transition-all duration-200 shadow-glow-magenta active:scale-[0.98] uppercase tracking-wider text-[11px]">
                        Register Restock Entry
                    </button>
                </form>
            </div>

            <!-- Recent Stock ledger logs -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card lg:col-span-2">
                <h3 class="text-lg font-bold text-white">Recent Inventory Ledger Logs</h3>
                <p class="mt-1 text-xs text-gray-500 mb-4">Stock ledger activity adjustments logs.</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-400">
                        <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="py-2.5 px-3 rounded-l-lg">Date</th>
                                <th class="py-2.5 px-3">Material</th>
                                <th class="py-2.5 px-3">Initial</th>
                                <th class="py-2.5 px-3">Added</th>
                                <th class="py-2.5 px-3">Wasted</th>
                                <th class="py-2.5 px-3 text-right rounded-r-lg">Ledger Remaining</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30 text-[11px]">
                            @forelse ($stockLogs as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition-colors duration-150">
                                    <td class="py-2.5 px-3 font-medium">{{ $log->logged_at }}</td>
                                    <td class="py-2.5 px-3 font-semibold text-white">
                                        {{ $log->material_name }}
                                    </td>
                                    <td class="py-2.5 px-3">{{ $log->initial_stock }}</td>
                                    <td class="py-2.5 px-3 text-emerald-400 font-semibold">+{{ $log->added_stock }}</td>
                                    <td class="py-2.5 px-3 text-red-400 font-semibold">{{ $log->wasted_stock }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-alja-cyan">{{ $log->remaining_stock }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-500">No stock ledger entries registered.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- DETAILED STOCK LEVEL WITH LOW STOCK WARN & RECENT EXPENSES -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Stock Levels Ledger -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card lg:col-span-2">
                <h3 class="text-lg font-bold text-white">Material Inventory Levels</h3>
                <p class="mt-1 text-xs text-gray-500 mb-4">Stock balances with low stock highlight flags.</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-400">
                        <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="py-2 px-2 rounded-l-lg">Material</th>
                                <th class="py-2 px-2">Initial</th>
                                <th class="py-2 px-2">Added</th>
                                <th class="py-2 px-2">Sold</th>
                                <th class="py-2 px-2">Wasted</th>
                                <th class="py-2 px-2 text-right rounded-r-lg">Remaining Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30 text-[11px]">
                            @foreach ($materialsList as $mItem)
                                @php
                                    $isLow = $mItem->remaining_stock <= 10.0;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition-colors duration-150 {{ $isLow ? 'bg-red-500/5 text-red-400' : '' }}">
                                    <td class="py-2 px-2 font-semibold">
                                        {{ $mItem->name }} 
                                        @if ($mItem->dimensions) <span class="text-[9px] font-normal text-gray-600">({{ $mItem->dimensions }})</span> @endif
                                    </td>
                                    <td class="py-2 px-2">{{ $mItem->initial_stock }}</td>
                                    <td class="py-2 px-2 text-emerald-400 font-semibold">+{{ $mItem->added_stock }}</td>
                                    <td class="py-2 px-2 text-alja-cyan font-semibold">{{ $mItem->sold_stock }}</td>
                                    <td class="py-2 px-2 text-red-400 font-semibold">{{ $mItem->wasted_stock }}</td>
                                    <td class="py-2 px-2 text-right font-black">
                                        {{ $mItem->remaining_stock }}
                                        @if ($isLow)
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[8px] bg-red-950/30 font-black tracking-wider uppercase ml-1 animate-pulse">Low</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Expenses log -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card lg:col-span-1">
                <h3 class="text-lg font-bold text-white mb-1">Expenses Ledger</h3>
                <p class="text-xs text-gray-500 mb-4">Drawer payouts audit tracking logs.</p>

                <div class="space-y-3.5 max-h-[400px] overflow-y-auto pr-1">
                    @forelse ($expenses as $exp)
                        <div class="p-3 rounded-xl border border-gray-200 dark:border-surface-300/30 bg-white dark:bg-surface-600/50 text-xs relative hover:border-surface-200/40 transition-colors duration-150">
                            <div class="flex justify-between items-start">
                                <span class="font-bold text-white uppercase">{{ $exp->cashier_name }}</span>
                                <span class="font-black text-red-400">Rp. {{ number_format($exp->amount) }}</span>
                            </div>
                            <div class="text-[10px] text-gray-500 mt-1 flex justify-between">
                                <span>{{ $exp->incurred_at }}</span>
                                <span class="font-semibold uppercase text-alja-cyan">{{ $exp->type }}</span>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-1.5 italic">{{ $exp->description }}</p>
                        </div>
                    @empty
                        <div class="text-center text-xs text-gray-500 py-6">No expenses recorded.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @elseif ($activeTab === 'users')
        <!-- User Management Panel -->
        <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="relative flex-1 max-w-md">
                    <input type="text" wire:model.live="userSearch" placeholder="Search staff name or username..." class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 pl-9 focus:outline-none focus:border-alja-magenta focus:ring-1 focus:ring-alja-magenta/30 transition-colors duration-200 text-xs">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
                <button wire:click="openCreateModal" class="rounded-xl bg-alja-magenta hover:bg-alja-magenta-hover text-white font-bold py-2 px-4 shadow-glow-magenta active:scale-[0.98] transition duration-150 uppercase tracking-wider text-[11px]">
                    Add Staff Member
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-400">
                    <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-2.5 px-3 rounded-l-lg">Name</th>
                            <th class="py-2.5 px-3">Username</th>
                            <th class="py-2.5 px-3">Email</th>
                            <th class="py-2.5 px-3">Role</th>
                            <th class="py-2.5 px-3">Phone</th>
                            <th class="py-2.5 px-3">Address</th>
                            <th class="py-2.5 px-3 text-right rounded-r-lg">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30 text-[11px]">
                        @forelse ($usersList as $u)
                            @php
                                $isRestricted = in_array($u->role, ['owner', 'manager']);
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition-colors duration-150">
                                <td class="py-2.5 px-3 font-semibold text-white">{{ $u->name }}</td>
                                <td class="py-2.5 px-3">{{ $u->username }}</td>
                                <td class="py-2.5 px-3">{{ $u->email }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider
                                        {{ $u->role === 'owner' ? 'bg-red-500/10 text-red-400' : '' }}
                                        {{ $u->role === 'manager' ? 'bg-orange-500/10 text-orange-400' : '' }}
                                        {{ $u->role === 'cashier' ? 'bg-alja-cyan/10 text-alja-cyan' : '' }}
                                        {{ $u->role === 'designer' ? 'bg-alja-magenta/10 text-alja-magenta' : '' }}
                                        {{ $u->role === 'production' ? 'bg-alja-yellow/10 text-alja-yellow' : '' }}
                                    ">
                                        {{ $u->role }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3">{{ $u->phone ?: '-' }}</td>
                                <td class="py-2.5 px-3 truncate max-w-[150px]">{{ $u->address ?: '-' }}</td>
                                <td class="py-2.5 px-3 text-right">
                                    @if ($isRestricted)
                                        <div class="flex justify-end text-gray-500 pr-4" title="Security Lock: Managers cannot edit owners or other managers">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        </div>
                                    @else
                                        <div class="flex justify-end gap-2">
                                            <button wire:click="openEditModal({{ $u->id }})" class="text-alja-cyan hover:text-alja-cyan-light font-semibold uppercase tracking-wider text-[10px]">Edit</button>
                                            <button wire:click="deleteUser({{ $u->id }})" onclick="confirm('Are you sure you want to delete this staff member?') || event.stopImmediatePropagation()" class="text-red-400 hover:text-red-300 font-semibold uppercase tracking-wider text-[10px]">Delete</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">No staff members found matching search query.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- ADD/EDIT USER MODAL -->
    @if ($showUserModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-surface-650 border border-gray-200 dark:border-surface-300/40 shadow-2xl p-6 relative text-xs text-gray-800 dark:text-gray-200">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-150 dark:border-gray-750 pb-3 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                            {{ $userIdBeingEdited ? 'Edit Staff Member' : 'Add Staff Member' }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Manage user credentials and dashboard access role.</p>
                    </div>
                    <button wire:click="$set('showUserModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="saveUser" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Full Name</label>
                            <input type="text" wire:model="formName" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium" required>
                            @error('formName') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Username</label>
                            <input type="text" wire:model="formUsername" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium" required>
                            @error('formUsername') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Email Address</label>
                            <input type="email" wire:model="formEmail" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium" required>
                            @error('formEmail') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Staff Role</label>
                            <select wire:model="formRole" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                                <option value="cashier" class="text-gray-900 bg-white">Cashier (cashier)</option>
                                <option value="designer" class="text-gray-900 bg-white">Designer (designer)</option>
                                <option value="production" class="text-gray-900 bg-white">Production (production)</option>
                            </select>
                            @error('formRole') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Password {{ $userIdBeingEdited ? '(Leave blank to keep current)' : '' }}</label>
                            <input type="password" wire:model="formPassword" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium" {{ $userIdBeingEdited ? '' : 'required' }}>
                            @error('formPassword') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Phone Number</label>
                            <input type="text" wire:model="formPhone" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                            @error('formPhone') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Address</label>
                        <input type="text" wire:model="formAddress" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                        @error('formAddress') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-2 border-t border-gray-150 dark:border-gray-750 pt-4">
                        <button type="button" wire:click="$set('showUserModal', false)" class="rounded-xl border border-gray-300 dark:border-surface-300/40 bg-white dark:bg-surface-600 text-gray-800 dark:text-white px-4 py-2 hover:bg-gray-100 dark:hover:bg-surface-500 transition duration-150 font-bold uppercase tracking-wider text-[10px]">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-alja-magenta hover:bg-alja-magenta-hover text-white px-4 py-2 shadow-glow-magenta active:scale-[0.98] transition duration-150 font-bold uppercase tracking-wider text-[10px]">
                            Save Staff
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
