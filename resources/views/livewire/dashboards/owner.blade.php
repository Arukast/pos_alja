<div class="space-y-6">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-alja-magenta via-[#B0185F] to-surface-700 p-6 shadow-lg">
        <div class="relative z-10 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/15 rounded-full backdrop-blur-sm">
                        Executive Suite
                    </span>
                    <!-- CMYK dots -->
                    <div class="flex gap-1">
                        <div class="w-2 h-2 rounded-sm bg-white/80"></div>
                        <div class="w-2 h-2 rounded-sm bg-alja-cyan"></div>
                        <div class="w-2 h-2 rounded-sm bg-alja-yellow"></div>
                    </div>
                </div>
                <h1 class="text-3xl font-extrabold tracking-tight">Financial & Performance Dashboard</h1>
                <p class="mt-1 text-white/70 max-w-xl text-sm">
                    Overview of global print shop profits, expenses, cash drawer flows, and individual designer/cashier metrics.
                </p>
            </div>
            
            <!-- Controls (Branch select & Period select) -->
            <div class="flex flex-wrap items-center gap-3 self-start">
                <!-- Branch dropdown -->
                <div class="relative">
                    <select wire:model.live="selectedBranchId" class="block w-40 rounded-xl bg-white/15 border-0 py-1.5 pl-3 pr-8 text-xs font-semibold text-white focus:ring-2 focus:ring-white/20 backdrop-blur-sm cursor-pointer appearance-none">
                        <option value="all" class="text-gray-900 bg-white">All Branches</option>
                        @foreach($allBranches as $branch)
                            <option value="{{ $branch->id }}" class="text-gray-900 bg-white">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-white">
                        <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                    </div>
                </div>

                <!-- Period selectors -->
                <div class="flex items-center gap-1.5 bg-white/10 p-1 rounded-xl backdrop-blur-sm text-xs font-semibold">
                    <button wire:click="$set('period', 'Daily')" class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Daily' ? 'bg-white text-alja-magenta shadow-sm' : 'hover:bg-white/10' }}">Daily</button>
                    <button wire:click="$set('period', 'Weekly')" class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Weekly' ? 'bg-white text-alja-magenta shadow-sm' : 'hover:bg-white/10' }}">Weekly</button>
                    <button wire:click="$set('period', 'Monthly')" class="px-3 py-1.5 rounded-lg transition-all duration-200 {{ $period === 'Monthly' ? 'bg-white text-alja-magenta shadow-sm' : 'hover:bg-white/10' }}">Monthly</button>
                </div>
            </div>
        </div>
        <!-- Decorative blur blob -->
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-alja-cyan/15 blur-2xl"></div>
        <div class="absolute -left-8 -top-8 h-32 w-32 rounded-full bg-alja-yellow/10 blur-2xl"></div>
    </div>

    <!-- Tabs Menu -->
    <div class="flex border-b border-gray-200 dark:border-surface-300/30 gap-2 mb-2">
        <button wire:click="setTab('finance')" class="py-2.5 px-4 text-xs font-semibold border-b-2 transition-all duration-200 {{ $activeTab === 'finance' ? 'border-alja-magenta text-alja-magenta' : 'border-transparent text-gray-400 hover:text-gray-200' }}">
            Financial Overview
        </button>
        <button wire:click="setTab('users')" class="py-2.5 px-4 text-xs font-semibold border-b-2 transition-all duration-200 {{ $activeTab === 'users' ? 'border-alja-magenta text-alja-magenta' : 'border-transparent text-gray-400 hover:text-gray-200' }}">
            User Management
        </button>
    </div>



    @if ($activeTab === 'finance')
        <!-- Quick stats grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Stat card 1 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Gross Revenue (Omset)</p>
                        <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($revenue, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-xl bg-alja-magenta/10 p-3 text-alja-magenta">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-emerald-600 dark:text-emerald-400">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span>Active {{ $period }} Transactions</span>
                </div>
            </div>

            <!-- Stat card 2 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Base Cost (HPP)</p>
                        <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($cost, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-xl bg-alja-yellow/10 p-3 text-alja-yellow">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-alja-yellow/80">
                    <span>Material & consumable usage</span>
                </div>
            </div>

            <!-- Stat card 3 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Net Profit (Laba)</p>
                        <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($profit, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-xl bg-emerald-500/10 p-3 text-emerald-500 dark:text-emerald-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-emerald-600 dark:text-emerald-400 font-semibold">
                    <span>Margin Rate: {{ number_format($marginRate, 1, ',', '.') }}%</span>
                </div>
            </div>

            <!-- Stat card 4 -->
            <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card transition-all duration-200 hover:shadow-card-hover hover-lift">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Expenses (Pengeluaran)</p>
                        <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Rp. {{ number_format($expensesTotal, 0, ',', '.') }}</h3>
                    </div>
                    <div class="rounded-xl bg-red-500/10 p-3 text-red-500 dark:text-red-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-red-500 dark:text-red-400 font-semibold">
                    <span>Operational expenses</span>
                </div>
            </div>
        </div>

        <!-- Analytical graphs & staff leaderboard -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Sales performance Sparkline graph -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Profit Analysis Sparkline</h3>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $period === 'Daily' ? 'Last 7 days' : ($period === 'Weekly' ? 'Weekly' : 'Monthly') }} net profit margins trend.
                        </p>
                    </div>
                    <span class="rounded-full bg-alja-magenta/10 px-3 py-1 text-[10px] font-bold text-alja-magenta uppercase tracking-wider">Live margins</span>
                </div>

                <!-- Premium SVG Chart -->
                <div class="mt-6 h-40 w-full relative">
                    <svg class="w-full h-full overflow-visible" viewBox="0 0 300 100" preserveAspectRatio="none">
                        <!-- Grid Lines -->
                        <line x1="0" y1="20" x2="300" y2="20" stroke="rgba(156, 163, 175, 0.2)" stroke-dasharray="4" />
                        <line x1="0" y1="50" x2="300" y2="50" stroke="rgba(156, 163, 175, 0.2)" stroke-dasharray="4" />
                        <line x1="0" y1="80" x2="300" y2="80" stroke="rgba(156, 163, 175, 0.2)" stroke-dasharray="4" />
                        
                        <!-- Gradient under graph -->
                        <defs>
                            <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="rgb(233, 30, 140)" stop-opacity="0.25" />
                                <stop offset="100%" stop-color="rgb(233, 30, 140)" stop-opacity="0.0" />
                            </linearGradient>
                        </defs>

                        @if(count($sparklinePoints) > 1 && max($sparklinePoints) > 0)
                            <path d="{{ $svgPath }} L 300 100 L 0 100 Z" fill="url(#chartGradient)" />
                            <!-- Graph line -->
                            <path d="{{ $svgPath }}" fill="none" stroke="rgb(233, 30, 140)" stroke-width="2.5" stroke-linecap="round" />
                            
                            <!-- Pulsing hover point -->
                            @if(count($svgPoints) > 0)
                                @php
                                    $lastPoint = end($svgPoints);
                                    $coords = explode(' ', $lastPoint);
                                    $cx = $coords[0] ?? 300;
                                    $cy = $coords[1] ?? 80;
                                    $cx = is_numeric($cx) ? $cx : 300;
                                    $cy = is_numeric($cy) ? $cy : 80;
                                @endphp
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="4.5" fill="rgb(233, 30, 140)" />
                                <circle cx="{{ $cx }}" cy="{{ $cy }}" r="9" fill="rgb(233, 30, 140)" fill-opacity="0.2" class="animate-ping" />
                            @endif
                        @else
                            <!-- Flat baseline and message -->
                            <path d="M 0 80 L 300 80" fill="none" stroke="rgba(156, 163, 175, 0.4)" stroke-width="2" stroke-dasharray="4" />
                            <text x="150" y="50" text-anchor="middle" fill="currentColor" class="text-gray-400 dark:text-gray-600 text-xs font-semibold">No sales recorded for this period</text>
                        @endif
                    </svg>
                </div>
                
                <div class="mt-4 flex items-center justify-between text-[11px] text-gray-500 font-semibold">
                    @if($period === 'Daily')
                        <span>7 days ago</span>
                        <span>4 days ago</span>
                        <span>Today</span>
                    @elseif($period === 'Weekly')
                        <span>Mon</span>
                        <span>Wed</span>
                        <span>Fri</span>
                        <span>Sun</span>
                    @else
                        <span>Week 1</span>
                        <span>Week 2</span>
                        <span>Week 3</span>
                        <span>Week 4</span>
                    @endif
                </div>
            </div>

            <!-- Leaderboard / Staff performance -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Designer Leaderboard</h3>
                <p class="mt-1 text-xs text-gray-500">Intake metrics and order totals by CS designer.</p>

                <div class="mt-4 space-y-4">
                    @forelse($leaders as $index => $leader)
                        @php
                            $colors = [
                                ['bg-alja-magenta/15', 'text-alja-magenta'],
                                ['bg-alja-cyan/15', 'text-alja-cyan'],
                                ['bg-alja-yellow/15', 'text-alja-yellow'],
                            ];
                            $color = $colors[$index % count($colors)];
                            
                            $initials = '';
                            $words = explode(' ', $leader->name);
                            foreach($words as $w) {
                                $initials .= strtoupper(substr($w, 0, 1));
                            }
                            $initials = substr($initials, 0, 2) ?: 'CS';
                            
                            $valStr = $leader->total_value >= 1000000 
                                ? 'Rp. ' . number_format($leader->total_value / 1000000, 1, ',', '.') . 'M'
                                : 'Rp. ' . number_format($leader->total_value, 0, ',', '.');
                        @endphp
                        <div class="flex items-center justify-between p-2.5 rounded-lg hover:bg-gray-100 dark:hover:bg-surface-400/30 transition-colors duration-150">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full {{ $color[0] }} {{ $color[1] }} flex items-center justify-center font-bold text-xs">
                                    {{ $initials }}
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $leader->name }}</p>
                                    <p class="text-[10px] text-gray-500">CS & Design Specialist</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-gray-900 dark:text-white">{{ $leader->orders_count }} orders</p>
                                <p class="text-[10px] text-emerald-650 dark:text-emerald-400">{{ $valStr }} val</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-xs text-gray-400 dark:text-gray-600">
                            No designer metrics for this period.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @elseif ($activeTab === 'users')
        {{-- Shared UserManagement sub-component handles search, table, CRUD modal & its own alert --}}
        <livewire:admin.user-management mode="owner" />
    @endif
</div>
