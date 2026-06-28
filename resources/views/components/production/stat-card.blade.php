@props([ 'label', 'value', 'iconClass' => '', 'footerClass' => '', ])

<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
>
    <div class="flex items-center justify-between">
        <div>
            <p
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
            >
                {{ $label }}
            </p>
            <h3
                class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
            >
                {{ $value }}
            </h3>
        </div>
        @if (isset($icon))
        <div class="{{ $iconClass }}">{{ $icon }}</div>
        @endif
    </div>
    @if (isset($footer))
    <div class="{{ $footerClass }} mt-4 flex items-center text-xs font-bold">
        {{ $footer }}
    </div>
    @endif
</div>
