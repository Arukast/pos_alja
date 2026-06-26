@props([ 'size' => 'sm', ]) @php $dotClass = $size === 'md' ? 'w-3 h-3
rounded-sm' : 'w-2 h-2 rounded-sm'; $gapClass = $size === 'md' ? 'gap-1.5' :
'gap-1'; @endphp

<div {{ $attributes->
    merge(['class' => 'flex ' . $gapClass]) }}>
    <div
        class="{{ $dotClass }} bg-brand-primary transition-transform duration-200 group-hover:scale-110"
    ></div>
    <div
        class="{{ $dotClass }} bg-brand-secondary transition-transform duration-200 group-hover:scale-110"
        style="transition-delay: 50ms"
    ></div>
    <div
        class="{{ $dotClass }} bg-brand-accent transition-transform duration-200 group-hover:scale-110"
        style="transition-delay: 100ms"
    ></div>
    @if ($size === 'md')
    <div class="{{ $dotClass }} bg-gray-400 dark:bg-surface-300"></div>
    @endif
</div>
