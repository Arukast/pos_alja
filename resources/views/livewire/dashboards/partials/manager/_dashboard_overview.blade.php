<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Stat card 1 -->
    <x-manager.metric-card
        title="Stock Materials"
        value="{{ $materialCount }}"
        badge-text="Active stock tracking enabled"
        badge-type="success"
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
                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
            ></path>
        </svg>
    </x-manager.metric-card>

    <!-- Stat card 2 -->
    <x-manager.metric-card
        title="Printers & Machinery"
        value="{{ $machineCount }}"
        badge-text="All machines online"
        badge-type="ping"
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
                d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"
            ></path>
        </svg>
    </x-manager.metric-card>

    <!-- Stat card 3 -->
    <x-manager.metric-card
        title="Active Staff"
        value="{{ $staffCount }}"
        footer-text="Managed across 5 roles"
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
                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
            ></path>
        </svg>
    </x-manager.metric-card>

    <!-- Stat card 4 -->
    <x-manager.metric-card
        title="Pricing Engine Rows"
        value="{{ $pricingRulesCount }}"
        badge-text="Active price rule matrix"
        badge-type="success"
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
                d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"
            ></path>
        </svg>
    </x-manager.metric-card>
</div>

<!-- Active Overview Ledger Tables -->
@island(name: 'overview-ledgers')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Recent Stock ledger logs -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
    >
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Recent Inventory Ledger Logs
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Stock ledger activity adjustments logs.
                </p>
            </div>
            <button
                type="button"
                wire:click="setTab('ledgers')"
                class="text-brand-primary hover:text-brand-primary/85 text-xs font-bold"
            >
                View All
            </button>
        </div>

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

    <!-- Recent Expenses log -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Expenses Ledger
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Drawer payouts audit tracking logs.
                </p>
            </div>
            <button
                type="button"
                wire:click="setTab('ledgers')"
                class="text-brand-primary hover:text-brand-primary/85 text-xs font-bold"
            >
                View All
            </button>
        </div>

        <div class="max-h-[300px] space-y-3.5 overflow-y-auto pr-1">
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
@endisland
