<div class="text-light-text space-y-6 dark:text-gray-100">
    @if ($activeTab === 'finance' || $activeTab === 'leaderboard')
    <!-- Header banner -->
    <x-brand-header-banner
        badge="Executive Suite"
        title="Financial & Performance Dashboard"
        description="Overview of global print shop profits, expenses, cash drawer flows, and individual designer/cashier metrics."
        theme="owner"
    >
        <!-- Controls (Branch select & Period select) -->
        <div class="flex flex-wrap items-center gap-3 self-start">
            <!-- Branch dropdown -->
            <div class="relative">
                <select
                    id="branch-selector"
                    wire:model.live="selectedBranchId"
                    class="border-brand-primary/20 bg-brand-primary/10 text-light-text focus:ring-brand-primary/20 block w-40 cursor-pointer appearance-none rounded-xl border py-1.5 pr-8 pl-3 text-xs font-semibold backdrop-blur-sm focus:ring-2 focus:outline-none dark:border-white/20 dark:bg-white/15 dark:text-white dark:focus:ring-white/20"
                >
                    <option value="all" class="bg-white text-gray-900">
                        All Branches
                    </option>
                    @foreach($allBranches as $branch)
                    <option
                        value="{{ $branch->id }}"
                        class="bg-white text-gray-900"
                    >
                        {{ $branch->name }}
                    </option>
                    @endforeach
                </select>
                <div
                    class="text-light-text pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 dark:text-white"
                >
                    <svg
                        class="h-4 w-4 fill-current"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                    >
                        <path
                            d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                        />
                    </svg>
                </div>
            </div>

            <!-- Period selectors -->
            <div
                class="bg-brand-primary/10 flex items-center gap-1.5 rounded-xl p-1 text-xs font-semibold backdrop-blur-sm dark:bg-white/10"
            >
                <button
                    id="period-daily"
                    wire:click="$set('period', 'Daily')"
                    class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Daily' ? 'bg-brand-primary text-white dark:bg-white dark:text-brand-primary shadow-sm' : 'text-light-text dark:text-white hover:bg-brand-primary/15 dark:hover:bg-white/10' }}"
                >
                    Daily
                </button>
                <button
                    id="period-weekly"
                    wire:click="$set('period', 'Weekly')"
                    class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Weekly' ? 'bg-brand-primary text-white dark:bg-white dark:text-brand-primary shadow-sm' : 'text-light-text dark:text-white hover:bg-brand-primary/15 dark:hover:bg-white/10' }}"
                >
                    Weekly
                </button>
                <button
                    id="period-monthly"
                    wire:click="$set('period', 'Monthly')"
                    class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Monthly' ? 'bg-brand-primary text-white dark:bg-white dark:text-brand-primary shadow-sm' : 'text-light-text dark:text-white hover:bg-brand-primary/15 dark:hover:bg-white/10' }}"
                >
                    Monthly
                </button>
            </div>
        </div>
    </x-brand-header-banner>
    @endif @if ($activeTab === 'finance')
    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat card 1 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Gross Revenue (Omset)
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        Rp. {{ number_format($revenue, 0, ',', '.') }}
                    </h3>
                </div>
                <div
                    class="bg-brand-primary/10 text-brand-primary rounded-xl p-3"
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
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-medium text-emerald-600 dark:text-emerald-400"
            >
                <svg
                    class="mr-1 h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                    ></path>
                </svg>
                <span>Active {{ $period }} Transactions</span>
            </div>

            <!-- Cash Distribution Breakdown -->
            <div
                class="border-light-border dark:border-surface-300/30 text-light-muted mt-3 flex flex-wrap items-center justify-between gap-1 border-t pt-3 text-[10px] dark:text-gray-400"
            >
                <span class="flex items-center gap-1 font-semibold"
                    ><span
                        class="bg-emerald-550 h-1.5 w-1.5 rounded-full"
                    ></span>
                    Cash: Rp. {{ number_format($cashPayments, 0, ',', '.')
                    }}</span
                >
                <span class="flex items-center gap-1 font-semibold"
                    ><span class="bg-blue-550 h-1.5 w-1.5 rounded-full"></span>
                    Transfer: Rp. {{ number_format($transferPayments, 0, ',',
                    '.') }}</span
                >
                <span class="flex items-center gap-1 font-semibold"
                    ><span
                        class="bg-purple-550 h-1.5 w-1.5 rounded-full"
                    ></span>
                    QRIS: Rp. {{ number_format($qrisPayments, 0, ',', '.')
                    }}</span
                >
            </div>
        </div>

        <!-- Stat card 2 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Base Cost (HPP)
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        Rp. {{ number_format($cost, 0, ',', '.') }}
                    </h3>
                </div>
                <div
                    class="bg-brand-secondary/10 text-brand-secondary rounded-xl p-3"
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
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                        ></path>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="text-light-muted mt-4 flex items-center text-xs font-semibold dark:text-gray-400"
            >
                <span>Material & consumable usage</span>
            </div>
        </div>

        <!-- Stat card 3 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Net Profit (Laba)
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        Rp. {{ number_format($profit, 0, ',', '.') }}
                    </h3>
                </div>
                <div
                    class="text-emerald-550 rounded-xl bg-emerald-500/10 p-3 dark:text-emerald-400"
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
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400"
            >
                <span
                    >Margin Rate: {{ number_format($marginRate, 1, ',', '.')
                    }}%</span
                >
            </div>
        </div>

        <!-- Stat card 4 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Expenses (Pengeluaran)
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        Rp. {{ number_format($expensesTotal, 0, ',', '.') }}
                    </h3>
                </div>
                <div
                    class="text-red-550 rounded-xl bg-red-500/10 p-3 dark:text-red-400"
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="text-red-550 mt-4 flex items-center text-xs font-bold dark:text-red-400"
            >
                <span>Operational expenses</span>
            </div>
        </div>
    </div>

    <!-- Analytical graphs & staff leaderboard -->
    <div class="w-full">
        <!-- Sales performance Sparkline graph -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        Profit Analysis Sparkline
                    </h3>
                    <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                        {{ $period === 'Daily' ? 'Today\'s hourly' : ($period
                        === 'Weekly' ? 'Last 7 days' : 'Last 4 weeks') }} net
                        profit margins trend.
                    </p>
                </div>
                <span
                    class="bg-brand-primary/10 text-brand-primary rounded-full px-3 py-1 text-[10px] font-bold tracking-wider uppercase"
                    >Live margins</span
                >
            </div>

            <!-- Premium ApexCharts Line Chart -->
            <div
                class="mt-2 w-full"
                x-data="{
                    chart: null,
                    initChart() {
                        const points = @js($sparklinePoints);
                        const labels = @js($sparklineLabels);
                        const isDark = document.documentElement.classList.contains('dark');
                        
                        const options = {
                            series: [{
                                name: 'Net Profit',
                                data: points
                            }],
                            chart: {
                                type: 'area',
                                height: 160,
                                sparkline: {
                                    enabled: false
                                },
                                toolbar: {
                                    show: false
                                },
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 600
                                }
                            },
                            stroke: {
                                curve: 'smooth',
                                width: 2.5,
                                colors: [getComputedStyle(document.documentElement).getPropertyValue('--color-brand-primary').trim() || '#e82574']
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.35,
                                    opacityTo: 0.05,
                                    stops: [0, 90, 100]
                                }
                            },
                            markers: {
                                size: points.length > 0 ? 4 : 0,
                                colors: [getComputedStyle(document.documentElement).getPropertyValue('--color-brand-primary').trim() || '#e82574'],
                                strokeWidth: 2,
                                hover: {
                                    size: 6
                                }
                            },
                            xaxis: {
                                categories: labels,
                                labels: {
                                    style: {
                                        colors: isDark ? '#9ca3af' : '#6b7280',
                                        fontSize: '11px',
                                        fontWeight: 'bold',
                                        fontFamily: 'Outfit, sans-serif'
                                    }
                                },
                                axisBorder: {
                                    show: false
                                },
                                axisTicks: {
                                    show: false
                                }
                            },
                            yaxis: {
                                labels: {
                                    formatter: function(val) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                    },
                                    style: {
                                        colors: isDark ? '#9ca3af' : '#6b7280',
                                        fontSize: '10px',
                                        fontWeight: 'bold',
                                        fontFamily: 'Outfit, sans-serif'
                                    }
                                }
                            },
                            grid: {
                                show: true,
                                borderColor: isDark ? 'rgba(156, 163, 175, 0.15)' : 'rgba(156, 163, 175, 0.2)',
                                strokeDashArray: 4,
                                xaxis: {
                                    lines: {
                                        show: false
                                    }
                                },
                                yaxis: {
                                    lines: {
                                        show: true
                                    }
                                },
                                padding: {
                                    top: -15,
                                    right: 10,
                                    bottom: 0,
                                    left: 10
                                }
                            },
                            tooltip: {
                                theme: isDark ? 'dark' : 'light',
                                x: {
                                    show: true
                                },
                                y: {
                                    formatter: function(val) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                    }
                                }
                            },
                            colors: [getComputedStyle(document.documentElement).getPropertyValue('--color-brand-primary').trim() || '#e82574']
                        };

                        if (this.chart) {
                            this.chart.destroy();
                        }
                        this.chart = new ApexCharts(this.$refs.chartContainer, options);
                        this.chart.render();
                    }
                }"
                wire:key="owner-sparkline-{{ $period }}-{{ $selectedBranchId }}-{{ count($sparklinePoints) }}"
                x-init="initChart()"
                x-on:theme-changed.window="initChart()"
                class="relative"
            >
                <div x-ref="chartContainer" class="w-full"></div>
            </div>
        </div>
    </div>
    @elseif ($activeTab === 'leaderboard')
    <!-- Designer Performance Leaderboard Tab -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Top Revenue -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Top Performer (Omset)
                    </p>
                    <h3
                        class="text-light-text mt-1 max-w-[150px] truncate text-base font-extrabold dark:text-white"
                    >
                        {{ $topRevenue->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="bg-amber-550/10 text-amber-550 rounded-xl p-3">
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
                </div>
            </div>
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
                    >{{ $topRevenue ? $topRevenue->orders_count : 0 }}
                    orders</span
                >
            </div>
        </div>

        <!-- Card 2: Order Champion -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Order Champion
                    </p>
                    <h3
                        class="text-light-text mt-1 max-w-[150px] truncate text-base font-extrabold dark:text-white"
                    >
                        {{ $orderChampion->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="rounded-xl bg-blue-500/10 p-3 text-blue-500">
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
                </div>
            </div>
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-blue-600 dark:text-blue-400"
            >
                <span
                    >{{ $orderChampion ? $orderChampion->orders_count : 0 }}
                    Orders</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                    >Rp. {{ $orderChampion ?
                    number_format($orderChampion->total_value, 0, ',', '.') :
                    '0' }}</span
                >
            </div>
        </div>

        <!-- Card 3: Highest AOV -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Highest Avg Ticket (AOV)
                    </p>
                    <h3
                        class="text-light-text mt-1 max-w-[150px] truncate text-base font-extrabold dark:text-white"
                    >
                        {{ $topAov->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="rounded-xl bg-indigo-500/10 p-3 text-indigo-500">
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
                </div>
            </div>
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
        </div>

        <!-- Card 4: Accuracy Leader -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Accuracy Leader
                    </p>
                    <h3
                        class="text-light-text mt-1 max-w-[150px] truncate text-base font-extrabold dark:text-white"
                    >
                        {{ $accuracyLeader->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="text-emerald-555 rounded-xl bg-emerald-500/10 p-3">
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
                </div>
            </div>
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
        </div>
    </div>

    <!-- Main leaderboard grid content -->
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
                <table class="w-full border-collapse text-left">
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
                        class="divide-light-border dark:divide-surface-300/10 divide-y text-xs"
                    >
                        @forelse($leaders as $index => $leader) @php $rankColors
                        = [ 0 => 'bg-amber-500 text-white', 1 => 'bg-slate-400
                        text-white', 2 => 'bg-amber-700 text-white', ];
                        $rankBadge = $rankColors[$index] ?? 'bg-gray-200
                        dark:bg-surface-300/30 text-light-text dark:text-white';
                        @endphp
                        <tr
                            class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                        >
                            <td class="py-4 pl-4">
                                <span
                                    class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold {{ $rankBadge }}"
                                >
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="py-4">
                                <div
                                    class="text-light-text font-bold dark:text-white"
                                >
                                    {{ $leader->name }}
                                </div>
                                <div
                                    class="text-light-muted text-[10px] dark:text-gray-400"
                                >
                                    CS & Design Specialist
                                </div>
                            </td>
                            <td
                                class="text-light-muted py-4 font-medium dark:text-gray-400"
                            >
                                {{ $leader->branch_name ?? 'Global' }}
                            </td>
                            <td
                                class="text-light-text py-4 text-right font-semibold dark:text-white"
                            >
                                {{ $leader->orders_count }}
                            </td>
                            <td
                                class="py-4 text-right font-semibold text-emerald-600 dark:text-emerald-400"
                            >
                                Rp. {{ number_format($leader->total_value, 0,
                                ',', '.') }}
                            </td>
                            <td
                                class="py-4 text-right font-semibold text-indigo-600 dark:text-indigo-400"
                            >
                                Rp. {{ number_format($leader->aov, 0, ',', '.')
                                }}
                            </td>
                            <td class="py-4 pr-4 text-right">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $leader->waste_count > 0 ? 'bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' }}"
                                >
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
                @php $totalRevenueSum = $leaders->sum('total_value'); @endphp
                @forelse($leaders as $leader) @php $percentage =
                $totalRevenueSum > 0 ? ($leader->total_value / $totalRevenueSum)
                * 100 : 0; @endphp
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span
                            class="text-light-text max-w-[120px] truncate font-bold dark:text-white"
                            >{{ $leader->name }}</span
                        >
                        <span
                            class="text-light-muted font-semibold dark:text-gray-400"
                            >{{ number_format($percentage, 1) }}%</span
                        >
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
                        Rp. {{ number_format($leader->total_value, 0, ',', '.')
                        }}
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
    @elseif ($activeTab === 'users') {{-- Shared UserManagement sub-component
    --}}
    <livewire:admin.user-management
        mode="owner"
        wire:key="owner-user-management"
    />
    @elseif ($activeTab === 'audit')
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <div
            class="mb-4 flex flex-col justify-between gap-4 md:flex-row md:items-center"
        >
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    System Audit Trail
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Real-time logs of system actions and modifications.
                </p>
            </div>
            <div class="w-full md:w-72">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="auditSearch"
                    placeholder="Search logs..."
                    class="bg-light-surface-2 dark:bg-surface-500 border-light-border dark:border-surface-300/30 text-light-text focus:ring-brand-primary placeholder-light-muted w-full rounded-xl border px-4 py-2 text-xs focus:ring-1 focus:outline-none dark:text-white dark:placeholder-gray-400"
                />
            </div>
        </div>

        <div
            class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
        >
            <table
                class="text-light-muted dark:text-gray-450 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th
                            class="hover:text-brand-primary cursor-pointer rounded-l-xl px-4 py-3 transition-colors duration-150 select-none"
                            wire:click="sortByAudit('created_at')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Timestamp</span>
                                <span
                                    class="text-[10px] {{ $auditSortField === 'created_at' ? 'text-brand-primary font-bold' : 'text-gray-400 dark:text-gray-600' }}"
                                >
                                    {{ $auditSortField === 'created_at' ?
                                    ($auditSortDirection === 'asc' ? '↑' : '↓')
                                    : '↕' }}
                                </span>
                            </div>
                        </th>
                        <th
                            class="hover:text-brand-primary cursor-pointer px-4 py-3 transition-colors duration-150 select-none"
                            wire:click="sortByAudit('username')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>User</span>
                                <span
                                    class="text-[10px] {{ $auditSortField === 'username' ? 'text-brand-primary font-bold' : 'text-gray-400 dark:text-gray-600' }}"
                                >
                                    {{ $auditSortField === 'username' ?
                                    ($auditSortDirection === 'asc' ? '↑' : '↓')
                                    : '↕' }}
                                </span>
                            </div>
                        </th>
                        <th
                            class="hover:text-brand-primary cursor-pointer px-4 py-3 transition-colors duration-150 select-none"
                            wire:click="sortByAudit('role')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Role</span>
                                <span
                                    class="text-[10px] {{ $auditSortField === 'role' ? 'text-brand-primary font-bold' : 'text-gray-400 dark:text-gray-600' }}"
                                >
                                    {{ $auditSortField === 'role' ?
                                    ($auditSortDirection === 'asc' ? '↑' : '↓')
                                    : '↕' }}
                                </span>
                            </div>
                        </th>
                        <th
                            class="hover:text-brand-primary cursor-pointer px-4 py-3 transition-colors duration-150 select-none"
                            wire:click="sortByAudit('action')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Action</span>
                                <span
                                    class="text-[10px] {{ $auditSortField === 'action' ? 'text-brand-primary font-bold' : 'text-gray-400 dark:text-gray-600' }}"
                                >
                                    {{ $auditSortField === 'action' ?
                                    ($auditSortDirection === 'asc' ? '↑' : '↓')
                                    : '↕' }}
                                </span>
                            </div>
                        </th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Payload</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($activityLogs as $log)
                    <tr
                        class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                    >
                        <td
                            class="text-light-muted px-4 py-3 font-medium whitespace-nowrap dark:text-gray-400"
                        >
                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td
                            class="text-light-text px-4 py-3 font-bold dark:text-white"
                        >
                            {{ $log->username }}
                        </td>
                        <td class="px-4 py-3">
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $log->role === 'owner' ? 'bg-red-500/10 text-red-500' : '' }}
                                {{ $log->role === 'manager' ? 'bg-blue-500/10 text-blue-500' : '' }}
                                {{ $log->role === 'cashier' ? 'bg-emerald-500/10 text-emerald-500' : '' }}
                                {{ $log->role === 'designer' ? 'bg-purple-500/10 text-purple-500' : '' }}
                                {{ $log->role === 'production' ? 'bg-amber-500/10 text-amber-500' : '' }}
                            "
                            >
                                {{ $log->role }}
                            </span>
                        </td>
                        <td
                            class="text-brand-secondary px-4 py-3 font-bold uppercase"
                        >
                            {{ $log->action }}
                        </td>
                        <td
                            class="text-light-text px-4 py-3 font-medium dark:text-gray-300"
                        >
                            {{ $log->description }}
                        </td>
                        <td
                            class="text-light-muted max-w-xs truncate px-4 py-3 dark:text-gray-400"
                            title="{{ json_encode($log->payload) }}"
                        >
                            @if ($log->payload)
                            <code
                                class="bg-light-surface-2 dark:bg-surface-500 text-light-text border-light-border rounded border px-1.5 py-0.5 text-[10px] dark:border-transparent dark:text-gray-300"
                            >
                                {{ json_encode($log->payload) }}
                            </code>
                            @else - @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button
                                wire:click="openAuditDetail('{{ $log->id }}')"
                                class="bg-brand-primary/10 hover:bg-brand-primary/20 text-brand-primary rounded px-2.5 py-1 font-bold transition-colors duration-150"
                            >
                                Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="7"
                            class="text-light-muted py-8 text-center dark:text-gray-500"
                        >
                            No activity logs recorded.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $activityLogs->links() }}</div>
    </div>
    @endif {{-- VIEW AUDIT LOG DETAIL MODAL --}} @if ($showAuditDetailModal &&
    $selectedAuditLog)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    >
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text dark:text-gray-250 shadow-card animate-in fade-in zoom-in relative w-full max-w-2xl overflow-hidden rounded-2xl border bg-white p-6 pt-8 text-xs duration-200"
        >
            <x-brand-stripe />

            {{-- Modal Header --}}
            <div
                class="border-light-border dark:border-surface-300/10 mb-5 flex items-center justify-between border-b pb-3"
            >
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        System Activity Log Details
                    </h3>
                    <p
                        class="text-light-muted mt-0.5 text-xs dark:text-gray-400"
                    >
                        Detailed log metadata and state changes payload.
                    </p>
                </div>
                <button
                    wire:click="closeAuditDetail"
                    class="text-light-muted hover:text-light-text dark:text-gray-450 transition-colors dark:hover:text-gray-300"
                >
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div>

            {{-- Details Grid --}}
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div
                        class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                    >
                        <span
                            class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                            >Timestamp</span
                        >
                        <span
                            class="text-light-text mt-1 block text-sm font-semibold dark:text-white"
                            >{{ $selectedAuditLog->created_at->format('Y-m-d
                            H:i:s') }}</span
                        >
                    </div>
                    <div
                        class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                    >
                        <span
                            class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                            >User (Username)</span
                        >
                        <span
                            class="text-light-text mt-1 block text-sm font-semibold dark:text-white"
                            >{{ $selectedAuditLog->username }}</span
                        >
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div
                        class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                    >
                        <span
                            class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                            >Role</span
                        >
                        <span
                            class="text-light-text mt-1 block text-sm font-semibold uppercase dark:text-white"
                            >{{ $selectedAuditLog->role }}</span
                        >
                    </div>
                    <div
                        class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                    >
                        <span
                            class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                            >Action</span
                        >
                        <span
                            class="text-brand-secondary mt-1 block text-sm font-bold uppercase"
                            >{{ $selectedAuditLog->action }}</span
                        >
                    </div>
                </div>

                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Description</span
                    >
                    <span
                        class="text-light-text mt-1 block text-sm font-semibold whitespace-pre-wrap dark:text-white"
                        >{{ $selectedAuditLog->description }}</span
                    >
                </div>

                <div
                    class="bg-light-surface-2 dark:bg-surface-500/30 rounded-xl p-3"
                >
                    <span
                        class="text-light-muted mb-2 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Payload Data</span
                    >
                    @if ($selectedAuditLog->payload)
                    <pre
                        class="dark:bg-surface-650 border-light-border dark:border-surface-300/10 text-light-text max-h-60 overflow-auto rounded-lg border bg-white p-3 font-mono text-[10px] whitespace-pre dark:text-gray-300"
                    ><code>{{ json_encode($selectedAuditLog->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                    @else
                    <span
                        class="text-light-muted mt-1 block text-sm font-semibold dark:text-gray-400"
                        >-</span
                    >
                    @endif
                </div>
            </div>

            {{-- Action Buttons --}}
            <div
                class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end border-t pt-4"
            >
                <button
                    type="button"
                    wire:click="closeAuditDetail"
                    class="border-light-border hover:bg-light-surface dark:border-surface-300/20 dark:hover:bg-surface-500/20 rounded-xl border px-4 py-2 font-bold transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
