<?php

use Livewire\Volt\Component;

new class extends Component {
    protected $listeners = [
        'echo:orders,OrderUpdated' => '$refresh',
    ];

    public string $period = 'Monthly';

    public function mount()
    {
        // Owner dashboard initializations
    }
}; ?>

<div class="space-y-6">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 to-indigo-700 p-6 shadow-lg dark:from-violet-700 dark:to-indigo-800">
        <div class="relative z-10 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/20 rounded-full backdrop-blur-sm">
                    Executive Suite
                </span>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Financial & Performance Dashboard</h1>
                <p class="mt-1 text-violet-100 max-w-xl text-sm">
                    Overview of global print shop profits, expenses, cash drawer flows, and individual designer/cashier metrics.
                </p>
            </div>
            <!-- Period selectors -->
            <div class="flex items-center gap-1.5 self-start bg-white/10 p-1 rounded-lg backdrop-blur-sm text-xs font-semibold">
                <button wire:click="$set('period', 'Daily')" class="px-3 py-1.5 rounded-md transition-all {{ $period === 'Daily' ? 'bg-white text-indigo-900 shadow-sm' : 'hover:bg-white/10' }}">Daily</button>
                <button wire:click="$set('period', 'Weekly')" class="px-3 py-1.5 rounded-md transition-all {{ $period === 'Weekly' ? 'bg-white text-indigo-900 shadow-sm' : 'hover:bg-white/10' }}">Weekly</button>
                <button wire:click="$set('period', 'Monthly')" class="px-3 py-1.5 rounded-md transition-all {{ $period === 'Monthly' ? 'bg-white text-indigo-900 shadow-sm' : 'hover:bg-white/10' }}">Monthly</button>
            </div>
        </div>
        <!-- Decorative blur blob -->
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
    </div>

    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat card 1 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Gross Revenue (Omset)</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. 42,750,000</h3>
                </div>
                <div class="rounded-lg bg-violet-50 dark:bg-violet-900/20 p-3 text-violet-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-green-500">
                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <span>+12.4% vs last period</span>
            </div>
        </div>

        <!-- Stat card 2 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Base Cost (HPP)</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. 18,340,000</h3>
                </div>
                <div class="rounded-lg bg-orange-50 dark:bg-orange-900/20 p-3 text-orange-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-orange-500">
                <span>Material & consumable usage</span>
            </div>
        </div>

        <!-- Stat card 3 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Net Profit (Laba)</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. 22,274,000</h3>
                </div>
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-emerald-500 font-semibold">
                <span>Margin Rate: 52.1%</span>
            </div>
        </div>

        <!-- Stat card 4 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Expenses (Pengeluaran)</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. 2,136,000</h3>
                </div>
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-red-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-red-500">
                <span>Operasional & Konsumsi</span>
            </div>
        </div>
    </div>

    <!-- Analytical graphs & staff leaderboard -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Sales performance Sparkline graph -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">Profit Analysis Sparkline</h3>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Weekly net margins trend visualization.</p>
                </div>
                <span class="rounded-full bg-indigo-50 dark:bg-indigo-950/30 px-3 py-1 text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Live margins</span>
            </div>

            <!-- Premium SVG Chart placeholder -->
            <div class="mt-6 h-40 w-full relative">
                <svg class="w-full h-full overflow-visible" viewBox="0 0 300 100" preserveAspectRatio="none">
                    <!-- Grid Lines -->
                    <line x1="0" y1="20" x2="300" y2="20" stroke="rgba(156, 163, 175, 0.07)" stroke-dasharray="4" />
                    <line x1="0" y1="50" x2="300" y2="50" stroke="rgba(156, 163, 175, 0.07)" stroke-dasharray="4" />
                    <line x1="0" y1="80" x2="300" y2="80" stroke="rgba(156, 163, 175, 0.07)" stroke-dasharray="4" />
                    
                    <!-- Gradient under graph -->
                    <defs>
                        <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="rgb(99, 102, 241)" stop-opacity="0.25" />
                            <stop offset="100%" stop-color="rgb(99, 102, 241)" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>
                    <path d="M 0 80 Q 50 40 100 65 T 200 20 T 300 35 L 300 100 L 0 100 Z" fill="url(#chartGradient)" />

                    <!-- Graph line -->
                    <path d="M 0 80 Q 50 40 100 65 T 200 20 T 300 35" fill="none" stroke="rgb(99, 102, 241)" stroke-width="2.5" stroke-linecap="round" />
                    
                    <!-- Pulsing hover point -->
                    <circle cx="200" cy="20" r="4.5" fill="rgb(99, 102, 241)" />
                    <circle cx="200" cy="20" r="9" fill="rgb(99, 102, 241)" fill-opacity="0.2" class="animate-ping" />
                </svg>
            </div>
            
            <div class="mt-4 flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500 font-semibold">
                <span>Week 1</span>
                <span>Week 2</span>
                <span>Week 3</span>
                <span>Week 4 (Current)</span>
            </div>
        </div>

        <!-- Leaderboard / Staff performance -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Designer Leaderboard</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Intake metrics and order totals by CS designer.</p>

            <div class="mt-4 space-y-4">
                <!-- Leader 1 -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-violet-100 dark:bg-violet-900/30 text-violet-600 flex items-center justify-center font-bold text-xs">
                            AJ
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-bold text-gray-900 dark:text-white">Ahmad Jajuli</p>
                            <p class="text-[10px] text-gray-400">CS & Design Specialist</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-900 dark:text-white">412 orders</p>
                        <p class="text-[10px] text-emerald-500">Rp. 18.5M val</p>
                    </div>
                </div>

                <!-- Leader 2 -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center font-bold text-xs">
                            MM
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-bold text-gray-900 dark:text-white">Mahmudin</p>
                            <p class="text-[10px] text-gray-400">Senior Designer</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-900 dark:text-white">356 orders</p>
                        <p class="text-[10px] text-emerald-500">Rp. 14.2M val</p>
                    </div>
                </div>

                <!-- Leader 3 -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-teal-100 dark:bg-teal-900/30 text-teal-600 flex items-center justify-center font-bold text-xs">
                            JP
                        </div>
                        <div class="ml-3">
                            <p class="text-xs font-bold text-gray-900 dark:text-white">Jumadi Permana</p>
                            <p class="text-[10px] text-gray-400">CS Representative</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-gray-900 dark:text-white">291 orders</p>
                        <p class="text-[10px] text-emerald-500">Rp. 9.8M val</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
