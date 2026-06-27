<div class="text-light-text space-y-6 dark:text-gray-100">
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

    <!-- Tabs Menu -->
    <div
        class="dark:border-surface-300/30 border-light-border mb-4 flex gap-2 border-b"
    >
        <button
            id="tab-finance"
            wire:click="setTab('finance')"
            class="py-2.5 px-4 text-xs font-bold border-b-2 transition-all duration-200 {{ $activeTab === 'finance' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-light-muted hover:text-light-text dark:text-gray-400 dark:hover:text-white' }}"
        >
            Financial Overview
        </button>
        <button
            id="tab-users"
            wire:click="setTab('users')"
            class="py-2.5 px-4 text-xs font-bold border-b-2 transition-all duration-200 {{ $activeTab === 'users' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-light-muted hover:text-light-text dark:text-gray-400 dark:hover:text-white' }}"
        >
            User Management
        </button>
        <button
            id="tab-audit"
            wire:click="setTab('audit')"
            class="py-2.5 px-4 text-xs font-bold border-b-2 transition-all duration-200 {{ $activeTab === 'audit' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-light-muted hover:text-light-text dark:text-gray-400 dark:hover:text-white' }}"
        >
            Audit Trail
        </button>
    </div>

    @if ($activeTab === 'finance')
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
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Sales performance Sparkline graph -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md lg:col-span-2"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        Profit Analysis Sparkline
                    </h3>
                    <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                        {{ $period === 'Daily' ? 'Last 7 days' : ($period ===
                        'Weekly' ? 'Weekly' : 'Monthly') }} net profit margins
                        trend.
                    </p>
                </div>
                <span
                    class="bg-brand-primary/10 text-brand-primary rounded-full px-3 py-1 text-[10px] font-bold tracking-wider uppercase"
                    >Live margins</span
                >
            </div>

            <!-- Premium SVG Chart -->
            <div class="relative mt-6 h-40 w-full">
                <svg
                    class="h-full w-full overflow-visible"
                    viewBox="0 0 300 100"
                    preserveAspectRatio="none"
                >
                    <!-- Grid Lines -->
                    <line
                        x1="0"
                        y1="20"
                        x2="300"
                        y2="20"
                        stroke="rgba(156, 163, 175, 0.2)"
                        stroke-dasharray="4"
                    />
                    <line
                        x1="0"
                        y1="50"
                        x2="300"
                        y2="50"
                        stroke="rgba(156, 163, 175, 0.2)"
                        stroke-dasharray="4"
                    />
                    <line
                        x1="0"
                        y1="80"
                        x2="300"
                        y2="80"
                        stroke="rgba(156, 163, 175, 0.2)"
                        stroke-dasharray="4"
                    />

                    <!-- Gradient under graph -->
                    <defs>
                        <linearGradient
                            id="chartGradient"
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                stop-color="var(--brand-primary)"
                                stop-opacity="0.25"
                            />
                            <stop
                                offset="100%"
                                stop-color="var(--brand-primary)"
                                stop-opacity="0.0"
                            />
                        </linearGradient>
                    </defs>

                    @if(count($sparklinePoints) > 1 && max($sparklinePoints) >
                    0)
                    <path
                        d="{{ $svgPath }} L 300 100 L 0 100 Z"
                        fill="url(#chartGradient)"
                    />
                    <!-- Graph line -->
                    <path
                        d="{{ $svgPath }}"
                        fill="none"
                        stroke="var(--brand-primary)"
                        stroke-width="2.5"
                        stroke-linecap="round"
                    />

                    <!-- Pulsing hover point -->
                    @if(count($svgPoints) > 0) @php $lastPoint =
                    end($svgPoints); $coords = explode(' ', $lastPoint); $cx =
                    $coords[0] ?? 300; $cy = $coords[1] ?? 80; $cx =
                    is_numeric($cx) ? $cx : 300; $cy = is_numeric($cy) ? $cy :
                    80; @endphp
                    <circle
                        cx="{{ $cx }}"
                        cy="{{ $cy }}"
                        r="4.5"
                        fill="var(--brand-primary)"
                    />
                    <circle
                        cx="{{ $cx }}"
                        cy="{{ $cy }}"
                        r="9"
                        fill="var(--brand-primary)"
                        fill-opacity="0.2"
                        class="animate-ping"
                    />
                    @endif @else
                    <!-- Flat baseline and message -->
                    <path
                        d="M 0 80 L 300 80"
                        fill="none"
                        stroke="rgba(156, 163, 175, 0.4)"
                        stroke-width="2"
                        stroke-dasharray="4"
                    />
                    <text
                        x="150"
                        y="50"
                        text-anchor="middle"
                        fill="currentColor"
                        class="text-light-muted text-xs font-semibold dark:text-gray-500"
                    >
                        No sales recorded for this period
                    </text>
                    @endif
                </svg>
            </div>

            <div
                class="text-light-muted mt-4 flex items-center justify-between text-[11px] font-bold dark:text-gray-400"
            >
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
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm transition-all duration-200 hover:shadow-md"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Designer Leaderboard
            </h3>
            <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                Intake metrics and order totals by CS designer.
            </p>

            <div class="mt-4 space-y-3">
                @forelse($leaders as $index => $leader) @php $colors = [
                ['bg-brand-primary/10', 'text-brand-primary'],
                ['bg-brand-secondary/10', 'text-brand-secondary'],
                ['bg-emerald-500/10', 'text-emerald-555'], ]; $color =
                $colors[$index % count($colors)]; $initials = ''; $words =
                explode(' ', $leader->name); foreach($words as $w) { $initials
                .= strtoupper(substr($w, 0, 1)); } $initials = substr($initials,
                0, 2) ?: 'CS'; $valStr = $leader->total_value >= 1000000 ? 'Rp.
                ' . number_format($leader->total_value / 1000000, 1, ',', '.') .
                'M' : 'Rp. ' . number_format($leader->total_value, 0, ',', '.');
                @endphp
                <div
                    class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 flex items-center justify-between rounded-xl p-2.5 transition-colors duration-150"
                >
                    <div class="flex items-center">
                        <div
                            class="h-8 w-8 rounded-full {{ $color[0] }} {{ $color[1] }} flex items-center justify-center font-extrabold text-xs"
                        >
                            {{ $initials }}
                        </div>
                        <div class="ml-3">
                            <p
                                class="text-light-text text-xs font-bold dark:text-white"
                            >
                                {{ $leader->name }}
                            </p>
                            <p
                                class="text-light-muted text-[10px] dark:text-gray-400"
                            >
                                CS & Design Specialist
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p
                            class="text-light-text text-xs font-bold dark:text-white"
                        >
                            {{ $leader->orders_count }} orders
                        </p>
                        <p
                            class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                        >
                            {{ $valStr }} val
                        </p>
                    </div>
                </div>
                @empty
                <div
                    class="text-light-muted py-8 text-center text-xs dark:text-gray-500"
                >
                    No designer metrics for this period.
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @elseif ($activeTab === 'users') {{-- Shared UserManagement sub-component
    --}}
    <livewire:admin.user-management mode="owner" />
    @elseif ($activeTab === 'audit')
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    System Audit Trail
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Real-time logs of system actions and modifications.
                </p>
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
                        <th class="rounded-l-xl px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="rounded-r-xl px-4 py-3">Payload</th>
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
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="6"
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
    @endif
</div>
