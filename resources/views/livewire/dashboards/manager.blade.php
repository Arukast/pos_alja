<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- Header banner -->
    <x-brand-header-banner
        badge="Branch Manager Portal"
        title="Main Branch Overview"
        description="Manage your shop inventory, adjust pricing engine parameters, oversee printing machinery, and coordinate staff."
        theme="manager"
    />

    <!-- Feedback alert -->
    @if ($showSuccessAlert)
    <div
        class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-50 p-4 text-emerald-800 backdrop-blur-sm transition dark:bg-emerald-950/20 dark:text-emerald-300"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center">
            <svg
                class="mr-2 h-5 w-5 text-emerald-500 dark:text-emerald-400"
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
            <span
                class="text-sm font-bold text-emerald-700 dark:text-emerald-300"
                >{{ $successMessage }}</span
            >
        </div>
        <button
            type="button"
            @click="show = false; $wire.set('showSuccessAlert', false)"
            class="text-emerald-500 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
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

    <!-- Tabs Menu -->
    <div
        class="dark:border-surface-300/30 border-light-border mb-4 flex gap-2 border-b"
    >
        <button
            id="tab-inventory"
            wire:click="setTab('inventory')"
            class="py-2.5 px-4 text-xs font-bold border-b-2 transition-all duration-200 {{ $activeTab === 'inventory' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-light-muted hover:text-light-text dark:text-gray-400 dark:hover:text-white' }}"
        >
            Overview & Inventory
        </button>
        <button
            id="tab-users"
            wire:click="setTab('users')"
            class="py-2.5 px-4 text-xs font-bold border-b-2 transition-all duration-200 {{ $activeTab === 'users' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-light-muted hover:text-light-text dark:text-gray-400 dark:hover:text-white' }}"
        >
            User Management
        </button>
    </div>

    @if ($activeTab === 'inventory')
    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat card 1 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Stock Materials
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ $materialCount }}
                    </h3>
                </div>
                <div
                    class="bg-brand-primary/10 text-brand-primary rounded-xl p-3"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400"
            >
                <svg
                    class="mr-1 h-4 w-4"
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
                <span>Active stock tracking enabled</span>
            </div>
        </div>

        <!-- Stat card 2 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Printers & Machinery
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ $machineCount }}
                    </h3>
                </div>
                <div
                    class="bg-brand-secondary/10 text-brand-secondary rounded-xl p-3"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="text-brand-secondary mt-4 flex items-center text-xs font-bold"
            >
                <span
                    class="bg-brand-secondary mr-2 h-2 w-2 animate-ping rounded-full"
                ></span>
                <span>All machines online</span>
            </div>
        </div>

        <!-- Stat card 3 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Active Staff
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ $staffCount }}
                    </h3>
                </div>
                <div
                    class="bg-brand-primary/10 text-brand-primary rounded-xl p-3"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="text-light-muted mt-4 flex items-center text-xs font-semibold dark:text-gray-400"
            >
                <span>Managed across 5 roles</span>
            </div>
        </div>

        <!-- Stat card 4 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Pricing Engine Rows
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ $pricingRulesCount }}
                    </h3>
                </div>
                <div
                    class="rounded-xl bg-emerald-500/10 p-3 text-emerald-500 dark:text-emerald-400"
                >
                    <svg
                        class="h-6 w-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400"
            >
                <span>Active price rule matrix</span>
            </div>
        </div>
    </div>

    <!-- Management actions and recent stock ledger -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Stock Restock Payout form -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
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
                        >Select Material</label
                    >
                    <select
                        id="restock-material-id"
                        wire:model="selectedMaterialId"
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
                            "({$mat->dimensions})" : '' }} [{{ $mat->category
                            }}]
                        </option>
                        @endforeach
                    </select>
                    @error('selectedMaterialId')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Added Restock Qty</label
                    >
                    <input
                        id="restock-qty"
                        type="number"
                        step="any"
                        min="0.01"
                        wire:model="addStockQuantity"
                        placeholder="e.g. 500 or 12.5"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        required
                    />
                    @error('addStockQuantity')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Restocking Notes</label
                    >
                    <textarea
                        id="restock-notes"
                        wire:model="addStockNotes"
                        placeholder="Restock batch invoice number, vendor name, etc..."
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-20 w-full resize-none rounded-xl border bg-white px-3 py-2 transition-colors duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    ></textarea>
                    @error('addStockNotes')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <button
                    id="btn-submit-restock"
                    type="submit"
                    class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary w-full cursor-pointer rounded-xl py-2.5 text-[11px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                >
                    Register Restock Entry
                </button>
            </form>
        </div>

        <!-- Recent Stock ledger logs -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Recent Inventory Ledger Logs
            </h3>
            <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
                Stock ledger activity adjustments logs.
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
                            <th class="rounded-l-xl px-4 py-3">Date</th>
                            <th class="px-4 py-3">Material</th>
                            <th class="px-4 py-3">Initial</th>
                            <th class="px-4 py-3">Added</th>
                            <th class="px-4 py-3">Wasted</th>
                            <th class="rounded-r-xl px-4 py-3 text-right">
                                Ledger Remaining
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                    >
                        @forelse ($stockLogs as $log)
                        <tr
                            class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                        >
                            <td
                                class="text-light-muted px-4 py-3 font-medium dark:text-gray-400"
                            >
                                {{ $log->logged_at }}
                            </td>
                            <td
                                class="text-light-text px-4 py-3 font-bold dark:text-white"
                            >
                                {{ $log->material->name ?? '' }}
                            </td>
                            <td
                                class="text-light-text px-4 py-3 font-semibold dark:text-gray-300"
                            >
                                {{ $log->initial_stock }}
                            </td>
                            <td
                                class="px-4 py-3 font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                +{{ $log->added_stock }}
                            </td>
                            <td
                                class="text-red-550 px-4 py-3 font-bold dark:text-red-400"
                            >
                                {{ $log->wasted_stock }}
                            </td>
                            <td
                                class="text-brand-secondary px-4 py-3 text-right font-extrabold"
                            >
                                {{ $log->remaining_stock }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="6"
                                class="text-light-muted py-6 text-center dark:text-gray-500"
                            >
                                No stock ledger entries registered.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- DETAILED STOCK LEVEL WITH LOW STOCK WARN & RECENT EXPENSES -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Stock Levels Ledger -->
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
                        @foreach ($materialsList as $mItem) @php $isLow =
                        $mItem->current_stock <= $mItem->min_stock; @endphp
                        <tr
                            class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150 {{ $isLow ? 'bg-red-500/5 dark:bg-red-500/10 text-red-650 dark:text-red-400 font-semibold border-l-4 border-red-500' : 'even:bg-light-bg/50 dark:even:bg-surface-500/20' }}"
                        >
                            <td
                                class="text-light-text px-4 py-3 font-bold dark:text-white"
                            >
                                {{ $mItem->name }} @if ($mItem->dimensions)
                                <span
                                    class="text-light-muted text-[9px] font-semibold dark:text-gray-400"
                                    >({{ $mItem->dimensions }})</span
                                >
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $mItem->min_stock }}</td>
                            <td
                                class="text-light-text px-4 py-3 text-right font-extrabold dark:text-white"
                            >
                                {{ $mItem->current_stock }} @if ($isLow)
                                <span
                                    class="ml-1 inline-block animate-pulse rounded bg-red-500/10 px-1.5 py-0.5 text-[8px] font-black tracking-wider text-red-600 uppercase dark:bg-red-950/30 dark:text-red-400"
                                    >Low</span
                                >
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Expenses log -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
        >
            <h3 class="text-light-text mb-1 text-lg font-bold dark:text-white">
                Expenses Ledger
            </h3>
            <p class="text-light-muted mb-4 text-xs dark:text-gray-400">
                Drawer payouts audit tracking logs.
            </p>

            <div class="max-h-[400px] space-y-3.5 overflow-y-auto pr-1">
                @forelse ($expenses as $exp)
                <div
                    class="border-light-border dark:border-surface-300/30 dark:bg-surface-600/50 hover:border-brand-primary/25 dark:hover:border-surface-200/45 rounded-xl border bg-white p-3.5 text-xs transition-colors duration-150"
                >
                    <div class="flex items-start justify-between">
                        <span
                            class="text-light-text font-extrabold uppercase dark:text-white"
                            >{{ $exp->cashier_name }}</span
                        >
                        <span
                            class="font-extrabold text-red-600 dark:text-red-400"
                            >Rp. {{ number_format($exp->amount) }}</span
                        >
                    </div>
                    <div
                        class="text-light-muted dark:text-gray-405 mt-1 flex justify-between text-[10px] font-medium"
                    >
                        <span>{{ $exp->incurred_at }}</span>
                        <span class="text-brand-secondary font-bold uppercase"
                            >{{ $exp->type }}</span
                        >
                    </div>
                    <p
                        class="text-light-muted mt-2 text-[10px] leading-relaxed font-medium italic dark:text-gray-400"
                    >
                        {{ $exp->description }}
                    </p>
                </div>
                @empty
                <div
                    class="text-light-muted py-6 text-center text-xs dark:text-gray-500"
                >
                    No expenses recorded.
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @elseif ($activeTab === 'users') {{-- Shared UserManagement sub-component
    --}}
    <livewire:admin.user-management
        mode="manager"
        wire:key="manager-user-management"
    />
    @endif
</div>
