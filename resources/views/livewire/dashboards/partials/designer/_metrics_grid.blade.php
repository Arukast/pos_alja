<div class="grid grid-cols-3 gap-5">
    <!-- Placed Today -->
    <x-designer.metric-card
        title="Invoices Placed Today"
        :value="$placedTodayCount"
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
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
        </svg>
    </x-designer.metric-card>

    <!-- Revenue Today -->
    <!-- prettier-ignore -->
    <x-designer.metric-card
            title="Grand Total Placed Today"
            value="Rp. {{ number_format($grandTotalToday) }}"
            valueClass="text-brand-secondary dark:text-teal-400"
            iconContainerClass="bg-brand-secondary/10 text-brand-secondary dark:bg-brand-secondary/20"
        >
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M12 14a2 2 0 110-4h2.5"/>
            </svg>
        </x-designer.metric-card>

    <!-- Items in Cart -->
    <x-designer.metric-card
        title="Items in Draft Cart"
        :value="$cartItemsCount"
        iconContainerClass="bg-pink-100 text-pink-600 dark:bg-pink-900/30 dark:text-pink-400"
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
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
            />
        </svg>
    </x-designer.metric-card>
</div>
