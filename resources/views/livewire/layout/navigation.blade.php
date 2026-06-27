@php $role = auth()->user()->role; $currentRoute = request()->route() ?
request()->route()->getName() : ''; $currentTab = request()->query('tab');
$isActive = function($route, $tab = null) use ($currentRoute, $currentTab) { if
($currentRoute !== $route) { return false; } if ($route === 'profile') { return
true; } if ($tab === null) { return empty($currentTab); } return $currentTab ===
$tab; }; $initials = collect(explode(' ', auth()->user()->name)) ->map(fn($n) =>
mb_substr($n, 0, 1)) ->take(2) ->join(''); // Reusable desktop navigation item
builder $navItem = function($label, $route, $tab = null, $iconHtml, $hash =
null) use ($isActive) { $active = $isActive($route, $tab); $url = route($route,
array_filter(['tab' => $tab])) . ($hash ?: ''); $activeClass = 'bg-brand-primary
text-white shadow-glow-primary'; $inactiveClass = 'text-light-muted
hover:bg-brand-primary/10 hover:text-brand-primary dark:text-gray-300
dark:hover:bg-brand-primary/10 dark:hover:text-brand-primary'; return "
<a
    href="{$url}"
    wire:navigate
    class="&quot; . ($active ? $activeClass : $inactiveClass) . &quot; flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-300"
    title="{$label}"
>
    {$iconHtml}
    <span
        x-show="sidebarOpen"
        x-transition.opacity.duration.300ms
        class="truncate text-sm font-semibold"
        >{$label}</span
    >
</a>
"; }; // Reusable mobile navigation item builder $mobileNavItem =
function($label, $route, $tab = null, $iconHtml, $hash = null) use ($isActive) {
$active = $isActive($route, $tab); $url = route($route, array_filter(['tab' =>
$tab])) . ($hash ?: ''); $activeClass = 'bg-brand-primary text-white
shadow-glow-primary'; $inactiveClass = 'text-light-muted
hover:bg-brand-primary/10 hover:text-brand-primary dark:text-gray-300
dark:hover:bg-brand-primary/10 dark:hover:text-brand-primary'; return "
<a
    href="{$url}"
    wire:navigate
    @click="mobileOpen = false"
    class="&quot; . ($active ? $activeClass : $inactiveClass) . &quot; flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-300"
    title="{$label}"
>
    {$iconHtml}
    <span class="truncate text-sm font-semibold">{$label}</span>
</a>
"; }; // SVG Icons $icons = [ 'dashboard' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11V11a1 1 0 00-1-1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
    /></svg
>', 'leaderboard' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.475 3.475 0 012.67 2.67 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.475 3.475 0 01-2.67 2.67 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.475 3.475 0 01-2.67-2.67 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.475 3.475 0 012.67-2.67z"
    /></svg
>', 'audit' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
    /></svg
>', 'users' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 009 11V7a2 2 0 00-2-2H6a2 2 0 00-2 2v2c0 2.518.553 4.908 1.547 7.053m6-10.053a9 9 0 018.667-3.342M18 16a3 3 0 11-6 0 3 3 0 016 0z"
    /></svg
>', 'materials' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
    /></svg
>', 'ledgers' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
    /></svg
>', 'finishing' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
    /></svg
>', 'pricing' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
    /></svg
>', 'payments' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
    /></svg
>', 'pickup' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
    /></svg
>', 'expenses' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M15 13l-3 3m0 0l-3-3m3 3V8m0 13a9 9 0 11-18 0 9 9 0 0118 0z"
    /></svg
>', 'new_order' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
    /></svg
>', 'order_list' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
    /></svg
>', 'print_queue' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
    /></svg
>', 'waste' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
    /></svg
>', 'profile' => '<svg
    class="h-5 w-5 shrink-0"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
>
    <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"
    /></svg
>' ]; @endphp

<div>
    <!-- ========================================== -->
    <!-- 1. DESKTOP SIDEBAR                         -->
    <!-- ========================================== -->
    <aside
        class="border-light-border dark:border-surface-300/40 bg-light-surface dark:bg-surface-700 relative z-30 hidden h-full shrink-0 flex-col border-r transition-all duration-300 ease-in-out lg:flex"
        :class="sidebarOpen ? 'w-60' : 'w-16'"
    >
        <!-- Brand signature stripe at the very top -->
        <x-brand-stripe />

        <!-- Sidebar Header (Logo & Collapse Button) -->
        <div
            class="border-light-border dark:border-surface-300/40 relative flex h-16 shrink-0 items-center justify-between border-b px-4"
        >
            <a
                href="{{ route('dashboard') }}"
                wire:navigate
                class="flex items-center gap-2 overflow-hidden"
            >
                <div
                    :class="sidebarOpen ? '' : 'w-8 overflow-hidden transition-all duration-300'"
                >
                    <x-brand-logo type="short" size="sm" />
                </div>
            </a>

            <!-- Collapse/Expand Toggle Chevron Button -->
            <button
                @click="toggleSidebar()"
                class="bg-light-surface dark:bg-surface-600 border-light-border dark:border-surface-300/40 text-light-muted hover:text-light-text absolute top-1/2 -right-3 z-40 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-full border shadow-sm transition hover:scale-110 dark:text-gray-400 dark:hover:text-white"
                aria-label="Collapse sidebar"
                id="sidebar-desktop-toggle"
            >
                <svg
                    class="h-4 w-4 transition-transform duration-300"
                    :class="sidebarOpen ? 'rotate-180' : ''"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                    />
                </svg>
            </button>
        </div>

        <!-- Sidebar Middle (Navigation Content) -->
        <div class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
            <!-- ================= OWNER NAV ================= -->
            @if ($role === 'owner')
            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $navItem('Dashboard', 'dashboard', 'finance',
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Insights
                </div>
                {!! $navItem('Designer Leaderboard', 'dashboard', 'finance',
                $icons['leaderboard'], '#leaderboard') !!} {!! $navItem('Audit
                Trail', 'dashboard', 'audit', $icons['audit']) !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Administration
                </div>
                {!! $navItem('User Management', 'dashboard', 'users',
                $icons['users']) !!}
            </div>

            <!-- ================= MANAGER NAV ================= -->
            @elseif ($role === 'manager')
            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $navItem('Dashboard', 'dashboard', 'inventory',
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Inventory
                </div>
                {!! $navItem('Materials & Stock', 'dashboard', 'inventory',
                $icons['materials']) !!} {!! $navItem('Stock Ledgers',
                'dashboard', 'inventory', $icons['ledgers'], '#stock-ledgers')
                !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Configuration
                </div>
                {!! $navItem('Display & Finishing', 'dashboard', 'inventory',
                $icons['finishing'], '#display-finishing') !!} {!!
                $navItem('Pricing Matrices', 'dashboard', 'inventory',
                $icons['pricing'], '#pricing-matrices') !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Administration
                </div>
                {!! $navItem('User Management', 'dashboard', 'users',
                $icons['users']) !!}
            </div>

            <!-- ================= CASHIER NAV ================= -->
            @elseif ($role === 'cashier')
            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $navItem('Dashboard', 'dashboard', null,
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Operations
                </div>
                {!! $navItem('Payment Queue', 'dashboard', null,
                $icons['payments'], '#payment-queue') !!} {!! $navItem('Pickup
                Queue', 'dashboard', null, $icons['pickup'], '#pickup-queue')
                !!} {!! $navItem('Expenses', 'dashboard', null,
                $icons['expenses'], '#expenses') !!}
            </div>

            <!-- ================= DESIGNER NAV ================= -->
            @elseif ($role === 'designer')
            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $navItem('Dashboard', 'dashboard', null,
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Orders
                </div>
                {!! $navItem('New Order', 'dashboard', null,
                $icons['new_order'], '#new-order') !!} {!! $navItem('Order
                List', 'dashboard', null, $icons['order_list'], '#order-list')
                !!}
            </div>

            <!-- ================= PRODUCTION NAV ================= -->
            @elseif ($role === 'production')
            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $navItem('Dashboard', 'dashboard', null,
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Operations
                </div>
                {!! $navItem('Print Queue', 'dashboard', null,
                $icons['print_queue'], '#print-queue') !!} {!! $navItem('Waste
                Logging', 'dashboard', null, $icons['waste'], '#waste-logging')
                !!}
            </div>
            @endif

            <!-- ================= SHARED ACCOUNT SECTION ================= -->
            <div
                class="border-light-border dark:border-surface-300/20 space-y-1 border-t pt-4"
            >
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Account
                </div>
                {!! $navItem('Profile & Settings', 'profile', null,
                $icons['profile']) !!}
            </div>
        </div>

        <!-- Sidebar Footer (User info & Actions) -->
        <div
            class="border-light-border dark:border-surface-300/40 bg-light-surface-2/50 dark:bg-surface-600/30 border-t p-3"
        >
            <div
                class="flex transition-all duration-300"
                :class="sidebarOpen ? 'flex-row items-center justify-between gap-2' : 'flex-col items-center gap-4 py-2'"
            >
                <!-- User Avatar details -->
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="bg-brand-primary/10 text-brand-primary flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold"
                        title="{{ auth()->user()->name }} ({{ auth()->user()->role }})"
                    >
                        {{ $initials }}
                    </div>
                    <div
                        x-show="sidebarOpen"
                        x-transition.opacity.duration.300ms
                        class="min-w-0"
                    >
                        <p
                            class="text-light-text max-w-[95px] truncate text-xs font-semibold dark:text-white"
                        >
                            {{ auth()->user()->name }}
                        </p>
                        <p
                            class="text-brand-secondary text-[10px] font-bold tracking-wider uppercase"
                        >
                            {{ $role }}
                        </p>
                    </div>
                </div>

                <!-- Theme toggle & logout actions -->
                <div
                    class="flex"
                    :class="sidebarOpen ? 'flex-row gap-1' : 'flex-col gap-2'"
                >
                    <!-- Theme Toggle -->
                    <button
                        @click="
                            if (document.documentElement.classList.contains('dark')) {
                                document.documentElement.classList.remove('dark');
                                localStorage.setItem('theme', 'light');
                            } else {
                                document.documentElement.classList.add('dark');
                                localStorage.setItem('theme', 'dark');
                            }
                        "
                        class="text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:bg-surface-400/50 flex items-center justify-center rounded-xl p-2 transition duration-200 dark:text-gray-400 dark:hover:text-white"
                        title="Toggle Dark/Light Mode"
                        aria-label="Toggle Dark/Light Mode"
                        id="desktop-theme-toggle"
                    >
                        <svg
                            class="hidden h-5 w-5 shrink-0 dark:block"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <svg
                            class="block h-5 w-5 shrink-0 dark:hidden"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                            />
                        </svg>
                    </button>

                    <!-- Logout Button -->
                    <button
                        wire:click="logout"
                        class="text-light-muted flex items-center justify-center rounded-xl p-2 transition duration-200 hover:bg-red-500/10 hover:text-red-500 dark:text-gray-400 dark:hover:bg-red-500/20 dark:hover:text-red-400"
                        title="Log Out"
                        aria-label="Log Out"
                        id="desktop-logout-btn"
                    >
                        <svg
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- 2. MOBILE DRAWER NAVIGATION                -->
    <!-- ========================================== -->
    <!-- Drawer Backdrop overlay (mobile) -->
    <div
        x-show="mobileOpen"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileOpen = false"
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm lg:hidden"
        style="display: none"
    ></div>

    <!-- Left Drawer slide-in panel (mobile) -->
    <aside
        class="bg-light-surface dark:bg-surface-700 border-light-border dark:border-surface-300/40 fixed inset-y-0 left-0 z-50 flex h-full w-60 transform flex-col border-r transition-transform duration-300 ease-in-out lg:hidden"
        :class="mobileOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <!-- Brand signature stripe at the very top of drawer -->
        <x-brand-stripe />

        <!-- Header of Drawer -->
        <div
            class="border-light-border dark:border-surface-300/40 flex h-16 shrink-0 items-center justify-between border-b px-4"
        >
            <a
                href="{{ route('dashboard') }}"
                wire:navigate
                @click="mobileOpen = false"
                class="flex items-center gap-2"
            >
                <x-brand-logo type="short" size="sm" />
            </a>

            <button
                @click="mobileOpen = false"
                class="text-light-muted hover:bg-light-surface-2 hover:text-light-text dark:hover:bg-surface-400/50 rounded-lg p-2 dark:text-gray-400 dark:hover:text-white"
                aria-label="Close menu"
                id="mobile-drawer-close"
            >
                <svg
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <!-- Middle Content of Drawer -->
        <div class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
            <!-- ================= OWNER NAV (MOBILE) ================= -->
            @if ($role === 'owner')
            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $mobileNavItem('Dashboard', 'dashboard', 'finance',
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Insights
                </div>
                {!! $mobileNavItem('Designer Leaderboard', 'dashboard',
                'finance', $icons['leaderboard'], '#leaderboard') !!} {!!
                $mobileNavItem('Audit Trail', 'dashboard', 'audit',
                $icons['audit']) !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Administration
                </div>
                {!! $mobileNavItem('User Management', 'dashboard', 'users',
                $icons['users']) !!}
            </div>

            <!-- ================= MANAGER NAV (MOBILE) ================= -->
            @elseif ($role === 'manager')
            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $mobileNavItem('Dashboard', 'dashboard', 'inventory',
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Inventory
                </div>
                {!! $mobileNavItem('Materials & Stock', 'dashboard',
                'inventory', $icons['materials']) !!} {!! $mobileNavItem('Stock
                Ledgers', 'dashboard', 'inventory', $icons['ledgers'],
                '#stock-ledgers') !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Configuration
                </div>
                {!! $mobileNavItem('Display & Finishing', 'dashboard',
                'inventory', $icons['finishing'], '#display-finishing') !!} {!!
                $mobileNavItem('Pricing Matrices', 'dashboard', 'inventory',
                $icons['pricing'], '#pricing-matrices') !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Administration
                </div>
                {!! $mobileNavItem('User Management', 'dashboard', 'users',
                $icons['users']) !!}
            </div>

            <!-- ================= CASHIER NAV (MOBILE) ================= -->
            @elseif ($role === 'cashier')
            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $mobileNavItem('Dashboard', 'dashboard', null,
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Operations
                </div>
                {!! $mobileNavItem('Payment Queue', 'dashboard', null,
                $icons['payments'], '#payment-queue') !!} {!!
                $mobileNavItem('Pickup Queue', 'dashboard', null,
                $icons['pickup'], '#pickup-queue') !!} {!!
                $mobileNavItem('Expenses', 'dashboard', null,
                $icons['expenses'], '#expenses') !!}
            </div>

            <!-- ================= DESIGNER NAV (MOBILE) ================= -->
            @elseif ($role === 'designer')
            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $mobileNavItem('Dashboard', 'dashboard', null,
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Orders
                </div>
                {!! $mobileNavItem('New Order', 'dashboard', null,
                $icons['new_order'], '#new-order') !!} {!! $mobileNavItem('Order
                List', 'dashboard', null, $icons['order_list'], '#order-list')
                !!}
            </div>

            <!-- ================= PRODUCTION NAV (MOBILE) ================= -->
            @elseif ($role === 'production')
            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $mobileNavItem('Dashboard', 'dashboard', null,
                $icons['dashboard']) !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Operations
                </div>
                {!! $mobileNavItem('Print Queue', 'dashboard', null,
                $icons['print_queue'], '#print-queue') !!} {!!
                $mobileNavItem('Waste Logging', 'dashboard', null,
                $icons['waste'], '#waste-logging') !!}
            </div>
            @endif

            <!-- ================= SHARED ACCOUNT SECTION (MOBILE) ================= -->
            <div
                class="border-light-border dark:border-surface-300/20 space-y-1 border-t pt-4"
            >
                <div
                    class="text-light-muted mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Account
                </div>
                {!! $mobileNavItem('Profile & Settings', 'profile', null,
                $icons['profile']) !!}
            </div>
        </div>

        <!-- Footer of Drawer (Mobile) -->
        <div
            class="border-light-border dark:border-surface-300/40 bg-light-surface-2/50 dark:bg-surface-600/30 border-t p-3"
        >
            <div class="flex items-center justify-between gap-2">
                <!-- User details -->
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="bg-brand-primary/10 text-brand-primary flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold"
                    >
                        {{ $initials }}
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-light-text max-w-[100px] truncate text-xs font-semibold dark:text-white"
                        >
                            {{ auth()->user()->name }}
                        </p>
                        <p
                            class="text-brand-secondary text-[10px] font-bold tracking-wider uppercase"
                        >
                            {{ $role }}
                        </p>
                    </div>
                </div>

                <!-- Theme toggle & logout actions -->
                <div class="flex gap-1">
                    <!-- Theme Toggle -->
                    <button
                        @click="
                            if (document.documentElement.classList.contains('dark')) {
                                document.documentElement.classList.remove('dark');
                                localStorage.setItem('theme', 'light');
                            } else {
                                document.documentElement.classList.add('dark');
                                localStorage.setItem('theme', 'dark');
                            }
                        "
                        class="text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:bg-surface-400/50 flex items-center justify-center rounded-xl p-2 transition duration-200 dark:text-gray-400 dark:hover:text-white"
                        title="Toggle Dark/Light Mode"
                        aria-label="Toggle Dark/Light Mode"
                        id="mobile-theme-toggle"
                    >
                        <svg
                            class="hidden h-5 w-5 shrink-0 dark:block"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        <svg
                            class="block h-5 w-5 shrink-0 dark:hidden"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                            />
                        </svg>
                    </button>

                    <!-- Logout Button -->
                    <button
                        wire:click="logout"
                        class="text-light-muted flex items-center justify-center rounded-xl p-2 transition duration-200 hover:bg-red-500/10 hover:text-red-500 dark:text-gray-400 dark:hover:bg-red-500/20 dark:hover:text-red-400"
                        title="Log Out"
                        aria-label="Log Out"
                        id="mobile-logout-btn"
                    >
                        <svg
                            class="h-5 w-5 shrink-0"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </aside>
</div>
