<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-xl text-white leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                <div class="bg-surface-500/60 backdrop-blur-sm border border-surface-300/40 overflow-hidden shadow-card rounded-2xl">
                    <div class="p-6 text-gray-200">
                        {{ __("You're logged in!") }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
