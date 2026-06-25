<div class="space-y-6 text-gray-200">
    <!-- Header banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-500 to-emerald-600 p-6 shadow-lg dark:from-teal-600 dark:to-emerald-700">
        <div class="relative z-10 text-white">
            <span class="inline-block px-3 py-1 text-xs font-semibold uppercase tracking-wider bg-white/20 rounded-full backdrop-blur-sm">
                Customer Intake Desk
            </span>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Design & Quotes Board</h1>
            <p class="mt-1 text-white/70 max-w-xl text-sm">
                Calculate instant print quotes, upload design files, generate Work Orders (WO), and queue jobs directly to the print machines.
            </p>
        </div>
        <div class="absolute -right-10 -bottom-10 h-40 w-40 rounded-full bg-alja-magenta/10 blur-2xl"></div>
    </div>

    <!-- Alert Panel -->
    @if ($showSuccessAlert)
        <div class="rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4 backdrop-blur-sm transition-all" x-data="{ show: true }" x-show="show">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="mr-2.5 h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-semibold text-emerald-300">{{ $successMessage }}</span>
                </div>
                <button type="button" @click="show = false; $wire.set('showSuccessAlert', false)" class="text-emerald-400 hover:text-emerald-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- LEFT PANEL: CUSTOMER & METADATA -->
        <div class="space-y-6 lg:col-span-1">
            <!-- Customer Section -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card backdrop-blur-md">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">1. Customer Intake</h2>
                
                @if (!$showNewCustomerForm)
                    <!-- Search Field -->
                    <div class="space-y-3">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500">Search Customer</label>
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="customerSearch" placeholder="Type name or phone..." class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 pl-3 pr-10 text-sm focus:border-teal-500 focus:outline-none">
                            @if ($customerId)
                                <div class="absolute right-3 top-2.5 text-emerald-400">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            @endif
                        </div>

                        <!-- Dropdown Results -->
                        @if ($customerSearch && !$customerId)
                            <div class="max-h-40 overflow-y-auto rounded-lg border border-gray-250 dark:border-gray-700/50 bg-white dark:bg-surface-600 shadow-lg divide-y divide-gray-100 dark:divide-surface-300/30">
                                @forelse ($customers as $c)
                                    {{-- Use Js::from() to safely JSON-encode the name inside HTML attributes --}}
                                    {{-- This prevents apostrophes/special chars from breaking the wire:click JS call --}}
                                    <button type="button" wire:click="selectCustomer({{ $c->id }}, {{ \Illuminate\Support\Js::from($c->name) }})" class="w-full text-left px-3 py-2 text-xs hover:bg-teal-50 dark:hover:bg-teal-950/20 text-gray-700 dark:text-gray-200 font-medium">
                                        {{ $c->name }} ({{ $c->phone ?: 'No Phone' }}) - <span class="text-teal-550">{{ $c->type }}</span>
                                    </button>
                                @empty
                                    <div class="px-3 py-2.5 text-xs text-gray-500 text-center">No customer found.</div>
                                @endforelse
                            </div>
                        @endif

                        <button type="button" wire:click="$set('showNewCustomerForm', true)" class="w-full rounded-lg border border-dashed border-gray-200 dark:border-gray-700/50 py-2 text-xs text-teal-500 hover:bg-teal-50/30 dark:hover:bg-teal-950/10 font-bold transition">
                            + Create New Customer
                        </button>
                    </div>
                @else
                    <!-- New Customer Form -->
                    <form wire:submit.prevent="createCustomer" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-gray-500 uppercase">New Customer</h3>
                            <button type="button" wire:click="$set('showNewCustomerForm', false)" class="text-xs text-red-400 font-semibold">Cancel</button>
                        </div>

                        <input type="text" wire:model="customerName" placeholder="Full Name *" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-xs" required>
                        <input type="text" wire:model="customerPhone" placeholder="Phone Number" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-xs">
                        <input type="email" wire:model="customerEmail" placeholder="Email" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-xs">
                        <textarea wire:model="customerAddress" placeholder="Address" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-xs h-16 resize-none"></textarea>
                        
                        <select wire:model="customerType" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-xs">
                            <option value="Reguler">Reguler</option>
                            <option value="Mitra">Mitra</option>
                        </select>

                        <button type="submit" class="w-full rounded-lg bg-teal-500 py-2 text-xs font-bold text-white hover:bg-teal-600 transition">
                            Save & Select Customer
                        </button>
                    </form>
                @endif
                @error('customerId') <p class="mt-2 text-xs text-red-400 font-semibold">{{ $message }}</p> @enderror
            </div>

            <!-- Order Settings -->
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-5 shadow-card backdrop-blur-md space-y-4">
                <h2 class="text-lg font-bold text-white">2. Job Parameters</h2>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Production Deadline</label>
                    <input type="datetime-local" wire:model="deadline" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Order Intake Channel</label>
                    <select wire:model="source" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
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
            <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card">
                <h2 class="text-lg font-bold text-white mb-4">3. Quote Calculator</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Product Category Selector -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Product Category</label>
                        <select wire:model.live="productId" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
                            <option value="">-- Select Product Category --</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Material Selector -->
                    @if ($productId)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Material (Bahan)</label>
                            <select wire:model.live="materialId" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
                                <option value="">-- Select Material --</option>
                                @foreach ($materials as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} {{ $m->dimensions ? "({$m->dimensions})" : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Machine Selector -->
                    @if ($productId && $hasMachines)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Print Machine</label>
                            <select wire:model.live="machineId" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
                                <option value="">-- Select Machine --</option>
                                @foreach ($machines as $mc)
                                    <option value="{{ $mc->id }}">{{ $mc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <!-- Finishing Selector -->
                    @if ($productId && $hasFinishings)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Post-Finishing</label>
                            <select wire:model.live="finishingId" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
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
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Cutting Style</label>
                            <select wire:model.live="cutId" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
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
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Display Stands & Accessories</label>
                            <select wire:model.live="displayId" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
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
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">
                                @if ($productId == 7) Color Options (Stempel)
                                @elseif ($productId == 10) Note Copies (Nota)
                                @else Print Sides (Sisi)
                                @endif
                            </label>
                            <select wire:model.live="sides" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
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
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Finishing Sides</label>
                            <select wire:model.live="finishingSides" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2.5 px-3 text-sm focus:border-teal-500 focus:outline-none">
                                <option value="0">0 Sisi</option>
                                <option value="1">1 Sisi</option>
                                <option value="2">2 Sisi</option>
                            </select>
                        </div>
                    @endif

                    <!-- Large Format Dimensions -->
                    @if ($productId == 5)
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Length (m)</label>
                            <input type="number" step="0.01" min="0.1" wire:model.live.debounce.300ms="length" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Width (m)</label>
                            <input type="number" step="0.01" min="0.1" wire:model.live.debounce.300ms="width" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none">
                        </div>
                    @endif

                    <!-- Quantity Input -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Quantity (Pcs/Sheets/Qty)</label>
                        <input type="number" min="1" step="any" wire:model.live.debounce.300ms="quantity" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none">
                    </div>

                    <!-- File Name Input -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">File Layout Name</label>
                        <input type="text" wire:model="fileName" placeholder="layout_design.pdf" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none">
                    </div>

                    <!-- Notes Input -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Prepress / Layout Instructions</label>
                        <textarea wire:model="notes" placeholder="Additional notes for production operators..." class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-2 px-3 text-sm focus:border-teal-500 focus:outline-none h-16 resize-none"></textarea>
                    </div>
                </div>

                <!-- Dynamic Pricing Display Section -->
                <div class="mt-6 p-4 rounded-xl bg-gray-50/40 border border-gray-100 dark:border-gray-700/30 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        @if ($isCalculated)
                            <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Estimated Subtotal</div>
                            <div class="text-2xl font-black text-teal-500 dark:text-teal-400 mt-1">Rp. {{ number_format($calcSubtotal) }}</div>
                            <div class="text-[10px] text-gray-400 mt-0.5">Unit Price: Rp. {{ number_format($calcPrice) }} @if ($productId == 5) / m² @endif</div>
                        @elseif ($calcError)
                            <span class="text-xs text-red-400 font-semibold flex items-center">
                                <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                {{ $calcError }}
                            </span>
                        @else
                            <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Estimated Subtotal</div>
                            <div class="text-2xl font-bold text-gray-300 dark:text-gray-700 mt-1">Rp. --</div>
                        @endif
                    </div>

                    <button type="button" wire:click="addItem" @if(!$isCalculated) disabled @endif class="rounded-lg bg-teal-500 px-6 py-2.5 text-xs font-bold text-white hover:bg-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-500/50 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        + Add Item to Order
                    </button>
                </div>
            </div>

            <!-- 4. Pre-Queued Draft Cart -->
            @if (!empty($items))
                <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card">
                    <h2 class="text-lg font-bold text-white mb-4">4. Order Summary Queue</h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-gray-400">
                            <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                                <tr>
                                    <th class="py-2.5 px-3">Product / Layout File</th>
                                    <th class="py-2.5 px-3">Specifications</th>
                                    <th class="py-2.5 px-3">Qty</th>
                                    <th class="py-2.5 px-3 text-right">Subtotal</th>
                                    <th class="py-2.5 px-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30">
                                @foreach ($items as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition">
                                        <td class="py-3 px-3">
                                            <div class="font-bold text-white">{{ $item['product_name'] }}</div>
                                            <div class="text-gray-500 italic mt-0.5">{{ $item['file_name'] }}</div>
                                        </td>
                                        <td class="py-3 px-3 max-w-[200px] truncate">{{ $item['details_string'] }}</td>
                                        <td class="py-3 px-3 font-semibold">{{ $item['quantity'] }}</td>
                                        <td class="py-3 px-3 text-right font-bold text-white">
                                            Rp. {{ number_format($item['subtotal']) }}
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <button type="button" wire:click="removeItem('{{ $item['id'] }}')" class="text-red-400 hover:text-red-700 dark:hover:text-red-400 font-semibold">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Discount & Grand Total block -->
                    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-700/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Order Discount</label>
                            <div class="relative w-36">
                                <span class="absolute left-3 top-2 text-xs font-bold text-gray-400">Rp.</span>
                                <input type="number" wire:model.live.debounce.300ms="discount" class="w-full rounded-xl border border-gray-300 dark:border-surface-200 bg-white dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 py-1.5 pl-9 pr-3 text-xs focus:border-teal-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="text-right">
                            <div class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Grand Total</div>
                            @php
                                $total = array_sum(array_column($items, 'subtotal'));
                                $disc = (int) ($discount ?: 0);
                                $grandTotal = max(0, $total - $disc);
                            @endphp
                            <div class="text-3xl font-black text-alja-cyan dark:text-teal-400 mt-1">Rp. {{ number_format($grandTotal) }}</div>
                        </div>
                    </div>

                    @error('items') <p class="mt-2 text-xs text-red-400 font-semibold text-right">{{ $message }}</p> @enderror

                    <!-- Submit Action -->
                    <div class="mt-6 text-right">
                        <button type="button" wire:click="submitOrder" class="w-full md:w-auto rounded-lg bg-alja-magenta hover:bg-alja-magenta-hover text-white font-bold px-8 py-3 text-sm shadow-md hover:shadow-lg transition">
                            Submit Draft Order to Cashier & Print Queues
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- RECENTLY PLACED INVOICES -->
    <div class="rounded-xl border border-gray-200 dark:border-surface-300/40 bg-white dark:bg-surface-500/60 shadow-sm dark:shadow-card backdrop-blur-sm p-6 shadow-card">
        <h2 class="text-lg font-bold text-white mb-4">Recent Placed Invoices</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-400">
                <thead class="bg-gray-100 dark:bg-surface-600/80 text-gray-650 dark:text-gray-400 uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="py-2.5 px-3">Invoice ID</th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Grand Total</th>
                        <th class="py-2.5 px-3">Deadline</th>
                        <th class="py-2.5 px-3">Checkout Status</th>
                        <th class="py-2.5 px-3 text-center">Work Ticket</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-surface-300/30">
                    @forelse ($activeInvoices as $inv)
                        <tr class="hover:bg-gray-50 dark:hover:bg-surface-400/20 transition">
                            <td class="py-3 px-3 font-semibold text-white">#{{ str_pad($inv->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3 px-3 font-semibold text-white">{{ $inv->customer->name }}</td>
                            <td class="py-3 px-3 font-bold text-white">Rp. {{ number_format($inv->grand_total) }}</td>
                            <td class="py-3 px-3 text-red-400 font-semibold">{{ $inv->deadline }}</td>
                            <td class="py-3 px-3">
                                @if ($inv->status == 0)
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-50 bg-red-950/30 text-red-600 dark:text-red-400 uppercase tracking-wider">Unpaid</span>
                                @elseif ($inv->status == 1)
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 uppercase tracking-wider">Down Payment</span>
                                @elseif ($inv->status == 2)
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Paid Lunas</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <a href="/orders/{{ $inv->id }}/ticket" target="_blank" class="inline-flex items-center text-teal-500 hover:text-teal-700 font-semibold">
                                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-11h.01M12 11h.01"></path></svg>
                                    Job Ticket
                                </a>
                            </td>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 px-3 text-center text-gray-500">No active invoices placed yet.</td>
                            </tr>
                        @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
