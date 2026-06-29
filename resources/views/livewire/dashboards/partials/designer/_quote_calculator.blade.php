@php $selectedCategoryId = $calculatorForm->productCategoryId;
$hasSelectedCategory = !empty($selectedCategoryId); $activeCategory =
$hasSelectedCategory ? $productCategories->firstWhere('id', $selectedCategoryId)
: null; $categoryName = $activeCategory ? $activeCategory->name : ''; $archetype
= $activeCategory ? $activeCategory->calculation_archetype : ''; $showLength =
$hasSelectedCategory && in_array($archetype, ['surface_area',
'linear_dimension']); $showWidth = $hasSelectedCategory && ($archetype ===
'surface_area'); $unitPriceSuffix = ($hasSelectedCategory && $archetype ===
'surface_area') ? ' / m²' : (($hasSelectedCategory && $archetype ===
'linear_dimension') ? ' / m' : ''); $hasAttributes = $hasSelectedCategory &&
!empty($availableAttributes) && $availableAttributes->isNotEmpty(); @endphp

<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <h2 class="text-light-text mb-4 text-lg font-bold dark:text-white">
        3. Quote Calculator
    </h2>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <!-- Product Category Selector -->
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Product Category</label
            >
            <select
                id="calc-product-id"
                wire:model.live="calculatorForm.productCategoryId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted dark:placeholder-gray-555 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
            >
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Select Product Category --
                </option>
                @foreach ($productCategories as $c)
                <option
                    value="{{ $c->id }}"
                    class="text-light-text dark:text-gray-200"
                >
                    {{ $c->name }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Material Selector -->
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Material (Bahan)</label
            >
            <select
                id="calc-material-id"
                wire:model.live="calculatorForm.materialId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
            >
                @if (!$hasSelectedCategory)
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Select a category first... --
                </option>
                @else
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Select Material --
                </option>
                @foreach ($materials as $m)
                <option
                    value="{{ $m->id }}"
                    class="text-light-text font-semibold dark:text-gray-200"
                >
                    {{ $m->name }} {{ $m->dimensions ? "({$m->dimensions})" : ''
                    }}
                </option>
                @endforeach @endif
            </select>
            @error('calculatorForm.materialId')
            <span
                class="text-red-550 mt-1 block text-[10px] font-bold dark:text-red-400"
                >{{ $message }}</span
            >
            @enderror
        </div>

        <!-- Machine Selector -->
        @if ($hasSelectedCategory && $hasMachines)
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Print Machine</label
            >
            <select
                id="calc-machine-id"
                wire:model.live="calculatorForm.machineId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                data-loading:attr="disabled"
            >
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Select Machine --
                </option>
                @foreach ($machines as $mc)
                <option
                    value="{{ $mc->id }}"
                    class="text-light-text font-semibold dark:text-gray-200"
                >
                    {{ $mc->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Finishing Selector -->
        @if ($hasSelectedCategory && $hasFinishings)
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Post-Finishing</label
            >
            <select
                id="calc-finishing-id"
                wire:model.live="calculatorForm.finishingId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                data-loading:attr="disabled"
            >
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Select Finishing --
                </option>
                @foreach ($finishings as $f)
                <option
                    value="{{ $f->id }}"
                    class="text-light-text dark:text-gray-200"
                >
                    {{ $f->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Cutting Selector -->
        @if ($hasSelectedCategory && $hasCuts)
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Cutting Style</label
            >
            <select
                id="calc-cut-id"
                wire:model.live="calculatorForm.cutId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                data-loading:attr="disabled"
            >
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Select Cutting --
                </option>
                @foreach ($cuts as $c)
                <option
                    value="{{ $c->id }}"
                    class="text-light-text dark:text-gray-200"
                >
                    {{ $c->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Display Selector -->
        @if ($hasSelectedCategory && $hasDisplays)
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Display Stands & Accessories</label
            >
            <select
                id="calc-display-id"
                wire:model.live="calculatorForm.displayId"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                data-loading:attr="disabled"
            >
                <option value="" class="text-light-muted dark:text-gray-400">
                    -- Select Display --
                </option>
                @foreach ($displays as $d)
                <option
                    value="{{ $d->id }}"
                    class="text-light-text dark:text-gray-200"
                >
                    {{ $d->name }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Sides / Colors Selector -->
        @if ($hasSelectedCategory && $hasSides)
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
            >
                {{ $categoryName === 'Stempel' ? 'Color Options (Stempel)' :
                ($categoryName === 'NOTA' ? 'Note Copies (Nota)' : 'Print Sides
                (Sisi)') }}
            </label>
            <select
                id="calc-sides"
                wire:model.live="calculatorForm.sides"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                data-loading:attr="disabled"
            >
                @if ($categoryName === 'Stempel')
                <option value="1" class="bg-white text-gray-900">
                    1 Warna
                </option>
                <option value="2" class="bg-white text-gray-900">
                    2 Warna
                </option>
                <option value="3" class="bg-white text-gray-900">
                    3 Warna
                </option>
                <option value="4" class="bg-white text-gray-900">
                    4 Warna
                </option>
                @elseif ($categoryName === 'NOTA')
                <option value="1" class="bg-white text-gray-900">
                    1 Rangkap
                </option>
                <option value="2" class="bg-white text-gray-900">
                    2 Rangkap
                </option>
                <option value="3" class="bg-white text-gray-900">
                    3 Rangkap
                </option>
                <option value="4" class="bg-white text-gray-900">
                    4 Rangkap
                </option>
                @else
                <option value="1" class="bg-white text-gray-900">1 Sisi</option>
                <option value="2" class="bg-white text-gray-900">2 Sisi</option>
                @endif
            </select>
        </div>
        @endif

        <!-- Finishing Sides -->
        @if ($hasSelectedCategory && $hasFinishingSides)
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Finishing Sides</label
            >
            <select
                id="calc-finishing-sides"
                wire:model.live="calculatorForm.finishingSides"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                data-loading:attr="disabled"
            >
                <option value="0" class="bg-white text-gray-900">0 Sisi</option>
                <option value="1" class="bg-white text-gray-900">1 Sisi</option>
                <option value="2" class="bg-white text-gray-900">2 Sisi</option>
            </select>
        </div>
        @endif

        <!-- Dynamic geometric parameters -->
        @if ($showLength)
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Length (m)</label
            >
            <input
                id="calc-length"
                type="number"
                step="0.01"
                min="0.1"
                wire:model.live.debounce.300ms="calculatorForm.length"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                data-loading:attr="disabled"
            />
        </div>
        @endif @if ($showWidth)
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Width (m)</label
            >
            <input
                id="calc-width"
                type="number"
                step="0.01"
                min="0.1"
                wire:model.live.debounce.300ms="calculatorForm.width"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                data-loading:attr="disabled"
            />
        </div>
        @endif

        <!-- Quantity Input -->
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Quantity (Pcs/Sheets/Qty)</label
            >
            <input
                id="calc-qty"
                type="number"
                min="1"
                step="any"
                wire:model.live.debounce.300ms="calculatorForm.quantity"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                data-loading:attr="disabled"
            />
        </div>

        <!-- File Name Input -->
        <div>
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >File Layout Name</label
            >
            <input
                id="calc-file-name"
                type="text"
                wire:model="calculatorForm.fileName"
                placeholder="layout_design.pdf"
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
            />
        </div>

        <!-- Notes Input -->
        <div class="md:col-span-2">
            <label
                class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                >Prepress / Layout Instructions</label
            >
            <textarea
                id="calc-notes"
                wire:model="calculatorForm.notes"
                placeholder="Additional notes for production operators..."
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-16 w-full resize-none rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
            ></textarea>
        </div>

        <!-- Dynamic Pricing Display Section -->
        <div
            class="border-light-border dark:border-surface-300/30 bg-light-surface-2/40 dark:bg-surface-650/40 mt-6 flex flex-col gap-4 rounded-xl border p-4 md:flex-row md:items-center md:justify-between"
            data-loading:class="opacity-75 pointer-events-none"
        >
            <div class="relative flex-1">
                <!-- Loading indicator for calculation updates -->
                <div
                    data-loading
                    class="dark:bg-surface-600/50 absolute inset-0 z-10 flex items-center justify-start gap-2 bg-white/50"
                >
                    <span
                        class="border-brand-primary h-4 w-4 animate-spin rounded-full border-2 border-t-transparent"
                    ></span>
                    <span
                        class="text-light-muted text-xs font-bold dark:text-gray-400"
                        >Calculating...</span
                    >
                </div>

                <div data-loading:class="opacity-0">
                    @if ($isCalculated)
                    <div
                        class="text-light-muted text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Estimated Subtotal
                    </div>
                    <div
                        class="text-brand-secondary mt-1 text-2xl font-black dark:text-teal-400"
                    >
                        Rp. {{ number_format($calcSubtotal) }}
                    </div>
                    <div
                        class="text-light-muted mt-0.5 text-[10px] font-bold dark:text-gray-400"
                    >
                        Unit Price: Rp. {{ number_format($calcPrice) }}{{
                        $unitPriceSuffix }}
                    </div>
                    @elseif ($calcError)
                    <span
                        class="text-red-550 dark:text-red-405 flex items-center text-xs font-bold"
                    >
                        <svg
                            class="mr-1.5 h-4 w-4 shrink-0 text-red-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            ></path>
                        </svg>
                        {{ $calcError }}
                    </span>
                    @else
                    <div
                        class="text-light-muted text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Estimated Subtotal
                    </div>
                    <div
                        class="text-light-muted mt-1 text-2xl font-bold dark:text-gray-600"
                    >
                        Rp. --
                    </div>
                    @endif
                </div>
            </div>

            <button
                id="btn-add-calc-item"
                type="button"
                wire:click="addItem"
                @if(!$isCalculated)
                disabled
                @endif
                class="bg-brand-secondary hover:bg-brand-secondary/95 flex cursor-pointer items-center justify-center gap-2 rounded-xl px-6 py-2.5 text-xs font-bold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-40"
                data-loading:class="opacity-50 pointer-events-none"
            >
                <span
                    data-loading:inline-block
                    class="hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
                ></span>
                + Add Item to Order
            </button>
        </div>
    </div>
</div>
