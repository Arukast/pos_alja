<?php

use Livewire\Volt\Component;

new class extends Component {
    public function mount()
    {
        // Designer dashboard initializations
    }
}; ?>

<div class="space-y-6">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-500 to-emerald-600 p-6 shadow-lg dark:from-teal-600 dark:to-emerald-700">
        <div class="relative z-10 text-white">
            <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/20 rounded-full backdrop-blur-sm">
                Customer Intake Desk
            </span>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Design & Quotes Board</h1>
            <p class="mt-1 text-teal-100 max-w-xl text-sm">
                Calculate instant print quotes, upload design files, generate Work Orders (WO), and queue jobs directly to the print machines.
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
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Quotes Generated</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">45</h3>
                </div>
                <div class="rounded-lg bg-teal-50 dark:bg-teal-900/20 p-3 text-teal-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-teal-500">
                <span>Calculated quotes today</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Active Uploads</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">3 jobs</h3>
                </div>
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-emerald-500">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping mr-2"></span>
                <span>Uploading design layouts...</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Sent to Print</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">18</h3>
                </div>
                <div class="rounded-lg bg-cyan-50 dark:bg-cyan-900/20 p-3 text-cyan-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-cyan-500 font-semibold">
                <span>Dispatched to production queue</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Awaiting Assets</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">2</h3>
                </div>
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 p-3 text-indigo-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-indigo-500">
                <span>Design proof pending review</span>
            </div>
        </div>
    </div>

    <!-- Active orders placement & calculator -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- New quote quick link -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Intake Quick Actions</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Launch actions for customer orders.</p>

            <div class="mt-6 space-y-3">
                <button class="flex w-full items-center justify-between rounded-lg bg-teal-500 p-3 text-left text-sm font-bold text-white hover:bg-teal-600 transition shadow-sm">
                    <div class="flex items-center">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>New Quote & Order</span>
                    </div>
                    <span>&rarr;</span>
                </button>

                <button class="flex w-full items-center justify-between rounded-lg border border-gray-100 dark:border-gray-700/30 p-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-teal-50/50 dark:hover:bg-teal-950/20 transition-all">
                    <div class="flex items-center">
                        <svg class="mr-3 h-5 w-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Upload Design Layout</span>
                    </div>
                    <span>&rarr;</span>
                </button>
            </div>
        </div>

        <!-- Designer task list / queued items -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Recent Intake Layouts</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Design specs logged for production lines.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-2.5 px-3">File Name</th>
                            <th class="py-2.5 px-3">Specs</th>
                            <th class="py-2.5 px-3">Qty</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Deadline</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                            <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">x-banner_soft_opening.pdf</td>
                            <td class="py-3 px-3">60 x 160 cm (Flexy 340gr)</td>
                            <td class="py-3 px-3">5 pcs</td>
                            <td class="py-3 px-3">
                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-cyan-50 dark:bg-cyan-950/30 text-cyan-600 dark:text-cyan-400 uppercase tracking-wider">
                                    Awaiting Print
                                </span>
                            </td>
                            <td class="py-3 px-3 text-red-500 font-semibold">15:00 Today</td>
                        </tr>
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                            <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">sticker_kemasan_bakso.png</td>
                            <td class="py-3 px-3">A3+ (Chromo Sticker)</td>
                            <td class="py-3 px-3">100 sheets</td>
                            <td class="py-3 px-3">
                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                                    Designing
                                </span>
                            </td>
                            <td class="py-3 px-3">Tomorrow</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
