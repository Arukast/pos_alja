<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- Header banner (Only on Dashboard Overview tab) -->
    <!-- prettier-ignore -->
    @if ($activeTab === 'dashboard')
    <x-brand-header-banner
        badge="Branch Manager Portal"
        title="Main Branch Overview"
        description="Manage your shop inventory, adjust pricing engine parameters, oversee printing machinery, and coordinate staff."
        theme="manager"
    />
    @endif

    <!-- Feedback alert -->
    <x-manager.feedback-alert
        :show="$showSuccessAlert"
        :message="$successMessage"
    />

    <!-- 1. DASHBOARD TAB -->
    <!-- prettier-ignore -->
    @if ($activeTab === 'dashboard')
        @include('livewire.dashboards.partials.manager._dashboard_overview')
    @endif

    <!-- 2. MATERIALS & STOCK TAB -->
    <!-- prettier-ignore -->
    @if ($activeTab === 'materials')
        @include('livewire.dashboards.partials.manager._materials_stock')
    @endif

    <!-- 3. STOCK LEDGERS TAB -->
    <!-- prettier-ignore -->
    @if ($activeTab === 'ledgers')
        @include('livewire.dashboards.partials.manager._stock_ledgers')
    @endif

    <!-- 4. DISPLAY & FINISHING TAB -->
    <!-- prettier-ignore -->
    @if ($activeTab === 'finishing')
        @include('livewire.dashboards.partials.manager._display_finishing')
    @endif

    <!-- 5. PRICING MATRICES TAB -->
    <!-- prettier-ignore -->
    @if ($activeTab === 'pricing')
        @include('livewire.dashboards.partials.manager._pricing_matrices')
    @endif

    <!-- 5a. DESIGNER LEADERBOARD TAB -->
    <!-- prettier-ignore -->
    @if ($activeTab === 'leaderboard')
        @include('livewire.dashboards.partials.manager._designer_leaderboard')
    @endif

    <!-- 6. USER MANAGEMENT SUB-COMPONENT -->
    <!-- prettier-ignore -->
    @if ($activeTab === 'users')
    <livewire:admin.user-management
        mode="manager"
        wire:key="manager-user-management"
    />
    @endif
</div>
