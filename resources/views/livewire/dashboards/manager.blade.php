<?php

use Livewire\Volt\Component;

new class extends Component {
    public function mount()
    {
        // BM dashboard initializations
    }
}; ?>

<div class="space-y-6">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 p-6 shadow-lg dark:from-emerald-600 dark:to-teal-700">
        <div class="relative z-10 text-white">
            <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/20 rounded-full backdrop-blur-sm">
                Branch Manager Portal
            </span>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Main Branch Overview</h1>
            <p class="mt-1 text-emerald-100 max-w-xl text-sm">
                Manage your shop inventory, adjust pricing engine parameters, oversee printing machinery, and coordinate staff.
            </p>
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
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Stock Materials</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">395</h3>
                </div>
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-emerald-500">
                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Active stock tracking enabled</span>
            </div>
        </div>

        <!-- Stat card 2 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Printers & Machinery</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">18</h3>
                </div>
                <div class="rounded-lg bg-teal-50 dark:bg-teal-900/20 p-3 text-teal-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-teal-500">
                <span class="h-2 w-2 rounded-full bg-teal-500 animate-ping mr-2"></span>
                <span>All machines online</span>
            </div>
        </div>

        <!-- Stat card 3 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Active Staff</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">20</h3>
                </div>
                <div class="rounded-lg bg-cyan-50 dark:bg-cyan-900/20 p-3 text-cyan-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-cyan-500">
                <span>Managed across 5 roles</span>
            </div>
        </div>

        <!-- Stat card 4 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Pricing Engine Rows</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">869</h3>
                </div>
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 p-3 text-indigo-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-indigo-500">
                <span>Active price rule matrix</span>
            </div>
        </div>
    </div>

    <!-- Management actions and recent stock ledger -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Quick actions -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm">
            <h3 class="text-lg font-bold text-gray-950 dark:text-white">Administrative Actions</h3>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Quick shortcuts to manage operational data.</p>
            
            <div class="mt-4 space-y-3">
                <button class="flex w-full items-center justify-between rounded-lg border border-gray-100 dark:border-gray-700/30 p-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all">
                    <div class="flex items-center">
                        <span class="mr-3 text-emerald-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        </span>
                        <span>Manage Materials & Stock</span>
                    </div>
                    <span class="text-gray-400 dark:text-gray-600">&rarr;</span>
                </button>

                <button class="flex w-full items-center justify-between rounded-lg border border-gray-100 dark:border-gray-700/30 p-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all">
                    <div class="flex items-center">
                        <span class="mr-3 text-teal-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"></path></svg>
                        </span>
                        <span>Adjust Pricing Matrix</span>
                    </div>
                    <span class="text-gray-400 dark:text-gray-600">&rarr;</span>
                </button>

                <button class="flex w-full items-center justify-between rounded-lg border border-gray-100 dark:border-gray-700/30 p-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all">
                    <div class="flex items-center">
                        <span class="mr-3 text-cyan-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </span>
                        <span>Manage Employees (Users)</span>
                    </div>
                    <span class="text-gray-400 dark:text-gray-600">&rarr;</span>
                </button>

                <button class="flex w-full items-center justify-between rounded-lg border border-gray-100 dark:border-gray-700/30 p-3 text-left text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 transition-all">
                    <div class="flex items-center">
                        <span class="mr-3 text-indigo-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>
                        </span>
                        <span>Configure Printer Machines</span>
                    </div>
                    <span class="text-gray-400 dark:text-gray-600">&rarr;</span>
                </button>
            </div>
        </div>

        <!-- Recent Stock ledger -->
        <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">Recent Inventory Log</h3>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Stock updates from printing jobs and restock entries.</p>
                </div>
                <button class="text-xs font-semibold text-emerald-500 hover:underline">View All &rarr;</button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Material</th>
                            <th class="py-2.5 px-3">Added</th>
                            <th class="py-2.5 px-3">Used</th>
                            <th class="py-2.5 px-3">Failed</th>
                            <th class="py-2.5 px-3">Remaining</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                            <td class="py-3 px-3">2026-06-24</td>
                            <td class="py-3 px-3 font-medium text-gray-900 dark:text-white">Flexy 280gr (Outdoor)</td>
                            <td class="py-3 px-3 text-green-500">+100.0 m</td>
                            <td class="py-3 px-3">12.5 m</td>
                            <td class="py-3 px-3 text-red-500">1.0 m</td>
                            <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">86.5 m</td>
                        </tr>
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                            <td class="py-3 px-3">2026-06-24</td>
                            <td class="py-3 px-3 font-medium text-gray-900 dark:text-white">Art Carton A3 (Kertas)</td>
                            <td class="py-3 px-3 text-gray-400">0.0 pcs</td>
                            <td class="py-3 px-3">25.0 pcs</td>
                            <td class="py-3 px-3 text-red-500">3.0 pcs</td>
                            <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">412.0 pcs</td>
                        </tr>
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                            <td class="py-3 px-3">2026-06-24</td>
                            <td class="py-3 px-3 font-medium text-gray-900 dark:text-white">DTF Film Sheet</td>
                            <td class="py-3 px-3 text-green-500">+50.0 pcs</td>
                            <td class="py-3 px-3">8.0 pcs</td>
                            <td class="py-3 px-3">0.0 pcs</td>
                            <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">42.0 pcs</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
