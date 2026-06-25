<?php

use Livewire\Volt\Component;

new class extends Component {
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    public string $period = 'Monthly';
    public $selectedBranchId = 'all';

    // Active tab
    public $activeTab = 'finance';

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
    public $formBranchId = 1;

    // Alert feedback
    public $showSuccessAlert = false;
    public $successMessage = '';

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetErrorBag();
        $this->showSuccessAlert = false;
    }

    public function mount()
    {
        // Owner dashboard initializations
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
        $this->formBranchId = 1;
        $this->showUserModal = true;
    }

    public function openEditModal($userId)
    {
        $this->resetErrorBag();
        $user = \App\Models\User::findOrFail($userId);

        // Restriction Guard: Only Primary Owner (ID = 1) can edit other owners.
        // Secondary owners (ID > 1) cannot edit the primary owner or other owners.
        if ($user->role === 'owner' && auth()->user()->id !== 1) {
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
        $this->formBranchId = $user->branch_id ?: 1;
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
            'formRole' => 'required|string|in:owner,manager,cashier,designer,production',
            'formBranchId' => 'required|integer|exists:branches,id',
        ];

        if (!$this->userIdBeingEdited) {
            $rules['formPassword'] = 'required|string|min:6';
        } else {
            $rules['formPassword'] = 'nullable|string|min:6';
        }

        $this->validate($rules);

        if ($this->userIdBeingEdited) {
            $user = \App\Models\User::findOrFail($this->userIdBeingEdited);

            // Restriction Guards
            if ($user->role === 'owner' && auth()->user()->id !== 1) {
                abort(403, 'Unauthorized.');
            }
            if ($this->formRole === 'owner' && auth()->user()->id !== 1) {
                abort(403, 'Unauthorized.');
            }

            $data = [
                'name' => $this->formName,
                'username' => $this->formUsername,
                'email' => $this->formEmail,
                'phone' => $this->formPhone,
                'address' => $this->formAddress,
                'role' => $this->formRole,
                'branch_id' => $this->formBranchId,
            ];

            if (!empty($this->formPassword)) {
                $data['password'] = \Illuminate\Support\Facades\Hash::make($this->formPassword);
            }

            $user->update($data);
            $this->successMessage = 'User updated successfully!';
        } else {
            // Secondary owner cannot create owner accounts
            if ($this->formRole === 'owner' && auth()->user()->id !== 1) {
                abort(403, 'Unauthorized.');
            }

            \App\Models\User::create([
                'name' => $this->formName,
                'username' => $this->formUsername,
                'email' => $this->formEmail,
                'phone' => $this->formPhone,
                'address' => $this->formAddress,
                'role' => $this->formRole,
                'branch_id' => $this->formBranchId,
                'password' => \Illuminate\Support\Facades\Hash::make($this->formPassword),
            ]);
            $this->successMessage = 'User created successfully!';
        }

        $this->showUserModal = false;
        $this->showSuccessAlert = true;
    }

    public function deleteUser($userId)
    {
        $user = \App\Models\User::findOrFail($userId);

        // Restriction Guards
        if ($user->role === 'owner' && auth()->user()->id !== 1) {
            abort(403, 'Unauthorized.');
        }

        $user->delete();

        $this->successMessage = 'User deleted successfully!';
        $this->showSuccessAlert = true;
    }

    public function with(): array
    {
        $branchId = $this->selectedBranchId;
        $period = $this->period;

        // Date range calculation
        $startDate = null;
        $endDate = null;

        if ($period === 'Daily') {
            $startDate = now()->toDateString();
            $endDate = now()->toDateString();
        } elseif ($period === 'Weekly') {
            $startDate = now()->startOfWeek()->toDateString();
            $endDate = now()->endOfWeek()->toDateString();
        } else { // Monthly
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
        }

        // Build invoice query base
        $invoiceQuery = \App\Models\Invoice::query();
        if ($branchId !== 'all') {
            $invoiceQuery->where('branch_id', (int) $branchId);
        }
        $invoiceQuery->whereBetween('created_date', [$startDate, $endDate]);

        // Sum values
        $revenue = (int) $invoiceQuery->sum('grand_total');
        $cost = (int) $invoiceQuery->sum('total_cost');
        $profit = (int) $invoiceQuery->sum('total_profit');

        // Build expenses query base
        $expenseQuery = \App\Models\Expense::query();
        if ($branchId !== 'all') {
            $expenseQuery->where('branch_id', (int) $branchId);
        }
        $expenseQuery->whereBetween('incurred_at', [$startDate, $endDate]);
        $expensesTotal = (int) $expenseQuery->sum('amount');

        // Net margin rate
        $marginRate = $revenue > 0 ? ($profit / $revenue) * 100 : 0.0;

        // Leaderboard: group invoices by customer_service (CS designer) in the range
        $leaderQuery = \App\Models\Invoice::query()
            ->select('customer_service as name', \Illuminate\Support\Facades\DB::raw('count(*) as orders_count'), \Illuminate\Support\Facades\DB::raw('sum(grand_total) as total_value'))
            ->whereNotNull('customer_service')
            ->whereBetween('created_date', [$startDate, $endDate]);
        if ($branchId !== 'all') {
            $leaderQuery->where('branch_id', (int) $branchId);
        }
        $leaders = $leaderQuery->groupBy('customer_service')
            ->orderBy('total_value', 'desc')
            ->get();

        // Sparkline coordinates: divide the selected range into 4 intervals
        $sparklinePoints = [];
        $carbonStart = \Carbon\Carbon::parse($startDate);
        $carbonEnd = \Carbon\Carbon::parse($endDate);
        
        $diffInDays = $carbonStart->diffInDays($carbonEnd);
        if ($diffInDays < 3) {
            // For Daily: query profit for the last 7 days to make a nice chart
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $q = \App\Models\Invoice::where('created_date', $date);
                if ($branchId !== 'all') {
                    $q->where('branch_id', (int) $branchId);
                }
                $sparklinePoints[] = (int) $q->sum('total_profit');
            }
        } else {
            // Divide range into 4 sub-periods
            $step = max(1, (int)($diffInDays / 3));
            for ($i = 0; $i < 4; $i++) {
                $subStart = $carbonStart->copy()->addDays($i * $step)->toDateString();
                $subEnd = $carbonStart->copy()->addDays(($i + 1) * $step - 1)->toDateString();
                if ($i === 3) {
                    $subEnd = $endDate;
                }
                $q = \App\Models\Invoice::whereBetween('created_date', [$subStart, $subEnd]);
                if ($branchId !== 'all') {
                    $q->where('branch_id', (int) $branchId);
                }
                $sparklinePoints[] = (int) $q->sum('total_profit');
            }
        }

        // Map points to SVG coordinates
        $svgPath = 'M 0 80 L 300 80'; // Default flat line
        $svgPoints = [];
        $maxVal = max($sparklinePoints);
        $minVal = min($sparklinePoints);
        $valRange = $maxVal - $minVal;

        if ($maxVal > 0) {
            $svgPoints = [];
            $count = count($sparklinePoints);
            for ($idx = 0; $idx < $count; $idx++) {
                // Guard: if only 1 point, avoid divide-by-zero ($count - 1 = 0)
                // Place single point at horizontal center
                $x = $count > 1 ? (int) (($idx / ($count - 1)) * 300) : 150;
                $val = $sparklinePoints[$idx];
                if ($valRange > 0) {
                    $y = 80 - (int) ((($val - $minVal) / $valRange) * 60);
                } else {
                    $y = 50;
                }
                $svgPoints[] = "$x $y";
            }
            if ($svgPoints) {
                $svgPath = "M " . implode(" L ", $svgPoints);
            }
        }

        $allBranches = \App\Models\Branch::all();

        // 6. User Management list (if active)
        $usersList = [];
        if ($this->activeTab === 'users') {
            $query = \App\Models\User::query()
                ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
                ->select('users.*', 'branches.name as branch_name');
            if (!empty($this->userSearch)) {
                $query->where(function($q) {
                    $q->where('users.name', 'like', '%' . $this->userSearch . '%')
                      ->orWhere('users.username', 'like', '%' . $this->userSearch . '%');
                });
            }
            $usersList = $query->orderBy('users.name')->get();
        }

        return compact(
            'revenue',
            'cost',
            'profit',
            'expensesTotal',
            'marginRate',
            'leaders',
            'svgPath',
            'sparklinePoints',
            'svgPoints',
            'allBranches',
            'usersList'
        );
    }
}; ?>

