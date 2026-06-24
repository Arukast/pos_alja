<?php

use Livewire\Volt\Component;
use App\Models\Expense;
use App\Models\Invoice;

new class extends Component {
    // Expense logging properties
    public $amount = '';
    public $description = '';
    public $type = 'laba'; // Default to operations expense
    public $incurredAt = '';

    // Alert feedback
    public $showSuccessAlert = false;
    public $successMessage = '';

    public function mount()
    {
        $this->incurredAt = now()->toDateString();
    }

    public function logExpense()
    {
        $this->validate([
            'amount' => 'required|integer|min:100',
            'type' => 'required|string|in:modal,laba',
            'incurredAt' => 'required|date',
            'description' => 'required|string|max:500',
        ]);

        Expense::create([
            'branch_id' => 1,
            'amount' => (int) $this->amount,
            'type' => $this->type,
            'incurred_at' => $this->incurredAt,
            'description' => $this->description,
            'cashier_name' => auth()->user()->name,
        ]);

        $this->reset(['amount', 'description']);
        $this->incurredAt = now()->toDateString();
        $this->type = 'laba';

        $this->successMessage = 'Expense logged successfully!';
        $this->showSuccessAlert = true;
    }

    public function with(): array
    {
        // Load recent expenses logged by any cashier/manager
        $expenses = Expense::orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // Calculate today's total expenses
        $todayExpenseTotal = Expense::whereDate('incurred_at', now()->toDateString())
            ->sum('amount');

        // Query actual active unpaid/DP invoices
        $activeInvoices = Invoice::with('customer')
            ->whereIn('status', [0, 1]) // 0: Unpaid, 1: DP
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Calculate checkout metrics
        $awaitingCount = Invoice::where('status', 0)->count();
        $dpCollectedToday = Invoice::whereDate('created_date', now()->toDateString())
            ->where('status', 1)
            ->sum('dp_amount');
        $settlementsToday = Invoice::whereDate('created_date', now()->toDateString())
            ->where('status', 2)
            ->sum('settlement_amount');

        return compact(
            'expenses', 
            'todayExpenseTotal', 
            'activeInvoices',
            'awaitingCount',
            'dpCollectedToday',
            'settlementsToday'
        );
    }
}; ?>

<div class="space-y-6 text-gray-900 dark:text-gray-100">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-500 to-blue-600 p-6 shadow-lg dark:from-cyan-600 dark:to-blue-700">
        <div class="relative z-10 text-white">
            <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/20 rounded-full backdrop-blur-sm">
                Cash Register Desk
            </span>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Active Cashier Desk</h1>
            <p class="mt-1 text-cyan-100 max-w-xl text-sm">
                Process customer payments, register down payments, settle outstanding invoices, and log cash drawer withdrawals.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
    </div>

    <!-- Feedback alert -->
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
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Awaiting Checkout</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $awaitingCount }} Invoices</h3>
                </div>
                <div class="rounded-lg bg-cyan-50 dark:bg-cyan-900/20 p-3 text-cyan-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-cyan-500 font-semibold">
                <span class="h-2 w-2 rounded-full bg-cyan-500 animate-ping mr-2"></span>
                <span>Unpaid jobs queue ready</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">DP Collected Today</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($dpCollectedToday) }}</h3>
                </div>
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3 text-blue-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-blue-500">
                <span>Down payment deposits</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Settlements Today</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($settlementsToday) }}</h3>
                </div>
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-emerald-500 font-semibold">
                <span>Completed orders pickup cash</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Store Expenses (Today)</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($todayExpenseTotal) }}</h3>
                </div>
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-red-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-red-500 font-semibold">
                <span>Withdrawals & modal costs today</span>
            </div>
        </div>
    </div>

    <!-- Active bills list & Log Expenses layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Billing Queue Panel -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Active Billing Queue</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 mb-4">Invoices ready for Down Payment (DP) or final settlement.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-2.5 px-3">Invoice ID</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Grand Total</th>
                            <th class="py-2.5 px-3">Remaining AR</th>
                            <th class="py-2.5 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                        @forelse ($activeInvoices as $inv)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                                <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">#{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td class="py-3 px-3">
                                    <p class="font-bold text-gray-800 dark:text-gray-200">{{ $inv->customer->name }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $inv->customer->phone ?: 'No phone' }}</p>
                                </td>
                                <td class="py-3 px-3">
                                    @if ($inv->status == 0)
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 uppercase tracking-wider">Unpaid</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-yellow-50 dark:bg-yellow-950/30 text-yellow-600 dark:text-yellow-400 uppercase tracking-wider">Down Payment</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">Rp. {{ number_format($inv->grand_total) }}</td>
                                <td class="py-3 px-3 font-bold text-red-500">Rp. {{ number_format($inv->accounts_receivable) }}</td>
                                <td class="py-3 px-3 text-right space-x-1">
                                    @if ($inv->status == 0)
                                        <button class="rounded bg-cyan-500 hover:bg-cyan-600 px-3 py-1 text-[11px] font-bold text-white shadow-sm transition">Register DP</button>
                                    @endif
                                    <button class="rounded bg-emerald-500 hover:bg-emerald-600 px-3 py-1 text-[11px] font-bold text-white shadow-sm transition">Checkout</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-400 dark:text-gray-500">No active invoices in the checkout queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Expense Form Panel -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm lg:col-span-1">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Log Store Expense</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 mb-4">Record operational cash payouts.</p>

            <form wire:submit.prevent="logExpense" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Expense Amount (Rp.)</label>
                    <input type="number" wire:model="amount" placeholder="e.g. 50000" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-cyan-500 text-gray-900 dark:text-white" required>
                    @error('amount') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Expense Classification</label>
                    <select wire:model="type" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-cyan-500 text-gray-900 dark:text-white">
                        <option value="laba">Operational / Operational cost (Laba)</option>
                        <option value="modal">Capital / Purchase supplies (Modal)</option>
                    </select>
                    @error('type') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Incurred Date</label>
                    <input type="date" wire:model="incurredAt" class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-cyan-500 text-gray-900 dark:text-white" required>
                    @error('incurredAt') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-gray-400 dark:text-gray-500 mb-1">Description / Reason</label>
                    <textarea wire:model="description" placeholder="Specify items purchased, utility, or reasons..." class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 py-2 px-3 focus:outline-none focus:border-cyan-500 text-gray-900 dark:text-white h-20 resize-none" required></textarea>
                    @error('description') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full rounded-lg bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-2.5 transition shadow-sm">
                    Record Store Payout
                </button>
            </form>
        </div>
    </div>

    <!-- EXPENSES LEDGER LIST -->
    <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm">
        <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-4">Recent Cash Drawer Expenses Logs</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3">Cashier / Logged by</th>
                        <th class="py-2.5 px-3">Classification</th>
                        <th class="py-2.5 px-3">Description</th>
                        <th class="py-2.5 px-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                    @forelse ($expenses as $ex)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                            <td class="py-3 px-3">{{ $ex->incurred_at }}</td>
                            <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">{{ $ex->cashier_name }}</td>
                            <td class="py-3 px-3">
                                @if ($ex->type === 'laba')
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 uppercase tracking-wider">Operational (Laba)</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400 uppercase tracking-wider">Capital / Goods (Modal)</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-medium text-gray-700 dark:text-gray-300">{{ $ex->description }}</td>
                            <td class="py-3 px-3 text-right font-bold text-red-500">Rp. {{ number_format($ex->amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-400 dark:text-gray-500">No store expenses recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
