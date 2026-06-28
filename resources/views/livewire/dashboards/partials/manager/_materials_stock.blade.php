<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Stock Restock form -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 h-fit rounded-2xl border bg-white p-6 shadow-sm"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            Material Restocking
        </h3>
        <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
            Replenish material sheet or banner rolls inventory levels.
        </p>

        <form wire:submit.prevent="addMaterialStock" class="space-y-4 text-xs">
            <div>
                <label
                    class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >
                    Select Material
                </label>
                <select
                    id="restock-material-id"
                    wire:model="restockForm.selectedMaterialId"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    required
                >
                    <option
                        value=""
                        class="text-light-muted dark:text-gray-400"
                    >
                        -- Choose Material to Restock --
                    </option>
                    @foreach ($allMaterials as $mat)
                    <option
                        value="{{ $mat->id }}"
                        class="text-light-text dark:text-gray-200"
                    >
                        {{ $mat->name }} {{ $mat->dimensions ?
                        "({$mat->dimensions})" : '' }} [{{ $mat->category }}]
                    </option>
                    @endforeach
                </select>
                @error('restockForm.selectedMaterialId')
                <span
                    class="text-red-550 text-[10px] font-bold dark:text-red-400"
                >
                    {{ $message }}
                </span>
                @enderror
            </div>

            <div>
                <label
                    class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >
                    Added Restock Qty
                </label>
                <input
                    id="restock-qty"
                    type="number"
                    step="any"
                    min="0.01"
                    wire:model="restockForm.addStockQuantity"
                    placeholder="e.g. 500 or 12.5"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    required
                />
                @error('restockForm.addStockQuantity')
                <span
                    class="text-red-550 text-[10px] font-bold dark:text-red-400"
                >
                    {{ $message }}
                </span>
                @enderror
            </div>

            <div>
                <label
                    class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >
                    Restocking Notes
                </label>
                <textarea
                    id="restock-notes"
                    wire:model="restockForm.addStockNotes"
                    placeholder="Restock batch invoice number, vendor name, etc..."
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-20 w-full resize-none rounded-xl border bg-white px-3 py-2 transition-colors duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                ></textarea>
                @error('restockForm.addStockNotes')
                <span
                    class="text-red-550 text-[10px] font-bold dark:text-red-400"
                >
                    {{ $message }}
                </span>
                @enderror
            </div>

            <button
                id="btn-submit-restock"
                type="submit"
                class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary w-full cursor-pointer rounded-xl py-2.5 text-[11px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98] data-loading:cursor-not-allowed data-loading:opacity-50"
                data-loading-target="addMaterialStock"
            >
                Register Restock Entry
            </button>
        </form>
    </div>

    <!-- Material Inventory Levels list -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            Material Inventory Levels
        </h3>
        <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
            Stock balances with low stock highlight flags.
        </p>

        <div
            class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
        >
            <table
                class="text-light-muted dark:text-gray-455 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="rounded-l-xl px-4 py-3">Material</th>
                        <th class="px-4 py-3">Min Stock</th>
                        <th class="rounded-r-xl px-4 py-3 text-right">
                            Current Stock
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @foreach ($materialsList as $mItem)
                    <!-- prettier-ignore -->
                    @php $isLow = $mItem->current_stock <= $mItem->min_stock; @endphp
                    <!-- prettier-ignore -->
                    <tr class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150 {{ $isLow ? 'bg-red-500/5 dark:bg-red-500/10 text-red-650 dark:text-red-400 font-semibold border-l-4 border-red-500' : 'even:bg-light-bg/50 dark:even:bg-surface-500/20' }}">
                        <td class="text-light-text px-4 py-3 font-bold dark:text-white">
                            {{ $mItem->name }}
                            <!-- prettier-ignore -->
                            @if ($mItem->dimensions)
                            <span class="text-light-muted text-[9px] font-semibold dark:text-gray-400">
                                ({{ $mItem->dimensions }})
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $mItem->min_stock }}</td>
                        <!-- prettier-ignore -->
                        <td class="text-light-text px-4 py-3 text-right font-extrabold dark:text-white">
                            {{ $mItem->current_stock }}
                            <!-- prettier-ignore -->
                            @if ($isLow)
                            <span class="ml-1 inline-block animate-pulse rounded bg-red-500/10 px-1.5 py-0.5 text-[8px] font-black tracking-wider text-red-600 uppercase dark:bg-red-950/30 dark:text-red-400">
                                Low
                            </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
