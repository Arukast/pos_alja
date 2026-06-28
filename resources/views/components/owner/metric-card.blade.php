@props([ 'title', 'value', 'iconColor' => 'bg-brand-primary/10
text-brand-primary' ])

<!-- prettier-ignore -->
<div class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                {{ $title }}
            </p>
            <h3 class="text-light-text mt-1 text-2xl font-extrabold dark:text-white">
                {{ $value }}
            </h3>
        </div>
        @if (isset($icon))
            <div class="{{ $iconColor }} rounded-xl p-3">
                {{ $icon }}
            </div>
        @endif
    </div>
    @if (isset($comparison))
        <div class="mt-4 flex items-center text-xs font-medium">
            {{ $comparison }}
        </div>
    @endif
    @if (isset($footer))
        {{ $footer }}
    @endif
</div>
