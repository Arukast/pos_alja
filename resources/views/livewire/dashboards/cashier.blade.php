<?php

use Livewire\Volt\Component;
use App\Models\Expense;
use App\Models\Invoice;

new class extends Component {
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    // Expense logging properties
    public $amount = '';
    public $description = '';
    public $type = 'laba'; // Default to operations expense
    public $incurredAt = '';

    // Checkout properties
    public $selectedInvoiceId = null;
    public $paymentType = 'settle'; // 'dp' or 'settle'
    public $inputAmount = '';
    public $inputDiscount = 0;
    public $inputMethod = 'Cash';

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
            'branch_id' => auth()->user()->branch_id ?: 1,
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

    public function selectInvoice($id, $type = 'settle')
    {
        $this->selectedInvoiceId = $id;
        $this->paymentType = $type;
        $invoice = Invoice::findOrFail($id);
        
        if ($type === 'dp') {
            $this->inputAmount = (int) ($invoice->grand_total * 0.5); // suggest 50% DP
        } else {
            $this->inputAmount = $invoice->accounts_receivable;
        }
        $this->inputDiscount = 0;
        $this->inputMethod = 'Cash';
        $this->resetErrorBag();
    }

    public function closeCheckout()
    {
        $this->selectedInvoiceId = null;
    }

    public function processCheckout()
    {
        $invoice = Invoice::findOrFail($this->selectedInvoiceId);
        
        if ($this->paymentType === 'dp') {
            $this->validate([
                'inputAmount' => 'required|integer|min:1000|max:' . $invoice->grand_total,
                'inputMethod' => 'required|string|in:Cash,Transfer,QRIS',
            ]);
            
            $dpAmt = (int) $this->inputAmount;
            
            $invoice->dp_amount = $dpAmt;
            $invoice->dp_paid = $dpAmt;
            $invoice->payment_dp_method = $this->inputMethod;
            $invoice->cashier_dp_name = auth()->user()->name;
            $invoice->dp_date = now()->toDateString();
            $invoice->accounts_receivable = $invoice->grand_total - $dpAmt;
            $invoice->status = 1; // Down Payment
            $invoice->save();
            
            $this->successMessage = 'Down Payment registered successfully!';
        } else {
            $maxDiscount = $invoice->accounts_receivable;
            $this->validate([
                'inputDiscount' => 'required|integer|min:0|max:' . $maxDiscount,
                'inputAmount' => 'required|integer|min:0',
                'inputMethod' => 'required|string|in:Cash,Transfer,QRIS',
            ]);
            
            $discount = (int) $this->inputDiscount;
            $amountToPay = $invoice->accounts_receivable - $discount;
            $cashPaid = (int) $this->inputAmount;
            
            if ($cashPaid < $amountToPay) {
                $this->addError('inputAmount', 'Insufficient payment. Amount must be at least Rp. ' . number_format($amountToPay));
                return;
            }
            
            // Adjust invoice totals
            $invoice->discount += $discount;
            $invoice->grand_total -= $discount;
            $invoice->total_profit = max(0, $invoice->total_profit - $discount);
            $invoice->settlement_amount = $amountToPay;
            $invoice->payment_amount = $cashPaid;
            $invoice->change_amount = $cashPaid - $amountToPay;
            $invoice->payment_method = $this->inputMethod;
            $invoice->cashier_name = auth()->user()->name;
            $invoice->payment_date = now()->toDateString();
            $invoice->accounts_receivable = 0;
            $invoice->status = 2; // Paid / Lunas
            $invoice->save();
            
            $this->successMessage = 'Payment settled and invoice closed!';
        }
        
        event(new \App\Events\OrderUpdated());
        
        $this->selectedInvoiceId = null;
        $this->showSuccessAlert = true;
    }

    public function markAsPickedUp($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        if (auth()->user()->branch_id && $invoice->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Unauthorized branch access.');
        }

        $invoice->pickup_status = 1;
        $invoice->pickup_date = now()->toDateString();
        $invoice->save();

        $this->successMessage = 'Order marked as Picked Up!';
        $this->showSuccessAlert = true;
        
        // Broadcast via WebSocket (Reverb) for cross-role sync
        event(new \App\Events\OrderUpdated());
        
        // Also directly refresh this component in case Reverb is not running
        $this->dispatch('$refresh');
    }

    public function with(): array
    {
        $branchId = auth()->user()->branch_id ?: 1;

        // Load recent expenses logged by this branch
        $expenses = Expense::where('branch_id', $branchId)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        // Calculate today's total expenses for this branch
        $todayExpenseTotal = Expense::where('branch_id', $branchId)
            ->whereDate('incurred_at', now()->toDateString())
            ->sum('amount');

        // Query actual active unpaid/DP invoices for this branch
        $activeInvoices = Invoice::with('customer')
            ->where('branch_id', $branchId)
            ->whereIn('status', [0, 1]) // 0: Unpaid, 1: DP
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        // Calculate checkout metrics for this branch
        $awaitingCount = Invoice::where('branch_id', $branchId)->where('status', 0)->count();
        $dpCollectedToday = Invoice::where('branch_id', $branchId)
            ->whereDate('dp_date', now()->toDateString())  // Use dp_date: the date DP was actually paid
            ->where('status', 1)
            ->sum('dp_amount');
        $settlementsToday = Invoice::where('branch_id', $branchId)
            ->whereDate('payment_date', now()->toDateString())  // Use payment_date: the date settlement occurred
            ->where('status', 2)
            ->sum('settlement_amount');

        // Fetch paid, not yet picked up invoices for Customer Pickup Queue in this branch
        $pickupQueue = Invoice::with('customer')
            ->where('branch_id', $branchId)
            ->where('status', 2) // Paid / Lunas
            ->where('pickup_status', 0) // Not Picked Up
            ->orderBy('payment_date', 'asc')
            ->get();

        return compact(
            'expenses', 
            'todayExpenseTotal', 
            'activeInvoices',
            'awaitingCount',
            'dpCollectedToday',
            'settlementsToday',
            'pickupQueue'
        );
    }
}; ?>

