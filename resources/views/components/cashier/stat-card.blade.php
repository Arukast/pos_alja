@props([ 'title', 'value', 'subtitle', 'theme' => 'secondary', ])

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
        <div
            class="rounded-xl p-3 {{ $theme === 'emerald' ? 'bg-emerald-500/10 text-emerald-500 dark:text-emerald-400' : ($theme === 'red' ? 'bg-red-500/10 text-red-500 dark:text-red-400' : 'bg-brand-secondary/10 text-brand-secondary') }}"
        >
            <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                {{ $slot }}
            </svg>
        </div>
    </div>
    @if ($subtitle)
    <div
        class="mt-4 flex items-center text-xs {{ $theme === 'emerald' ? 'text-emerald-600 font-bold dark:text-emerald-400' : ($theme === 'red' ? 'text-red-550 font-bold dark:text-red-400' : ($title === 'Awaiting Checkout' ? 'text-brand-secondary font-bold' : 'text-light-muted font-semibold dark:text-gray-400')) }}"
    >
        @if ($title === 'Awaiting Checkout')
        <span
            class="bg-brand-secondary mr-2 h-2 w-2 animate-ping rounded-full"
        ></span>
        @endif
        <span>{{ $subtitle }}</span>
    </div>
    @endif
</div>
