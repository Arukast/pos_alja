{{-- Success Alert --}} @if ($showSuccessAlert)
<div
    class="border-emerald-250 flex items-center justify-between rounded-xl border bg-emerald-50 p-4 backdrop-blur-sm transition dark:border-emerald-500/20 dark:bg-emerald-950/20"
    x-data="{ show: true }"
    x-show="show"
>
    <div class="flex items-center">
        <svg
            class="mr-2 h-5 w-5 text-emerald-600 dark:text-emerald-400"
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
        <span
            class="text-sm font-semibold text-emerald-800 dark:text-emerald-300"
            >{{ $successMessage }}</span
        >
    </div>
    <button
        type="button"
        @click="show = false; $wire.set('showSuccessAlert', false)"
        class="hover:text-emerald-750 text-emerald-600 transition-colors dark:text-emerald-400 dark:hover:text-emerald-300"
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
