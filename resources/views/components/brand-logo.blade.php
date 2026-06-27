@props([ 'type' => 'short', 'size' => 'md', ]) @php $brandName =
config('brand.name', 'ALJA'); $brandSubname = config('brand.subname', 'POS');
$brandFullname = config('brand.fullname', 'ALJA Digital Printing'); $sizeClasses
= [ 'sm' => 'text-sm font-bold tracking-wide text-light-text dark:text-white',
'md' => 'text-base font-bold tracking-wide text-light-text dark:text-white',
'lg' => 'text-3xl font-extrabold tracking-tight text-light-text
dark:text-white', ]; $logoHeights = [ 'sm' => 'h-6', 'md' => 'h-8', 'lg' =>
'h-12', ]; $mergedAttributes = $attributes->merge(['class' => 'flex items-center
gap-2.5']); @endphp

<div {{ $mergedAttributes }}>
    @if (config('brand.logo'))
    <img
        src="{{ asset(config('brand.logo')) }}"
        alt="{{ $brandFullname }}"
        class="{{ $logoHeights[$size] ?? 'h-8' }} w-auto object-contain"
    />
    @else
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
        <span class="text-brand-primary ml-0.5"
            >{{ str_replace($brandName . ' ', '', $brandFullname) }}</span
        >
    </h1>
    @endif @endif
</div>
