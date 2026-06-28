@props([ 'initials', 'role', 'sidebarOpen' => true, ])

<button {{ $attributes->
    merge([ 'class' => 'hover:bg-brand-primary/5 dark:hover:bg-surface-400/10
    flex min-w-0 items-center gap-2.5 rounded-lg p-1 text-left transition
    duration-200 focus:outline-none' ]) }} title="{{ auth()->user()->name }} ({{
    auth()->user()->role }})" >
    <div
        class="bg-brand-primary/10 text-brand-primary flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold transition duration-200 group-hover:scale-105"
    >
        {{ $initials }}
    </div>
    @if($sidebarOpen)
    <div
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        class="min-w-0 flex-1"
    >
        <!-- prettier-ignore -->
        <p class="text-light-text truncate text-xs font-semibold dark:text-white">
                {{ auth()->user()->name }}
            </p>
        <p
            class="text-brand-secondary text-[10px] font-bold tracking-wider uppercase"
        >
            {{ $role }}
        </p>
    </div>
    @endif
</button>
