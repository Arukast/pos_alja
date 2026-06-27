<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (auth()->user()->role === 'manager')
            <livewire:dashboards.manager />
            @elseif (auth()->user()->role === 'owner')
            <livewire:dashboards.owner />
            @elseif (auth()->user()->role === 'cashier')
            <livewire:dashboards.cashier />
            @elseif (auth()->user()->role === 'designer')
            <livewire:dashboards.designer />
            @elseif (auth()->user()->role === 'production')
            <livewire:dashboards.production />
            @else
            <div
                class="dark:bg-surface-600 border-light-border dark:border-surface-300/40 dark:shadow-card overflow-hidden rounded-2xl border bg-white shadow-sm"
            >
                <div class="text-light-text p-6 dark:text-gray-200">
                    {{ __("You're logged in!") }}
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
