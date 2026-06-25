<div class="space-y-6 text-gray-200">
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
        class="rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4 backdrop-blur-sm transition-all"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg
                    class="mr-2.5 h-5 w-5 text-emerald-400"
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
    </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- LEFT PANEL: CUSTOMER & METADATA -->
        <div class="space-y-6 lg:col-span-1">
            <!-- Customer Section -->
            <div
                class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-md backdrop-blur-sm"
            >
                <h2
                    class="mb-4 text-lg font-bold text-gray-900 dark:text-white"
                >
                    1. Customer Intake
                </h2>

                @if (!$showNewCustomerForm)
                <!-- Search Field -->
                <div class="space-y-3">
                    <label
                        class="block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Search Customer</label
                    >
                    <div class="relative">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="customerSearch"
                            placeholder="Type name or phone..."
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white py-2.5 pr-10 pl-3 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        />
                        @if ($customerId)
                        <div class="absolute top-2.5 right-3 text-emerald-400">
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
                        class="border-gray-250 dark:bg-surface-600 dark:divide-surface-300/30 max-h-40 divide-y divide-gray-100 overflow-y-auto rounded-lg border bg-white shadow-lg dark:border-gray-700/50"
                    >
                        @forelse ($customers as $c) {{-- Use Js::from() to
                        safely JSON-encode the name inside HTML attributes --}}
                        {{-- This prevents apostrophes/special chars from
                        breaking the wire:click JS call --}}
                        <button
                            type="button"
                            wire:click="selectCustomer({{ $c->id }}, {{ \Illuminate\Support\Js::from($c->name) }})"
                            class="w-full px-3 py-2 text-left text-xs font-medium text-gray-700 hover:bg-teal-50 dark:text-gray-200 dark:hover:bg-teal-950/20"
                        >
                            {{ $c->name }} ({{ $c->phone ?: 'No Phone' }}) -
                            <span class="text-teal-550">{{ $c->type }}</span>
                        </button>
                        @empty
                        <div
                            class="px-3 py-2.5 text-center text-xs text-gray-500"
                        >
                            No customer found.
                        </div>
                        @endforelse
                    </div>
                    @endif

                    <button
                        type="button"
                        wire:click="$set('showNewCustomerForm', true)"
                        class="w-full rounded-lg border border-dashed border-gray-200 py-2 text-xs font-bold text-teal-500 transition hover:bg-teal-50/30 dark:border-gray-700/50 dark:hover:bg-teal-950/10"
                    >
                        + Create New Customer
                    </button>
                </div>
                @else
                <!-- New Customer Form -->
                <form wire:submit.prevent="createCustomer" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold text-gray-500 uppercase">
                            New Customer
                        </h3>
                        <button
                            type="button"
                            wire:click="$set('showNewCustomerForm', false)"
                            class="text-xs font-semibold text-red-400"
                        >
                            Cancel
                        </button>
                    </div>

                    <input
                        type="text"
                        wire:model="customerName"
                        placeholder="Full Name *"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-800 placeholder-gray-400 dark:text-gray-100 dark:placeholder-gray-500"
                        required
                    />
                    <input
                        type="text"
                        wire:model="customerPhone"
                        placeholder="Phone Number"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-800 placeholder-gray-400 dark:text-gray-100 dark:placeholder-gray-500"
                    />
                    <input
                        type="email"
                        wire:model="customerEmail"
                        placeholder="Email"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-800 placeholder-gray-400 dark:text-gray-100 dark:placeholder-gray-500"
                    />
                    <textarea
                        wire:model="customerAddress"
                        placeholder="Address"
                        class="dark:border-surface-200 dark:bg-surface-600/50 h-16 w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-800 placeholder-gray-400 dark:text-gray-100 dark:placeholder-gray-500"
                    ></textarea>

                    <select
                        wire:model="customerType"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-800 placeholder-gray-400 dark:text-gray-100 dark:placeholder-gray-500"
                    >
                        <option value="Reguler">Reguler</option>
                        <option value="Mitra">Mitra</option>
                    </select>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-teal-500 py-2 text-xs font-bold text-white transition hover:bg-teal-600"
                    >
                        Save & Select Customer
                    </button>
                </form>
                @endif @error('customerId')
                <p class="mt-2 text-xs font-semibold text-red-400">
                    {{ $message }}
                </p>
                @enderror
            </div>

            <!-- Order Settings -->
            <div
                class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-md backdrop-blur-sm"
            >
                <h2 class="text-lg font-bold text-white">2. Job Parameters</h2>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Production Deadline</label
                    >
                    <input
                        type="datetime-local"
                        wire:model="deadline"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                    />
                </div>

                <div>
                    <label
                        class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >Order Intake Channel</label
                    >
                    <select
                        wire:model="source"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                    >
                        <option value="Offline">Offline / Walk-In</option>
                        <option value="WhatsApp">WhatsApp Message</option>
                        <option value="Tokopedia">Tokopedia Store</option>
                        <option value="Shopee">Shopee Store</option>
                        <option value="Email">Email Channel</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: INTERACTIVE CALCULATOR & CART -->
        <div class="space-y-6 lg:col-span-2">
            <!-- 3. Dynamic Quote Calculator -->
            <div
                class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm"
            >
                <h2 class="mb-4 text-lg font-bold text-white">
                    3. Quote Calculator
                </h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- Product Category Selector -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Product Category</label
                        >
                        <select
                            wire:model.live="productId"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        >
                            <option value="">
                                -- Select Product Category --
                            </option>
                            @foreach ($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Material Selector -->
                    @if ($productId)
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Material (Bahan)</label
                        >
                        <select
                            wire:model.live="materialId"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        >
                            <option value="">-- Select Material --</option>
                            @foreach ($materials as $m)
                            <option value="{{ $m->id }}">
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
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Print Machine</label
                        >
                        <select
                            wire:model.live="machineId"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        >
                            <option value="">-- Select Machine --</option>
                            @foreach ($machines as $mc)
                            <option value="{{ $mc->id }}">
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
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Post-Finishing</label
                        >
                        <select
                            wire:model.live="finishingId"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        >
                            <option value="">-- Select Finishing --</option>
                            @foreach ($finishings as $f)
                            <option value="{{ $f->id }}">{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Cutting Selector -->
                    @if ($productId && $hasCuts)
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Cutting Style</label
                        >
                        <select
                            wire:model.live="cutId"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        >
                            <option value="">-- Select Cutting --</option>
                            @foreach ($cuts as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Display Selector -->
                    @if ($productId && $hasDisplays)
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Display Stands & Accessories</label
                        >
                        <select
                            wire:model.live="displayId"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        >
                            <option value="">-- Select Display --</option>
                            @foreach ($displays as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Sides / Colors Selector -->
                    @if ($productId && $hasSides)
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >
                            @if ($productId == 7) Color Options (Stempel)
                            @elseif ($productId == 10) Note Copies (Nota) @else
                            Print Sides (Sisi) @endif
                        </label>
                        <select
                            wire:model.live="sides"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        >
                            @if ($productId == 7)
                            <option value="1">1 Warna</option>
                            <option value="2">2 Warna</option>
                            <option value="3">3 Warna</option>
                            <option value="4">4 Warna</option>
                            @elseif ($productId == 10)
                            <option value="1">1 Rangkap</option>
                            <option value="2">2 Rangkap</option>
                            <option value="3">3 Rangkap</option>
                            <option value="4">4 Rangkap</option>
                            @else
                            <option value="1">1 Sisi</option>
                            <option value="2">2 Sisi</option>
                            @endif
                        </select>
                    </div>
                    @endif

                    <!-- Finishing Sides -->
                    @if ($productId && $hasFinishingSides)
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Finishing Sides</label
                        >
                        <select
                            wire:model.live="finishingSides"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        >
                            <option value="0">0 Sisi</option>
                            <option value="1">1 Sisi</option>
                            <option value="2">2 Sisi</option>
                        </select>
                    </div>
                    @endif

                    <!-- Large Format Dimensions -->
                    @if ($productId == 5)
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Length (m)</label
                        >
                        <input
                            type="number"
                            step="0.01"
                            min="0.1"
                            wire:model.live.debounce.300ms="length"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Width (m)</label
                        >
                        <input
                            type="number"
                            step="0.01"
                            min="0.1"
                            wire:model.live.debounce.300ms="width"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        />
                    </div>
                    @endif

                    <!-- Quantity Input -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Quantity (Pcs/Sheets/Qty)</label
                        >
                        <input
                            type="number"
                            min="1"
                            step="any"
                            wire:model.live.debounce.300ms="quantity"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        />
                    </div>

                    <!-- File Name Input -->
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >File Layout Name</label
                        >
                        <input
                            type="text"
                            wire:model="fileName"
                            placeholder="layout_design.pdf"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        />
                    </div>

                    <!-- Notes Input -->
                    <div class="md:col-span-2">
                        <label
                            class="mb-1.5 block text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Prepress / Layout Instructions</label
                        >
                        <textarea
                            wire:model="notes"
                            placeholder="Additional notes for production operators..."
                            class="dark:border-surface-200 dark:bg-surface-600/50 h-16 w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        ></textarea>
                    </div>
                </div>

                <!-- Dynamic Pricing Display Section -->
                <div
                    class="mt-6 flex flex-col gap-4 rounded-xl border border-gray-100 bg-gray-50/40 p-4 md:flex-row md:items-center md:justify-between dark:border-gray-700/30"
                >
                    <div>
                        @if ($isCalculated)
                        <div
                            class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >
                            Estimated Subtotal
                        </div>
                        <div
                            class="mt-1 text-2xl font-black text-teal-500 dark:text-teal-400"
                        >
                            Rp. {{ number_format($calcSubtotal) }}
                        </div>
                        <div class="mt-0.5 text-[10px] text-gray-400">
                            Unit Price: Rp. {{ number_format($calcPrice) }} @if
                            ($productId == 5) / m² @endif
                        </div>
                        @elseif ($calcError)
                        <span
                            class="flex items-center text-xs font-semibold text-red-400"
                        >
                            <svg
                                class="mr-1.5 h-4 w-4"
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
                            class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                        >
                            Estimated Subtotal
                        </div>
                        <div
                            class="mt-1 text-2xl font-bold text-gray-300 dark:text-gray-700"
                        >
                            Rp. --
                        </div>
                        @endif
                    </div>

                    <button
                        type="button"
                        wire:click="addItem"
                        @if(!$isCalculated)
                        disabled
                        @endif
                        class="rounded-lg bg-teal-500 px-6 py-2.5 text-xs font-bold text-white transition hover:bg-teal-600 focus:ring-2 focus:ring-teal-500/50 focus:outline-none disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        + Add Item to Order
                    </button>
                </div>
            </div>

            <!-- 4. Pre-Queued Draft Cart -->
            @if (!empty($items))
            <div
                class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm"
            >
                <h2 class="mb-4 text-lg font-bold text-white">
                    4. Order Summary Queue
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-gray-400">
                        <thead
                            class="dark:bg-surface-600/80 text-gray-650 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-3 py-2.5">
                                    Product / Layout File
                                </th>
                                <th class="px-3 py-2.5">Specifications</th>
                                <th class="px-3 py-2.5">Qty</th>
                                <th class="px-3 py-2.5 text-right">Subtotal</th>
                                <th class="px-3 py-2.5 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody
                            class="dark:divide-surface-300/30 divide-y divide-gray-100"
                        >
                            @foreach ($items as $item)
                            <tr
                                class="dark:hover:bg-surface-400/20 transition hover:bg-gray-50"
                            >
                                <td class="px-3 py-3">
                                    <div class="font-bold text-white">
                                        {{ $item['product_name'] }}
                                    </div>
                                    <div class="mt-0.5 text-gray-500 italic">
                                        {{ $item['file_name'] }}
                                    </div>
                                </td>
                                <td class="max-w-[200px] truncate px-3 py-3">
                                    {{ $item['details_string'] }}
                                </td>
                                <td class="px-3 py-3 font-semibold">
                                    {{ $item['quantity'] }}
                                </td>
                                <td
                                    class="px-3 py-3 text-right font-bold text-white"
                                >
                                    Rp. {{ number_format($item['subtotal']) }}
                                </td>
                                <td class="px-3 py-3 text-center">
                                    <button
                                        type="button"
                                        wire:click="removeItem('{{ $item['id'] }}')"
                                        class="font-semibold text-red-400 hover:text-red-700 dark:hover:text-red-400"
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
                    class="mt-6 flex flex-col gap-4 border-t border-gray-100 pt-5 md:flex-row md:items-center md:justify-between dark:border-gray-700/50"
                >
                    <div class="flex items-center space-x-3">
                        <label
                            class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
                            >Order Discount</label
                        >
                        <div class="relative w-36">
                            <span
                                class="absolute top-2 left-3 text-xs font-bold text-gray-400"
                                >Rp.</span
                            >
                            <input
                                type="number"
                                wire:model.live.debounce.300ms="discount"
                                class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white py-1.5 pr-3 pl-9 text-xs text-gray-800 placeholder-gray-400 focus:border-teal-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                            />
                        </div>
                    </div>

                    <div class="text-right">
                        <div
                            class="text-xs font-semibold tracking-wider text-gray-500 uppercase"
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
                <p class="mt-2 text-right text-xs font-semibold text-red-400">
                    {{ $message }}
                </p>
                @enderror

                <!-- Submit Action -->
                <div class="mt-6 text-right">
                    <button
                        type="button"
                        wire:click="submitOrder"
                        class="bg-brand-primary hover:bg-brand-primary-hover w-full rounded-lg px-8 py-3 text-sm font-bold text-white shadow-md transition hover:shadow-lg md:w-auto"
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
        class="dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm"
    >
        <h2 class="mb-4 text-lg font-bold text-white">
            Recent Placed Invoices
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-400">
                <thead
                    class="dark:bg-surface-600/80 text-gray-650 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="px-3 py-2.5">Invoice ID</th>
                        <th class="px-3 py-2.5">Customer</th>
                        <th class="px-3 py-2.5">Grand Total</th>
                        <th class="px-3 py-2.5">Deadline</th>
                        <th class="px-3 py-2.5">Checkout Status</th>
                        <th class="px-3 py-2.5 text-center">Work Ticket</th>
                    </tr>
                </thead>
                <tbody
                    class="dark:divide-surface-300/30 divide-y divide-gray-100"
                >
                    @forelse ($activeInvoices as $inv)
                    <tr
                        class="dark:hover:bg-surface-400/20 transition hover:bg-gray-50"
                    >
                        <td class="px-3 py-3 font-semibold text-white">
                            #{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-3 py-3 font-semibold text-white">
                            {{ $inv->customer->name }}
                        </td>
                        <td class="px-3 py-3 font-bold text-white">
                            Rp. {{ number_format($inv->grand_total) }}
                        </td>
                        <td class="px-3 py-3 font-semibold text-red-400">
                            {{ $inv->deadline }}
                        </td>
                        <td class="px-3 py-3">
                            @if ($inv->status == 0)
                            <span
                                class="inline-block rounded-full bg-red-50 bg-red-950/30 px-2 py-0.5 text-[10px] font-bold tracking-wider text-red-600 uppercase dark:text-red-400"
                                >Unpaid</span
                            >
                            @elseif ($inv->status == 1)
                            <span
                                class="inline-block rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-amber-600 uppercase dark:bg-amber-950/30 dark:text-amber-400"
                                >Down Payment</span
                            >
                            @elseif ($inv->status == 2)
                            <span
                                class="inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-bold tracking-wider text-emerald-600 uppercase dark:bg-emerald-950/30 dark:text-emerald-400"
                                >Paid Lunas</span
                            >
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center">
                            <a
                                href="/orders/{{ $inv->id }}/ticket"
                                target="_blank"
                                class="inline-flex items-center font-semibold text-teal-500 hover:text-teal-700"
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
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-11h.01M12 11h.01"
                                    ></path>
                                </svg>
                                Job Ticket
                            </a>
                        </td>
                        @empty
                    </tr>

                    <tr>
                        <td
                            colspan="6"
                            class="px-3 py-4 text-center text-gray-500"
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
