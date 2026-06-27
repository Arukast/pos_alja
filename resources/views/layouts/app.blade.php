<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    style="
          --color-brand-primary: {{ config('brand.colors.primary') }};
          --color-brand-secondary: {{ config('brand.colors.secondary') }};
          --color-brand-accent: {{ config('brand.colors.accent') }};
          --color-brand-dark: {{ config('brand.colors.dark') }};
      "
>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ config('brand.fullname', 'ALJA Digital Printing') }}</title>

        <!-- Theme script to prevent flash of wrong theme (defaults to light mode) -->
        <script>
            function applyTheme() {
                if (localStorage.getItem('theme') === 'dark') {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.classList.add('light');
                    document.documentElement.setAttribute(
                        'data-theme',
                        'light'
                    );
                }
            }
            applyTheme();
            document.addEventListener('livewire:navigated', applyTheme);
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
            rel="stylesheet"
        />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    </head>
    <body
        class="bg-light-bg dark:bg-surface-800 text-light-text font-sans antialiased dark:text-gray-200"
    >
        <div
            class="bg-light-bg dark:bg-surface-800 flex h-screen w-screen overflow-hidden"
            x-data="{
                sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false',
                toggleSidebar() {
                    this.sidebarOpen = !this.sidebarOpen;
                    localStorage.setItem('sidebarOpen', this.sidebarOpen);
                },
                mobileOpen: false,
                currentHash: window.location.hash
            }"
            x-on:hashchange.window="currentHash = window.location.hash"
            x-on:livewire:navigated.window="currentHash = window.location.hash"
        >
            <x-brand-glow-blobs />

            <livewire:layout.navigation />

            <!-- Main Content Area -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <!-- Mobile topbar strip (shown only below lg) -->
                <header
                    class="border-light-border dark:border-surface-300/40 bg-light-surface dark:bg-surface-700 flex h-16 shrink-0 items-center justify-between border-b px-4 lg:hidden"
                >
                    <button
                        @click="mobileOpen = !mobileOpen"
                        class="text-light-muted hover:bg-light-surface-2 hover:text-light-text dark:hover:bg-surface-400/50 rounded-lg p-2 dark:text-gray-400 dark:hover:text-white"
                        aria-label="Toggle menu"
                        id="mobile-menu-toggle"
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
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <a
                        href="{{ route('dashboard') }}"
                        wire:navigate
                        class="flex items-center gap-2"
                    >
                        <x-brand-logo type="short" size="sm" />
                    </a>

                    <!-- Spacer -->
                    <div class="w-10"></div>
                </header>

                <!-- Page Heading -->
                @if (isset($header))
                <header
                    class="border-light-border dark:border-surface-300/40 bg-light-surface dark:bg-surface-700 shrink-0 border-b px-6 py-4 shadow-sm"
                >
                    <div class="mx-auto max-w-[1600px]">{{ $header }}</div>
                </header>
                @endif

                <!-- Page Content -->
                <main
                    class="animate-slide-in flex-1 overflow-y-auto px-4 py-8 sm:px-6 lg:px-8"
                >
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
