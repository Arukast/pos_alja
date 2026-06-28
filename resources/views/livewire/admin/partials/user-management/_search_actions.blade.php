{{-- Search & Add --}}
<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex max-w-xl flex-1 flex-wrap items-center gap-4">
        <div class="relative min-w-[200px] flex-1">
            <x-text-input
                type="text"
                wire:model.live.debounce.300ms="userSearch"
                placeholder="Search staff name or username..."
                class="w-full !py-2 pl-9 !text-xs"
            />
            <div
                class="text-light-muted pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 dark:text-gray-400"
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
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    ></path>
                </svg>
            </div>
        </div>

        <label
            class="inline-flex cursor-pointer items-center space-x-2 select-none"
        >
            <input
                type="checkbox"
                wire:model.live="showDeactivated"
                class="dark:bg-surface-500 border-light-border dark:border-surface-300/30 text-brand-primary focus:ring-brand-primary rounded"
            />
            <span
                class="text-light-text text-xs font-semibold dark:text-gray-300"
                >Show Deactivated</span
            >
        </label>
    </div>
    <x-primary-button
        type="button"
        wire:click="openCreateModal"
        class="!px-4 !py-2 !text-[11px] !font-bold !tracking-wider"
    >
        Add Staff Member
    </x-primary-button>
</div>
