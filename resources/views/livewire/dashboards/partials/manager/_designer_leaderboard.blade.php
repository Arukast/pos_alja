<!-- Designer Performance Leaderboard Tab -->
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <!-- Card 1: Top Performer (Omset) -->
    <x-manager.metric-card
        title="Top Performer (Omset)"
        value="{{ $topRevenue->name ?? 'N/A' }}"
    >
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
                d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2zm0 0h4m-4 0H8m12-1a3 3 0 01-3 3h-1m-4-3a3 3 0 00-3 3H7m0 0a3 3 0 01-3-3V7a3 3 0 013-3h1m4 3a3 3 0 003-3h1"
            />
        </svg>
        <x-slot name="footer">
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-emerald-600 dark:text-emerald-400"
            >
                <span
                    >Rp. {{ $topRevenue ?
                    number_format($topRevenue->total_value, 0, ',', '.') : '0'
                    }}</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                >
                    {{ $topRevenue ? $topRevenue->orders_count : 0 }} orders
                </span>
            </div>
        </x-slot>
    </x-manager.metric-card>

    <!-- Card 2: Order Champion -->
    <x-manager.metric-card
        title="Order Champion"
        value="{{ $orderChampion->name ?? 'N/A' }}"
    >
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
                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.246.588 1.81l-3.97 2.883a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.883a1 1 0 00-1.175 0l-3.97 2.883c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.364-1.118l-3.97-2.883c-.773-.564-.375-1.81.587-1.81h4.907a1 1 0 00.95-.69l1.519-4.674z"
            />
        </svg>
        <x-slot name="footer">
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-blue-600 dark:text-blue-400"
            >
                <span
                    >{{ $orderChampion ? $orderChampion->orders_count : 0 }}
                    Orders</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                >
                    Rp. {{ $orderChampion ?
                    number_format($orderChampion->total_value, 0, ',', '.') :
                    '0' }}
                </span>
            </div>
        </x-slot>
    </x-manager.metric-card>

    <!-- Card 3: Highest AOV -->
    <x-manager.metric-card
        title="Highest Avg Ticket (AOV)"
        value="{{ $topAov->name ?? 'N/A' }}"
    >
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
                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
            />
        </svg>
        <x-slot name="footer">
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-indigo-600 dark:text-indigo-400"
            >
                <span
                    >Rp. {{ $topAov ? number_format($topAov->aov, 0, ',', '.') :
                    '0' }}</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                    >Average AOV</span
                >
            </div>
        </x-slot>
    </x-manager.metric-card>

    <!-- Card 4: Accuracy Leader -->
    <x-manager.metric-card
        title="Accuracy Leader"
        value="{{ $accuracyLeader->name ?? 'N/A' }}"
    >
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
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
            />
        </svg>
        <x-slot name="footer">
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-emerald-600 dark:text-emerald-400"
            >
                <span
                    >{{ $accuracyLeader ? $accuracyLeader->waste_count : 0 }}
                    wasted log(s)</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                    >Lowest design errors</span
                >
            </div>
        </x-slot>
    </x-manager.metric-card>
</div>

<!-- Main leaderboard grid content -->
@island(name: 'designer-rankings')
<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Detailed rankings table -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            Designer Rankings
        </h3>
        <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
            Detailed performance metrics sorted by gross revenue generated.
        </p>
        <div class="mt-6 overflow-x-auto">
            <table class="w-full border-collapse text-left text-xs">
                <thead>
                    <tr
                        class="border-light-border dark:border-surface-300/20 text-light-muted border-b text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        <th class="pb-3 pl-4">Rank</th>
                        <th class="pb-3">Designer</th>
                        <th class="pb-3">Branch</th>
                        <th class="pb-3 text-right">Orders</th>
                        <th class="pb-3 text-right">Total Revenue</th>
                        <th class="pb-3 text-right">Avg Order Value</th>
                        <th class="pr-4 pb-3 text-right">Design Waste</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/10 divide-y"
                >
                    @forelse($leaders as $index => $leader)
                    <!-- prettier-ignore -->
                    @php $rankColors = [ 0 => 'bg-amber-500 text-white', 1 => 'bg-slate-400 text-white', 2 => 'bg-amber-700 text-white', ]; $rankBadge = $rankColors[$index] ?? 'bg-gray-200 dark:bg-surface-300/30 text-light-text dark:text-white'; @endphp
                    <!-- prettier-ignore -->
                    <tr class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150">
                        <td class="py-4 pl-4">
                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold {{ $rankBadge }}">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td class="py-4">
                            <div class="text-light-text font-bold dark:text-white">
                                {{ $leader->name }}
                            </div>
                            <div class="text-light-muted text-[10px] dark:text-gray-400">
                                CS & Design Specialist
                            </div>
                        </td>
                        <td class="text-light-muted py-4 font-medium dark:text-gray-400">
                            {{ $leader->branch_name ?? 'Global' }}
                        </td>
                        <td class="text-light-text py-4 text-right font-semibold dark:text-white">
                            {{ $leader->orders_count }}
                        </td>
                        <td class="py-4 text-right font-semibold text-emerald-600 dark:text-emerald-400">
                            Rp. {{ number_format($leader->total_value, 0, ',', '.') }}
                        </td>
                        <td class="py-4 text-right font-semibold text-indigo-600 dark:text-indigo-400">
                            Rp. {{ number_format($leader->aov, 0, ',', '.') }}
                        </td>
                        <td class="py-4 pr-4 text-right">
                            <!-- prettier-ignore -->
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $leader->waste_count > 0 ? 'bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' }}">
                                {{ $leader->waste_count }} error(s)
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="7"
                            class="text-light-muted py-8 text-center text-xs dark:text-gray-500"
                        >
                            No designer metrics recorded for this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Revenue contribution chart -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            Revenue Share
        </h3>
        <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
            Proportion of gross revenue generated by each designer.
        </p>
        <div class="mt-6 space-y-4">
            <!-- prettier-ignore -->
            @php $totalRevenueSum = $leaders->sum('total_value'); @endphp
            @forelse($leaders as $leader)
            <!-- prettier-ignore -->
            @php $percentage = $totalRevenueSum > 0 ? ($leader->total_value / $totalRevenueSum) * 100 : 0; @endphp
            <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span
                        class="text-light-text max-w-[120px] truncate font-bold dark:text-white"
                    >
                        {{ $leader->name }}
                    </span>
                    <span
                        class="text-light-muted font-semibold dark:text-gray-400"
                    >
                        {{ number_format($percentage, 1) }}%
                    </span>
                </div>
                <div
                    class="dark:bg-surface-300/20 h-2 w-full overflow-hidden rounded-full bg-gray-100"
                >
                    <div
                        class="bg-brand-primary h-full rounded-full transition-all duration-500"
                        style="width: {{ $percentage }}%"
                    ></div>
                </div>
                <div
                    class="text-right text-[10px] font-medium text-emerald-600 dark:text-emerald-400"
                >
                    Rp. {{ number_format($leader->total_value, 0, ',', '.') }}
                </div>
            </div>
            @empty
            <div
                class="text-light-muted py-6 text-center text-xs dark:text-gray-500"
            >
                No share data available.
            </div>
            @endforelse
        </div>
    </div>
</div>
@endisland
