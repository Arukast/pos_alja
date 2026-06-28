<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <!-- Header Controls and Date Range Filter -->
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Net Profit (Laba) Detail
            </h3>
            <p class="text-light-muted text-xs dark:text-gray-400">
                Detailed breakdown of Net Profit margin rate including invoice
                details, wasted materials, and expenses.
            </p>
        </div>

        <form
            wire:submit.prevent="applyCustomRange"
            class="flex flex-wrap items-end gap-3"
        >
            <div>
                <label
                    for="start-date-input"
                    class="text-light-text block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Start Date</label
                >
                <input
                    id="start-date-input"
                    type="date"
                    wire:model="form.startDate"
                    class="border-light-border dark:border-surface-300/50 text-light-text dark:bg-surface-600 focus:ring-brand-primary/20 block rounded-xl border bg-white px-3 py-1.5 text-xs focus:ring-2 focus:outline-none dark:text-white"
                />
            </div>
            <div>
                <label
                    for="end-date-input"
                    class="text-light-text block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >End Date</label
                >
                <input
                    id="end-date-input"
                    type="date"
                    wire:model="form.endDate"
                    class="border-light-border dark:border-surface-300/50 text-light-text dark:bg-surface-600 focus:ring-brand-primary/20 block rounded-xl border bg-white px-3 py-1.5 text-xs focus:ring-2 focus:outline-none dark:text-white"
                />
            </div>
            <button
                type="submit"
                class="bg-brand-primary hover:bg-brand-primary/95 block rounded-xl px-4 py-1.5 text-xs font-semibold text-white shadow-xs transition-all"
            >
                Apply Filter
            </button>
        </form>
    </div>

    <!-- Livewire Error Messages -->
    @if ($errors->any())
    <div
        class="text-red-650 mt-4 rounded-xl bg-red-500/10 p-3 text-xs dark:text-red-400"
    >
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Summary Metrics Cards -->
    <div
        class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
        data-loading:opacity-50
    >
        <div
            class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-500/20 dark:bg-emerald-500/10"
        >
            <span
                class="text-[10px] font-bold tracking-wider text-emerald-800 uppercase dark:text-emerald-400"
                >Net Profit</span
            >
            <h4
                class="mt-1 text-xl font-extrabold text-emerald-950 dark:text-emerald-300"
            >
                Rp. {{ number_format($net_profit, 0, ',', '.') }}
            </h4>
            <p
                class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-400"
            >
                Margin: {{ number_format($margin_rate, 1, ',', '.') }}%
            </p>
        </div>

        <div
            class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/20 dark:bg-blue-500/10"
        >
            <span
                class="text-[10px] font-bold tracking-wider text-blue-800 uppercase dark:text-blue-400"
                >Gross Revenue</span
            >
            <h4
                class="mt-1 text-xl font-extrabold text-blue-950 dark:text-blue-300"
            >
                Rp. {{ number_format($gross_revenue, 0, ',', '.') }}
            </h4>
            <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                Total payments collected
            </p>
        </div>

        <div
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-500/10"
        >
            <span
                class="text-[10px] font-bold tracking-wider text-amber-800 uppercase dark:text-amber-400"
                >Total HPP</span
            >
            <h4
                class="mt-1 text-xl font-extrabold text-amber-950 dark:text-amber-300"
            >
                Rp. {{ number_format($total_hpp, 0, ',', '.') }}
            </h4>
            <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                Base cost + wasted materials
            </p>
        </div>

        <div
            class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10"
        >
            <span
                class="text-[10px] font-bold tracking-wider text-red-800 uppercase dark:text-red-400"
                >Expenses</span
            >
            <h4
                class="mt-1 text-xl font-extrabold text-red-950 dark:text-red-300"
            >
                Rp. {{ number_format($expenses_total, 0, ',', '.') }}
            </h4>
            <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                Operational expenditures
            </p>
        </div>
    </div>

    <!-- Ledger Tables -->
    <div class="mt-8 space-y-6" data-loading:opacity-50>
        <!-- Invoices Ledger -->
        <div>
            <h4 class="text-light-text mb-3 text-sm font-bold dark:text-white">
                Invoices Ledger
            </h4>
            <div
                class="border-light-border dark:border-surface-300/40 overflow-hidden rounded-xl border"
            >
                <table
                    class="dark:divide-surface-300/20 min-w-full divide-y divide-gray-200"
                >
                    <thead class="dark:bg-surface-700/50 bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Date
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Invoice No.
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Branch
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Grand Total
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                HPP
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Profit
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="dark:bg-surface-600 dark:divide-surface-300/20 divide-y divide-gray-200 bg-white"
                    >
                        @forelse($invoices as $invoice)
                        <tr
                            class="dark:hover:bg-surface-700/20 hover:bg-gray-50/50"
                        >
                            <td
                                class="px-6 py-4 text-xs font-medium whitespace-nowrap text-gray-900 dark:text-white"
                            >
                                {{ $invoice->created_date }}
                            </td>
                            <td
                                class="text-brand-primary px-6 py-4 text-xs font-bold whitespace-nowrap"
                            >
                                {{ $invoice->invoice_number }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ $invoice->branch->name }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-xs whitespace-nowrap text-gray-900 dark:text-white"
                            >
                                Rp. {{ number_format($invoice->grand_total, 0,
                                ',', '.') }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-xs whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                Rp. {{ number_format($invoice->total_cost, 0,
                                ',', '.') }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-xs font-semibold whitespace-nowrap text-emerald-600 dark:text-emerald-400"
                            >
                                Rp. {{ number_format($invoice->total_profit, 0,
                                ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-6 py-8 text-center text-xs text-gray-500 dark:text-gray-400"
                            >
                                No invoices recorded in this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expenses Ledger -->
        <div>
            <h4 class="text-light-text mb-3 text-sm font-bold dark:text-white">
                Operational Expenses
            </h4>
            <div
                class="border-light-border dark:border-surface-300/40 overflow-hidden rounded-xl border"
            >
                <table
                    class="dark:divide-surface-300/20 min-w-full divide-y divide-gray-200"
                >
                    <thead class="dark:bg-surface-700/50 bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Date
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Description
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Branch
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Cashier
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Amount
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="dark:bg-surface-600 dark:divide-surface-300/20 divide-y divide-gray-200 bg-white"
                    >
                        @forelse($expenses as $expense)
                        <tr
                            class="dark:hover:bg-surface-700/20 hover:bg-gray-50/50"
                        >
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-900 dark:text-white"
                            >
                                {{ $expense->incurred_at }}
                            </td>
                            <td
                                class="max-w-xs truncate px-6 py-4 text-xs text-gray-900 dark:text-white"
                            >
                                {{ $expense->description }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ $expense->branch->name }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ $expense->cashier_name }}
                            </td>
                            <td
                                class="text-red-650 px-6 py-4 text-right text-xs font-semibold whitespace-nowrap dark:text-red-400"
                            >
                                Rp. {{ number_format($expense->amount, 0, ',',
                                '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="5"
                                class="px-6 py-8 text-center text-xs text-gray-500 dark:text-gray-400"
                            >
                                No operational expenses recorded in this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Wasted Materials Ledger -->
        <div>
            <h4 class="text-light-text mb-3 text-sm font-bold dark:text-white">
                Wasted Materials
            </h4>
            <div
                class="border-light-border dark:border-surface-300/40 overflow-hidden rounded-xl border"
            >
                <table
                    class="dark:divide-surface-300/20 min-w-full divide-y divide-gray-200"
                >
                    <thead class="dark:bg-surface-700/50 bg-gray-50">
                        <tr>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Date
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Material
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Branch
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Operator
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Quantity
                            </th>
                            <th
                                scope="col"
                                class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Estimated Cost
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="dark:bg-surface-600 dark:divide-surface-300/20 divide-y divide-gray-200 bg-white"
                    >
                        @forelse($wasted_materials as $waste)
                        <tr
                            class="dark:hover:bg-surface-700/20 hover:bg-gray-50/50"
                        >
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-900 dark:text-white"
                            >
                                {{ $waste->logged_at }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs font-semibold whitespace-nowrap text-gray-950 dark:text-white"
                            >
                                {{ $waste->material->name }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ $waste->branch->name }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ optional($waste->operator)->name ?? 'System'
                                }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-xs whitespace-nowrap text-gray-900 dark:text-white"
                            >
                                {{ $waste->quantity }} {{ $waste->material->unit
                                }}
                            </td>
                            <td
                                class="px-6 py-4 text-right text-xs font-semibold whitespace-nowrap text-amber-600 dark:text-amber-500"
                            >
                                Rp. {{ number_format($waste->quantity *
                                $waste->material->cost, 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-6 py-8 text-center text-xs text-gray-500 dark:text-gray-400"
                            >
                                No material waste recorded in this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
