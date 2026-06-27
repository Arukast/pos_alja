<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>
            {{ config('brand.fullname', 'ALJA Digital Printing') }} - Sign In
        </title>

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
    </head>
    <body
        class="dark:bg-surface-900 overflow-hidden bg-gray-50 font-sans text-gray-800 antialiased dark:text-gray-200"
    >
        <div
            class="relative flex min-h-screen flex-col items-center justify-center px-4"
        >
            <x-brand-glow-blobs mode="guest" />

            <!-- Subtle grid mesh overlay -->
            <div
                class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#1A1A2E08_1px,transparent_1px),linear-gradient(to_bottom,#1A1A2E08_1px,transparent_1px)] bg-[size:4rem_4rem]"
            ></div>

            <div class="animate-slide-in relative w-full sm:max-w-md">
                <!-- Logo / Brand Header -->
                <div class="mb-8 flex flex-col items-center text-center">
                    <x-brand-logo type="full" size="lg" class="mb-2" />
                    <p class="mt-2 text-sm font-light text-gray-500">
                        {{ config('brand.slogan') }}
                    </p>
                </div>

                <!-- Glassmorphism Container with Brand accent bar -->
                <div class="relative w-full">
                    <x-brand-stripe class="rounded-t-2xl" />

                    <div
                        class="dark:bg-surface-700/80 dark:border-surface-300/50 w-full rounded-b-2xl border border-t-0 border-gray-200 bg-white/95 px-8 py-8 shadow-xl backdrop-blur-xl dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)]"
                    >
                        {{ $slot }}
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-8 text-center">
                    <p class="text-xs text-gray-600">
                        &copy; {{ date('Y') }} {{ config('brand.footer') }}
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
