<?php

use Livewire\Volt\Component;

new class extends Component {
    public function mount()
    {
        // Cashier dashboard initializations
    }
}; ?>

<div class="space-y-6">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-500 to-blue-600 p-6 shadow-lg dark:from-cyan-600 dark:to-blue-700">
        <div class="relative z-10 text-white">
            <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/20 rounded-full backdrop-blur-sm">
                Cash Register Desk
            </span>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Active Cashier Desk</h1>
            <p class="mt-1 text-cyan-100 max-w-xl text-sm">
                Process customer payments, register down payments, settle outstanding invoices, and log cash drawer withdrawals.
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
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Awaiting Checkout</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">12 invoices</h3>
                </div>
                <div class="rounded-lg bg-cyan-50 dark:bg-cyan-900/20 p-3 text-cyan-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-cyan-500 font-semibold">
                <span class="h-2 w-2 rounded-full bg-cyan-500 animate-ping mr-2"></span>
                <span>Unpaid jobs queue ready</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">DP Collected Today</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. 8,450,000</h3>
                </div>
                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3 text-blue-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-blue-500">
                <span>Down payment deposits</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Settlements (Pelunasan)</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. 15,200,000</h3>
                </div>
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-emerald-500">
                <span>Completed orders pickup cash</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div class="relative overflow-hidden rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-5 shadow-sm transition hover:shadow-md backdrop-blur-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">Net Drawer Total</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. 23,650,000</h3>
                </div>
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 p-3 text-indigo-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"></path></svg>
                </div>
            </div>
            <div class="mt-4 flex items-center text-xs text-indigo-500">
                <span>Safe cache limit warning safe</span>
            </div>
        </div>
    </div>

    <!-- Active bills list -->
    <div class="rounded-xl border border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800/80 p-6 shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">Active Billing Queue</h3>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Invoices ready for Down Payment (DP) or final settlement.</p>
            </div>
            <!-- Search field -->
            <div class="w-full sm:w-64">
                <input type="text" placeholder="Search customer / invoice..." class="w-full rounded-lg border border-gray-100 dark:border-gray-700/30 bg-gray-50/50 dark:bg-gray-900/50 px-3 py-2 text-xs text-gray-800 dark:text-gray-200 focus:border-cyan-500 focus:ring-cyan-500 focus:outline-none transition-all" />
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-2.5 px-3">Invoice ID</th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3">Total Cost</th>
                        <th class="py-2.5 px-3">DP Amount</th>
                        <th class="py-2.5 px-3">Remaining AR</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
                    <!-- Queue Row 1 -->
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                        <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">#47453</td>
                        <td class="py-3 px-3">
                            <p class="font-bold text-gray-800 dark:text-gray-200">Mahmudin</p>
                            <p class="text-[10px] text-gray-400">0878-xxxx-xxxx</p>
                        </td>
                        <td class="py-3 px-3">
                            <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-yellow-50 dark:bg-yellow-950/30 text-yellow-600 dark:text-yellow-400 uppercase tracking-wider">
                                Down Payment
                            </span>
                        </td>
                        <td class="py-3 px-3">Rp. 350,000</td>
                        <td class="py-3 px-3 text-green-500">Rp. 150,000</td>
                        <td class="py-3 px-3 font-bold text-red-500">Rp. 200,000</td>
                        <td class="py-3 px-3 text-right">
                            <button class="rounded bg-emerald-500 hover:bg-emerald-600 px-3 py-1 text-[11px] font-bold text-white shadow-sm transition">Settle Bill</button>
                        </td>
                    </tr>

                    <!-- Queue Row 2 -->
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-900/20 transition-all">
                        <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">#47454</td>
                        <td class="py-3 px-3">
                            <p class="font-bold text-gray-800 dark:text-gray-200">Ahmad Jajuli</p>
                            <p class="text-[10px] text-gray-400">0812-xxxx-xxxx</p>
                        </td>
                        <td class="py-3 px-3">
                            <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 uppercase tracking-wider">
                                Unpaid
                            </span>
                        </td>
                        <td class="py-3 px-3">Rp. 75,000</td>
                        <td class="py-3 px-3 text-gray-400">Rp. 0</td>
                        <td class="py-3 px-3 font-bold text-red-500">Rp. 75,000</td>
                        <td class="py-3 px-3 text-right">
                            <button class="rounded bg-cyan-500 hover:bg-cyan-600 px-3 py-1 text-[11px] font-bold text-white shadow-sm transition mr-1">Register DP</button>
                            <button class="rounded bg-emerald-500 hover:bg-emerald-600 px-3 py-1 text-[11px] font-bold text-white shadow-sm transition">Cash Checkout</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
