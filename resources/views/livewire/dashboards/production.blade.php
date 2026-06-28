<div class="text-light-text space-y-6 dark:text-gray-100">
    @include('livewire.dashboards.partials.production._header')
    @include('livewire.dashboards.partials.production._stats')
    @include('livewire.dashboards.partials.production._print_queue') @if
    ($activeTab === 'waste')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        @include('livewire.dashboards.partials.production._waste_form')
        @include('livewire.dashboards.partials.production._waste_log')
    </div>
    @endif
</div>
