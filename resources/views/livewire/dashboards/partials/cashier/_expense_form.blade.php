<!-- Log Expense Form Panel -->
<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <h3 class="text-light-text text-lg font-bold dark:text-white">
        Log Store Expense
    </h3>
    <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
        Record operational cash payouts.
    </p>

    <form wire:submit.prevent="logExpense" class="space-y-4 text-xs">
        <div>
            <label
                class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >Expense Amount (Rp.)</label
            >
            <input
                id="expense-amount"
                type="number"
                wire:model="expenseForm.amount"
                placeholder="e.g. 50000"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-gray-800 transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                required
            />
            @error('expenseForm.amount')
            <span
                class="text-red-550 text-[10px] font-semibold dark:text-red-400"
                >{{ $message }}</span
            >
            @enderror
        </div>

        <div>
            <label
                class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >Expense Classification</label
            >
            <select
                id="expense-type"
                wire:model="expenseForm.type"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 font-medium transition duration-200 focus:ring-2 focus:outline-none"
            >
                <option value="laba" class="bg-white text-gray-900">
                    Operational / Operational cost (Laba)
                </option>
                <option value="modal" class="bg-white text-gray-900">
                    Capital / Purchase supplies (Modal)
                </option>
            </select>
            @error('expenseForm.type')
            <span
                class="text-red-550 text-[10px] font-semibold dark:text-red-400"
                >{{ $message }}</span
            >
            @enderror
        </div>

        <div>
            <label
                class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >Incurred Date</label
            >
            <input
                id="expense-date"
                type="date"
                wire:model="expenseForm.incurred_at"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 font-medium transition duration-200 focus:ring-2 focus:outline-none"
                required
            />
            @error('expenseForm.incurred_at')
            <span
                class="text-red-550 text-[10px] font-semibold dark:text-red-400"
                >{{ $message }}</span
            >
            @enderror
        </div>

        <div>
            <label
                class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >Description / Reason</label
            >
            <textarea
                id="expense-description"
                wire:model="expenseForm.description"
                placeholder="Specify items purchased, utility, or reasons..."
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-20 w-full resize-none rounded-xl border bg-white px-3 py-2 transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                required
            ></textarea>
            @error('expenseForm.description')
            <span
                class="text-red-550 text-[10px] font-semibold dark:text-red-400"
                >{{ $message }}</span
            >
            @enderror
        </div>

        <button
            id="btn-submit-expense"
            type="submit"
            class="bg-brand-secondary hover:bg-brand-secondary/95 w-full cursor-pointer rounded-xl py-2.5 font-bold text-white shadow-sm transition data-loading:cursor-not-allowed data-loading:opacity-50"
            data-loading-disable
        >
            Record Store Payout
        </button>
    </form>
</div>
