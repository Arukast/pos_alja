<!-- Quick stats grid -->
@if ($activeTab === 'dashboard')
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Stat 1 -->
    <x-cashier.stat-card
        title="Awaiting Checkout"
        :value="$awaitingCount . ' Invoices'"
        subtitle="Unpaid jobs queue ready"
        theme="secondary"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
        ></path>
    </x-cashier.stat-card>

    <!-- Stat 2 -->
    <x-cashier.stat-card
        title="DP Collected Today"
        value="Rp. {{ number_format($dpCollectedToday) }}"
        subtitle="Down payment deposits"
        theme="secondary"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
        ></path>
    </x-cashier.stat-card>

    <!-- Stat 3 -->
    <x-cashier.stat-card
        title="Settlements Today"
        value="Rp. {{ number_format($settlementsToday) }}"
        subtitle="Completed orders pickup cash"
        theme="emerald"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
        ></path>
    </x-cashier.stat-card>

    <!-- Stat 4 -->
    <x-cashier.stat-card
        title="Store Expenses (Today)"
        value="Rp. {{ number_format($todayExpenseTotal) }}"
        subtitle="Withdrawals & modal costs today"
        theme="red"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
        ></path>
    </x-cashier.stat-card>
</div>
@endif
