@if ($showSuccessAlert)
<div
    class="relative mb-4 flex items-center justify-between rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800/30 dark:bg-green-950/30 dark:text-green-400"
    role="alert"
>
    <div class="flex items-center gap-2">
        <svg
            class="h-4 w-4 shrink-0"
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
        <span class="text-xs font-semibold">{{ $successMessage }}</span>
    </div>
    <button
        type="button"
        wire:click="$set('showSuccessAlert', false)"
        class="dark:text-green-450 text-green-500 hover:text-green-700 dark:hover:text-green-300"
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
