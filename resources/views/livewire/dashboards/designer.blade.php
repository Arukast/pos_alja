<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- Header banner -->
    <x-brand-header-banner
        badge="Customer Intake Desk"
        title="Design & Quotes Board"
        description="Calculate instant print quotes, upload design files, generate Work Orders (WO), and queue jobs directly to the print machines."
        theme="designer"
    />

    <!-- Alert Panel -->
    @if ($showSuccessAlert)
    <div
        class="rounded-xl border border-emerald-500/20 bg-emerald-50 p-4 text-emerald-800 backdrop-blur-sm transition-all dark:bg-emerald-950/20 dark:text-emerald-300"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg
                    class="mr-2.5 h-5 w-5 text-emerald-500 dark:text-emerald-400"
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
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- LEFT PANEL: CUSTOMER & METADATA -->
        <div class="space-y-6 lg:col-span-1">
            <!-- Customer Section -->
            <div
                class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-5 shadow-sm"
            >
                <h2
                    class="text-light-text mb-4 text-lg font-bold dark:text-white"
                >
                    1. Customer Intake
                </h2>

                @if (!$showNewCustomerForm)
                <!-- Search Field -->
                <div class="space-y-3">
                    <label
                        class="text-light-muted block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                        >Search Customer</label
                    >
                    <div class="relative">
                        <input
                            id="customer-search"
                            type="text"
                            wire:model.live.debounce.300ms="customerSearch"
                            placeholder="Type name or phone..."
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white py-2.5 pr-10 pl-3 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        />
                        @if ($customerId)
                        <div
                            class="absolute top-3 right-3 text-emerald-500 dark:text-emerald-400"
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
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                        </div>
                        @endif
                    </div>

                    <!-- Dropdown Results -->
                    @if ($customerSearch && !$customerId)
                    <div
                        class="border-light-border dark:bg-surface-600 divide-light-border dark:divide-surface-300/30 max-h-40 divide-y overflow-y-auto rounded-lg border bg-white shadow-lg dark:border-gray-700/50"
                    >
                        @forelse ($customers as $c)
                        <button
                            id="customer-option-{{ $c->id }}"
                            type="button"
                            wire:click="selectCustomer({{ $c->id }})"
                            class="text-light-text hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 w-full cursor-pointer px-3 py-2 text-left text-xs font-bold transition dark:text-gray-200"
                        >
                            {{ $c->name }} ({{ $c->phone ?: 'No Phone' }}) -
                            <span class="text-brand-secondary font-bold"
                                >{{ $c->type }}</span
                            >
                        </button>
                        @empty
                        <div
                            class="text-light-muted px-3 py-2.5 text-center text-xs font-medium dark:text-gray-500"
                        >
                            No customer found.
                        </div>
                        @endforelse
                    </div>
                    @endif

                    <button
                        id="btn-create-customer-mode"
                        type="button"
                        wire:click="$set('showNewCustomerForm', true)"
                        class="border-light-border text-brand-secondary hover:bg-light-surface-2 dark:hover:bg-surface-500/50 w-full cursor-pointer rounded-xl border border-dashed py-2.5 text-xs font-bold transition dark:border-gray-700/50"
                    >
                        + Create New Customer
                    </button>
                </div>
                @else
                <!-- New Customer Form -->
                <form wire:submit.prevent="createCustomer" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3
                            class="text-light-muted text-xs font-bold uppercase dark:text-gray-400"
                        >
                            New Customer
                        </h3>
                        <button
                            id="btn-cancel-create-customer"
                            type="button"
                            wire:click="$set('showNewCustomerForm', false)"
                            class="text-red-550 cursor-pointer text-xs font-bold dark:text-red-400"
                        >
                            Cancel
                        </button>
                    </div>

                    <input
                        id="new-customer-name"
                        type="text"
                        wire:model="customerName"
                        placeholder="Full Name *"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        required
                    />
                    <input
                        id="new-customer-phone"
                        type="text"
                        wire:model="customerPhone"
                        placeholder="Phone Number"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    />
                    <input
                        id="new-customer-email"
                        type="email"
                        wire:model="customerEmail"
                        placeholder="Email"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    />
                    <textarea
                        id="new-customer-address"
                        wire:model="customerAddress"
                        placeholder="Address"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-16 w-full resize-none rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    ></textarea>

                    <select
                        id="new-customer-type"
                        wire:model="customerType"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none"
                    >
                        <option value="Reguler" class="bg-white text-gray-900">
                            Reguler
                        </option>
                        <option value="Mitra" class="bg-white text-gray-900">
                            Mitra
                        </option>
                    </select>

                    <button
                        id="btn-submit-new-customer"
                        type="submit"
                        class="bg-brand-secondary hover:bg-brand-secondary/95 w-full cursor-pointer rounded-xl py-2 text-xs font-bold text-white transition"
                    >
                        Save & Select Customer
                    </button>
                </form>
                @endif @error('customerId')
                <p
                    class="text-red-550 mt-2 text-xs font-bold dark:text-red-400"
                >
                    {{ $message }}
                </p>
                @enderror
            </div>

            <!-- Order Settings -->
            <div
                class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 space-y-4 rounded-2xl border bg-white p-5 shadow-sm"
            >
                <h2 class="text-light-text text-lg font-bold dark:text-white">
                    2. Job Parameters
                </h2>

                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                        >Production Deadline</label
                    >
                    <input
                        id="order-deadline"
                        type="datetime-local"
                        wire:model="deadline"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    />
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                        >Order Intake Channel</label
                    >
                    <select
                        id="order-source"
                        wire:model="source"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                    >
                        <option value="Offline" class="bg-white text-gray-900">
                            Offline / Walk-In
                        </option>
                        <option value="WhatsApp" class="bg-white text-gray-900">
                            WhatsApp Message
                        </option>
                        <option
                            value="Tokopedia"
                            class="bg-white text-gray-900"
                        >
                            Tokopedia Store
                        </option>
                        <option value="Shopee" class="bg-white text-gray-900">
                            Shopee Store
                        </option>
                        <option value="Email" class="bg-white text-gray-900">
                            Email Channel
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: INTERACTIVE CALCULATOR & CART -->
        <div class="space-y-6 lg:col-span-2">
            <!-- 3. Dynamic Quote Calculator -->
            <div
                class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
            >
                <h2
                    class="text-light-text mb-4 text-lg font-bold dark:text-white"
                >
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
                            wire:model.live="productId"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted dark:placeholder-gray-555 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                        >
                            <option
                                value=""
                                class="text-light-muted dark:text-gray-400"
                            >
                                -- Select Product Category --
                            </option>
                            @foreach ($products as $p)
                            <option
                                value="{{ $p->id }}"
                                class="text-light-text dark:text-gray-200"
                            >
                                {{ $p->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Material Selector -->
                    @if ($productId)
                    <div>
                        <label
                            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                            >Material (Bahan)</label
                        >
                        <select
                            id="calc-material-id"
                            wire:model.live="materialId"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                        >
                            <option
                                value=""
                                class="text-light-muted dark:text-gray-400"
                            >
                                -- Select Material --
                            </option>
                            @foreach ($materials as $m)
                            <option
                                value="{{ $m->id }}"
                                class="text-light-text font-semibold dark:text-gray-200"
                            >
                                {{ $m->name }} {{ $m->dimensions ?
                                "({$m->dimensions})" : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Machine Selector -->
                    @if ($productId && $hasMachines)
                    <div>
                        <label
                            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                            >Print Machine</label
                        >
                        <select
                            id="calc-machine-id"
                            wire:model.live="machineId"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                        >
                            <option
                                value=""
                                class="text-light-muted dark:text-gray-400"
                            >
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
                    @if ($productId && $hasFinishings)
                    <div>
                        <label
                            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                            >Post-Finishing</label
                        >
                        <select
                            id="calc-finishing-id"
                            wire:model.live="finishingId"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                        >
                            <option
                                value=""
                                class="text-light-muted dark:text-gray-400"
                            >
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
                    @if ($productId && $hasCuts)
                    <div>
                        <label
                            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                            >Cutting Style</label
                        >
                        <select
                            id="calc-cut-id"
                            wire:model.live="cutId"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                        >
                            <option
                                value=""
                                class="text-light-muted dark:text-gray-400"
                            >
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
                    @if ($productId && $hasDisplays)
                    <div>
                        <label
                            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                            >Display Stands & Accessories</label
                        >
                        <select
                            id="calc-display-id"
                            wire:model.live="displayId"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                        >
                            <option
                                value=""
                                class="text-light-muted dark:text-gray-400"
                            >
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
                    @if ($productId && $hasSides)
                    <div>
                        <label
                            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                        >
                            @if ($productId == 7) Color Options (Stempel)
                            @elseif ($productId == 10) Note Copies (Nota) @else
                            Print Sides (Sisi) @endif
                        </label>
                        <select
                            id="calc-sides"
                            wire:model.live="sides"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                        >
                            @if ($productId == 7)
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
                            @elseif ($productId == 10)
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
                            <option value="1" class="bg-white text-gray-900">
                                1 Sisi
                            </option>
                            <option value="2" class="bg-white text-gray-900">
                                2 Sisi
                            </option>
                            @endif
                        </select>
                    </div>
                    @endif

                    <!-- Finishing Sides -->
                    @if ($productId && $hasFinishingSides)
                    <div>
                        <label
                            class="text-light-muted mb-1.5 block text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                            >Finishing Sides</label
                        >
                        <select
                            id="calc-finishing-sides"
                            wire:model.live="finishingSides"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2.5 text-sm transition duration-200 focus:ring-2 focus:outline-none"
                        >
                            <option value="0" class="bg-white text-gray-900">
                                0 Sisi
                            </option>
                            <option value="1" class="bg-white text-gray-900">
                                1 Sisi
                            </option>
                            <option value="2" class="bg-white text-gray-900">
                                2 Sisi
                            </option>
                        </select>
                    </div>
                    @endif

                    <!-- Large Format Dimensions -->
                    @if ($productId == 5)
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
                            wire:model.live.debounce.300ms="length"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        />
                    </div>
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
                            wire:model.live.debounce.300ms="width"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
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
                            wire:model.live.debounce.300ms="quantity"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
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
                            wire:model="fileName"
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
                            wire:model="notes"
                            placeholder="Additional notes for production operators..."
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-16 w-full resize-none rounded-xl border bg-white px-3 py-2 text-sm transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        ></textarea>
                    </div>
                </div>

                <!-- Dynamic Pricing Display Section -->
                <div
                    class="border-light-border dark:border-surface-300/30 bg-light-surface-2/40 dark:bg-surface-650/40 mt-6 flex flex-col gap-4 rounded-xl border p-4 md:flex-row md:items-center md:justify-between"
                >
                    <div>
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
                            $productId == 5 ? ' / m²' : '' }}
                        </div>
                        @elseif ($calcError)
                        <span
                            class="text-red-550 dark:text-red-405 flex items-center text-xs font-bold"
                        >
                            <svg
                                class="mr-1.5 h-4 w-4 text-red-500"
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

                    <button
                        id="btn-add-calc-item"
                        type="button"
                        wire:click="addItem"
                        @if(!$isCalculated)
                        disabled
                        @endif
                        class="bg-brand-secondary hover:bg-brand-secondary/95 cursor-pointer rounded-xl px-6 py-2.5 text-xs font-bold text-white shadow-sm transition disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        + Add Item to Order
                    </button>
                </div>
            </div>

            <!-- 4. Pre-Queued Draft Cart -->
            @if (!empty($items))
            <div
                class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
            >
                <h2
                    class="text-light-text mb-4 text-lg font-bold dark:text-white"
                >
                    4. Order Summary Queue
                </h2>

                <div
                    class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
                >
                    <table
                        class="text-light-muted dark:text-gray-450 w-full text-left text-xs"
                    >
                        <thead
                            class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                        >
                            <tr>
                                <th class="rounded-l-xl px-4 py-3">
                                    Product / Layout File
                                </th>
                                <th class="px-4 py-3">Specifications</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="rounded-r-xl px-4 py-3 text-center">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                        >
                            @foreach ($items as $item)
                            <tr
                                class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                            >
                                <td class="px-4 py-3">
                                    <div
                                        class="text-light-text font-bold dark:text-white"
                                    >
                                        {{ $item['product_name'] }}
                                    </div>
                                    <div
                                        class="text-light-muted mt-0.5 font-medium italic dark:text-gray-400"
                                    >
                                        {{ $item['file_name'] }}
                                    </div>
                                </td>
                                <td
                                    class="text-light-text max-w-[200px] truncate px-4 py-3 font-semibold dark:text-gray-300"
                                >
                                    {{ $item['details_string'] }}
                                </td>
                                <td
                                    class="text-light-text px-4 py-3 font-bold dark:text-white"
                                >
                                    {{ $item['quantity'] }}
                                </td>
                                <td
                                    class="text-light-text px-4 py-3 text-right font-extrabold dark:text-white"
                                >
                                    Rp. {{ number_format($item['subtotal']) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        id="btn-remove-draft-{{ $item['id'] }}"
                                        type="button"
                                        wire:click="removeItem('{{ $item['id'] }}')"
                                        class="text-red-550 hover:text-red-750 cursor-pointer font-bold"
                                    >
                                        Remove
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Discount & Grand Total block -->
                <div
                    class="border-light-border dark:border-surface-300/50 mt-6 flex flex-col gap-4 border-t pt-5 md:flex-row md:items-center md:justify-between"
                >
                    <div class="flex items-center space-x-3">
                        <label
                            class="text-light-muted text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                            >Order Discount</label
                        >
                        <div class="relative w-36">
                            <span
                                class="text-light-muted absolute top-2 left-3 text-xs font-bold dark:text-gray-400"
                                >Rp.</span
                            >
                            <input
                                id="order-discount"
                                type="number"
                                wire:model.live.debounce.300ms="discount"
                                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white py-1.5 pr-3 pl-9 text-xs transition duration-200 focus:ring-2 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="text-right">
                        <div
                            class="text-light-muted text-xs font-bold tracking-wider uppercase dark:text-gray-400"
                        >
                            Grand Total
                        </div>
                        @php $total = array_sum(array_column($items,
                        'subtotal')); $disc = (int) ($discount ?: 0);
                        $grandTotal = max(0, $total - $disc); @endphp
                        <div
                            class="text-brand-secondary mt-1 text-3xl font-black dark:text-teal-400"
                        >
                            Rp. {{ number_format($grandTotal) }}
                        </div>
                    </div>
                </div>

                @error('items')
                <p
                    class="text-red-550 mt-2 text-right text-xs font-bold dark:text-red-400"
                >
                    {{ $message }}
                </p>
                @enderror

                <!-- Submit Action -->
                <div class="mt-6 text-right">
                    <button
                        id="btn-submit-order"
                        type="button"
                        wire:click="submitOrder"
                        class="bg-brand-primary hover:bg-brand-primary/95 w-full cursor-pointer rounded-xl px-8 py-3 text-sm font-bold text-white shadow-md transition md:w-auto"
                    >
                        Submit Draft Order to Cashier & Print Queues
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- RECENTLY PLACED INVOICES -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <h2 class="text-light-text mb-4 text-lg font-bold dark:text-white">
            Recent Placed Invoices
        </h2>
        <div
            class="border-light-border dark:border-surface-300/30 overflow-x-auto rounded-2xl border"
        >
            <table
                class="text-light-muted dark:text-gray-450 w-full text-left text-xs"
            >
                <thead
                    class="bg-light-surface-2 dark:bg-surface-500 text-light-muted text-xs font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="rounded-l-xl px-4 py-3">Invoice ID</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Grand Total</th>
                        <th class="px-4 py-3">Deadline</th>
                        <th class="px-4 py-3">Checkout Status</th>
                        <th class="rounded-r-xl px-4 py-3 text-center">
                            Work Ticket
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                >
                    @forelse ($activeInvoices as $inv)
                    <tr
                        class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                    >
                        <td
                            class="text-light-text px-4 py-3 font-bold dark:text-white"
                        >
                            #{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td
                            class="text-light-text px-4 py-3 font-bold dark:text-white"
                        >
                            {{ $inv->customer->name }}
                        </td>
                        <td
                            class="text-light-text px-4 py-3 font-extrabold dark:text-white"
                        >
                            Rp. {{ number_format($inv->grand_total) }}
                        </td>
                        <td
                            class="text-red-550 px-4 py-3 font-bold dark:text-red-400"
                        >
                            {{ $inv->deadline }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($inv->status == 0)
                            <span
                                class="text-red-750 inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider uppercase dark:bg-red-900/30 dark:text-red-400"
                                >Unpaid</span
                            >
                            @elseif ($inv->status == 1)
                            <span
                                class="inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-amber-700 uppercase dark:bg-amber-900/30 dark:text-amber-400"
                                >Down Payment</span
                            >
                            @elseif ($inv->status == 2)
                            <span
                                class="inline-block rounded-full bg-emerald-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-emerald-700 uppercase dark:bg-emerald-900/30 dark:text-emerald-400"
                                >Paid Lunas</span
                            >
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a
                                id="link-ticket-{{ $inv->id }}"
                                href="/orders/{{ $inv->id }}/ticket"
                                target="_blank"
                                class="text-brand-secondary hover:text-brand-secondary/85 inline-flex items-center font-bold transition"
                            >
                                <svg
                                    class="text-brand-secondary mr-1 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-11h.01M12 11h.01"
                                    ></path>
                                </svg>
                                Job Ticket
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td
                            colspan="6"
                            class="text-light-muted px-4 py-6 text-center font-semibold dark:text-gray-500"
                        >
                            No active invoices placed yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
