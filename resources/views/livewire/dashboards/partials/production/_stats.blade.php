@if ($activeTab === 'dashboard')
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Stat 1: Jobs in Queue -->
    <x-production.stat-card
        label="Jobs in Queue"
        value="{{ $queueCount }} Jobs"
        iconClass="bg-brand-accent/10 text-brand-primary rounded-xl p-3"
        footerClass="text-brand-primary"
    >
        <x-slot:icon>
            <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                ></path>
            </svg>
        </x-slot:icon>
        <x-slot:footer>
            <span
                class="bg-brand-primary mr-2 h-2 w-2 animate-ping rounded-full"
            ></span>
            <span>Ready to assign to printers</span>
        </x-slot:footer>
    </x-production.stat-card>

    <!-- Stat 2: Currently Printing -->
    <x-production.stat-card
        label="Currently Printing"
        value="{{ $printingCount }} active"
        iconClass="rounded-xl bg-amber-100/50 p-3 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400"
        footerClass="text-amber-500"
    >
        <x-slot:icon>
            <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3 3L22 4"
                ></path>
            </svg>
        </x-slot:icon>
        <x-slot:footer>
            <span>Active print machine runs</span>
        </x-slot:footer>
    </x-production.stat-card>

    <!-- Stat 3: Jobs Completed Today -->
    <x-production.stat-card
        label="Jobs Completed (Today)"
        value="{{ $completedToday }} jobs"
        iconClass="rounded-xl bg-emerald-500/10 p-3 text-emerald-500 dark:text-emerald-400"
        footerClass="text-emerald-600 dark:text-emerald-400"
    >
        <x-slot:icon>
            <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
            </svg>
        </x-slot:icon>
        <x-slot:footer>
            <span>Finished today</span>
        </x-slot:footer>
    </x-production.stat-card>

    <!-- Stat 4: Failed / Waste logged -->
    <x-production.stat-card
        label="Failed / Waste logged"
        value="{{ number_format($wasteToday, 2) }} units"
        iconClass="text-red-550 dark:text-red-405 rounded-xl bg-red-500/10 p-3"
        footerClass="text-red-550 dark:text-red-405"
    >
        <x-slot:icon>
            <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                ></path>
            </svg>
        </x-slot:icon>
        <x-slot:footer>
            <span>Loss registered today</span>
        </x-slot:footer>
    </x-production.stat-card>
</div>
@endif
