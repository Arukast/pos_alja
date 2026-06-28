@if ($showFormModal)
<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
>
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 text-light-text dark:text-gray-250 shadow-card relative w-full max-w-lg overflow-hidden rounded-2xl border bg-white p-6 pt-8 text-xs"
    >
        <x-brand-stripe />

        {{-- Modal Header --}}
        <div
            class="border-light-border dark:border-surface-300/10 mb-4 flex items-center justify-between border-b pb-3"
        >
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    {{ $editingBranchId ? 'Edit Physical Branch' : 'Add Physical
                    Branch' }}
                </h3>
                <p class="text-light-muted mt-0.5 text-xs dark:text-gray-400">
                    Configure settings, tax parameters, and contact info for
                    this physical branch location.
                </p>
            </div>
            <button
                type="button"
                wire:click="$set('showFormModal', false)"
                class="text-light-muted hover:text-light-text dark:text-gray-450 transition-colors dark:hover:text-gray-300"
            >
                <svg
                    class="h-5 w-5"
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

        {{-- Modal Form --}}
        <form wire:submit.prevent="saveBranch" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Branch Name
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="text"
                        wire:model="form.name"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('form.name')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Branch Code (Prefix)
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="text"
                        wire:model="form.code"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('form.code')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >Phone Number</label
                    >
                    <x-text-input
                        type="text"
                        wire:model="form.phone"
                        class="w-full !py-2 !text-xs"
                    />
                    @error('form.phone')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Tax Rate (%)
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="number"
                        step="0.01"
                        wire:model="form.tax_rate"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('form.tax_rate')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div>
                <label
                    class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Address</label
                >
                <x-text-input
                    type="text"
                    wire:model="form.address"
                    class="w-full !py-2 !text-xs"
                />
                @error('form.address')
                <span class="text-[10px] text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label
                    class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Receipt Custom Header Message</label
                >
                <textarea
                    wire:model="form.receipt_header"
                    rows="2"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text focus:border-brand-primary focus:ring-brand-primary/30 w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none dark:text-gray-100"
                    placeholder="E.g., Thank you for shopping with us!"
                ></textarea>
                @error('form.receipt_header')
                <span class="text-[10px] text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center space-x-2 pt-2">
                <input
                    type="checkbox"
                    id="branch_active"
                    wire:model="form.is_active"
                    class="dark:bg-surface-500 border-light-border dark:border-surface-300/30 text-brand-primary focus:ring-brand-primary rounded"
                />
                <!-- prettier-ignore -->
                <label for="branch_active" class="text-light-text text-xs font-semibold dark:text-gray-300 cursor-pointer select-none">
                    Active (Allows orders, staff allocations, and transactions)
                </label>
            </div>

            <div
                class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end gap-2 border-t pt-4"
            >
                <button
                    type="button"
                    wire:click="$set('showFormModal', false)"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    Cancel
                </button>
                <x-primary-button
                    type="submit"
                    class="!px-4 !py-2 !text-[10px] !font-bold !tracking-wider"
                >
                    Save Branch
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
@endif
