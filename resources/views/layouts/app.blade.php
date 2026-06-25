<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ALJA Digiprint') }}</title>

        <!-- Theme script to prevent flash of wrong theme -->
        <script>
            if (localStorage.getItem('theme') === 'light' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {
                document.documentElement.classList.remove('dark')
            } else {
                document.documentElement.classList.add('dark')
            }
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 text-gray-800 dark:bg-surface-800 dark:text-gray-200">
        <div class="min-h-screen relative">
            <!-- Subtle ambient glow blobs for premium feel -->
            <div class="fixed top-0 left-0 w-[500px] h-[500px] rounded-full bg-alja-magenta opacity-0 dark:opacity-[0.03] blur-[120px] pointer-events-none"></div>
            <div class="fixed bottom-0 right-0 w-[400px] h-[400px] rounded-full bg-alja-cyan opacity-0 dark:opacity-[0.03] blur-[120px] pointer-events-none"></div>

            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white/80 dark:bg-surface-700/80 backdrop-blur-md border-b border-gray-200 dark:border-surface-300/50">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="animate-fade-in">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
