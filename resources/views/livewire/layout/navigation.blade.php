<nav
    x-data="{ open: false }"
    class="dark:bg-surface-700/90 dark:border-surface-300/40 relative z-50 border-b border-gray-200 bg-white/90 backdrop-blur-xl"
>
    <!-- CMYK signature stripe at the very top -->
    <div class="cmyk-bar absolute top-0 left-0 w-full"></div>

    <!-- Primary Navigation Menu -->
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mt-[3px] flex h-16 justify-between">
            <div class="flex">
                <!-- Logo -->
                <div class="flex shrink-0 items-center">
                    <a
                        href="{{ route('dashboard') }}"
                        wire:navigate
                        class="group flex items-center gap-2.5"
                    >
                        <!-- CMYK dots -->
                        <div class="flex gap-1">
                            <div
                                class="bg-alja-magenta h-2 w-2 rounded-sm transition-transform duration-200 group-hover:scale-110"
                            ></div>
                            <div
                                class="bg-alja-cyan h-2 w-2 rounded-sm transition-transform duration-200 group-hover:scale-110"
                                style="transition-delay: 50ms"
                            ></div>
                            <div
                                class="bg-alja-yellow h-2 w-2 rounded-sm transition-transform duration-200 group-hover:scale-110"
                                style="transition-delay: 100ms"
                            ></div>
                        </div>
                        <span
                            class="text-sm font-bold tracking-wide text-gray-800 dark:text-white"
                            >ALJA<span class="text-alja-magenta ml-0.5"
                                >POS</span
                            ></span
                        >
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link
                        :href="route('dashboard')"
                        :active="request()->routeIs('dashboard')"
                        wire:navigate
                    >
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown & Theme Toggle -->
            <div class="hidden gap-3 sm:ms-6 sm:flex sm:items-center">
                <!-- Theme Toggle Button -->
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
                    class="dark:hover:bg-surface-400/50 rounded-lg p-2 text-gray-500 transition duration-200 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white"
                    title="Toggle theme"
                >
                    <!-- Sun icon (visible in dark mode) -->
                    <svg
                        class="hidden h-5 w-5 dark:block"
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
                    <!-- Moon icon (visible in light mode) -->
                    <svg
                        class="block h-5 w-5 dark:hidden"
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

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center rounded-lg border border-transparent bg-transparent px-3 py-2 text-sm leading-4 font-medium text-gray-500 transition duration-200 ease-in-out hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:text-white"
                        >
                            <div
                                x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                                x-text="name"
                                x-on:profile-updated.window="name = $event.detail.name"
                            ></div>

                            <div class="ms-1">
                                <svg
                                    class="h-4 w-4 fill-current"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <button wire:click="logout" class="w-full text-start">
                            <x-dropdown-link>
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </button>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button
                    @click="open = ! open"
                    class="dark:hover:bg-surface-400/50 inline-flex items-center justify-center rounded-lg p-2 text-gray-500 transition duration-200 ease-in-out hover:bg-gray-100 hover:text-gray-700 focus:outline-none dark:hover:text-white"
                >
                    <svg
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <path
                            :class="{'hidden': open, 'inline-flex': ! open }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                        <path
                            :class="{'hidden': ! open, 'inline-flex': open }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div
        :class="{'block': open, 'hidden': ! open}"
        class="dark:bg-surface-700/95 dark:border-surface-300/30 hidden border-t border-gray-200 bg-white/95 backdrop-blur-xl sm:hidden"
    >
        <div class="space-y-1 pt-2 pb-3">
            <x-responsive-nav-link
                :href="route('dashboard')"
                :active="request()->routeIs('dashboard')"
                wire:navigate
            >
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-surface-300/30 border-t pt-4 pb-1">
            <div class="px-4">
                <div
                    class="text-base font-medium text-gray-800 dark:text-white"
                    x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                    x-text="name"
                    x-on:profile-updated.window="name = $event.detail.name"
                ></div>
                <div class="text-sm font-medium text-gray-500">
                    {{ auth()->user()->email }}
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Theme Toggle for Mobile -->
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
                    class="dark:hover:bg-surface-400/30 flex w-full items-center border-l-4 border-transparent px-4 py-2 text-start text-base font-medium text-gray-600 transition duration-200 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                >
                    <span class="dark:hidden">Switch to Dark Mode</span>
                    <span class="hidden dark:inline">Switch to Light Mode</span>
                </button>
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link>
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>
