<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- Alert Panel -->
    @include('livewire.dashboards.partials.designer._alert_panel')

    <!-- prettier-ignore -->
    @if ($activeTab === 'dashboard')
    @include('livewire.dashboards.partials.designer._header_banner')
    @include('livewire.dashboards.partials.designer._metrics_grid')

    @include('livewire.dashboards.partials.designer._invoice_history')

    <!-- prettier-ignore -->
    @elseif ($activeTab === 'new_order')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- LEFT PANEL: CUSTOMER & METADATA -->
        <div class="space-y-6 lg:col-span-1">
            @include('livewire.dashboards.partials.designer._customer_intake')
            @include('livewire.dashboards.partials.designer._job_parameters')
        </div>

        <!-- RIGHT PANEL: INTERACTIVE CALCULATOR & CART -->
        <div class="space-y-6 lg:col-span-2">
            <!-- prettier-ignore -->
            @island(name: 'designer-calculator')
                    @include('livewire.dashboards.partials.designer._quote_calculator')
                @endisland

            <!-- prettier-ignore -->
            @island(name: 'designer-cart')
                    @include('livewire.dashboards.partials.designer._order_cart')
                @endisland
        </div>
    </div>

    <!-- prettier-ignore -->
    @elseif ($activeTab === 'order_list')
        @include('livewire.dashboards.partials.designer._invoice_history')
    @endif
</div>
