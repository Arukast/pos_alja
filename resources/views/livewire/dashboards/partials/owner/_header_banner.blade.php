<x-brand-header-banner
    badge="Executive Suite"
    title="Financial & Performance Dashboard"
    description="Overview of global print shop profits, expenses, cash drawer flows, and individual designer/cashier metrics."
    theme="owner"
>
    <!-- Controls (Branch select & Period select) -->
    <!-- prettier-ignore -->
    <div class="flex flex-wrap items-center gap-3 self-start" data-loading:opacity-75>
        <!-- Branch dropdown -->
        <div class="relative">
            <select
                id="branch-selector"
                wire:model.live="selectedBranchId"
                class="border-brand-primary/20 bg-brand-primary/10 text-light-text focus:ring-brand-primary/20 block w-40 cursor-pointer appearance-none rounded-xl border py-1.5 pr-8 pl-3 text-xs font-semibold backdrop-blur-sm focus:ring-2 focus:outline-none dark:border-white/20 dark:bg-white/15 dark:text-white dark:focus:ring-white/20 data-loading:opacity-50 data-loading:cursor-wait"
            >
                <option value="all" class="bg-white text-gray-900">
                    All Branches
                </option>
                @foreach($allBranches as $branch)
                <!-- prettier-ignore -->
                <option
                    value="{{ $branch->id }}"
                    class="bg-white text-gray-900"
                >{{ $branch->name }}</option>
                @endforeach
            </select>
            <div class="text-light-text pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 dark:text-white">
                <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                </svg>
            </div>
        </div>

        <!-- Period selectors -->
        <div class="bg-brand-primary/10 flex items-center gap-1.5 rounded-xl p-1 text-xs font-semibold backdrop-blur-sm dark:bg-white/10">
            @php
                $dailyActive = $period === 'Daily';
                $weeklyActive = $period === 'Weekly';
                $monthlyActive = $period === 'Monthly';
                
                $dailyClasses = $dailyActive 
                    ? 'bg-brand-primary text-white dark:bg-white dark:text-brand-primary shadow-sm' 
                    : 'text-light-text dark:text-white hover:bg-brand-primary/15 dark:hover:bg-white/10';
                    
                $weeklyClasses = $weeklyActive 
                    ? 'bg-brand-primary text-white dark:bg-white dark:text-brand-primary shadow-sm' 
                    : 'text-light-text dark:text-white hover:bg-brand-primary/15 dark:hover:bg-white/10';
                    
                $monthlyClasses = $monthlyActive 
                    ? 'bg-brand-primary text-white dark:bg-white dark:text-brand-primary shadow-sm' 
                    : 'text-light-text dark:text-white hover:bg-brand-primary/15 dark:hover:bg-white/10';
            @endphp

            <!-- prettier-ignore -->
            <button
                id="period-daily"
                wire:click="setPeriod('Daily')"
                class="px-3 py-1.5 rounded-lg transition-all duration-200 data-loading:opacity-50 {{ $dailyClasses }}"
            >Daily</button>
            <!-- prettier-ignore -->
            <button
                id="period-weekly"
                wire:click="setPeriod('Weekly')"
                class="px-3 py-1.5 rounded-lg transition-all duration-200 data-loading:opacity-50 {{ $weeklyClasses }}"
            >Weekly</button>
            <!-- prettier-ignore -->
            <button
                id="period-monthly"
                wire:click="setPeriod('Monthly')"
                class="px-3 py-1.5 rounded-lg transition-all duration-200 data-loading:opacity-50 {{ $monthlyClasses }}"
            >Monthly</button>
        </div>
    </div>
</x-brand-header-banner>
