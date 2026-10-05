<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <!-- Pricing matrix rule form -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 h-fit rounded-2xl border bg-white p-6 shadow-sm"
    >
        <h3 class="text-light-text text-lg font-bold dark:text-white">
            {{ $pricingForm->pricingId ? 'Edit Pricing Rule' : 'Create Pricing
            Rule' }}
        </h3>
        <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
            Define product base pricing matrices by materials and client tiers.
        </p>

        <form wire:submit.prevent="savePricing" class="space-y-4 text-xs">
            <div>
                <label
                    class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                >
                    Select Product
                </label>
                <select
                    wire:model="pricingForm.productId"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                    required
                >
                    <option value="">-- Choose Product --</option>
                    @foreach ($productsList as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                @error('pricingForm.productId')
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
                    Select Material
                </label>
                <select
                    wire:model="pricingForm.materialId"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                    required
                >
                    <option value="">-- Choose Material --</option>
                    @foreach ($allMaterials as $m)
                    <option value="{{ $m->id }}">
                        {{ $m->name }} {{ $m->dimensions ? "({$m->dimensions})"
                        : '' }}
                    </option>
                    @endforeach
                </select>
                @error('pricingForm.materialId')
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
                    Customer Type
                </label>
                <select
                    wire:model="pricingForm.customerType"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                    required
                >
                    <option value="Reguler">Reguler</option>
                    <option value="Mitra">Mitra</option>
                </select>
                @error('pricingForm.customerType')
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
                    Minimum Quantity
                </label>
                <input
                    type="number"
                    wire:model="pricingForm.minQty"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors focus:ring-2 focus:outline-none"
                    required
                />
                @error('pricingForm.minQty')
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
                    Selling Price
                </label>
                <input
                    type="number"
                    step="any"
                    wire:model="pricingForm.price"
                    placeholder="e.g. 25000"
                    class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors focus:ring-2 focus:outline-none"
                    required
                />
                @error('pricingForm.price')
                <span
                    class="text-red-550 text-[10px] font-bold dark:text-red-400"
                >
                    {{ $message }}
                </span>
                @enderror
            </div>

            <div class="flex gap-2 pt-2">
                <button
                    type="submit"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary flex-1 cursor-pointer rounded-xl py-2.5 text-[11px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98] data-loading:cursor-not-allowed data-loading:opacity-50"
                    data-loading-target="savePricing"
                >
                    {{ $pricingForm->pricingId ? 'Update Rule' : 'Create Rule'
                    }}
                </button>
                @if ($pricingForm->pricingId)
                <button
                    type="button"
                    wire:click="resetPricingForm"
                    class="dark:bg-surface-500 dark:hover:bg-surface-400 rounded-xl bg-gray-100 px-3.5 py-2.5 text-[11px] font-bold text-gray-700 transition-all duration-200 hover:bg-gray-200 dark:text-gray-200"
                >
                    Cancel
                </button>
                @endif
            </div>
        </form>
    </div>

    <!-- Pricing rules list -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
    >
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Active Pricing Engine Matrices
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Base prices matched by client tiers and dimensions.
                </p>
            </div>
            <div>
                <button
                    type="button"
                    wire:click="$set('showCloneModal', true)"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary cursor-pointer rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                >
                    Clone Tiers
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
                        <th class="rounded-l-xl px-4 py-3">Product</th>
                        <th class="px-4 py-3">Material</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-center">Min Qty</th>
                        <th class="px-4 py-3 text-right">HPP (Cost)</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th
                            class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400"
                        >
                            Profit
                        </th>
                        <th class="rounded-r-xl px-4 py-3 text-right">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($pricingMatrices as $pRule)
                    <!-- prettier-ignore -->
                    <tr class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150">
                        <td class="text-light-text px-4 py-3 font-bold dark:text-white">
                            {{ $pRule->product->name ?? '' }}
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">
                            {{ $pRule->material->name ?? '' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="dark:bg-surface-500 rounded bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-800 uppercase dark:text-gray-200">
                                {{ $pRule->customer_type }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-bold">
                            {{ $pRule->min_qty }}
                        </td>
                        <td class="px-4 py-3 text-right font-medium">
                            Rp. {{ number_format($pRule->cost > 0 ? $pRule->cost : ($pRule->material->cost ?? 0)) }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold">
                            Rp. {{ number_format($pRule->price) }}
                        </td>
                        <td class="px-4 py-3 text-right font-extrabold text-emerald-600 dark:text-emerald-400">
                            Rp. {{ number_format(max(0, $pRule->price - ($pRule->cost > 0 ? $pRule->cost : ($pRule->material->cost ?? 0)))) }}
                        </td>
                        <td class="space-x-2 px-4 py-3 text-right font-bold">
                            <button
                                wire:click="editPricing('{{ $pRule->id }}')"
                                class="text-brand-primary hover:text-brand-primary/80 transition"
                            >
                                Edit
                            </button>
                            <button
                                onclick="confirm('Are you sure you want to delete this pricing matrix rule?') || event.stopImmediatePropagation()"
                                wire:click="deletePricing('{{ $pRule->id }}')"
                                class="text-red-550 hover:text-red-650 transition"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="8"
                            class="text-light-muted py-6 text-center dark:text-gray-500"
                        >
                            No pricing matrix rules registered.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Clone Tiers Modal -->
    @if ($showCloneModal)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/55 p-4 backdrop-blur-xs"
    >
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 w-full max-w-md space-y-4 rounded-2xl border bg-white p-6 shadow-xl"
        >
            <div
                class="dark:border-surface-300/10 flex items-center justify-between border-b border-gray-100 pb-3"
            >
                <h3 class="text-light-text text-base font-bold dark:text-white">
                    Clone Pricing Tiers Bulk Tool
                </h3>
                <button
                    type="button"
                    wire:click="$set('showCloneModal', false)"
                    class="text-light-muted hover:text-light-text dark:text-gray-455 text-lg font-bold dark:hover:text-white"
                >
                    &times;
                </button>
            </div>

            <form
                wire:submit.prevent="clonePricingTiers"
                class="space-y-4 text-left text-xs"
            >
                <!-- Source configuration -->
                <div
                    class="dark:bg-surface-500/10 dark:border-surface-300/5 space-y-3 rounded-xl border border-gray-100 bg-gray-50/50 p-3"
                >
                    <div
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase"
                    >
                        Source Settings
                    </div>
                    <div>
                        <label
                            class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                            >Source Material</label
                        >
                        <select
                            wire:model="cloneSourceMaterialId"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                            required
                        >
                            <option value="">-- Choose Material --</option>
                            @foreach ($allMaterials as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                        @error('cloneSourceMaterialId')
                        <span
                            class="text-red-550 text-[10px] font-bold dark:text-red-400"
                            >{{ $message }}</span
                        >
                        @enderror
                    </div>

                    <div>
                        <label
                            class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                            >Source Customer Type</label
                        >
                        <select
                            wire:model="cloneSourceCustomerType"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                            required
                        >
                            <option value="Reguler">Reguler</option>
                            <option value="Mitra">Mitra</option>
                            <option value="Reseller">Reseller</option>
                        </select>
                    </div>
                </div>

                <!-- Target configuration -->
                <div
                    class="dark:bg-surface-500/10 dark:border-surface-300/5 space-y-3 rounded-xl border border-gray-100 bg-gray-50/50 p-3"
                >
                    <div
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase"
                    >
                        Target Settings
                    </div>
                    <div>
                        <label
                            class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                            >Target Material</label
                        >
                        <select
                            wire:model="cloneTargetMaterialId"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                            required
                        >
                            <option value="">-- Choose Material --</option>
                            @foreach ($allMaterials as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                            @endforeach
                        </select>
                        @error('cloneTargetMaterialId')
                        <span
                            class="text-red-550 text-[10px] font-bold dark:text-red-400"
                            >{{ $message }}</span
                        >
                        @enderror
                    </div>

                    <div>
                        <label
                            class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                            >Target Customer Type</label
                        >
                        <select
                            wire:model="cloneTargetCustomerType"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                            required
                        >
                            <option value="Reguler">Reguler</option>
                            <option value="Mitra">Mitra</option>
                            <option value="Reseller">Reseller</option>
                        </select>
                    </div>
                </div>

                <!-- Adjustments -->
                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Price Adjustment Percent (%)</label
                    >
                    <input
                        type="number"
                        step="0.01"
                        wire:model="cloneAdjustmentPercent"
                        placeholder="e.g. -10 for 10% discount, +5 for markup"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors focus:ring-2 focus:outline-none"
                        required
                    />
                    <span class="text-light-muted text-[10px]"
                        >Adjusts the selling price of duplicated rules. Cost
                        (HPP) will remain identical to the source tier
                        cost.</span
                    >
                    @error('cloneAdjustmentPercent')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div
                    class="dark:border-surface-300/10 flex items-center justify-end gap-3 border-t border-gray-100 pt-3"
                >
                    <button
                        type="button"
                        wire:click="$set('showCloneModal', false)"
                        class="border-light-border dark:border-surface-300/30 text-light-text dark:hover:bg-surface-500 rounded-xl border px-4 py-2 text-[10px] font-bold tracking-wider uppercase transition-colors hover:bg-gray-50 dark:text-white"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="bg-brand-primary hover:bg-brand-primary/95 rounded-xl px-4 py-2 text-[10px] font-bold tracking-wider text-white uppercase shadow-sm transition-all"
                    >
                        Clone Now
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
