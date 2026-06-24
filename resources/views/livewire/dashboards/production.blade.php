<?php

use Livewire\Volt\Component;

new class extends Component {
    public function mount()
    {
        // Production dashboard initializations
    }
}; ?>

<div class="space-y-6">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-orange-500 to-amber-600 p-6 shadow-lg dark:from-orange-600 dark:to-amber-700">
        <div class="relative z-10 text-white">
            <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/20 rounded-full backdrop-blur-sm">
                Print Shop Production Line
            </span>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Active Print Queue</h1>
            <p class="mt-1 text-orange-100 max-w-xl text-sm">
                Track print jobs dispatched from designers, log printer machine usage, report failed prints (wasted stock), and execute completed work tickets.
            </p>
        </div>
        <!-- Decorative blur blob -->
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
    </div>

    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat 1 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Jobs in Queue</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">8 jobs</h3>
                </div>
                <div class="rounded-lg bg-orange-50 dark:bg-orange-900/20 p-3 text-orange-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-orange-500 font-semibold">
                <span class="h-2 w-2 rounded-full bg-orange-500 animate-ping mr-2"></span>
                <span>Ready to assign to printers</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Currently Printing</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">3 machines</h3>
                </div>
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3 text-amber-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3 3L22 4"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-amber-500">
                <span>Active print runs</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Jobs Completed</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">34</h3>
                </div>
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-emerald-500 font-semibold">
                <span>Finished today</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Failed Prints (Wasted)</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">1.6 meters</h3>
                </div>
                <div class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 text-red-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-red-500">
                <span>Flexy 280gr loss today</span>
            </div>
        </div>
    </div>

    <!-- Active print queue board -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Live print queue board -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">Active Print Tickets</h3>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Work orders prioritized by job deadline.</p>
                </div>
                <span class="rounded-full bg-orange-50 dark:bg-orange-950/30 px-3 py-1 text-[10px] font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider">Queue: 8 pending</span>
            </div>

            <div class="mt-4 space-y-3">
                <!-- Ticket 1 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between rounded-lg border border-gray-100 dark:border-gray-700/30 p-4 gap-3 bg-gray-50/20 dark:bg-gray-900/10 hover:shadow-sm transition-all">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">#47453-1</span>
                            <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 uppercase tracking-wider">Outdoor</span>
                        </div>
                        <h4 class="mt-1 text-sm font-bold text-gray-900 dark:text-white">x-banner_soft_opening.pdf</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Flexy 340gr (60 x 160 cm) &bull; Qty: 5 pcs</p>
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-center">
                        <button class="rounded border border-orange-500 text-orange-500 hover:bg-orange-50 px-3 py-1.5 text-xs font-bold transition">Assign printer</button>
                        <button class="rounded bg-emerald-500 hover:bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition">Mark Done</button>
                    </div>
                </div>

                <!-- Ticket 2 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between rounded-lg border border-gray-100 dark:border-gray-700/30 p-4 gap-3 bg-gray-50/20 dark:bg-gray-900/10 hover:shadow-sm transition-all">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">#47454-1</span>
                            <span class="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">Kertas</span>
                        </div>
                        <h4 class="mt-1 text-sm font-bold text-gray-900 dark:text-white">sticker_kemasan_bakso.png</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Chromo Sticker (A3+) &bull; Qty: 100 sheets</p>
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-center">
                        <span class="inline-flex items-center text-xs text-amber-500 font-semibold mr-2">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-ping mr-1.5"></span>
                            Printing on Konica 1085
                        </span>
                        <button class="rounded bg-emerald-500 hover:bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition">Mark Done</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log failed prints / quick actions -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Log Material Waste</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Report a print layout failure to decrement stock ledger.</p>

            <div class="mt-4 space-y-3">
                <button class="flex w-full items-center justify-between rounded-lg bg-red-500 p-3 text-left text-sm font-bold text-white hover:bg-red-600 transition shadow-sm">
                    <div class="flex items-center">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Log Failed Print</span>
                    </div>
                    <span>&rarr;</span>
                </button>
            </div>
        </div>
    </div>
</div>
