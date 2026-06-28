<!-- Header banner -->
@if ($activeTab === 'dashboard')
<x-brand-header-banner
    badge="Print Shop Production Line"
    title="Active Print Queue"
    description="Track print jobs dispatched from designers, log printer machine usage, report failed prints (wasted stock), and execute completed work tickets."
    theme="production"
/>
@endif

<!-- Alert Feedback -->
@if ($showSuccessAlert)
<div
    class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-50 p-4 text-emerald-800 backdrop-blur-sm transition dark:bg-emerald-950/20 dark:text-emerald-300"
    x-data="{ show: true }"
    x-show="show"
>
    <div class="flex items-center">
        <svg
            class="mr-2 h-5 w-5 text-emerald-500 dark:text-emerald-400"
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
        <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300">
            {{ $successMessage }}
        </span>
    </div>
    <button
        type="button"
        @click="show = false; $wire.set('showSuccessAlert', false)"
        class="text-emerald-500 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
    >
        <svg
            class="h-4 w-4"
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
@endif
