<!-- Quick stats grid -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Stat card 1: Gross Revenue (Omset) -->
    <x-owner.metric-card
        title="Gross Revenue (Omset)"
        value="Rp. {{ number_format($grandTotal, 0, ',', '.') }}"
    >
        <x-slot name="icon">
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
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
            </svg>
        </x-slot>

        <x-slot name="comparison">
            <svg
                class="mr-1 h-4 w-4 text-emerald-600 dark:text-emerald-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                ></path>
            </svg>
            <span class="text-emerald-600 dark:text-emerald-400"
                >Active {{ $period }} Transactions</span
            >
        </x-slot>

        <x-slot name="footer">
            <div
                class="border-light-border dark:border-surface-300/30 text-light-muted mt-3 flex flex-wrap items-center justify-between gap-1 border-t pt-3 text-[10px] dark:text-gray-400"
            >
                <span class="flex items-center gap-1 font-semibold">
                    <span
                        class="bg-emerald-550 h-1.5 w-1.5 rounded-full"
                    ></span>
                    Cash: Rp. {{ number_format($cashPayments, 0, ',', '.') }}
                </span>
                <span class="flex items-center gap-1 font-semibold">
                    <span class="bg-blue-550 h-1.5 w-1.5 rounded-full"></span>
                    Transfer: Rp. {{ number_format($transferPayments, 0, ',',
                    '.') }}
                </span>
                <span class="flex items-center gap-1 font-semibold">
                    <span class="bg-purple-550 h-1.5 w-1.5 rounded-full"></span>
                    QRIS: Rp. {{ number_format($qrisPayments, 0, ',', '.') }}
                </span>
            </div>
        </x-slot>
    </x-owner.metric-card>

    <!-- Stat card 2: Base Cost (HPP) -->
    <x-owner.metric-card
        title="Base Cost (HPP)"
        value="Rp. {{ number_format($cost, 0, ',', '.') }}"
        iconColor="bg-brand-secondary/10 text-brand-secondary"
    >
        <x-slot name="icon">
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
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                ></path>
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                ></path>
            </svg>
        </x-slot>

        <x-slot name="comparison">
            <span class="text-light-muted dark:text-gray-400"
                >Material & consumable usage</span
            >
        </x-slot>
    </x-owner.metric-card>

    <!-- Stat card 3: Net Profit (Laba) -->
    <x-owner.metric-card
        title="Net Profit (Laba)"
        value="Rp. {{ number_format($profit, 0, ',', '.') }}"
        iconColor="text-emerald-550 bg-emerald-500/10 dark:text-emerald-400"
    >
        <x-slot name="icon">
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
                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                ></path>
            </svg>
        </x-slot>

        <x-slot name="comparison">
            <span class="text-emerald-600 dark:text-emerald-400"
                >Margin Rate: {{ number_format($marginRate, 1, ',', '.')
                }}%</span
            >
        </x-slot>
    </x-owner.metric-card>

    <!-- Stat card 4: Expenses (Pengeluaran) -->
    <x-owner.metric-card
        title="Expenses (Pengeluaran)"
        value="Rp. {{ number_format($expensesTotal, 0, ',', '.') }}"
        iconColor="text-red-550 bg-red-500/10 dark:text-red-400"
    >
        <x-slot name="icon">
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
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                ></path>
            </svg>
        </x-slot>

        <x-slot name="comparison">
            <span class="text-red-550 dark:text-red-400"
                >Operational expenses</span
            >
        </x-slot>
    </x-owner.metric-card>
</div>
