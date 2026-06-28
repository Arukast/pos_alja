<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- Header banner -->
    @include('livewire.dashboards.partials.cashier._header_banner')

    <!-- Feedback alert -->
    @include('livewire.dashboards.partials.cashier._feedback_alert')

    <!-- Quick stats grid -->
    @include('livewire.dashboards.partials.cashier._stats_grid')

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
