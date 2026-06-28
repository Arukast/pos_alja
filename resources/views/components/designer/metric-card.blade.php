@props([ 'title', 'value', 'valueClass' => 'text-light-text dark:text-white',
'iconContainerClass' => 'bg-brand-primary/10 text-brand-primary
dark:bg-brand-primary/20' ])

<div {{ $attributes->
    merge(['class' => 'border-light-border dark:border-surface-300/40
    dark:bg-surface-600 flex items-center justify-between rounded-2xl border
    bg-white p-6 shadow-sm']) }}>
    <div class="space-y-1">
        <p
            class="text-light-muted text-xs font-bold tracking-wider uppercase dark:text-gray-400"
        >
            {{ $title }}
        </p>
        <h3 class="text-3xl font-black {{ $valueClass }}">{{ $value }}</h3>
    </div>
    <div
        class="flex h-12 w-12 items-center justify-center rounded-xl {{ $iconContainerClass }}"
    >
        {{ $slot }}
    </div>
</div>
