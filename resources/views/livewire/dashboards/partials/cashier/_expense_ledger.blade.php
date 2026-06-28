<!-- Expenses Ledger List -->
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
                    <th class="rounded-r-xl px-4 py-3 text-right">Amount</th>
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
                        <x-cashier.status-badge
                            type="expense_type"
                            :value="$ex->type"
                        />
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
