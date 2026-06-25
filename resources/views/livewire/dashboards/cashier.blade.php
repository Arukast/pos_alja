<div class="space-y-6 text-gray-200">
    <!-- Header banner -->
    <x-brand-header-banner
        badge="Cash Register Desk"
        title="Active Cashier Desk"
        description="Process customer payments, register down payments, settle outstanding invoices, and log cash drawer withdrawals."
        theme="cashier"
    />

    <!-- Feedback alert -->
    @if ($showSuccessAlert)
    <div
        class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4 backdrop-blur-sm transition"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center">
            <svg
                class="mr-2 h-5 w-5 text-emerald-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
            </svg>
            <span class="text-sm font-semibold text-emerald-300"
                >{{ $successMessage }}</span
            >
        </div>
        <button
            type="button"
            @click="show = false; $wire.set('showSuccessAlert', false)"
            class="text-emerald-400 hover:text-emerald-300"
        >
            <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                ></path>
            </svg>
        </button>
    </div>
    @endif

    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat 1 -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card hover:shadow-card-hover hover-lift relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Awaiting Checkout
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        {{ $awaitingCount }} Invoices
                    </h3>
                </div>
                <div class="bg-brand-secondary/10 rounded-lg p-3 text-cyan-500">
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-semibold text-cyan-500"
            >
                <span
                    class="mr-2 h-2 w-2 animate-ping rounded-full bg-cyan-500"
                ></span>
                <span>Unpaid jobs queue ready</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card hover:shadow-card-hover hover-lift relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        DP Collected Today
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        Rp. {{ number_format($dpCollectedToday) }}
                    </h3>
                </div>
                <div
                    class="text-brand-secondary rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div class="text-brand-secondary mt-4 flex items-center text-xs">
                <span>Down payment deposits</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card hover:shadow-card-hover hover-lift relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Settlements Today
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        Rp. {{ number_format($settlementsToday) }}
                    </h3>
                </div>
                <div class="rounded-lg bg-emerald-500/10 p-3 text-emerald-400">
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-semibold text-emerald-400"
            >
                <span>Completed orders pickup cash</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card hover:shadow-card-hover hover-lift relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Store Expenses (Today)
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        Rp. {{ number_format($todayExpenseTotal) }}
                    </h3>
                </div>
                <div class="rounded-lg bg-red-500/10 p-3 text-red-400">
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-semibold text-red-400"
            >
                <span>Withdrawals & modal costs today</span>
            </div>
        </div>
    </div>

    <!-- Active bills list & Log Expenses layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Billing Queue Panel -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm lg:col-span-2"
        >
            <h3 class="text-lg font-bold text-white">Active Billing Queue</h3>
            <p class="mt-1 mb-4 text-xs text-gray-500">
                Invoices ready for Down Payment (DP) or final settlement.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-400">
                    <thead
                        class="dark:bg-surface-600/80 text-gray-650 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
                    >
                        <tr>
                            <th class="px-3 py-2.5">Invoice ID</th>
                            <th class="px-3 py-2.5">Customer</th>
                            <th class="px-3 py-2.5">Status</th>
                            <th class="px-3 py-2.5">Grand Total</th>
                            <th class="px-3 py-2.5">Remaining AR</th>
                            <th class="px-3 py-2.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody
                        class="dark:divide-surface-300/30 divide-y divide-gray-100"
                    >
                        @forelse ($activeInvoices as $inv)
                        <tr
                            class="dark:hover:bg-surface-400/20 transition-all hover:bg-gray-50"
                        >
                            <td class="px-3 py-3 font-semibold text-white">
                                #{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="px-3 py-3">
                                <p class="font-bold text-gray-800">
                                    {{ $inv->customer->name }}
                                </p>
                                <p class="text-[10px] text-gray-400">
                                    {{ $inv->customer->phone ?: 'No phone' }}
                                </p>
                            </td>
                            <td class="px-3 py-3">
                                @if ($inv->status == 0)
                                <span
                                    class="inline-block rounded-full bg-red-50 bg-red-950/30 px-2 py-0.5 text-[10px] font-bold tracking-wider text-red-600 uppercase dark:text-red-400"
                                    >Unpaid</span
                                >
                                @else
                                <span
                                    class="inline-block rounded-full bg-yellow-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-yellow-600 uppercase dark:bg-yellow-950/30 dark:text-yellow-400"
                                    >Down Payment</span
                                >
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                Rp. {{ number_format($inv->grand_total) }}
                            </td>
                            <td class="px-3 py-3 font-bold text-red-400">
                                Rp. {{ number_format($inv->accounts_receivable)
                                }}
                            </td>
                            <td
                                class="flex items-center justify-end gap-1 space-x-1 px-3 py-3 text-right"
                            >
                                @if ($inv->status == 0)
                                <button
                                    wire:click="selectInvoice({{ $inv->id }}, 'dp')"
                                    class="rounded bg-cyan-500 px-2 py-1 text-[11px] font-bold text-white shadow-sm transition hover:bg-cyan-600"
                                >
                                    Register DP
                                </button>
                                @endif
                                <button
                                    wire:click="selectInvoice({{ $inv->id }}, 'settle')"
                                    class="rounded bg-emerald-500 px-2 py-1 text-[11px] font-bold text-white shadow-sm transition hover:bg-emerald-600"
                                >
                                    Checkout
                                </button>
                                <a
                                    href="/orders/{{ $inv->id }}/receipt"
                                    target="_blank"
                                    class="rounded border border-gray-300 px-2 py-1 text-[11px] font-bold text-gray-700 transition hover:bg-gray-100/50 dark:border-gray-600"
                                    >Receipt</a
                                >
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="6"
                                class="py-4 text-center text-gray-500"
                            >
                                No active invoices in the checkout queue.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Log Expense Form Panel -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm lg:col-span-1"
        >
            <h3 class="text-lg font-bold text-white">Log Store Expense</h3>
            <p class="mt-1 mb-4 text-xs text-gray-500">
                Record operational cash payouts.
            </p>

            <form wire:submit.prevent="logExpense" class="space-y-4 text-xs">
                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Expense Amount (Rp.)</label
                    >
                    <input
                        type="number"
                        wire:model="amount"
                        placeholder="e.g. 50000"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-cyan-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        required
                    />
                    @error('amount')
                    <span class="text-[10px] text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Expense Classification</label
                    >
                    <select
                        wire:model="type"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-cyan-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                    >
                        <option value="laba">
                            Operational / Operational cost (Laba)
                        </option>
                        <option value="modal">
                            Capital / Purchase supplies (Modal)
                        </option>
                    </select>
                    @error('type')
                    <span class="text-[10px] text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Incurred Date</label
                    >
                    <input
                        type="date"
                        wire:model="incurredAt"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-cyan-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        required
                    />
                    @error('incurredAt')
                    <span class="text-[10px] text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Description / Reason</label
                    >
                    <textarea
                        wire:model="description"
                        placeholder="Specify items purchased, utility, or reasons..."
                        class="dark:border-surface-200 dark:bg-surface-600/50 h-20 w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-cyan-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        required
                    ></textarea>
                    @error('description')
                    <span class="text-[10px] text-red-400">{{ $message }}</span>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-cyan-500 py-2.5 font-bold text-white shadow-sm transition hover:bg-cyan-600"
                >
                    Record Store Payout
                </button>
            </form>
        </div>
    </div>

    <!-- CUSTOMER PICKUP QUEUE -->
    <div
        class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm"
    >
        <h3 class="text-lg font-bold text-gray-950 dark:text-white">
            Customer Pickup Queue
        </h3>
        <p class="mt-1 mb-4 text-xs text-gray-500">
            Paid orders ready to be picked up by customers.
        </p>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-400">
                <thead
                    class="dark:bg-surface-600/80 text-gray-650 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="px-3 py-2.5">Invoice ID</th>
                        <th class="px-3 py-2.5">Customer</th>
                        <th class="px-3 py-2.5">Payment Date</th>
                        <th class="px-3 py-2.5">Grand Total</th>
                        <th class="px-3 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody
                    class="dark:divide-surface-300/30 divide-y divide-gray-100"
                >
                    @forelse ($pickupQueue as $inv)
                    <tr
                        class="dark:hover:bg-surface-400/20 transition-all hover:bg-gray-50"
                    >
                        <td
                            class="px-3 py-3 font-semibold text-gray-950 dark:text-white"
                        >
                            #{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-3 py-3">
                            <p
                                class="dark:text-gray-250 font-bold text-gray-800"
                            >
                                {{ $inv->customer->name }}
                            </p>
                            <p class="text-[10px] text-gray-400">
                                {{ $inv->customer->phone ?: 'No phone' }}
                            </p>
                        </td>
                        <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
                            {{ $inv->payment_date }}
                        </td>
                        <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
                            Rp. {{ number_format($inv->grand_total) }}
                        </td>
                        <td class="px-3 py-3 text-right">
                            <button
                                wire:click="markAsPickedUp({{ $inv->id }})"
                                class="bg-brand-primary hover:bg-brand-primary/90 rounded px-3 py-1 text-[11px] font-bold text-white shadow-sm transition"
                            >
                                Mark Picked Up
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-gray-500">
                            No items ready for pickup.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- EXPENSES LEDGER LIST -->
    <div
        class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm"
    >
        <h3 class="mb-4 text-lg font-bold text-gray-950 dark:text-white">
            Recent Cash Drawer Expenses Logs
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-400">
                <thead
                    class="dark:bg-surface-600/80 text-gray-650 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="px-3 py-2.5">Date</th>
                        <th class="px-3 py-2.5">Cashier / Logged by</th>
                        <th class="px-3 py-2.5">Classification</th>
                        <th class="px-3 py-2.5">Description</th>
                        <th class="px-3 py-2.5 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody
                    class="dark:divide-surface-300/30 divide-y divide-gray-100"
                >
                    @forelse ($expenses as $ex)
                    <tr
                        class="dark:hover:bg-surface-400/20 transition-all hover:bg-gray-50"
                    >
                        <td class="px-3 py-3 text-gray-700 dark:text-gray-300">
                            {{ $ex->incurred_at }}
                        </td>
                        <td
                            class="px-3 py-3 font-semibold text-gray-950 dark:text-white"
                        >
                            {{ $ex->cashier_name }}
                        </td>
                        <td class="px-3 py-3">
                            @if ($ex->type === 'laba')
                            <span
                                class="text-blue-650 inline-block rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase dark:bg-blue-950/30 dark:text-blue-400"
                                >Operational (Laba)</span
                            >
                            @else
                            <span
                                class="text-purple-650 inline-block rounded-full bg-purple-50 px-2 py-0.5 text-[10px] font-bold tracking-wider uppercase dark:bg-purple-950/30 dark:text-purple-400"
                                >Capital / Goods (Modal)</span
                            >
                            @endif
                        </td>
                        <td
                            class="text-gray-750 px-3 py-3 font-medium dark:text-gray-300"
                        >
                            {{ $ex->description }}
                        </td>
                        <td
                            class="text-red-650 px-3 py-3 text-right font-bold dark:text-red-400"
                        >
                            Rp. {{ number_format($ex->amount) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-gray-500">
                            No store expenses recorded.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- CHECKOUT MODAL -->
    @if ($selectedInvoiceId) @php $selInvoice =
    \App\Models\Invoice::with(['customer', 'orders'])->find($selectedInvoiceId);
    @endphp @if ($selInvoice)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
    >
        <div
            class="dark:bg-surface-650 dark:border-surface-300/40 text-gray-805 relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 text-xs shadow-2xl dark:text-gray-200"
        >
            <!-- Header -->
            <div
                class="border-gray-150 dark:border-gray-750 mb-4 flex items-center justify-between border-b pb-3"
            >
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">
                        {{ $paymentType === 'dp' ? 'Register Down Payment' :
                        'Invoice Payment Checkout' }}
                    </h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        Invoice #{{ str_pad($selInvoice->id, 5, '0',
                        STR_PAD_LEFT) }} &bull; Customer: {{
                        $selInvoice->customer->name }}
                    </p>
                </div>
                <button
                    wire:click="closeCheckout"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div>

            <!-- Items Summary -->
            <div class="mb-4 max-h-32 space-y-1.5 overflow-y-auto pr-1">
                <span
                    class="block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                    >Order Items</span
                >
                @foreach ($selInvoice->orders as $o)
                <div
                    class="flex justify-between text-[11px] text-gray-700 dark:text-gray-300"
                >
                    <span>{{ $o->quantity }}x {{ $o->file_name }}</span>
                    <span class="font-semibold text-gray-950 dark:text-white"
                        >Rp. {{ number_format($o->subtotal) }}</span
                    >
                </div>
                @endforeach
            </div>

            <!-- Payment Details Form -->
            <div class="space-y-4">
                @if ($selInvoice->status == 0)
                <div>
                    <label
                        class="mb-1.5 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                        >Payment Type</label
                    >
                    <div class="flex gap-4">
                        <label
                            class="flex cursor-pointer items-center gap-2 font-bold text-gray-700 dark:text-gray-300"
                        >
                            <input
                                type="radio"
                                wire:model.live="paymentType"
                                value="dp"
                                class="dark:bg-surface-600 dark:border-surface-200 border-gray-300 bg-white text-cyan-500 focus:ring-cyan-500"
                            />
                            <span>Down Payment (DP)</span>
                        </label>
                        <label
                            class="flex cursor-pointer items-center gap-2 font-bold text-gray-700 dark:text-gray-300"
                        >
                            <input
                                type="radio"
                                wire:model.live="paymentType"
                                value="settle"
                                class="dark:bg-surface-600 dark:border-surface-200 border-gray-300 bg-white text-cyan-500 focus:ring-cyan-500"
                            />
                            <span>Full Settlement</span>
                        </label>
                    </div>
                </div>
                @else
                <div
                    class="rounded-lg border border-amber-500/20 bg-amber-50 p-2.5 font-semibold text-amber-700 dark:bg-amber-950/20 dark:text-amber-400"
                >
                    Sisa Piutang Settlement (Current DP Paid: Rp. {{
                    number_format($selInvoice->dp_paid) }})
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >Payment Method</label
                        >
                        <select
                            wire:model="inputMethod"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:border-cyan-500 focus:outline-none dark:text-gray-100"
                        >
                            <option value="Cash" class="bg-white text-gray-900">
                                Cash
                            </option>
                            <option
                                value="Transfer"
                                class="bg-white text-gray-900"
                            >
                                Bank Transfer
                            </option>
                            <option value="QRIS" class="bg-white text-gray-900">
                                QRIS
                            </option>
                        </select>
                    </div>

                    @if ($paymentType === 'settle')
                    <div>
                        <label
                            class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >Additional Discount (Rp.)</label
                        >
                        <input
                            type="number"
                            wire:model.live.debounce.300ms="inputDiscount"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-medium text-gray-900 focus:border-cyan-500 focus:outline-none dark:text-gray-100"
                        />
                        @error('inputDiscount')
                        <span class="text-red-650 text-[10px] dark:text-red-400"
                            >{{ $message }}</span
                        >
                        @enderror
                    </div>
                    @endif
                </div>

                <div>
                    <label
                        class="mb-1 block text-[10px] font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                    >
                        {{ $paymentType === 'dp' ? 'DP Amount Received (Rp.)' :
                        'Total Cash Paid by Customer (Rp.)' }}
                    </label>
                    <input
                        type="number"
                        wire:model.live.debounce.300ms="inputAmount"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm font-bold text-gray-900 focus:border-cyan-500 focus:outline-none dark:text-gray-100"
                    />
                    @error('inputAmount')
                    <span class="text-red-650 text-[10px] dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <!-- Calculations Preview -->
                @php $discount = (int) $inputDiscount; $receivable =
                $selInvoice->accounts_receivable; $netToPay = $paymentType ===
                'dp' ? (int)$inputAmount : ($receivable - $discount); $paid =
                (int) $inputAmount; $change = $paid - $netToPay; @endphp
                <div
                    class="dark:bg-surface-700/30 dark:border-surface-700/50 space-y-2 rounded-xl border border-gray-200 bg-gray-50 p-3.5"
                >
                    @if ($paymentType === 'dp')
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Total Invoice:</span
                        >
                        <span class="font-bold text-gray-950 dark:text-white"
                            >Rp. {{ number_format($selInvoice->grand_total)
                            }}</span
                        >
                    </div>
                    <div
                        class="flex justify-between font-semibold text-cyan-600 dark:text-cyan-400"
                    >
                        <span>Down Payment Paid:</span>
                        <span>Rp. {{ number_format($paid) }}</span>
                    </div>
                    <div
                        class="flex justify-between border-t border-gray-200/50 pt-2 font-bold text-red-600 dark:border-gray-700/50 dark:text-red-400"
                    >
                        <span>Remaining Balance (AR):</span>
                        <span
                            >Rp. {{ number_format(max(0,
                            $selInvoice->grand_total - $paid)) }}</span
                        >
                    </div>
                    @else
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Remaining Balance:</span
                        >
                        <span class="font-bold text-gray-950 dark:text-white"
                            >Rp. {{ number_format($receivable) }}</span
                        >
                    </div>
                    @if ($discount > 0)
                    <div
                        class="flex justify-between font-semibold text-purple-600 dark:text-purple-400"
                    >
                        <span>Discount Given:</span>
                        <span>-Rp. {{ number_format($discount) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-medium">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Net Settle Amount:</span
                        >
                        <span class="font-bold text-gray-950 dark:text-white"
                            >Rp. {{ number_format(max(0, $receivable -
                            $discount)) }}</span
                        >
                    </div>
                    <div
                        class="flex justify-between border-t border-gray-200/50 pt-2 text-sm font-bold text-emerald-600 dark:border-gray-700/50 dark:text-emerald-400"
                    >
                        <span>Change (Kembalian):</span>
                        <span>Rp. {{ number_format(max(0, $change)) }}</span>
                    </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        wire:click="closeCheckout"
                        class="dark:border-surface-200 flex-1 rounded-xl border border-gray-300 py-2.5 font-bold text-gray-700 transition hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="processCheckout"
                        class="bg-brand-primary hover:bg-brand-primary-hover flex-1 rounded-lg py-2.5 font-bold text-white shadow-sm transition"
                    >
                        Process Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif @endif
</div>
