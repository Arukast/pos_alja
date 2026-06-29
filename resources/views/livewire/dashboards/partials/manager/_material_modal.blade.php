@php $categories = [ 'lembar' => 'Lembar (Sheet)', 'meter' => 'Meter (Roll)',
'pcs' => 'Pcs (Piece)', 'display' => 'Display', 'finishing' => 'Finishing',
'potong' => 'Potong (Cut)', 'stempel' => 'Stempel (Stamp)' ]; @endphp

<!-- prettier-ignore -->
@if ($isCreatingMaterial)
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
                    {{ $editingMaterialId ? 'Edit Material Details' : 'Register
                    New Material' }}
                </h3>
                <p class="text-light-muted mt-0.5 text-xs dark:text-gray-400">
                    {{ $editingMaterialId ? 'Modify settings for the selected
                    material catalog item.' : 'Define a new base material
                    catalog entry with default cost, price, and stocking
                    limits.' }}
                </p>
            </div>
            <button
                type="button"
                wire:click="closeMaterialModal"
                class="text-light-muted hover:text-light-text dark:text-gray-455 transition-colors dark:hover:text-gray-300"
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

        <!-- prettier-ignore -->
        @if ($showSimilarityWarning)
        <div class="space-y-4">
            <div
                class="rounded-xl bg-amber-500/10 p-4 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400"
            >
                <p class="text-sm font-bold">Similar Material Found</p>
                <p class="mt-1 text-xs leading-relaxed">
                    We found a highly similar material:
                    <strong>"{{ $similarGlobalMaterialName }}"</strong> already
                    registered in the global catalog. Did you mean to
                    activate/use this existing profile instead of creating
                    <strong>"{{ $materialForm->name }}"</strong>?
                </p>
            </div>

            <div
                class="border-light-border dark:border-surface-300/10 mt-6 flex flex-col justify-end gap-2 border-t pt-4 sm:flex-row"
            >
                <button
                    type="button"
                    wire:click="closeMaterialModal"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    wire:click="bypassSimilarityAndSave"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    No, Register New
                </button>
                <button
                    type="button"
                    wire:click="acceptSimilarMaterial"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary cursor-pointer rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                >
                    Yes, Use "{{ $similarGlobalMaterialName }}"
                </button>
            </div>
        </div>
        @elseif ($showActivationWizard)
        <div class="space-y-4">
            <div
                class="text-blue-650 rounded-xl bg-blue-500/10 p-4 dark:bg-blue-950/30 dark:text-blue-400"
            >
                <p class="text-sm font-bold">Global Definition Found</p>
                <p class="mt-1 text-xs leading-relaxed">
                    The material
                    <strong>"{{ $materialForm->name }}"</strong> already exists
                    in the global catalog. Would you like to activate it for
                    your active branch with an initial balance of
                    <strong>0</strong>?
                </p>
            </div>

            <div
                class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end gap-2 border-t pt-4"
            >
                <button
                    type="button; button-cancel-activation"
                    wire:click="closeMaterialModal"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    Cancel
                </button>
                <button
                    type="button; button-confirm-activation"
                    wire:click="activateExistingMaterial"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary cursor-pointer rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                >
                    Activate for my Branch
                </button>
            </div>
        </div>
        @else
        <form wire:submit.prevent="saveMaterial" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Material Name
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="text"
                        wire:model="materialForm.name"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('materialForm.name')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Category / Unit
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <select
                        wire:model="materialForm.category"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none"
                        required
                    >
                        <option value="">-- Choose Category --</option>
                        @foreach ($categories as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('materialForm.category')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Type / Material Group
                    </label>
                    <x-text-input
                        type="text"
                        wire:model="materialForm.type"
                        placeholder="e.g. Indoor, Outdoor, Kertas"
                        class="w-full !py-2 !text-xs"
                    />
                    @error('materialForm.type')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Dimensions / Size
                    </label>
                    <x-text-input
                        type="text"
                        wire:model="materialForm.dimensions"
                        placeholder="e.g. 1.2x50m, A3, etc."
                        class="w-full !py-2 !text-xs"
                    />
                    @error('materialForm.dimensions')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Base Cost per Unit (HPP)
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="number"
                        step="0.01"
                        wire:model="materialForm.cost"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('materialForm.cost')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Selling Price per Unit
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="number"
                        step="0.01"
                        wire:model="materialForm.price"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('materialForm.price')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Starting Stock Qty
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="number"
                        step="0.01"
                        wire:model="materialForm.current_stock"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('materialForm.current_stock')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <!-- prettier-ignore -->
                    <label class="text-light-muted mb-1.5 block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400">
                        Min Alert Threshold
                        <span class="font-bold text-red-500">*</span>
                    </label>
                    <x-text-input
                        type="number"
                        step="0.01"
                        wire:model="materialForm.min_stock"
                        class="w-full !py-2 !text-xs"
                        required
                    />
                    @error('materialForm.min_stock')
                    <span class="text-[10px] text-red-500">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div
                class="border-light-border dark:border-surface-300/10 mt-6 flex justify-end gap-2 border-t pt-4"
            >
                <button
                    type="button"
                    wire:click="closeMaterialModal"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:hover:bg-surface-400/50 cursor-pointer rounded-xl border bg-white px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition duration-150 hover:bg-gray-50 dark:text-gray-300"
                >
                    Cancel
                </button>
                <x-primary-button
                    type="submit"
                    class="!px-4 !py-2 !text-[10px] !font-bold !tracking-wider"
                    data-loading-target="saveMaterial"
                >
                    Save Material
                </x-primary-button>
            </div>
        </form>
        @endif
    </div>
</div>
@endif
