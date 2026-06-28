<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- prettier-ignore -->
    @if ($activeTab === 'finance')
        @include('livewire.dashboards.partials.owner._header_banner')
        @include('livewire.dashboards.partials.owner._finance_metrics')
        @include('livewire.dashboards.partials.owner._sparkline_chart')
    @elseif ($activeTab === 'revenue')
        @include('livewire.dashboards.partials.owner._header_banner')
        @island(name: 'gross-revenue-detail')
    <livewire:dashboards.owner.gross-revenue-detail
        :selectedBranchId="$selectedBranchId"
        :period="$period"
    />
    @endisland @elseif ($activeTab === 'users')
    <livewire:admin.user-management
        mode="owner"
        wire:key="owner-user-management"
    />
    @elseif ($activeTab === 'audit')
    @include('livewire.dashboards.partials.owner._audit_trail') @endif
    @include('livewire.dashboards.partials.owner._audit_detail_modal')
</div>
