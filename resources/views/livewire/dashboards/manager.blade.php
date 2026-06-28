<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- Header banner (Only on Dashboard Overview tab) -->
    @if ($activeTab === 'dashboard')
    <x-brand-header-banner
        badge="Branch Manager Portal"
        title="Main Branch Overview"
        description="Manage your shop inventory, adjust pricing engine parameters, oversee printing machinery, and coordinate staff."
        theme="manager"
    />
    @endif

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

    <!-- 1. DASHBOARD TAB -->
    @if ($activeTab === 'dashboard')
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

    <!-- Active Overview Ledger Tables -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Recent Stock ledger logs -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
        >
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        Recent Inventory Ledger Logs
                    </h3>
                    <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                        Stock ledger activity adjustments logs.
                    </p>
                </div>
                <a
                    href="?tab=ledgers"
                    wire:navigate
                    class="text-brand-primary hover:text-brand-primary/85 text-xs font-bold"
                >
                    View All
                </a>
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

        <!-- Recent Expenses log -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
        >
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        Expenses Ledger
                    </h3>
                    <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                        Drawer payouts audit tracking logs.
                    </p>
                </div>
                <a
                    href="?tab=ledgers"
                    wire:navigate
                    class="text-brand-primary hover:text-brand-primary/85 text-xs font-bold"
                >
                    View All
                </a>
            </div>

            <div class="max-h-[300px] space-y-3.5 overflow-y-auto pr-1">
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
    @endif

    <!-- 2. MATERIALS & STOCK TAB -->
    @if ($activeTab === 'materials')
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
    </div>
    @endif

    <!-- 3. STOCK LEDGERS TAB -->
    @if ($activeTab === 'ledgers')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Stock Ledger Logs -->
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

        <!-- Expenses Ledger -->
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
    @endif

    <!-- 4. DISPLAY & FINISHING TAB -->
    @if ($activeTab === 'finishing')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Attribute CRUD form -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 h-fit rounded-2xl border bg-white p-6 shadow-sm"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                {{ $attributeId ? 'Edit Attribute' : 'Create New Attribute' }}
            </h3>
            <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
                Manage display, finishing, and cutting parameters.
            </p>

            <form wire:submit.prevent="saveAttribute" class="space-y-4 text-xs">
                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Attribute Name</label
                    >
                    <input
                        type="text"
                        wire:model="attributeName"
                        placeholder="e.g. Glossy Lamination, Ring Eyelets"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        required
                    />
                    @error('attributeName')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Attribute Type</label
                    >
                    <select
                        wire:model="attributeType"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold transition-colors duration-200 focus:ring-2 focus:outline-none"
                        required
                    >
                        <option value="finishing">
                            Finishing (Laminating, Lipat, etc.)
                        </option>
                        <option value="cut">
                            Cut (Die Cut, Half Cut, Potong Pas, etc.)
                        </option>
                        <option value="display">
                            Display (X-Banner, Roll Up, Bingkai, etc.)
                        </option>
                    </select>
                    @error('attributeType')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div class="flex gap-2 pt-2">
                    <button
                        type="submit"
                        class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary flex-1 cursor-pointer rounded-xl py-2.5 text-[11px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                    >
                        {{ $attributeId ? 'Update Attribute' : 'Create
                        Attribute' }}
                    </button>
                    @if ($attributeId)
                    <button
                        type="button"
                        wire:click="resetAttributeForm"
                        class="dark:bg-surface-500 dark:hover:bg-surface-400 rounded-xl bg-gray-100 px-3.5 py-2.5 text-[11px] font-bold text-gray-700 transition-all duration-200 hover:bg-gray-200 dark:text-gray-200"
                    >
                        Cancel
                    </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Attributes listing -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Display & Finishing Master Data
            </h3>
            <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
                All registered system attributes for order customization.
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
                            <th class="rounded-l-xl px-4 py-3">
                                Attribute Name
                            </th>
                            <th class="px-4 py-3">Type</th>
                            <th class="rounded-r-xl px-4 py-3 text-right">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                    >
                        @forelse ($allAttributes as $attr)
                        <tr
                            class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                        >
                            <td
                                class="text-light-text px-4 py-3 font-bold dark:text-white"
                            >
                                {{ $attr->name }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($attr->type === 'finishing')
                                <span
                                    class="rounded bg-blue-500/10 px-2 py-0.5 text-[10px] font-bold text-blue-600 uppercase dark:text-blue-400"
                                    >Finishing</span
                                >
                                @elseif ($attr->type === 'cut')
                                <span
                                    class="rounded bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-600 uppercase dark:text-amber-400"
                                    >Cut</span
                                >
                                @else
                                <span
                                    class="rounded bg-purple-500/10 px-2 py-0.5 text-[10px] font-bold text-purple-600 uppercase dark:text-purple-400"
                                    >Display</span
                                >
                                @endif
                            </td>
                            <td
                                class="space-x-2 px-4 py-3 text-right font-bold"
                            >
                                <button
                                    wire:click="editAttribute('{{ $attr->id }}')"
                                    class="text-brand-primary hover:text-brand-primary/80 transition"
                                >
                                    Edit
                                </button>
                                <button
                                    onclick="
                                        confirm(
                                            'Are you sure you want to delete this attribute?'
                                        ) || event.stopImmediatePropagation()
                                    "
                                    wire:click="deleteAttribute('{{ $attr->id }}')"
                                    class="hover:text-red-650 text-red-500 transition"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="3"
                                class="text-light-muted py-6 text-center dark:text-gray-500"
                            >
                                No display/finishing attributes registered.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- 5. PRICING MATRICES TAB -->
    @if ($activeTab === 'pricing')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Pricing matrix rule form -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 h-fit rounded-2xl border bg-white p-6 shadow-sm"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                {{ $pricingId ? 'Edit Pricing Rule' : 'Create Pricing Rule' }}
            </h3>
            <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
                Define product base pricing matrices by materials and client
                tiers.
            </p>

            <form wire:submit.prevent="savePricing" class="space-y-4 text-xs">
                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Select Product</label
                    >
                    <select
                        wire:model="productId"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                        required
                    >
                        <option value="">-- Choose Product --</option>
                        @foreach ($productsList as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('productId')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Select Material</label
                    >
                    <select
                        wire:model="materialId"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                        required
                    >
                        <option value="">-- Choose Material --</option>
                        @foreach ($allMaterials as $m)
                        <option value="{{ $m->id }}">
                            {{ $m->name }} {{ $m->dimensions ?
                            "({$m->dimensions})" : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('materialId')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Customer Type</label
                    >
                    <select
                        wire:model="customerType"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 font-semibold"
                        required
                    >
                        <option value="Reguler">Reguler</option>
                        <option value="Mitra">Mitra</option>
                    </select>
                    @error('customerType')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Minimum Quantity</label
                    >
                    <input
                        type="number"
                        wire:model="minQty"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors focus:ring-2 focus:outline-none"
                        required
                    />
                    @error('minQty')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Cost (HPP)</label
                    >
                    <input
                        type="number"
                        step="any"
                        wire:model="cost"
                        placeholder="e.g. 15000"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors focus:ring-2 focus:outline-none"
                        required
                    />
                    @error('cost')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Selling Price</label
                    >
                    <input
                        type="number"
                        step="any"
                        wire:model="price"
                        placeholder="e.g. 25000"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 transition-colors focus:ring-2 focus:outline-none"
                        required
                    />
                    @error('price')
                    <span
                        class="text-red-550 text-[10px] font-bold dark:text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div class="flex gap-2 pt-2">
                    <button
                        type="submit"
                        class="bg-brand-primary hover:bg-brand-primary/95 shadow-glow-primary flex-1 cursor-pointer rounded-xl py-2.5 text-[11px] font-bold tracking-wider text-white uppercase shadow-sm transition-all duration-200 active:scale-[0.98]"
                    >
                        {{ $pricingId ? 'Update Rule' : 'Create Rule' }}
                    </button>
                    @if ($pricingId)
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
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Active Pricing Engine Matrices
            </h3>
            <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
                Base prices matched by client tiers and dimensions.
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
                            <th class="rounded-l-xl px-4 py-3">Product</th>
                            <th class="px-4 py-3">Material</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3 text-center">Min Qty</th>
                            <th class="px-4 py-3 text-right">Cost (HPP)</th>
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
                        <tr
                            class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                        >
                            <td
                                class="text-light-text px-4 py-3 font-bold dark:text-white"
                            >
                                {{ $pRule->product->name ?? '' }}
                            </td>
                            <td
                                class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300"
                            >
                                {{ $pRule->material->name ?? '' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="dark:bg-surface-500 rounded bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-800 uppercase dark:text-gray-200"
                                >
                                    {{ $pRule->customer_type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-bold">
                                {{ $pRule->min_qty }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium">
                                Rp. {{ number_format($pRule->cost) }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold">
                                Rp. {{ number_format($pRule->price) }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-extrabold text-emerald-600 dark:text-emerald-400"
                            >
                                Rp. {{ number_format($pRule->profit) }}
                            </td>
                            <td
                                class="space-x-2 px-4 py-3 text-right font-bold"
                            >
                                <button
                                    wire:click="editPricing('{{ $pRule->id }}')"
                                    class="text-brand-primary hover:text-brand-primary/80 transition"
                                >
                                    Edit
                                </button>
                                <button
                                    onclick="
                                        confirm(
                                            'Are you sure you want to delete this pricing matrix rule?'
                                        ) || event.stopImmediatePropagation()
                                    "
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
    </div>
    @endif

    <!-- 5a. DESIGNER LEADERBOARD TAB -->
    @if ($activeTab === 'leaderboard')
    <!-- Designer Performance Leaderboard Tab -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Top Performer (Omset) -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Top Performer (Omset)
                    </p>
                    <h3
                        class="text-light-text mt-1 max-w-[150px] truncate text-base font-extrabold dark:text-white"
                    >
                        {{ $topRevenue->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="bg-amber-550/10 text-amber-550 rounded-xl p-3">
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
                            d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2zm0 0h4m-4 0H8m12-1a3 3 0 01-3 3h-1m-4-3a3 3 0 00-3 3H7m0 0a3 3 0 01-3-3V7a3 3 0 013-3h1m4 3a3 3 0 003-3h1"
                        />
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-emerald-600 dark:text-emerald-400"
            >
                <span
                    >Rp. {{ $topRevenue ?
                    number_format($topRevenue->total_value, 0, ',', '.') : '0'
                    }}</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                    >{{ $topRevenue ? $topRevenue->orders_count : 0 }}
                    orders</span
                >
            </div>
        </div>

        <!-- Card 2: Order Champion -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Order Champion
                    </p>
                    <h3
                        class="text-light-text mt-1 max-w-[150px] truncate text-base font-extrabold dark:text-white"
                    >
                        {{ $orderChampion->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="rounded-xl bg-blue-500/10 p-3 text-blue-500">
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
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.907c.961 0 1.36 1.246.588 1.81l-3.97 2.883a1 1 0 00-.364 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.971-2.883a1 1 0 00-1.175 0l-3.97 2.883c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.364-1.118l-3.97-2.883c-.773-.564-.375-1.81.587-1.81h4.907a1 1 0 00.95-.69l1.519-4.674z"
                        />
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-blue-600 dark:text-blue-400"
            >
                <span
                    >{{ $orderChampion ? $orderChampion->orders_count : 0 }}
                    Orders</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                    >Rp. {{ $orderChampion ?
                    number_format($orderChampion->total_value, 0, ',', '.') :
                    '0' }}</span
                >
            </div>
        </div>

        <!-- Card 3: Highest AOV -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Highest Avg Ticket (AOV)
                    </p>
                    <h3
                        class="text-light-text mt-1 max-w-[150px] truncate text-base font-extrabold dark:text-white"
                    >
                        {{ $topAov->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="rounded-xl bg-indigo-500/10 p-3 text-indigo-500">
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
                            d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"
                        />
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-indigo-600 dark:text-indigo-400"
            >
                <span
                    >Rp. {{ $topAov ? number_format($topAov->aov, 0, ',', '.') :
                    '0' }}</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                    >Average AOV</span
                >
            </div>
        </div>

        <!-- Card 4: Accuracy Leader -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Accuracy Leader
                    </p>
                    <h3
                        class="text-light-text mt-1 max-w-[150px] truncate text-base font-extrabold dark:text-white"
                    >
                        {{ $accuracyLeader->name ?? 'N/A' }}
                    </h3>
                </div>
                <div class="text-emerald-555 rounded-xl bg-emerald-500/10 p-3">
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
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                        />
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center justify-between text-xs font-semibold text-emerald-600 dark:text-emerald-400"
            >
                <span
                    >{{ $accuracyLeader ? $accuracyLeader->waste_count : 0 }}
                    wasted log(s)</span
                >
                <span
                    class="text-light-muted text-[10px] font-normal dark:text-gray-400"
                    >Lowest design errors</span
                >
            </div>
        </div>
    </div>

    <!-- Main leaderboard grid content -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Detailed rankings table -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Designer Rankings
            </h3>
            <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                Detailed performance metrics sorted by gross revenue generated.
            </p>
            <div class="mt-6 overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr
                            class="border-light-border dark:border-surface-300/20 text-light-muted border-b text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >
                            <th class="pb-3 pl-4">Rank</th>
                            <th class="pb-3">Designer</th>
                            <th class="pb-3">Branch</th>
                            <th class="pb-3 text-right">Orders</th>
                            <th class="pb-3 text-right">Total Revenue</th>
                            <th class="pb-3 text-right">Avg Order Value</th>
                            <th class="pr-4 pb-3 text-right">Design Waste</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-light-border dark:divide-surface-300/10 divide-y"
                    >
                        @forelse($leaders as $index => $leader) @php $rankColors
                        = [ 0 => 'bg-amber-500 text-white', 1 => 'bg-slate-400
                        text-white', 2 => 'bg-amber-700 text-white', ];
                        $rankBadge = $rankColors[$index] ?? 'bg-gray-200
                        dark:bg-surface-300/30 text-light-text dark:text-white';
                        @endphp
                        <tr
                            class="hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                        >
                            <td class="py-4 pl-4">
                                <span
                                    class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold {{ $rankBadge }}"
                                >
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="py-4">
                                <div
                                    class="text-light-text font-bold dark:text-white"
                                >
                                    {{ $leader->name }}
                                </div>
                                <div
                                    class="text-light-muted text-[10px] dark:text-gray-400"
                                >
                                    CS & Design Specialist
                                </div>
                            </td>
                            <td
                                class="text-light-muted py-4 font-medium dark:text-gray-400"
                            >
                                {{ $leader->branch_name ?? 'Global' }}
                            </td>
                            <td
                                class="text-light-text py-4 text-right font-semibold dark:text-white"
                            >
                                {{ $leader->orders_count }}
                            </td>
                            <td
                                class="py-4 text-right font-semibold text-emerald-600 dark:text-emerald-400"
                            >
                                Rp. {{ number_format($leader->total_value, 0,
                                ',', '.') }}
                            </td>
                            <td
                                class="py-4 text-right font-semibold text-indigo-600 dark:text-indigo-400"
                            >
                                Rp. {{ number_format($leader->aov, 0, ',', '.')
                                }}
                            </td>
                            <td class="py-4 pr-4 text-right">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $leader->waste_count > 0 ? 'bg-red-500/10 text-red-600 dark:text-red-400' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' }}"
                                >
                                    {{ $leader->waste_count }} error(s)
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="7"
                                class="text-light-muted py-8 text-center text-xs dark:text-gray-500"
                            >
                                No designer metrics recorded for this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Revenue contribution chart -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Revenue Share
            </h3>
            <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                Proportion of gross revenue generated by each designer.
            </p>
            <div class="mt-6 space-y-4">
                @php $totalRevenueSum = $leaders->sum('total_value'); @endphp
                @forelse($leaders as $leader) @php $percentage =
                $totalRevenueSum > 0 ? ($leader->total_value / $totalRevenueSum)
                * 100 : 0; @endphp
                <div class="space-y-1">
                    <div class="flex items-center justify-between text-xs">
                        <span
                            class="text-light-text max-w-[120px] truncate font-bold dark:text-white"
                            >{{ $leader->name }}</span
                        >
                        <span
                            class="text-light-muted font-semibold dark:text-gray-400"
                            >{{ number_format($percentage, 1) }}%</span
                        >
                    </div>
                    <div
                        class="dark:bg-surface-300/20 h-2 w-full overflow-hidden rounded-full bg-gray-100"
                    >
                        <div
                            class="bg-brand-primary h-full rounded-full transition-all duration-500"
                            style="width: {{ $percentage }}%"
                        ></div>
                    </div>
                    <div
                        class="text-right text-[10px] font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        Rp. {{ number_format($leader->total_value, 0, ',', '.')
                        }}
                    </div>
                </div>
                @empty
                <div
                    class="text-light-muted py-6 text-center text-xs dark:text-gray-500"
                >
                    No share data available.
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <!-- 6. USER MANAGEMENT SUB-COMPONENT -->
    @if ($activeTab === 'users')
    <livewire:admin.user-management
        mode="manager"
        wire:key="manager-user-management"
    />
    @endif
</div>
