<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 space-y-4 rounded-2xl border bg-white p-5 shadow-sm"
>
    <h2 class="text-light-text text-lg font-bold dark:text-white">
        2. Job Parameters
    </h2>

    <div>
        <label
            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
            >Production Deadline</label
        >
        <input
            id="order-deadline"
            type="datetime-local"
            wire:model="deadline"
            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
        />
    </div>

    <div>
        <label
            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
            >Order Intake Channel</label
        >
        <select
            id="order-source"
            wire:model="source"
            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
        >
            <option value="Offline" class="bg-white text-gray-900">
                Offline / Walk-In
            </option>
            <option value="WhatsApp" class="bg-white text-gray-900">
                WhatsApp Message
            </option>
            <option value="Tokopedia" class="bg-white text-gray-900">
                Tokopedia Store
            </option>
            <option value="Shopee" class="bg-white text-gray-900">
                Shopee Store
            </option>
            <option value="Email" class="bg-white text-gray-900">
                Email Channel
            </option>
        </select>
    </div>
</div>
