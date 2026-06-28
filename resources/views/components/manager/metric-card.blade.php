@props([ 'title', 'value', 'badgeText' => null, 'badgeType' => 'info',
'footerText' => null ])

<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
>
    <div class="flex items-center justify-between">
        <div>
            <p
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
            >
                {{ $title }}
            </p>
            <h3
                class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
            >
                {{ $value }}
            </h3>
        </div>
        <div class="bg-brand-primary/10 text-brand-primary rounded-xl p-3">
            {{ $slot }}
        </div>
    </div>

    @if ($badgeText)
    <div
        class="mt-4 flex items-center text-xs font-bold {{ $badgeType === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-brand-secondary dark:text-brand-secondary' }}"
    >
        @if ($badgeType === 'ping')
        <span
            class="bg-brand-secondary mr-2 h-2 w-2 animate-ping rounded-full"
        ></span>
        @else
        <svg
            class="mr-1 h-4 w-4"
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
        @endif
        <span>{{ $badgeText }}</span>
    </div>
    @elseif ($footerText)
    <div
        class="text-light-muted mt-4 flex items-center text-xs font-semibold dark:text-gray-400"
    >
        <span>{{ $footerText }}</span>
    </div>
    @endif
</div>