<div class="space-y-6 text-gray-200">
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
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-alja-magenta/10 blur-2xl"></div>
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

    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat 1 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Awaiting Checkout</p>
                    <h3 class="mt-1 text-2xl font-bold text-white">{{ $awaitingCount }} Invoices</h3>
                </div>
                <div class="rounded-lg bg-alja-cyan/10 p-3 text-cyan-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-cyan-500 font-semibold">
                <span class="h-2 w-2 rounded-full bg-cyan-500 animate-ping mr-2"></span>
                <span>Unpaid jobs queue ready</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">DP Collected Today</p>
                    <h3 class="mt-1 text-2xl font-bold text-white">Rp. {{ number_format($dpCollectedToday) }}</h3>
                </div>
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3 text-alja-cyan">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-alja-cyan">
                <span>Down payment deposits</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Settlements Today</p>
                    <h3 class="mt-1 text-2xl font-bold text-white">Rp. {{ number_format($settlementsToday) }}</h3>
                </div>
                <div class="rounded-lg bg-emerald-500/10 p-3 text-emerald-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-emerald-400 font-semibold">
                <span>Completed orders pickup cash</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Store Expenses (Today)</p>
                    <h3 class="mt-1 text-2xl font-bold text-white">Rp. {{ number_format($todayExpenseTotal) }}</h3>
                </div>
                <div class="rounded-lg bg-red-500/10 p-3 text-red-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-red-400 font-semibold">
                <span>Withdrawals & modal costs today</span>
            </div>
        </div>
    </div>

    <!-- Active bills list & Log Expenses layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Billing Queue Panel -->
        <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card lg:col-span-2">
            <h3 class="text-lg font-bold text-white">Active Billing Queue</h3>
            <p class="mt-1 text-xs text-gray-500 mb-4">Invoices ready for Down Payment (DP) or final settlement.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-400">
                    <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-2.5 px-3">Invoice ID</th>
                            <th class="py-2.5 px-3">Customer</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Grand Total</th>
                            <th class="py-2.5 px-3">Remaining AR</th>
                            <th class="py-2.5 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30">
                        @forelse ($activeInvoices as $inv)
                            <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition-all">
                                <td class="py-3 px-3 font-semibold text-white">#{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td class="py-3 px-3">
                                    <p class="font-bold text-gray-800">{{ $inv->customer->name }}</p>
                                    <p class="text-[10px] text-gray-400">{{ $inv->customer->phone ?: 'No phone' }}</p>
                                </td>
                                <td class="py-3 px-3">
                                    @if ($inv->status == 0)
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-50 bg-red-950/30 text-red-600 dark:text-red-400 uppercase tracking-wider">Unpaid</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-yellow-50 dark:bg-yellow-950/30 text-yellow-600 dark:text-yellow-400 uppercase tracking-wider">Down Payment</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3">Rp. {{ number_format($inv->grand_total) }}</td>
                                <td class="py-3 px-3 font-bold text-red-400">Rp. {{ number_format($inv->accounts_receivable) }}</td>
                                <td class="py-3 px-3 text-right space-x-1 flex items-center justify-end gap-1">
                                    @if ($inv->status == 0)
                                        <button wire:click="selectInvoice({{ $inv->id }}, 'dp')" class="rounded bg-cyan-500 hover:bg-cyan-600 px-2 py-1 text-[11px] font-bold text-white shadow-sm transition">Register DP</button>
                                    @endif
                                    <button wire:click="selectInvoice({{ $inv->id }}, 'settle')" class="rounded bg-emerald-500 hover:bg-emerald-600 px-2 py-1 text-[11px] font-bold text-white shadow-sm transition">Checkout</button>
                                    <a href="/orders/{{ $inv->id }}/receipt" target="_blank" class="rounded border border-gray-300 dark:border-gray-600 text-gray-700 hover:bg-gray-100/50 px-2 py-1 text-[11px] font-bold transition">Receipt</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-gray-500">No active invoices in the checkout queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Expense Form Panel -->
        <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card lg:col-span-1">
            <h3 class="text-lg font-bold text-white">Log Store Expense</h3>
            <p class="mt-1 text-xs text-gray-500 mb-4">Record operational cash payouts.</p>

            <form wire:submit.prevent="logExpense" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Expense Amount (Rp.)</label>
                    <input type="number" wire:model="amount" placeholder="e.g. 50000" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 focus:outline-none focus:border-cyan-500 text-white" required>
                    @error('amount') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Expense Classification</label>
                    <select wire:model="type" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 focus:outline-none focus:border-cyan-500 text-white">
                        <option value="laba">Operational / Operational cost (Laba)</option>
                        <option value="modal">Capital / Purchase supplies (Modal)</option>
                    </select>
                    @error('type') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Incurred Date</label>
                    <input type="date" wire:model="incurredAt" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 focus:outline-none focus:border-cyan-500 text-white" required>
                    @error('incurredAt') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Description / Reason</label>
                    <textarea wire:model="description" placeholder="Specify items purchased, utility, or reasons..." class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 focus:outline-none focus:border-cyan-500 text-white h-20 resize-none" required></textarea>
                    @error('description') <span class="text-red-400 text-[10px]">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="w-full rounded-lg bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-2.5 transition shadow-sm">
                    Record Store Payout
                </button>
            </form>
        </div>
    </div>

    <!-- CUSTOMER PICKUP QUEUE -->
    <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card">
        <h3 class="text-lg font-bold text-gray-950 dark:text-white">Customer Pickup Queue</h3>
        <p class="mt-1 text-xs text-gray-500 mb-4">Paid orders ready to be picked up by customers.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-400">
                <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-2.5 px-3">Invoice ID</th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Payment Date</th>
                        <th class="py-2.5 px-3">Grand Total</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30">
                    @forelse ($pickupQueue as $inv)
                        <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition-all">
                            <td class="py-3 px-3 font-semibold text-gray-950 dark:text-white">#{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3 px-3">
                                <p class="font-bold text-gray-800 dark:text-gray-250">{{ $inv->customer->name }}</p>
                                <p class="text-[10px] text-gray-400">{{ $inv->customer->phone ?: 'No phone' }}</p>
                            </td>
                            <td class="py-3 px-3 text-gray-700 dark:text-gray-300">{{ $inv->payment_date }}</td>
                            <td class="py-3 px-3 text-gray-700 dark:text-gray-300">Rp. {{ number_format($inv->grand_total) }}</td>
                            <td class="py-3 px-3 text-right">
                                <button wire:click="markAsPickedUp({{ $inv->id }})" class="rounded bg-alja-magenta hover:bg-alja-magenta/90 px-3 py-1 text-[11px] font-bold text-white shadow-sm transition">Mark Picked Up</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No items ready for pickup.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- EXPENSES LEDGER LIST -->
    <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card">
        <h3 class="text-lg font-bold text-gray-950 dark:text-white mb-4">Recent Cash Drawer Expenses Logs</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-400">
                <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3">Cashier / Logged by</th>
                        <th class="py-2.5 px-3">Classification</th>
                        <th class="py-2.5 px-3">Description</th>
                        <th class="py-2.5 px-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30">
                    @forelse ($expenses as $ex)
                        <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition-all">
                            <td class="py-3 px-3 text-gray-700 dark:text-gray-300">{{ $ex->incurred_at }}</td>
                            <td class="py-3 px-3 font-semibold text-gray-950 dark:text-white">{{ $ex->cashier_name }}</td>
                            <td class="py-3 px-3">
                                @if ($ex->type === 'laba')
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-50 dark:bg-blue-950/30 text-blue-650 dark:text-blue-400 uppercase tracking-wider">Operational (Laba)</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-50 dark:bg-purple-950/30 text-purple-650 dark:text-purple-400 uppercase tracking-wider">Capital / Goods (Modal)</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-medium text-gray-750 dark:text-gray-300">{{ $ex->description }}</td>
                            <td class="py-3 px-3 text-right font-bold text-red-650 dark:text-red-400">Rp. {{ number_format($ex->amount) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-gray-500">No store expenses recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    @if ($selectedInvoiceId)
        @php
            $selInvoice = \App\Models\Invoice::with(['customer', 'orders'])->find($selectedInvoiceId);
        @endphp
        @if ($selInvoice)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-surface-650 border border-gray-200 dark:border-surface-300/40 shadow-2xl p-6 relative text-xs text-gray-805 dark:text-gray-200">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-150 dark:border-gray-750 pb-3 mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                                {{ $paymentType === 'dp' ? 'Register Down Payment' : 'Invoice Payment Checkout' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Invoice #{{ str_pad($selInvoice->id, 5, '0', STR_PAD_LEFT) }} &bull; Customer: {{ $selInvoice->customer->name }}</p>
                        </div>
                        <button wire:click="closeCheckout" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Items Summary -->
                    <div class="mb-4 space-y-1.5 max-h-32 overflow-y-auto pr-1">
                        <span class="block font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-[10px]">Order Items</span>
                        @foreach ($selInvoice->orders as $o)
                            <div class="flex justify-between text-[11px] text-gray-700 dark:text-gray-300">
                                <span>{{ $o->quantity }}x {{ $o->file_name }}</span>
                                <span class="font-semibold text-gray-950 dark:text-white">Rp. {{ number_format($o->subtotal) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- Payment Details Form -->
                    <div class="space-y-4">
                        @if ($selInvoice->status == 0)
                            <div>
                                <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wider text-[10px]">Payment Type</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 font-bold cursor-pointer text-gray-700 dark:text-gray-300">
                                        <input type="radio" wire:model.live="paymentType" value="dp" class="text-cyan-500 focus:ring-cyan-500 bg-white dark:bg-surface-600 border-gray-300 dark:border-surface-200">
                                        <span>Down Payment (DP)</span>
                                    </label>
                                    <label class="flex items-center gap-2 font-bold cursor-pointer text-gray-700 dark:text-gray-300">
                                        <input type="radio" wire:model.live="paymentType" value="settle" class="text-cyan-500 focus:ring-cyan-500 bg-white dark:bg-surface-600 border-gray-300 dark:border-surface-200">
                                        <span>Full Settlement</span>
                                    </label>
                                </div>
                            </div>
                        @else
                            <div class="bg-amber-50 dark:bg-amber-950/20 border border-amber-500/20 p-2.5 rounded-lg text-amber-700 dark:text-amber-400 font-semibold">
                                Sisa Piutang Settlement (Current DP Paid: Rp. {{ number_format($selInvoice->dp_paid) }})
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Payment Method</label>
                                <select wire:model="inputMethod" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-cyan-500 text-gray-900 dark:text-gray-100 font-medium">
                                    <option value="Cash" class="text-gray-900 bg-white">Cash</option>
                                    <option value="Transfer" class="text-gray-900 bg-white">Bank Transfer</option>
                                    <option value="QRIS" class="text-gray-900 bg-white">QRIS</option>
                                </select>
                            </div>

                            @if ($paymentType === 'settle')
                                <div>
                                    <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">Additional Discount (Rp.)</label>
                                    <input type="number" wire:model.live="inputDiscount" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2 px-3 focus:outline-none focus:border-cyan-500 text-gray-900 dark:text-gray-100 font-medium">
                                    @error('inputDiscount') <span class="text-red-650 dark:text-red-400 text-[10px]">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider text-[10px]">
                                {{ $paymentType === 'dp' ? 'DP Amount Received (Rp.)' : 'Total Cash Paid by Customer (Rp.)' }}
                            </label>
                            <input type="number" wire:model.live="inputAmount" class="w-full rounded-lg border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 py-2.5 px-3 focus:outline-none focus:border-cyan-500 text-gray-900 dark:text-gray-100 font-bold text-sm">
                            @error('inputAmount') <span class="text-red-650 dark:text-red-400 text-[10px]">{{ $message }}</span> @enderror
                        </div>

                        <!-- Calculations Preview -->
                        @php
                            $discount = (int) $inputDiscount;
                            $receivable = $selInvoice->accounts_receivable;
                            $netToPay = $paymentType === 'dp' ? (int)$inputAmount : ($receivable - $discount);
                            $paid = (int) $inputAmount;
                            $change = $paid - $netToPay;
                        @endphp
                        <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-surface-700/30 border border-gray-200 dark:border-surface-700/50 space-y-2">
                            @if ($paymentType === 'dp')
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Total Invoice:</span>
                                    <span class="text-gray-950 dark:text-white font-bold">Rp. {{ number_format($selInvoice->grand_total) }}</span>
                                </div>
                                <div class="flex justify-between text-cyan-600 dark:text-cyan-400 font-semibold">
                                    <span>Down Payment Paid:</span>
                                    <span>Rp. {{ number_format($paid) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-gray-200/50 dark:border-gray-700/50 pt-2 text-red-600 dark:text-red-400 font-bold">
                                    <span>Remaining Balance (AR):</span>
                                    <span>Rp. {{ number_format(max(0, $selInvoice->grand_total - $paid)) }}</span>
                                </div>
                            @else
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Remaining Balance:</span>
                                    <span class="text-gray-950 dark:text-white font-bold">Rp. {{ number_format($receivable) }}</span>
                                </div>
                                @if ($discount > 0)
                                    <div class="flex justify-between text-purple-600 dark:text-purple-400 font-semibold">
                                        <span>Discount Given:</span>
                                        <span>-Rp. {{ number_format($discount) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between font-medium">
                                    <span class="text-gray-500 dark:text-gray-400">Net Settle Amount:</span>
                                    <span class="text-gray-950 dark:text-white font-bold">Rp. {{ number_format(max(0, $receivable - $discount)) }}</span>
                                </div>
                                <div class="flex justify-between text-emerald-600 dark:text-emerald-400 border-t border-gray-200/50 dark:border-gray-700/50 pt-2 font-bold text-sm">
                                    <span>Change (Kembalian):</span>
                                    <span>Rp. {{ number_format(max(0, $change)) }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3 pt-2">
                            <button type="button" wire:click="closeCheckout" class="flex-1 rounded-xl border border-gray-300 dark:border-surface-200 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold py-2.5 transition">
                                Cancel
                            </button>
                            <button type="button" wire:click="processCheckout" class="flex-1 rounded-lg bg-alja-magenta hover:bg-alja-magenta-hover text-white font-bold py-2.5 transition shadow-sm">
                                Process Payment
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        @endif
    @endif
</div>
