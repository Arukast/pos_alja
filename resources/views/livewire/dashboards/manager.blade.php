<div class="space-y-6 text-gray-200">
    <!-- Header banner -->
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
        class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4 backdrop-blur-sm transition"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center">
            <svg
                class="mr-2 h-5 w-5 text-emerald-400"
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
            <span class="text-sm font-semibold text-emerald-300"
                >{{ $successMessage }}</span
            >
        </div>
        <button
            type="button"
            @click="show = false; $wire.set('showSuccessAlert', false)"
            class="text-emerald-400 hover:text-emerald-300"
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
        class="dark:border-surface-300/30 mb-2 flex gap-2 border-b border-gray-200"
    >
        <button
            wire:click="setTab('inventory')"
            class="py-2.5 px-4 text-xs font-semibold border-b-2 transition-all duration-200 {{ $activeTab === 'inventory' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-gray-400 hover:text-gray-200' }}"
        >
            Overview & Inventory
        </button>
        <button
            wire:click="setTab('users')"
            class="py-2.5 px-4 text-xs font-semibold border-b-2 transition-all duration-200 {{ $activeTab === 'users' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-gray-400 hover:text-gray-200' }}"
        >
            User Management
        </button>
    </div>

    @if ($activeTab === 'inventory')
    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat card 1 -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card hover:shadow-card-hover hover-lift relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Stock Materials
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
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
                class="mt-4 flex items-center text-xs font-semibold text-emerald-400"
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
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card hover:shadow-card-hover hover-lift relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Printers & Machinery
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
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
                class="text-brand-secondary mt-4 flex items-center text-xs font-semibold"
            >
                <span
                    class="bg-brand-secondary mr-2 h-2 w-2 animate-ping rounded-full"
                ></span>
                <span>All machines online</span>
            </div>
        </div>

        <!-- Stat card 3 -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card hover:shadow-card-hover hover-lift relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Active Staff
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        {{ $staffCount }}
                    </h3>
                </div>
                <div
                    class="bg-brand-accent/10 text-brand-accent rounded-xl p-3"
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
                class="text-brand-accent/70 mt-4 flex items-center text-xs font-semibold"
            >
                <span>Managed across 5 roles</span>
            </div>
        </div>

        <!-- Stat card 4 -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card hover:shadow-card-hover hover-lift relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Pricing Engine Rows
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        {{ $pricingRulesCount }}
                    </h3>
                </div>
                <div class="rounded-xl bg-emerald-500/10 p-3 text-emerald-400">
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
                class="mt-4 flex items-center text-xs font-semibold text-emerald-400"
            >
                <span>Active price rule matrix</span>
            </div>
        </div>
    </div>

    <!-- Management actions and recent stock ledger -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Stock Restock Payout form -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm"
        >
            <h3 class="text-lg font-bold text-white">Material Restocking</h3>
            <p class="mt-1 mb-4 text-xs text-gray-500">
                Replenish material sheet or banner rolls inventory levels.
            </p>

            <form
                wire:submit.prevent="addMaterialStock"
                class="space-y-4 text-xs"
            >
                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Select Material</label
                    >
                    <select
                        wire:model="selectedMaterialId"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary focus:ring-brand-primary/30 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-gray-800 text-white placeholder-gray-400 transition-colors duration-200 focus:ring-1 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        required
                    >
                        <option value="">
                            -- Choose Material to Restock --
                        </option>
                        @foreach ($allMaterials as $mat)
                        <option value="{{ $mat->id }}">
                            {{ $mat->name }} {{ $mat->dimensions ?
                            "({$mat->dimensions})" : '' }} [{{ $mat->category
                            }}]
                        </option>
                        @endforeach
                    </select>
                    @error('selectedMaterialId')
                    <span class="text-[10px] font-semibold text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Added Restock Qty</label
                    >
                    <input
                        type="number"
                        step="any"
                        min="0.01"
                        wire:model="addStockQuantity"
                        placeholder="e.g. 500 or 12.5"
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary focus:ring-brand-primary/30 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 transition-colors duration-200 focus:ring-1 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        required
                    />
                    @error('addStockQuantity')
                    <span class="text-[10px] font-semibold text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Restocking Notes</label
                    >
                    <textarea
                        wire:model="addStockNotes"
                        placeholder="Restock batch invoice number, vendor name, etc..."
                        class="dark:border-surface-200 dark:bg-surface-600/50 focus:border-brand-primary focus:ring-brand-primary/30 h-20 w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 transition-colors duration-200 focus:ring-1 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                    ></textarea>
                    @error('addStockNotes')
                    <span class="text-[10px] font-semibold text-red-400"
                        >{{ $message }}</span
                    >
                    @enderror
                </div>

                <button
                    type="submit"
                    class="bg-brand-primary hover:bg-brand-primary-hover shadow-glow-primary w-full rounded-xl py-2.5 text-[11px] font-bold tracking-wider text-white uppercase transition-all duration-200 active:scale-[0.98]"
                >
                    Register Restock Entry
                </button>
            </form>
        </div>

        <!-- Recent Stock ledger logs -->
        <div
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm lg:col-span-2"
        >
            <h3 class="text-lg font-bold text-white">
                Recent Inventory Ledger Logs
            </h3>
            <p class="mt-1 mb-4 text-xs text-gray-500">
                Stock ledger activity adjustments logs.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-400">
                    <thead
                        class="dark:bg-surface-600/80 text-gray-650 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
                    >
                        <tr>
                            <th class="rounded-l-lg px-3 py-2.5">Date</th>
                            <th class="px-3 py-2.5">Material</th>
                            <th class="px-3 py-2.5">Initial</th>
                            <th class="px-3 py-2.5">Added</th>
                            <th class="px-3 py-2.5">Wasted</th>
                            <th class="rounded-r-lg px-3 py-2.5 text-right">
                                Ledger Remaining
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="dark:divide-surface-300/30 divide-y divide-gray-100 text-[11px]"
                    >
                        @forelse ($stockLogs as $log)
                        <tr
                            class="dark:hover:bg-surface-400/20 transition-colors duration-150 hover:bg-gray-50"
                        >
                            <td class="px-3 py-2.5 font-medium">
                                {{ $log->logged_at }}
                            </td>
                            <td class="px-3 py-2.5 font-semibold text-white">
                                {{ $log->material_name }}
                            </td>
                            <td class="px-3 py-2.5">
                                {{ $log->initial_stock }}
                            </td>
                            <td
                                class="px-3 py-2.5 font-semibold text-emerald-400"
                            >
                                +{{ $log->added_stock }}
                            </td>
                            <td class="px-3 py-2.5 font-semibold text-red-400">
                                {{ $log->wasted_stock }}
                            </td>
                            <td
                                class="text-brand-secondary px-3 py-2.5 text-right font-bold"
                            >
                                {{ $log->remaining_stock }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="6"
                                class="py-4 text-center text-gray-500"
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
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm lg:col-span-2"
        >
            <h3 class="text-lg font-bold text-white">
                Material Inventory Levels
            </h3>
            <p class="mt-1 mb-4 text-xs text-gray-500">
                Stock balances with low stock highlight flags.
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-gray-400">
                    <thead
                        class="dark:bg-surface-600/80 text-gray-650 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
                    >
                        <tr>
                            <th class="rounded-l-lg px-2 py-2">Material</th>
                            <th class="px-2 py-2">Initial</th>
                            <th class="px-2 py-2">Added</th>
                            <th class="px-2 py-2">Sold</th>
                            <th class="px-2 py-2">Wasted</th>
                            <th class="rounded-r-lg px-2 py-2 text-right">
                                Remaining Stock
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="dark:divide-surface-300/30 divide-y divide-gray-100 text-[11px]"
                    >
                        @foreach ($materialsList as $mItem) @php $isLow =
                        $mItem->remaining_stock <= 10.0; @endphp
                        <tr
                            class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition-colors duration-150 {{ $isLow ? 'bg-red-500/5 text-red-400' : '' }}"
                        >
                            <td class="px-2 py-2 font-semibold">
                                {{ $mItem->name }} @if ($mItem->dimensions)
                                <span
                                    class="text-[9px] font-normal text-gray-600"
                                    >({{ $mItem->dimensions }})</span
                                >
                                @endif
                            </td>
                            <td class="px-2 py-2">
                                {{ $mItem->initial_stock }}
                            </td>
                            <td
                                class="px-2 py-2 font-semibold text-emerald-400"
                            >
                                +{{ $mItem->added_stock }}
                            </td>
                            <td
                                class="text-brand-secondary px-2 py-2 font-semibold"
                            >
                                {{ $mItem->sold_stock }}
                            </td>
                            <td class="px-2 py-2 font-semibold text-red-400">
                                {{ $mItem->wasted_stock }}
                            </td>
                            <td class="px-2 py-2 text-right font-black">
                                {{ $mItem->remaining_stock }} @if ($isLow)
                                <span
                                    class="ml-1 inline-block animate-pulse rounded bg-red-950/30 px-1.5 py-0.5 text-[8px] font-black tracking-wider uppercase"
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
            class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm lg:col-span-1"
        >
            <h3 class="mb-1 text-lg font-bold text-white">Expenses Ledger</h3>
            <p class="mb-4 text-xs text-gray-500">
                Drawer payouts audit tracking logs.
            </p>

            <div class="max-h-[400px] space-y-3.5 overflow-y-auto pr-1">
                @forelse ($expenses as $exp)
                <div
                    class="dark:border-surface-300/30 dark:bg-surface-600/50 hover:border-surface-200/40 relative rounded-xl border border-gray-200 bg-white p-3 text-xs transition-colors duration-150"
                >
                    <div class="flex items-start justify-between">
                        <span class="font-bold text-white uppercase"
                            >{{ $exp->cashier_name }}</span
                        >
                        <span class="font-black text-red-400"
                            >Rp. {{ number_format($exp->amount) }}</span
                        >
                    </div>
                    <div
                        class="mt-1 flex justify-between text-[10px] text-gray-500"
                    >
                        <span>{{ $exp->incurred_at }}</span>
                        <span
                            class="text-brand-secondary font-semibold uppercase"
                            >{{ $exp->type }}</span
                        >
                    </div>
                    <p class="mt-1.5 text-[10px] text-gray-500 italic">
                        {{ $exp->description }}
                    </p>
                </div>
                @empty
                <div class="py-6 text-center text-xs text-gray-500">
                    No expenses recorded.
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @elseif ($activeTab === 'users') {{-- Shared UserManagement sub-component
    handles search, table, CRUD modal & its own alert --}}
    <livewire:admin.user-management mode="manager" />
    @endif
</div>
