<!-- Analytical graphs & staff leaderboard -->
<div class="w-full">
    <!-- Sales performance Sparkline graph -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md"
    >
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Profit Analysis Sparkline
                </h3>
                <!-- prettier-ignore -->
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    {{ $period === 'Daily' ? 'Today\'s hourly' : ($period === 'Weekly' ? 'Last 7 days' : 'Last 4 weeks') }} net profit margins trend.
                </p>
            </div>
            <span
                class="bg-brand-primary/10 text-brand-primary rounded-full px-3 py-1 text-[10px] font-bold tracking-wider uppercase"
                >Live margins</span
            >
        </div>

        <!-- Premium ApexCharts Line Chart -->
        @island(name: 'owner-sparkline')
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
        @endisland
    </div>
</div>
