@props([ 'label', 'route', 'tab' => null, 'icon', 'hash' => null, 'mobile' =>
false, ]) @php $currentRoute = request()->route() ?
request()->route()->getName() : ''; $currentTab = request()->query('tab');
$active = false; if ($currentRoute === $route) { if ($route === 'profile') {
$active = true; } elseif ($tab === null) { $active = empty($currentTab); } else
{ $active = ($currentTab === $tab); } } $url = route($route, array_filter(['tab'
=> $tab])) . ($hash ?: ''); $activeClass = 'bg-brand-primary text-white
shadow-glow-primary'; $inactiveClass = 'text-light-muted
hover:bg-brand-primary/10 hover:text-brand-primary dark:text-gray-300
dark:hover:bg-brand-primary/10 dark:hover:text-brand-primary'; $activeStr =
$active ? 'true' : 'false'; $hashCondition = $hash ? "currentHash === '{$hash}'"
: "(currentHash === '' || currentHash === '#')"; $classCondition =
"!{$activeStr} ? '{$inactiveClass}' : ({$hashCondition} ? '{$activeClass}' :
'{$inactiveClass}')"; @endphp

<a
    href="{{ $url }}"
    wire:navigate
    @if($mobile)
    @click="mobileOpen = false"
    @endif
    :class="{{ $classCondition }}"
    class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-300"
    title="{{ $label }}"
>
    <x-icon :name="$icon" class="h-5 w-5 shrink-0 text-current" />

    @if($mobile)
    <span class="truncate text-sm font-semibold">{{ $label }}</span>
    @else
    <span
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        class="truncate text-sm font-semibold"
    >
        {{ $label }}
    </span>
    @endif
</a>
