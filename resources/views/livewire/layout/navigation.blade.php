@php $role = auth()->user()->role; $currentRoute = request()->route() ?
request()->route()->getName() : ''; $currentTab = request()->query('tab');
$isActive = function ($route, $tab = null) use ($currentRoute, $currentTab) { if
($currentRoute !== $route) { return false; } if ($route === 'profile') { return
true; } if ($tab === null) { return empty($currentTab); } return $currentTab ===
$tab; }; $initials = collect(explode(' ', auth()->user()->name)) ->map(fn($n) =>
mb_substr($n, 0, 1)) ->take(2) ->join(''); /* Reusable desktop navigation item
builder */ $navItem = function ($label, $route, $tab = null, $iconName, $hash =
null) use ($isActive) { $active = $isActive($route, $tab); $url = route($route,
array_filter(['tab' => $tab])) . ($hash ?: ''); $activeClass = 'bg-brand-primary
text-white shadow-glow-primary'; $inactiveClass = 'text-light-muted
hover:bg-brand-primary/10 hover:text-brand-primary dark:text-gray-300
dark:hover:bg-brand-primary/10 dark:hover:text-brand-primary'; $class = $active
? $activeClass : $inactiveClass; $iconHtml = view('components.icon', ['name' =>
$iconName, 'class' => 'h-5 w-5 shrink-0 text-current'])->render(); return "<" .
"a href=\"{$url}\" wire:navigate class=\"{$class} flex items-center gap-3
rounded-xl px-3 py-2.5 transition-all duration-300\" title=\"{$label}\">" .
$iconHtml . "<" . "span x-show=\"sidebarOpen\"
x-transition.opacity.duration.300ms class=\"truncate text-sm
font-semibold\">{$label}<" . "/span><" . "/a>"; }; /* Reusable mobile navigation
item builder */ $mobileNavItem = function ($label, $route, $tab = null,
$iconName, $hash = null) use ($isActive) { $active = $isActive($route, $tab);
$url = route($route, array_filter(['tab' => $tab])) . ($hash ?: '');
$activeClass = 'bg-brand-primary text-white shadow-glow-primary'; $inactiveClass
= 'text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary
dark:text-gray-300 dark:hover:bg-brand-primary/10
dark:hover:text-brand-primary'; $class = $active ? $activeClass :
$inactiveClass; $iconHtml = view('components.icon', ['name' => $iconName,
'class' => 'h-5 w-5 shrink-0 text-current'])->render(); return "<" . "a
href=\"{$url}\" wire:navigate @click=\"mobileOpen = false\" class=\"{$class}
flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-300\"
title=\"{$label}\">" . $iconHtml . "<" . "span class=\"truncate text-sm
font-semibold\">{$label}<" . "/span><" . "/a>"; }; @endphp

