<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Sign In</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body {
                font-family: 'Outfit', sans-serif;
            }
        </style>
    </head>
    <body class="antialiased bg-[#0B0F19] text-slate-200 overflow-hidden">
        <div class="min-h-screen relative flex flex-col justify-center items-center px-4">
            <!-- Background Glow Blobs -->
            <div class="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] rounded-full bg-gradient-to-br from-violet-600 to-indigo-600 opacity-20 blur-[150px] pointer-events-none"></div>
            <div class="absolute bottom-[-20%] right-[-10%] w-[600px] h-[600px] rounded-full bg-gradient-to-tl from-pink-600 to-rose-600 opacity-15 blur-[150px] pointer-events-none"></div>
            
            <!-- Abstract background mesh/grid -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#1f293710_1px,transparent_1px),linear-gradient(to_bottom,#1f293710_1px,transparent_1px)] bg-[size:4rem_4rem] pointer-events-none"></div>

            <div class="relative w-full sm:max-w-md">
                <!-- Sleek Logo / Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-violet-600 to-indigo-600 shadow-lg shadow-indigo-500/30 mb-4 transition-transform hover:scale-105 duration-300">
                        <!-- Custom Print Icon -->
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">
                        ALJA <span class="bg-gradient-to-r from-violet-400 to-pink-500 bg-clip-text text-transparent">Digiprint</span>
                    </h1>
                    <p class="text-slate-400 mt-2 text-sm font-light">Enter credentials to access your workspace</p>
                </div>

                <!-- Glassmorphism Container -->
                <div class="w-full bg-[#131825]/80 backdrop-blur-xl border border-slate-800/80 px-8 py-8 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.5),0_0_50px_-12px_rgba(99,102,241,0.2)]">
                    {{ $slot }}
                </div>

                <!-- Footer -->
                <div class="text-center mt-8">
                    <p class="text-xs text-slate-500">&copy; {{ date('Y') }} ALJA Digiprint. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
</html>
