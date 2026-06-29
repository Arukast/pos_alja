<div class="space-y-6">
    <!-- Feedback alert -->
    <x-manager.feedback-alert
        :show="$showSuccessAlert"
        :message="$successMessage"
    />

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

            <form
                wire:submit.prevent="addMaterialStock"
                class="space-y-4 text-xs"
            >
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
                            "({$mat->dimensions})" : '' }} [{{
                            $mat->category?->name }}]
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

            <!-- Quick-Activate Existing Global Material -->
            @if ($inactiveMaterials->isNotEmpty() && auth()->user()->role ===
            'manager')
            <div
                class="border-light-border dark:border-surface-300/30 mt-6 border-t pt-4"
            >
                <label
                    class="text-light-muted mb-1.5 block text-xs font-bold dark:text-gray-400"
                >
                    Quick-Activate Existing Global Material:
                </label>
                <div class="flex gap-2">
                    <select
                        id="quick-activate-select"
                        wire:model="duplicateGlobalMaterialId"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs font-semibold"
                    >
                        <option value="">
                            -- Select Inactive Global Material --
                        </option>
                        @foreach ($inactiveMaterials as $imat)
                        <option value="{{ $imat->id }}">
                            {{ $imat->name }} (Category: {{
                            $imat->category?->name }})
                        </option>
                        @endforeach
                    </select>
                    <button
                        type="button"
                        wire:click="activateExistingMaterial"
                        class="bg-brand-primary hover:bg-brand-primary/95 cursor-pointer rounded-xl px-4 py-2 text-xs font-bold whitespace-nowrap text-white disabled:cursor-not-allowed disabled:opacity-50"
                        @disabled(!$duplicateGlobalMaterialId)
                    >
                        Activate
                    </button>
                </div>
            </div>
            @endif
        </div>

        <!-- Material Inventory Levels list -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
        >
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        <!-- prettier-ignore -->
                        Material Inventory Levels {{ $branchId === 'all' ? '(Global Catalog)' : '' }}
                    </h3>
                    <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                        Stock balances with low stock highlight flags and
                        configuration controls.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <label
                        class="flex cursor-pointer items-center gap-1.5 text-xs select-none"
                    >
                        <input
                            type="checkbox"
                            wire:model.live="showDeleted"
                            class="text-brand-primary focus:ring-brand-primary/30 rounded border-gray-300"
                        />
                        <span
                            class="text-light-muted font-semibold dark:text-gray-400"
                        >
                            Show Deleted Only
                        </span>
                    </label>
                    <button
                        type="button"
                        wire:click="openMaterialModal"
                        class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary cursor-pointer rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                    >
                        Register Material
                    </button>
                </div>
            </div>

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
                            @if ($branchId === 'all')
                            <th class="px-4 py-3">Branch</th>
                            @endif
                            <th class="px-4 py-3">Min Stock</th>
                            <th class="px-4 py-3 text-right">Current Stock</th>
                            <th class="rounded-r-xl px-4 py-3 text-center">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                    >
                        @foreach ($materialsList as $mItem)
                        <!-- prettier-ignore -->
                        @php $isLow = !$mItem->trashed() && $mItem->current_stock <= $mItem->min_stock; @endphp
                        <!-- prettier-ignore -->
                        <tr class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150 {{ $mItem->trashed() ? 'bg-red-500/5 text-gray-450 dark:bg-red-950/10 dark:text-gray-500 border-l-4 border-gray-400' : ($isLow ? 'bg-red-500/5 dark:bg-red-500/10 text-red-650 dark:text-red-400 font-semibold border-l-4 border-red-500' : 'even:bg-light-bg/50 dark:even:bg-surface-500/20') }}">
                            <td class="text-light-text px-4 py-3 font-bold dark:text-white">
                                {{ $mItem->name }}
                                <!-- prettier-ignore -->
                                @if ($mItem->dimensions)
                                <span class="text-light-muted text-[9px] font-semibold dark:text-gray-400">
                                    ({{ $mItem->dimensions }})
                                </span>
                                @endif
                                <!-- prettier-ignore -->
                                @if ($mItem->trashed())
                                <span class="ml-1.5 inline-block rounded bg-red-100 text-red-800 dark:bg-red-950/30 dark:text-red-400 px-1.5 py-0.5 text-[8px] font-black uppercase">
                                    Deleted
                                </span>
                                @endif
                            </td>
                            @if ($branchId === 'all')
                            <td class="px-4 py-3 font-medium text-gray-500 dark:text-gray-300">
                                {{ $mItem->branch_name ?? 'Global' }}
                            </td>
                            @endif
                            <td class="px-4 py-3">{{ $mItem->min_stock ?? '-' }}</td>
                            <!-- prettier-ignore -->
                            <td class="text-light-text px-4 py-3 text-right font-extrabold dark:text-white">
                                {{ $mItem->current_stock ?? '0' }}
                                <!-- prettier-ignore -->
                                @if ($isLow)
                                <span class="ml-1 inline-block animate-pulse rounded bg-red-500/10 px-1.5 py-0.5 text-[8px] font-black tracking-wider text-red-600 uppercase dark:bg-red-950/30 dark:text-red-400">
                                    Low
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if ($mItem->trashed())
                                    <button
                                        type="button"
                                        wire:click="restoreMaterial('{{ $mItem->id }}')"
                                        class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 cursor-pointer font-bold transition-colors"
                                    >
                                        Restore
                                    </button>
                                    @else
                                    <button
                                        type="button"
                                        wire:click="editMaterial('{{ $mItem->id }}')"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 cursor-pointer font-bold transition-colors"
                                    >
                                        Edit
                                    </button>
                                    <span class="text-gray-350 dark:text-gray-600">|</span>
                                    <button
                                        type="button"
                                        wire:click="deleteMaterial('{{ $mItem->id }}')"
                                        onclick="confirm('Are you sure you want to delete this material catalog entry?') || event.stopImmediatePropagation()"
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 cursor-pointer font-bold transition-colors"
                                    >
                                        Delete
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('livewire.dashboards.partials.manager._material_modal')
</div>