<div class="flex h-full shrink-0">
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
                {!! $navItem('Dashboard', 'dashboard', 'finance', 'dashboard')
                !!}
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
                'leaderboard', '#leaderboard') !!} {!! $navItem('Audit Trail',
                'dashboard', 'audit', 'audit') !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Administration
                </div>
                {!! $navItem('User Management', 'dashboard', 'users', 'users')
                !!}
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
                {!! $navItem('Dashboard', 'dashboard', 'inventory', 'dashboard')
                !!}
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
                'materials') !!} {!! $navItem('Stock Ledgers', 'dashboard',
                'inventory', 'ledgers', '#stock-ledgers') !!}
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
                'finishing', '#display-finishing') !!} {!! $navItem('Pricing
                Matrices', 'dashboard', 'inventory', 'pricing',
                '#pricing-matrices') !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Administration
                </div>
                {!! $navItem('User Management', 'dashboard', 'users', 'users')
                !!}
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
                {!! $navItem('Dashboard', 'dashboard', null, 'dashboard') !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Operations
                </div>
                {!! $navItem('Payment Queue', 'dashboard', null, 'payments',
                '#payment-queue') !!} {!! $navItem('Pickup Queue', 'dashboard',
                null, 'pickup', '#pickup-queue') !!} {!! $navItem('Expenses',
                'dashboard', null, 'expenses', '#expenses') !!}
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
                {!! $navItem('Dashboard', 'dashboard', null, 'dashboard') !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Orders
                </div>
                {!! $navItem('New Order', 'dashboard', null, 'new_order',
                '#new-order') !!} {!! $navItem('Order List', 'dashboard', null,
                'order_list', '#order-list') !!}
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
                {!! $navItem('Dashboard', 'dashboard', null, 'dashboard') !!}
            </div>

            <div class="space-y-1">
                <div
                    x-show="sidebarOpen"
                    x-transition.opacity.duration.300ms
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Operations
                </div>
                {!! $navItem('Print Queue', 'dashboard', null, 'print_queue',
                '#print-queue') !!} {!! $navItem('Waste Logging', 'dashboard',
                null, 'waste', '#waste-logging') !!}
            </div>
            @endif
        </div>

        <!-- Sidebar Footer (User info & Popover Actions) -->
        <div
            class="border-light-border dark:border-surface-300/40 bg-light-surface-2/50 dark:bg-surface-600/30 relative z-40 border-t p-3"
            x-data="{ popoverOpen: false }"
            @click.outside="popoverOpen = false"
        >
            <!-- Popover Menu -->
            <div
                x-show="popoverOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                class="border-light-border bg-light-surface dark:border-surface-300/40 dark:bg-surface-700 absolute right-3 bottom-[100%] left-3 z-50 mb-2 rounded-xl border p-2 shadow-lg"
                style="bottom: 100%; display: none"
            >
                <div class="space-y-1">
                    <a
                        href="{{ route('profile') }}"
                        wire:navigate
                        class="text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:bg-brand-primary/10 dark:hover:text-brand-primary flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-semibold transition dark:text-gray-300"
                    >
                        <x-icon
                            name="profile"
                            class="h-4 w-4 shrink-0 text-current"
                        />
                        <span>Profile & Settings</span>
                    </a>
                    <div
                        class="border-light-border dark:border-surface-300/20 my-1 border-t"
                    ></div>
                    <button
                        wire:click="logout"
                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-xs font-semibold text-red-500 transition hover:bg-red-500/10 dark:hover:bg-red-500/20"
                    >
                        <x-icon
                            name="logout"
                            class="h-4 w-4 shrink-0 text-current"
                        />
                        <span>Log Out</span>
                    </button>
                </div>
            </div>

            <!-- Footer Details & Toggles Row -->
            <div
                class="flex items-center justify-between gap-1.5 transition-all duration-300"
                :class="sidebarOpen ? 'flex-row' : 'flex-col gap-3 py-1'"
            >
                <!-- Trigger Button holding profile details -->
                <button
                    @click="popoverOpen = !popoverOpen"
                    class="hover:bg-brand-primary/5 dark:hover:bg-surface-400/10 flex min-w-0 items-center gap-2.5 rounded-lg p-1 text-left transition duration-200 focus:outline-none"
                    :class="sidebarOpen ? 'flex-1' : 'w-full justify-center'"
                    title="{{ auth()->user()->name }} ({{ auth()->user()->role }})"
                >
                    <div
                        class="bg-brand-primary/10 text-brand-primary flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold transition duration-200 group-hover:scale-105"
                    >
                        {{ $initials }}
                    </div>
                    <div
                        x-show="sidebarOpen"
                        x-transition.opacity.duration.300ms
                        class="min-w-0 flex-1"
                    >
                        <!-- prettier-ignore -->
                        <p class="text-light-text truncate text-xs font-semibold dark:text-white">
                            {{ auth()->user()->name }}
                        </p>
                        <p
                            class="text-brand-secondary text-[10px] font-bold tracking-wider uppercase"
                        >
                            {{ $role }}
                        </p>
                    </div>
                </button>

                <!-- Theme toggle action -->
                <div
                    class="flex shrink-0"
                    :class="sidebarOpen ? 'flex-row' : 'flex-col'"
                >
                    <!-- Theme Toggle -->
                    <button
                        @click="
                            if (document.documentElement.classList.contains('dark')) {
                                document.documentElement.classList.remove('dark');
                                document.documentElement.classList.add('light');
                                document.documentElement.setAttribute('data-theme', 'light');
                                localStorage.setItem('theme', 'light');
                            } else {
                                document.documentElement.classList.add('dark');
                                document.documentElement.classList.remove('light');
                                document.documentElement.setAttribute('data-theme', 'dark');
                                localStorage.setItem('theme', 'dark');
                            }
                        "
                        class="text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:bg-surface-400/50 flex items-center justify-center rounded-xl p-2 transition duration-200 dark:text-gray-400 dark:hover:text-white"
                        title="Toggle Dark/Light Mode"
                        aria-label="Toggle Dark/Light Mode"
                        id="desktop-theme-toggle"
                    >
                        <x-icon
                            name="sun"
                            class="hidden h-5 w-5 shrink-0 dark:block"
                        />
                        <x-icon
                            name="moon"
                            class="block h-5 w-5 shrink-0 dark:hidden"
                        />
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
                'dashboard') !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Insights
                </div>
                {!! $mobileNavItem('Designer Leaderboard', 'dashboard',
                'finance', 'leaderboard', '#leaderboard') !!} {!!
                $mobileNavItem('Audit Trail', 'dashboard', 'audit', 'audit') !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Administration
                </div>
                {!! $mobileNavItem('User Management', 'dashboard', 'users',
                'users') !!}
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
                'dashboard') !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Inventory
                </div>
                {!! $mobileNavItem('Materials & Stock', 'dashboard',
                'inventory', 'materials') !!} {!! $mobileNavItem('Stock
                Ledgers', 'dashboard', 'inventory', 'ledgers', '#stock-ledgers')
                !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Configuration
                </div>
                {!! $mobileNavItem('Display & Finishing', 'dashboard',
                'inventory', 'finishing', '#display-finishing') !!} {!!
                $mobileNavItem('Pricing Matrices', 'dashboard', 'inventory',
                'pricing', '#pricing-matrices') !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Administration
                </div>
                {!! $mobileNavItem('User Management', 'dashboard', 'users',
                'users') !!}
            </div>

            <!-- ================= CASHIER NAV (MOBILE) ================= -->
            @elseif ($role === 'cashier')
            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $mobileNavItem('Dashboard', 'dashboard', null, 'dashboard')
                !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Operations
                </div>
                {!! $mobileNavItem('Payment Queue', 'dashboard', null,
                'payments', '#payment-queue') !!} {!! $mobileNavItem('Pickup
                Queue', 'dashboard', null, 'pickup', '#pickup-queue') !!} {!!
                $mobileNavItem('Expenses', 'dashboard', null, 'expenses',
                '#expenses') !!}
            </div>

            <!-- ================= DESIGNER NAV (MOBILE) ================= -->
            @elseif ($role === 'designer')
            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $mobileNavItem('Dashboard', 'dashboard', null, 'dashboard')
                !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Orders
                </div>
                {!! $mobileNavItem('New Order', 'dashboard', null, 'new_order',
                '#new-order') !!} {!! $mobileNavItem('Order List', 'dashboard',
                null, 'order_list', '#order-list') !!}
            </div>

            <!-- ================= PRODUCTION NAV (MOBILE) ================= -->
            @elseif ($role === 'production')
            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Overview
                </div>
                {!! $mobileNavItem('Dashboard', 'dashboard', null, 'dashboard')
                !!}
            </div>

            <div class="space-y-1">
                <div
                    class="text-light-muted mt-2 mb-1.5 px-3 text-[10px] font-bold tracking-widest uppercase dark:text-gray-400"
                >
                    Operations
                </div>
                {!! $mobileNavItem('Print Queue', 'dashboard', null,
                'print_queue', '#print-queue') !!} {!! $mobileNavItem('Waste
                Logging', 'dashboard', null, 'waste', '#waste-logging') !!}
            </div>
            @endif
        </div>

        <!-- Footer of Drawer (Mobile Popover Actions) -->
        <div
            class="border-light-border dark:border-surface-300/40 bg-light-surface-2/50 dark:bg-surface-600/30 relative z-40 border-t p-3"
            x-data="{ mobilePopoverOpen: false }"
            @click.outside="mobilePopoverOpen = false"
        >
            <!-- Mobile Popover Menu -->
            <div
                x-show="mobilePopoverOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                class="border-light-border bg-light-surface dark:border-surface-300/40 dark:bg-surface-700 absolute right-3 bottom-[100%] left-3 z-50 mb-2 rounded-xl border p-2 shadow-lg"
                style="bottom: 100%; display: none"
            >
                <div class="space-y-1">
                    <a
                        href="{{ route('profile') }}"
                        wire:navigate
                        @click="mobileOpen = false"
                        class="text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:bg-brand-primary/10 dark:hover:text-brand-primary flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-xs font-semibold transition dark:text-gray-300"
                    >
                        <x-icon
                            name="profile"
                            class="h-4 w-4 shrink-0 text-current"
                        />
                        <span>Profile & Settings</span>
                    </a>
                    <div
                        class="border-light-border dark:border-surface-300/20 my-1 border-t"
                    ></div>
                    <button
                        wire:click="logout"
                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-xs font-semibold text-red-500 transition hover:bg-red-500/10 dark:hover:bg-red-500/20"
                    >
                        <x-icon
                            name="logout"
                            class="h-4 w-4 shrink-0 text-current"
                        />
                        <span>Log Out</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-1.5">
                <!-- User details trigger button -->
                <button
                    @click="mobilePopoverOpen = !mobilePopoverOpen"
                    class="hover:bg-brand-primary/5 dark:hover:bg-surface-400/10 flex min-w-0 flex-1 items-center gap-2.5 rounded-lg p-1 text-left transition duration-200 focus:outline-none"
                    title="{{ auth()->user()->name }} ({{ auth()->user()->role }})"
                >
                    <div
                        class="bg-brand-primary/10 text-brand-primary flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl text-sm font-bold"
                    >
                        {{ $initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <!-- prettier-ignore -->
                        <p class="text-light-text truncate text-xs font-semibold dark:text-white">
                            {{ auth()->user()->name }}
                        </p>
                        <p
                            class="text-brand-secondary text-[10px] font-bold tracking-wider uppercase"
                        >
                            {{ $role }}
                        </p>
                    </div>
                </button>

                <!-- Theme toggle action -->
                <div class="flex gap-1">
                    <!-- Theme Toggle -->
                    <button
                        @click="
                            if (document.documentElement.classList.contains('dark')) {
                                document.documentElement.classList.remove('dark');
                                document.documentElement.classList.add('light');
                                document.documentElement.setAttribute('data-theme', 'light');
                                localStorage.setItem('theme', 'light');
                            } else {
                                document.documentElement.classList.add('dark');
                                document.documentElement.classList.remove('light');
                                document.documentElement.setAttribute('data-theme', 'dark');
                                localStorage.setItem('theme', 'dark');
                            }
                        "
                        class="text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:bg-surface-400/50 flex items-center justify-center rounded-xl p-2 transition duration-200 dark:text-gray-400 dark:hover:text-white"
                        title="Toggle Dark/Light Mode"
                        aria-label="Toggle Dark/Light Mode"
                        id="mobile-theme-toggle"
                    >
                        <x-icon
                            name="sun"
                            class="hidden h-5 w-5 shrink-0 dark:block"
                        />
                        <x-icon
                            name="moon"
                            class="block h-5 w-5 shrink-0 dark:hidden"
                        />
                    </button>
                </div>
            </div>
        </div>
    </aside>
</div>
