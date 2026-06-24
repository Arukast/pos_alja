<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ALJA Digiprint') }} - Sign In</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-surface-900 text-gray-200 overflow-hidden">
        <div class="min-h-screen relative flex flex-col justify-center items-center px-4">
            <!-- Background Glow Blobs — CMYK-inspired ambient lighting -->
            <div class="absolute top-[-15%] left-[-5%] w-[550px] h-[550px] rounded-full bg-alja-magenta opacity-[0.12] blur-[150px] pointer-events-none animate-glow-pulse"></div>
            <div class="absolute bottom-[-15%] right-[-5%] w-[500px] h-[500px] rounded-full bg-alja-cyan opacity-[0.08] blur-[150px] pointer-events-none animate-glow-pulse" style="animation-delay: 1.5s"></div>
            <div class="absolute top-[60%] left-[60%] w-[300px] h-[300px] rounded-full bg-alja-yellow opacity-[0.04] blur-[120px] pointer-events-none"></div>
            
            <!-- Subtle grid mesh overlay -->
            <div class="absolute inset-0 bg-[linear-gradient(to_right,#1A1A2E08_1px,transparent_1px),linear-gradient(to_bottom,#1A1A2E08_1px,transparent_1px)] bg-[size:4rem_4rem] pointer-events-none"></div>

            <div class="relative w-full sm:max-w-md animate-slide-in">
                <!-- Logo / Brand Header -->
                <div class="text-center mb-8">
                    <!-- CMYK Color Strip — signature element from the logo -->
                    <div class="inline-flex items-center gap-1.5 mb-5">
                        <div class="w-3 h-3 rounded-sm bg-alja-magenta"></div>
                        <div class="w-3 h-3 rounded-sm bg-alja-cyan"></div>
                        <div class="w-3 h-3 rounded-sm bg-alja-yellow"></div>
                        <div class="w-3 h-3 rounded-sm bg-surface-300"></div>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-white">
                        ALJA <span class="text-alja-gradient">Digital Printing</span>
                    </h1>
                    <p class="text-gray-500 mt-2 text-sm font-light">Enter credentials to access your workspace</p>
                </div>

                <!-- Glassmorphism Container with CMYK bar accent -->
                <div class="w-full relative">
                    <!-- Top CMYK accent bar -->
                    <div class="cmyk-bar w-full rounded-t-2xl"></div>
                    
                    <div class="w-full bg-surface-700/80 backdrop-blur-xl border border-surface-300/50 border-t-0 px-8 py-8 rounded-b-2xl shadow-[0_20px_50px_rgba(0,0,0,0.5)]">
                        {{ $slot }}
                    </div>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8">
                    <p class="text-xs text-gray-600">&copy; {{ date('Y') }} ALJA Digital Printing. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
</html>
