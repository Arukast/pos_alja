<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- Header banner -->
    @if ($activeTab === 'dashboard')
    <x-brand-header-banner
        badge="Cash Register Desk"
        title="Active Cashier Desk"
        description="Process customer payments, register down payments, settle outstanding invoices, and log cash drawer withdrawals."
        theme="cashier"
    />
    @endif

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

    <!-- Main Tab Contents -->
    @if ($activeTab === 'dashboard' || $activeTab === 'payments' || $activeTab
    === 'expenses') @if ($activeTab === 'dashboard' || $activeTab ===
    'payments') @if ($activeTab === 'dashboard')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        @endif

        <!-- Billing Queue Panel -->
        @island(name: 'billing-queue')
        @include('livewire.dashboards.partials.cashier._billing_queue')
        @endisland @if ($activeTab === 'dashboard')
        <!-- Log Expense Form Panel -->
        @include('livewire.dashboards.partials.cashier._expense_form')
    </div>
    @endif @endif @if ($activeTab === 'expenses')
    <!-- Tab Expenses: Log Store Expense & Recent Cash Drawer Expenses Logs side-by-side -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            @include('livewire.dashboards.partials.cashier._expense_form')
        </div>
        <div class="lg:col-span-2">
            @island(name: 'expense-ledger')
            @include('livewire.dashboards.partials.cashier._expense_ledger')
            @endisland
        </div>
    </div>
    @endif @endif

    <!-- CUSTOMER PICKUP QUEUE -->
    @if ($activeTab === 'dashboard' || $activeTab === 'pickup')
    @include('livewire.dashboards.partials.cashier._pickup_queue') @endif

    <!-- EXPENSES LEDGER LIST (Dashboard only) -->
    @if ($activeTab === 'dashboard') @island(name: 'expense-ledger')
    @include('livewire.dashboards.partials.cashier._expense_ledger') @endisland
    @endif

    <!-- CHECKOUT MODAL -->
    @include('livewire.dashboards.partials.cashier._checkout_modal')
</div>
