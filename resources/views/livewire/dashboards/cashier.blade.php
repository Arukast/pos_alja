<div class="text-light-text space-y-6 dark:text-gray-100">
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
        class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-50 p-4 text-emerald-800 backdrop-blur-sm transition dark:bg-emerald-950/20 dark:text-emerald-300"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center">
            <svg
                class="mr-2 h-5 w-5 text-emerald-500 dark:text-emerald-400"
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
            <span
                class="text-sm font-bold text-emerald-700 dark:text-emerald-300"
                >{{ $successMessage }}</span
            >
        </div>
        <button
            type="button"
            @click="show = false; $wire.set('showSuccessAlert', false)"
            class="text-emerald-500 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
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
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Awaiting Checkout
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ $awaitingCount }} Invoices
                    </h3>
                </div>
                <div
                    class="bg-brand-secondary/10 text-brand-secondary rounded-xl p-3"
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
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="text-brand-secondary mt-4 flex items-center text-xs font-bold"
            >
                <span
                    class="bg-brand-secondary mr-2 h-2 w-2 animate-ping rounded-full"
                ></span>
                <span>Unpaid jobs queue ready</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        DP Collected Today
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        Rp. {{ number_format($dpCollectedToday) }}
                    </h3>
                </div>
                <div
                    class="text-brand-secondary bg-brand-secondary/10 rounded-xl p-3"
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
            <div
                class="text-light-muted mt-4 flex items-center text-xs font-semibold dark:text-gray-400"
            >
                <span>Down payment deposits</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Settlements Today
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        Rp. {{ number_format($settlementsToday) }}
                    </h3>
                </div>
                <div
                    class="rounded-xl bg-emerald-500/10 p-3 text-emerald-500 dark:text-emerald-400"
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
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400"
            >
                <span>Completed orders pickup cash</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Store Expenses (Today)
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        Rp. {{ number_format($todayExpenseTotal) }}
                    </h3>
                </div>
                <div
                    class="rounded-xl bg-red-500/10 p-3 text-red-500 dark:text-red-400"
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
                            d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="text-red-550 mt-4 flex items-center text-xs font-bold dark:text-red-400"
            >
                <span>Withdrawals & modal costs today</span>
            </div>
        </div>
    </div>

    <!-- Active bills list & Log Expenses layout -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Billing Queue Panel -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Active Billing Queue
            </h3>
            <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
                Invoices ready for Down Payment (DP) or final settlement.
            </p>

            <div
                class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
            >
                <table
                    class="text-light-muted dark:text-gray-450 w-full text-left text-xs"
                >
                    <thead
                        class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                    >
                        <tr>
                            <th class="rounded-l-xl px-4 py-3">Invoice ID</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Grand Total</th>
                            <th class="px-4 py-3">Remaining AR</th>
                            <th class="rounded-r-xl px-4 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                    >
                        @forelse ($activeInvoices as $inv)
                        <tr
                            class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                        >
                            <td
                                class="text-light-text px-4 py-3 font-bold dark:text-white"
                            >
                                {{ $inv->invoice_number }}
                            </td>
                            <td class="px-4 py-3">
                                <p
                                    class="text-light-text font-bold dark:text-white"
                                >
                                    {{ $inv->customer->name }}
                                </p>
                                <p
                                    class="text-light-muted text-[10px] font-medium dark:text-gray-400"
                                >
                                    {{ $inv->customer->phone ?: 'No phone' }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                @if ($inv->status == 0)
                                <span
                                    class="dark:text-red-405 inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-red-700 uppercase dark:bg-red-900/30"
                                    >Unpaid</span
                                >
                                @else
                                <span
                                    class="dark:text-amber-405 inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-amber-700 uppercase dark:bg-amber-900/30"
                                    >Down Payment</span
                                >
                                @endif
                            </td>
                            <td
                                class="text-light-text dark:text-gray-250 px-4 py-3 font-semibold"
                            >
                                Rp. {{ number_format($inv->grand_total) }}
                            </td>
                            <td
                                class="text-red-550 px-4 py-3 font-extrabold dark:text-red-400"
                            >
                                Rp. {{ number_format($inv->accounts_receivable)
                                }}
                            </td>
                            <td
                                class="flex items-center justify-end gap-2 px-4 py-3 text-right"
                            >
                                @if ($inv->status == 0)
                                <button
                                    id="btn-dp-{{ $inv->id }}"
                                    wire:click="selectInvoice('{{ $inv->id }}', 'dp')"
                                    class="bg-brand-secondary hover:bg-brand-secondary/95 cursor-pointer rounded-lg px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition"
                                >
                                    Register DP
                                </button>
                                @endif
                                <button
                                    id="btn-settle-{{ $inv->id }}"
                                    wire:click="selectInvoice('{{ $inv->id }}', 'settle')"
                                    class="cursor-pointer rounded-lg bg-emerald-500 px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition hover:bg-emerald-600"
                                >
                                    Checkout
                                </button>
                                <a
                                    id="link-receipt-{{ $inv->id }}"
                                    href="/orders/{{ $inv->id }}/receipt"
                                    target="_blank"
                                    class="border-brand-primary text-brand-primary hover:bg-brand-primary/10 rounded-lg border px-3 py-1 text-[11px] font-bold transition"
                                    >Receipt</a
                                >
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="6"
                                class="text-light-muted py-6 text-center dark:text-gray-500"
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
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Log Store Expense
            </h3>
            <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
                Record operational cash payouts.
            </p>

            <form wire:submit.prevent="logExpense" class="space-y-4 text-xs">
                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Expense Amount (Rp.)</label
                    >
                    <input
                        id="expense-amount"
                        type="number"
                        wire:model="amount"
                        placeholder="e.g. 50000"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-gray-800 transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        required
                    />
                    @error('amount')
                    <span
                        class="text-red-550 text-[10px] font-semibold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Expense Classification</label
                    >
                    <select
                        id="expense-type"
                        wire:model="type"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 font-medium transition duration-200 focus:ring-2 focus:outline-none"
                    >
                        <option value="laba" class="bg-white text-gray-900">
                            Operational / Operational cost (Laba)
                        </option>
                        <option value="modal" class="bg-white text-gray-900">
                            Capital / Purchase supplies (Modal)
                        </option>
                    </select>
                    @error('type')
                    <span
                        class="text-red-550 text-[10px] font-semibold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Incurred Date</label
                    >
                    <input
                        id="expense-date"
                        type="date"
                        wire:model="incurredAt"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 font-medium transition duration-200 focus:ring-2 focus:outline-none"
                        required
                    />
                    @error('incurredAt')
                    <span
                        class="text-red-550 text-[10px] font-semibold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Description / Reason</label
                    >
                    <textarea
                        id="expense-description"
                        wire:model="description"
                        placeholder="Specify items purchased, utility, or reasons..."
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-20 w-full resize-none rounded-xl border bg-white px-3 py-2 transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        required
                    ></textarea>
                    @error('description')
                    <span
                        class="text-red-550 text-[10px] font-semibold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <button
                    id="btn-submit-expense"
                    type="submit"
                    class="bg-brand-secondary hover:bg-brand-secondary/95 w-full cursor-pointer rounded-xl py-2.5 font-bold text-white shadow-sm transition"
                >
                    Record Store Payout
                </button>
            </form>
        </div>
    </div>

    <!-- CUSTOMER PICKUP QUEUE -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            Customer Pickup Queue
        </h3>
        <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
            Paid orders ready to be picked up by customers.
        </p>

        <div
            class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
        >
            <table
                class="text-light-muted dark:text-gray-450 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="rounded-l-xl px-4 py-3">Invoice ID</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Payment Date</th>
                        <th class="px-4 py-3">Grand Total</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($pickupQueue as $inv)
                    <tr
                        class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                    >
                        <td
                            class="text-light-text px-4 py-3 font-bold dark:text-white"
                        >
                            {{ $inv->invoice_number }}
                        </td>
                        <td class="px-4 py-3">
                            <p
                                class="text-light-text font-bold dark:text-white"
                            >
                                {{ $inv->customer->name }}
                            </p>
                            <p
                                class="text-light-muted text-[10px] font-medium dark:text-gray-400"
                            >
                                {{ $inv->customer->phone ?: 'No phone' }}
                            </p>
                        </td>
                        <td
                            class="text-light-text px-4 py-3 font-semibold dark:text-gray-300"
                        >
                            {{ $inv->payment_date }}
                        </td>
                        <td
                            class="text-light-text px-4 py-3 font-extrabold dark:text-gray-300"
                        >
                            Rp. {{ number_format($inv->grand_total) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                id="btn-pickup-{{ $inv->id }}"
                                wire:click="markAsPickedUp('{{ $inv->id }}')"
                                class="bg-brand-primary hover:bg-brand-primary/95 cursor-pointer rounded-lg px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition"
                            >
                                Mark Picked Up
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="5"
                            class="text-light-muted py-6 text-center dark:text-gray-500"
                        >
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
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <h3 class="text-light-text mb-4 text-lg font-bold dark:text-white">
            Recent Cash Drawer Expenses Logs
        </h3>

        <div
            class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
        >
            <table
                class="text-light-muted dark:text-gray-450 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="rounded-l-xl px-4 py-3">Date</th>
                        <th class="px-4 py-3">Cashier / Logged by</th>
                        <th class="px-4 py-3">Classification</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">
                            Amount
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($expenses as $ex)
                    <tr
                        class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                    >
                        <td
                            class="text-light-text px-4 py-3 font-semibold dark:text-gray-300"
                        >
                            {{ $ex->incurred_at }}
                        </td>
                        <td
                            class="text-light-text px-4 py-3 font-bold dark:text-white"
                        >
                            {{ $ex->cashier_name }}
                        </td>
                        <td class="px-4 py-3 font-bold">
                            @if ($ex->type === 'laba')
                            <span
                                class="inline-block rounded-full bg-blue-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-blue-700 uppercase dark:bg-blue-900/30 dark:text-blue-400"
                                >Operational (Laba)</span
                            >
                            @else
                            <span
                                class="inline-block rounded-full bg-purple-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-purple-700 uppercase dark:bg-purple-900/30 dark:text-purple-400"
                                >Capital / Goods (Modal)</span
                            >
                            @endif
                        </td>
                        <td
                            class="text-light-text px-4 py-3 leading-relaxed font-medium dark:text-gray-300"
                        >
                            {{ $ex->description }}
                        </td>
                        <td
                            class="text-red-650 px-4 py-3 text-right font-extrabold dark:text-red-400"
                        >
                            Rp. {{ number_format($ex->amount) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="5"
                            class="text-light-muted py-6 text-center dark:text-gray-500"
                        >
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
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text relative w-full max-w-lg overflow-hidden rounded-2xl border bg-white p-6 text-xs shadow-2xl dark:text-gray-200"
        >
            <!-- Brand Stripe Accent -->
            <div
                class="from-brand-primary via-brand-secondary to-brand-accent absolute top-0 right-0 left-0 h-1.5 bg-gradient-to-r"
            ></div>

            <!-- Header -->
            <div
                class="border-light-border dark:border-surface-300/30 mb-4 flex items-center justify-between border-b pt-2 pb-3"
            >
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        {{ $paymentType === 'dp' ? 'Register Down Payment' :
                        'Invoice Payment Checkout' }}
                    </h3>
                    <p
                        class="text-light-muted mt-0.5 text-xs font-semibold dark:text-gray-400"
                    >
                        Invoice {{ $selInvoice->invoice_number }} &bull;
                        Customer: {{ $selInvoice->customer->name }}
                    </p>
                </div>
                <button
                    id="btn-close-checkout"
                    wire:click="closeCheckout"
                    class="text-light-muted hover:text-light-text cursor-pointer dark:text-gray-400 dark:hover:text-white"
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
                    class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Order Items</span
                >
                @foreach ($selInvoice->orders as $o)
                <div
                    class="text-light-text flex justify-between text-[11px] font-medium dark:text-gray-300"
                >
                    <span>{{ $o->quantity }}x {{ $o->file_name }}</span>
                    <span class="text-light-text font-bold dark:text-white"
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
                        class="text-light-muted mb-2 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Payment Type</label
                    >
                    <div class="flex gap-4">
                        <label
                            class="text-light-text flex cursor-pointer items-center gap-2 font-bold dark:text-gray-300"
                        >
                            <input
                                type="radio"
                                wire:model.live="paymentType"
                                value="dp"
                                class="dark:bg-surface-500 border-light-border dark:border-surface-300/50 text-brand-primary focus:ring-brand-primary/30 h-4 w-4"
                            />
                            <span>Down Payment (DP)</span>
                        </label>
                        <label
                            class="text-light-text flex cursor-pointer items-center gap-2 font-bold dark:text-gray-300"
                        >
                            <input
                                type="radio"
                                wire:model.live="paymentType"
                                value="settle"
                                class="dark:bg-surface-500 border-light-border dark:border-surface-300/50 text-brand-primary focus:ring-brand-primary/30 h-4 w-4"
                            />
                            <span>Full Settlement</span>
                        </label>
                    </div>
                </div>
                @else
                <div
                    class="dark:bg-amber-955/20 rounded-xl border border-amber-500/20 bg-amber-50 p-3 text-xs font-semibold text-amber-700 dark:text-amber-400"
                >
                    Sisa Piutang Settlement (Current DP Paid: Rp. {{
                    number_format($selInvoice->dp_paid) }})
                </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="text-light-muted mb-1 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                            >Payment Method</label
                        >
                        <select
                            id="checkout-method"
                            wire:model="inputMethod"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 font-semibold transition duration-200 focus:ring-2 focus:outline-none"
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
                            class="text-light-muted mb-1 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                            >Additional Discount (Rp.)</label
                        >
                        <input
                            id="checkout-discount"
                            type="number"
                            wire:model.live.debounce.300ms="inputDiscount"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 font-semibold transition duration-200 focus:ring-2 focus:outline-none"
                        />
                        @error('inputDiscount')
                        <span
                            class="text-red-550 text-[10px] font-bold dark:text-red-400"
                            >{{ $message }}</span
                        >
                        @enderror
                    </div>
                    @endif
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        {{ $paymentType === 'dp' ? 'DP Amount Received (Rp.)' :
                        'Total Cash Paid by Customer (Rp.)' }}
                    </label>
                    <input
                        id="checkout-amount"
                        type="number"
                        wire:model.live.debounce.300ms="inputAmount"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm font-extrabold transition duration-200 focus:ring-2 focus:outline-none"
                    />
                    @error('inputAmount')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
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
                    class="bg-light-surface-2 dark:bg-surface-700/30 border-light-border dark:border-surface-700/50 space-y-2 rounded-xl border p-3.5"
                >
                    @if ($paymentType === 'dp')
                    <div class="flex justify-between font-semibold">
                        <span class="text-light-muted dark:text-gray-400"
                            >Total Invoice:</span
                        >
                        <span class="text-light-text font-bold dark:text-white"
                            >Rp. {{ number_format($selInvoice->grand_total)
                            }}</span
                        >
                    </div>
                    <div
                        class="text-brand-secondary flex justify-between font-bold"
                    >
                        <span>Down Payment Paid:</span>
                        <span>Rp. {{ number_format($paid) }}</span>
                    </div>
                    <div
                        class="border-light-border dark:border-surface-300/30 text-red-550 dark:text-red-450 flex justify-between border-t pt-2 font-extrabold"
                    >
                        <span>Remaining Balance (AR):</span>
                        <span
                            >Rp. {{ number_format(max(0,
                            $selInvoice->grand_total - $paid)) }}</span
                        >
                    </div>
                    @else
                    <div class="flex justify-between font-semibold">
                        <span class="text-light-muted dark:text-gray-400"
                            >Remaining Balance:</span
                        >
                        <span class="text-light-text font-bold dark:text-white"
                            >Rp. {{ number_format($receivable) }}</span
                        >
                    </div>
                    @if ($discount > 0)
                    <div
                        class="flex justify-between font-bold text-purple-600 dark:text-purple-400"
                    >
                        <span>Discount Given:</span>
                        <span>-Rp. {{ number_format($discount) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold">
                        <span class="text-light-muted dark:text-gray-400"
                            >Net Settle Amount:</span
                        >
                        <span
                            class="text-light-text font-extrabold dark:text-white"
                            >Rp. {{ number_format(max(0, $receivable -
                            $discount)) }}</span
                        >
                    </div>
                    <div
                        class="border-light-border dark:border-surface-300/30 dark:text-emerald-450 flex justify-between border-t pt-2 text-sm font-extrabold text-emerald-600"
                    >
                        <span>Change (Kembalian):</span>
                        <span>Rp. {{ number_format(max(0, $change)) }}</span>
                    </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        id="btn-checkout-cancel"
                        type="button"
                        wire:click="closeCheckout"
                        class="border-light-border dark:border-surface-300/50 text-light-muted hover:text-light-text hover:bg-light-surface-2 dark:hover:bg-surface-500 flex-1 cursor-pointer rounded-xl border py-2.5 font-bold transition duration-150 dark:text-gray-300 dark:hover:text-white"
                    >
                        Cancel
                    </button>
                    <button
                        id="btn-checkout-confirm"
                        type="button"
                        wire:click="processCheckout"
                        class="bg-brand-primary hover:bg-brand-primary/95 flex-1 cursor-pointer rounded-xl py-2.5 font-bold text-white shadow-sm transition"
                    >
                        Process Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif @endif
</div>
