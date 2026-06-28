@props(['role', 'initials'])

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
        @include('livewire.layout.partials.navigation._role_links', ['role' =>
        $role, 'mobile' => true])
    </div>

    <!-- Footer of Drawer (Mobile Popover Actions) -->
    <div
        class="border-light-border dark:border-surface-300/40 bg-light-surface-2/50 dark:bg-surface-600/30 relative z-40 border-t p-3"
        x-data="{ mobilePopoverOpen: false }"
        @click.outside="mobilePopoverOpen = false"
    >
        <!-- Mobile Popover Menu -->
        @include('livewire.layout.partials.navigation._user_popover', ['mobile'
        => true])

        <div class="flex items-center justify-between gap-1.5">
            <!-- User details trigger button -->
            <x-navigation.profile-button
                :initials="$initials"
                :role="$role"
                :sidebarOpen="true"
                @click="mobilePopoverOpen = !mobilePopoverOpen"
                class="flex-1"
            />

            <!-- Theme toggle action -->
            <div class="flex gap-1">
                <x-navigation.theme-toggle id="mobile-theme-toggle" />
            </div>
        </div>
    </div>
</aside>
