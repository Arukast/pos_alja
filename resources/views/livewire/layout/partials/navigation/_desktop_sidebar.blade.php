@props(['role', 'initials'])

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
        @include('livewire.layout.partials.navigation._role_links', ['role' =>
        $role, 'mobile' => false])
    </div>

    <!-- Sidebar Footer (User info & Popover Actions) -->
    <div
        class="border-light-border dark:border-surface-300/40 bg-light-surface-2/50 dark:bg-surface-600/30 relative z-40 border-t p-3"
        x-data="{ popoverOpen: false }"
        @click.outside="popoverOpen = false"
    >
        <!-- Popover Menu -->
        @include('livewire.layout.partials.navigation._user_popover', ['mobile'
        => false])

        <!-- Footer Details & Toggles Row -->
        <div
            class="flex items-center justify-between gap-1.5 transition-all duration-300"
            :class="sidebarOpen ? 'flex-row' : 'flex-col gap-3 py-1'"
        >
            <!-- Trigger Button holding profile details -->
            <x-navigation.profile-button
                :initials="$initials"
                :role="$role"
                :sidebarOpen="true"
                @click="popoverOpen = !popoverOpen"
                ::class="sidebarOpen ? 'flex-1' : 'w-full justify-center'"
            />

            <!-- Theme toggle action -->
            <div
                class="flex shrink-0"
                :class="sidebarOpen ? 'flex-row' : 'flex-col'"
            >
                <x-navigation.theme-toggle id="desktop-theme-toggle" />
            </div>
        </div>
    </div>
</aside>