<div class="space-y-6">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-alja-magenta via-[#B0185F] to-surface-700 p-6 shadow-lg">
        <div class="relative z-10 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/15 rounded-full backdrop-blur-sm">
                        Executive Suite
                    </span>
                    <!-- CMYK dots -->
                    <div class="flex gap-1">
                        <div class="w-2 h-2 rounded-sm bg-white/80"></div>
                        <div class="w-2 h-2 rounded-sm bg-alja-cyan"></div>
                        <div class="w-2 h-2 rounded-sm bg-alja-yellow"></div>
                    </div>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight">Financial & Performance Dashboard</h1>
                <p class="mt-1 text-white/70 max-w-xl text-sm">
                    Overview of global print shop profits, expenses, cash drawer flows, and individual designer/cashier metrics.
                </p>
            </div>
            
            <!-- Controls (Branch select & Period select) -->
            <div class="flex flex-wrap items-center gap-3 self-start">
                <!-- Branch dropdown -->
                <div class="relative">
                    <select wire:model.live="selectedBranchId" class="block w-40 rounded-xl bg-white/15 border-0 py-1.5 pl-3 pr-8 text-xs font-semibold text-white focus:ring-2 focus:ring-white/20 backdrop-blur-sm cursor-pointer appearance-none">
                        <option value="all" class="text-gray-900 bg-white">All Branches</option>
                        @foreach($allBranches as $branch)
                            <option value="{{ $branch->id }}" class="text-gray-900 bg-white">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                        <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>

                <!-- Period selectors -->
                <div class="flex items-center gap-1.5 bg-white/10 p-1 rounded-xl backdrop-blur-sm text-xs font-semibold">
                    <button wire:click="$set('period', 'Daily')" class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Daily' ? 'bg-white text-alja-magenta shadow-sm' : 'hover:bg-white/10' }}">Daily</button>
                    <button wire:click="$set('period', 'Weekly')" class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Weekly' ? 'bg-white text-alja-magenta shadow-sm' : 'hover:bg-white/10' }}">Weekly</button>
                    <button wire:click="$set('period', 'Monthly')" class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Monthly' ? 'bg-white text-alja-magenta shadow-sm' : 'hover:bg-white/10' }}">Monthly</button>
                </div>
            </div>
        </div>
        <!-- Decorative blur blob -->
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-alja-cyan/15 blur-2xl"></div>
        <div class="absolute -left-8 -top-8 h-32 w-32 rounded-full bg-alja-yellow/10 blur-2xl"></div>
    </div>

    <!-- Tabs Menu -->
    <div class="flex border-b border-gray-200 dark:border-surface-300/30 gap-2 mb-2">
        <button wire:click="setTab('finance')" class="py-2.5 px-4 text-xs font-semibold border-b-2 transition-all duration-200 {{ $activeTab === 'finance' ? 'border-alja-magenta text-alja-magenta' : 'border-transparent text-gray-400 hover:text-gray-200' }}">
            Financial Overview
        </button>
        <button wire:click="setTab('users')" class="py-2.5 px-4 text-xs font-semibold border-b-2 transition-all duration-200 {{ $activeTab === 'users' ? 'border-alja-magenta text-alja-magenta' : 'border-transparent text-gray-400 hover:text-gray-200' }}">
            User Management
        </button>
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

    @if ($activeTab === 'finance')
        <!-- Quick stats grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Stat card 1 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Gross Revenue (Omset)</p>
                        <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($revenue, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-xl bg-alja-magenta/10 p-3 text-alja-magenta">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-emerald-600 dark:text-emerald-400">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Active {{ $period }} Transactions</span>
                </div>
            </div>

            <!-- Stat card 2 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Base Cost (HPP)</p>
                        <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($cost, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-xl bg-alja-yellow/10 p-3 text-alja-yellow">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-alja-yellow/80">
                    <span>Material & consumable usage</span>
                </div>
            </div>

            <!-- Stat card 3 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Net Profit (Laba)</p>
                        <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($profit, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-xl bg-emerald-500/10 p-3 text-emerald-500 dark:text-emerald-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                    <span>Margin Rate: {{ number_format($marginRate, 1, ',', '.') }}%</span>
                </div>
            </div>

            <!-- Stat card 4 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Expenses (Pengeluaran)</p>
                        <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($expensesTotal, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-xl bg-red-500/10 p-3 text-red-500 dark:text-red-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-red-500 dark:text-red-400 font-semibold">
                    <span>Operational expenses</span>
                </div>
            </div>
        </div>

        <!-- Analytical graphs & staff leaderboard -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Sales performance Sparkline graph -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Profit Analysis Sparkline</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $period === 'Daily' ? 'Last 7 days' : ($period === 'Weekly' ? 'Weekly' : 'Monthly') }} net profit margins trend.
                        </p>
                    </div>
                    <span class="rounded-full bg-alja-magenta/10 px-3 py-1 text-[10px] font-bold text-alja-magenta uppercase tracking-wider">Live margins</span>
                </div>

                <!-- Premium SVG Chart -->
                <div class="mt-6 h-40 w-full relative">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 300 100" preserveAspectRatio="none">
                        <!-- Grid Lines -->
                        <line x1="0" y1="20" x2="300" y2="20" stroke="rgba(156, 163, 175, 0.2)" stroke-dasharray="4" />
                        <line x1="0" y1="50" x2="300" y2="50" stroke="rgba(156, 163, 175, 0.2)" stroke-dasharray="4" />
                        <line x1="0" y1="80" x2="300" y2="80" stroke="rgba(156, 163, 175, 0.2)" stroke-dasharray="4" />
                        
                        <!-- Gradient under graph -->
                        <defs>
                            <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="rgb(233, 30, 140)" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="rgb(233, 30, 140)" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>

                        @if(count($sparklinePoints) > 1 && max($sparklinePoints) > 0)
                            <path d="{{ $svgPath }} L 300 100 L 0 100 Z" fill="url(#chartGradient)" />
                            <!-- Graph line -->
                            <path d="{{ $svgPath }}" fill="none" stroke="rgb(233, 30, 140)" stroke-width="2.5" stroke-linecap="round" />
                            
                            <!-- Pulsing hover point -->
                            @if(count($svgPoints) > 0)
                                @php
                                    $lastPoint = end($svgPoints);
                                    $coords = explode(' ', $lastPoint);
                                    $cx = $coords[0] ?? 300;
                                    $cy = $coords[1] ?? 80;
                                    $cx = is_numeric($cx) ? $cx : 300;
                                    $cy = is_numeric($cy) ? $cy : 80;
                                @endphp
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="4.5" fill="rgb(233, 30, 140)" />
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="9" fill="rgb(233, 30, 140)" fill-opacity="0.2" class="animate-ping" />
                            @endif
                        @else
                            <!-- Flat baseline and message -->
                            <path d="M 0 80 L 300 80" fill="none" stroke="rgba(156, 163, 175, 0.4)" stroke-width="2" stroke-dasharray="4" />
                            <text x="150" y="50" text-anchor="middle" fill="currentColor" class="text-gray-400 dark:text-gray-600 text-xs font-semibold">No sales recorded for this period</text>
                        @endif
                    </svg>
                </div>
                
                <div class="mt-4 flex items-center justify-between text-[11px] text-gray-500 font-semibold">
                    @if($period === 'Daily')
                        <span>7 days ago</span>
                        <span>4 days ago</span>
                        <span>Today</span>
                    @elseif($period === 'Weekly')
                        <span>Mon</span>
                        <span>Wed</span>
                        <span>Fri</span>
                        <span>Sun</span>
                    @else
                        <span>Week 1</span>
                        <span>Week 2</span>
                        <span>Week 3</span>
                        <span>Week 4</span>
                    @endif
                </div>
            </div>

            <!-- Leaderboard / Staff performance -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Designer Leaderboard</h3>
                <p class="mt-1 text-xs text-gray-500">Intake metrics and order totals by CS designer.</p>

                <div class="mt-4 space-y-4">
                    @forelse($leaders as $index => $leader)
                        @php
                            $colors = [
                                ['bg-alja-magenta/15', 'text-alja-magenta'],
                                ['bg-alja-cyan/15', 'text-alja-cyan'],
                                ['bg-alja-yellow/15', 'text-alja-yellow'],
                            ];
                            $color = $colors[$index % count($colors)];
                            
                            $initials = '';
                            $words = explode(' ', $leader->name);
                            foreach($words as $w) {
                                $initials .= strtoupper(substr($w, 0, 1));
                            }
                            $initials = substr($initials, 0, 2) ?: 'CS';
                            
                            $valStr = $leader->total_value >= 1000000 
                                ? 'Rp. ' . number_format($leader->total_value / 1000000, 1, ',', '.') . 'M'
                                : 'Rp. ' . number_format($leader->total_value, 0, ',', '.');
                        @endphp
                        <div class="flex items-center justify-between p-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-surface-400/30 transition-colors duration-150">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full {{ $color[0] }} {{ $color[1] }} flex items-center justify-center font-bold text-xs">
                                    {{ $initials }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $leader->name }}</p>
                                    <p class="text-[10px] text-gray-500">CS & Design Specialist</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $leader->orders_count }} orders</p>
                                <p class="text-[10px] text-emerald-650 dark:text-emerald-400">{{ $valStr }} val</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-xs text-gray-400 dark:text-gray-600">
                            No designer metrics for this period.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @elseif ($activeTab === 'users')
        <!-- User Management Panel -->
        <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card space-y-6 text-xs text-gray-200">
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
                            <th class="py-2.5 px-3">Branch</th>
                            <th class="py-2.5 px-3">Phone</th>
                            <th class="py-2.5 px-3">Address</th>
                            <th class="py-2.5 px-3 text-right rounded-r-lg">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30 text-[11px]">
                        @forelse ($usersList as $u)
                            @php
                                $canManage = true;
                                if ($u->role === 'owner' && auth()->user()->id !== 1) {
                                    $canManage = false;
                                }
                                $isSelf = ($u->id === auth()->user()->id);
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
                                <td class="py-2.5 px-3 text-gray-300 font-semibold">{{ $u->branch_name ?: 'Global' }}</td>
                                <td class="py-2.5 px-3">{{ $u->phone ?: '-' }}</td>
                                <td class="py-2.5 px-3 truncate max-w-[150px]">{{ $u->address ?: '-' }}</td>
                                <td class="py-2.5 px-3 text-right">
                                    @if (!$canManage)
                                        <div class="flex justify-end text-gray-500 pr-4" title="Security Lock: Only the Primary Owner (ID = 1) can edit or delete owner accounts">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                        </div>
                                    @else
                                        <div class="flex justify-end gap-2">
                                            <button wire:click="openEditModal({{ $u->id }})" class="text-alja-cyan hover:text-alja-cyan-light font-semibold uppercase tracking-wider text-[10px]">Edit</button>
                                            @if (!$isSelf)
                                                <button wire:click="deleteUser({{ $u->id }})" onclick="confirm('Are you sure you want to delete this user?') || event.stopImmediatePropagation()" class="text-red-400 hover:text-red-300 font-semibold uppercase tracking-wider text-[10px]">Delete</button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-4 text-center text-gray-500">No users found matching search query.</td>
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
                            {{ $userIdBeingEdited ? 'Edit User Account' : 'Add User Account' }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Configure user settings, role classification, and branch scoping.</p>
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
                                @if(auth()->user()->id === 1)
                                    <option value="owner" class="text-gray-900 bg-white">Owner (owner)</option>
                                @endif
                                <option value="manager" class="text-gray-900 bg-white">Manager (manager)</option>
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

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Assign Branch</label>
                            <select wire:model="formBranchId" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                                @foreach($allBranches as $branch)
                                    <option value="{{ $branch->id }}" class="text-gray-900 bg-white">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('formBranchId') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Address</label>
                            <input type="text" wire:model="formAddress" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-alja-magenta text-gray-900 dark:text-gray-100 font-medium">
                            @error('formAddress') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2 border-t border-gray-150 dark:border-gray-750 pt-4">
                        <button type="button" wire:click="$set('showUserModal', false)" class="rounded-xl border border-gray-300 dark:border-surface-300/40 bg-white dark:bg-surface-600 text-gray-800 dark:text-white px-4 py-2 hover:bg-gray-100 dark:hover:bg-surface-500 transition duration-150 font-bold uppercase tracking-wider text-[10px]">
                            Cancel
                        </button>
                        <button type="submit" class="rounded-xl bg-alja-magenta hover:bg-alja-magenta-hover text-white px-4 py-2 shadow-glow-magenta active:scale-[0.98] transition duration-150 font-bold uppercase tracking-wider text-[10px]">
                            Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
