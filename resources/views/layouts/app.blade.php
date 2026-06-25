<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />

        <title>{{ config('brand.fullname', 'ALJA Digital Printing') }}</title>

        <!-- Theme script to prevent flash of wrong theme -->
        <script>
            if (
                localStorage.getItem('theme') === 'light' ||
                (!('theme' in localStorage) &&
                    window.matchMedia('(prefers-color-scheme: light)').matches)
            ) {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
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
        class="dark:bg-surface-800 bg-gray-50 font-sans text-gray-800 antialiased dark:text-gray-200"
    >
        <div class="relative min-h-screen">
            <x-brand-glow-blobs />

            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
            <header
                class="dark:bg-surface-700/80 dark:border-surface-300/50 border-b border-gray-200 bg-white/80 backdrop-blur-md"
            >
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
            @endif

            <!-- Page Content -->
            <main class="animate-fade-in">{{ $slot }}</main>
        </div>
    </body>
</html>
