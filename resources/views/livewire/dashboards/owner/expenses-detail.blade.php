<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <!-- Header Controls and Date Range Filter -->
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Expenses (Pengeluaran) Detail
            </h3>
            <p class="text-light-muted text-xs dark:text-gray-400">
                Detailed breakdown of operational expenses and cashier drawer
                outflows by category.
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
        class="text-red-655 mt-4 rounded-xl bg-red-500/10 p-3 text-xs dark:text-red-400"
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
        <!-- Total Expenses Card -->
        <div
            class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-500/20 dark:bg-red-500/10"
        >
            <span
                class="text-[10px] font-bold tracking-wider text-red-800 uppercase dark:text-red-400"
                >Total Expenses</span
            >
            <h4
                class="mt-1 text-xl font-extrabold text-red-950 dark:text-red-300"
            >
                Rp. {{ number_format($expenses_total, 0, ',', '.') }}
            </h4>
            <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                All outlays in this period
            </p>
        </div>

        <!-- Categorized Spark Cards -->
        @php $displayCategories = ['Office', 'Supplies', 'Utilities',
        'Maintenance']; @endphp @foreach($displayCategories as $cat) @php
        $catTotal = $by_category[$cat] ?? 0; @endphp
        <div
            class="dark:border-surface-300/20 dark:bg-surface-700/50 rounded-xl border border-gray-200 bg-gray-50/50 p-4"
        >
            <span
                class="text-[10px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                >{{ $cat }}</span
            >
            <h4
                class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white"
            >
                Rp. {{ number_format($catTotal, 0, ',', '.') }}
            </h4>
            <p class="text-light-muted dark:text-gray-450 mt-1 text-xs">
                Category outlay
            </p>
        </div>
        @endforeach

        <!-- Other categories if any exist -->
        @php $otherTotal = 0; foreach ($by_category as $catName => $amount) { if
        (!in_array($catName, $displayCategories, true)) { $otherTotal +=
        $amount; } } @endphp @if($otherTotal > 0)
        <div
            class="dark:border-surface-300/20 dark:bg-surface-700/50 rounded-xl border border-gray-200 bg-gray-50/50 p-4"
        >
            <span
                class="text-[10px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                >Other</span
            >
            <h4
                class="mt-1 text-xl font-extrabold text-gray-900 dark:text-white"
            >
                Rp. {{ number_format($otherTotal, 0, ',', '.') }}
            </h4>
            <p class="text-light-muted dark:text-gray-455 mt-1 text-xs">
                Other misc categories
            </p>
        </div>
        @endif
    </div>

    <!-- Ledger Tables -->
    <div class="mt-8 space-y-6" data-loading:opacity-50>
        <!-- Expenses Ledger -->
        <div>
            <h4 class="text-light-text mb-3 text-sm font-bold dark:text-white">
                Operational Expenses Ledger
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
                                Category
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
                                class="px-6 py-4 text-xs font-medium whitespace-nowrap text-gray-900 dark:text-white"
                            >
                                {{ $expense->incurred_at }}
                            </td>
                            <td
                                class="text-gray-550 px-6 py-4 text-xs whitespace-nowrap dark:text-gray-300"
                            >
                                <span
                                    class="dark:bg-surface-700 rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-semibold dark:text-gray-200"
                                >
                                    {{ $expense->category }}
                                </span>
                            </td>
                            <td
                                class="max-w-xs truncate px-6 py-4 text-xs text-gray-900 dark:text-white"
                            >
                                {{ $expense->description }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ $expense->branch ? $expense->branch->name :
                                'Global' }}
                            </td>
                            <td
                                class="px-6 py-4 text-xs whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ $expense->cashier_name ?? 'System' }}
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
                                colspan="6"
                                class="px-6 py-8 text-center text-xs text-gray-500 dark:text-gray-400"
                            >
                                No expenses recorded in this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
