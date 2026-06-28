<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Stock Ledger Logs -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            Recent Inventory Ledger Logs
        </h3>
        <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
            Stock ledger activity adjustments logs.
        </p>

        <div
            class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
        >
            <table
                class="text-light-muted dark:text-gray-455 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="rounded-l-xl px-4 py-3">Date</th>
                        <th class="px-4 py-3">Material</th>
                        <th class="px-4 py-3">Initial</th>
                        <th class="px-4 py-3">Added</th>
                        <th class="px-4 py-3">Wasted</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">
                            Ledger Remaining
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($stockLogs as $log)
                    <!-- prettier-ignore -->
                    <tr class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150">
                        <td class="text-light-muted px-4 py-3 font-medium dark:text-gray-400">
                            {{ $log->logged_at }}
                        </td>
                        <td class="text-light-text px-4 py-3 font-bold dark:text-white">
                            {{ $log->material->name ?? '' }}
                        </td>
                        <td class="text-light-text px-4 py-3 font-semibold dark:text-gray-300">
                            {{ $log->initial_stock }}
                        </td>
                        <td class="px-4 py-3 font-bold text-emerald-600 dark:text-emerald-400">
                            +{{ $log->added_stock }}
                        </td>
                        <td class="text-red-550 px-4 py-3 font-bold dark:text-red-400">
                            {{ $log->wasted_stock }}
                        </td>
                        <td class="text-brand-secondary px-4 py-3 text-right font-extrabold">
                            {{ $log->remaining_stock }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="6"
                            class="text-light-muted py-6 text-center dark:text-gray-500"
                        >
                            No stock ledger entries registered.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Expenses Ledger -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <h3 class="text-light-text mb-1 text-lg font-bold dark:text-white">
            Expenses Ledger
        </h3>
        <p class="text-light-muted mb-4 text-xs dark:text-gray-400">
            Drawer payouts audit tracking logs.
        </p>

        <div class="max-h-[400px] space-y-3.5 overflow-y-auto pr-1">
            @forelse ($expenses as $exp)
            <div
                class="border-light-border dark:border-surface-300/30 dark:bg-surface-600/50 hover:border-brand-primary/25 dark:hover:border-surface-200/45 rounded-xl border bg-white p-3.5 text-xs transition-colors duration-150"
            >
                <div class="flex items-start justify-between">
                    <span
                        class="text-light-text font-extrabold uppercase dark:text-white"
                    >
                        {{ $exp->cashier_name }}
                    </span>
                    <span class="font-extrabold text-red-600 dark:text-red-400">
                        Rp. {{ number_format($exp->amount) }}
                    </span>
                </div>
                <div
                    class="text-light-muted dark:text-gray-405 mt-1 flex justify-between text-[10px] font-medium"
                >
                    <span>{{ $exp->incurred_at }}</span>
                    <span class="text-brand-secondary font-bold uppercase">
                        {{ $exp->type }}
                    </span>
                </div>
                <p
                    class="text-light-muted mt-2 text-[10px] leading-relaxed font-medium italic dark:text-gray-400"
                >
                    {{ $exp->description }}
                </p>
            </div>
            @empty
            <div
                class="text-light-muted py-6 text-center text-xs dark:text-gray-500"
            >
                No expenses recorded.
            </div>
            @endforelse
        </div>
    </div>
</div>
