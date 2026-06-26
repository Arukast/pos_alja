@props([ 'type' => 'short', 'size' => 'md', ]) @php $brandName =
config('brand.name', 'ALJA'); $brandSubname = config('brand.subname', 'POS');
$brandFullname = config('brand.fullname', 'ALJA Digital Printing'); $sizeClasses
= [ 'sm' => 'text-sm font-bold tracking-wide text-gray-800 dark:text-white',
'md' => 'text-base font-bold tracking-wide text-gray-800 dark:text-white', 'lg'
=> 'text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white', ];
@endphp

<div {{ $attributes->
    merge(['class' => 'flex items-center gap-2.5']) }}>
    <!-- Centralized Brand Dots decoration -->
    <x-brand-dots size="{{ $size === 'lg' ? 'md' : 'sm' }}" />

    @if ($type === 'short')
    <span class="{{ $sizeClasses[$size] ?? $sizeClasses['sm'] }}">
        {{ $brandName }}<span class="text-brand-primary ml-0.5"
            >{{ $brandSubname }}</span
        >
    </span>
    @else
    <h1 class="{{ $sizeClasses[$size] ?? $sizeClasses['lg'] }}">
        {{ $brandName }}
        <span class="text-brand-gradient"
            >{{ str_replace($brandName . ' ', '', $brandFullname) }}</span
        >
    </h1>
    @endif
</div>
