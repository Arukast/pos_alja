@if (!empty($items))
<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <h2 class="text-light-text mb-4 text-lg font-bold dark:text-white">
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
                    <th class="rounded-r-xl px-4 py-3 text-center">Actions</th>
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
                        <div class="text-light-text font-bold dark:text-white">
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
                    <!-- prettier-ignore -->
                    <td class="text-light-text px-4 py-3 text-right font-extrabold dark:text-white">
                            Rp. {{ number_format($item['subtotal']) }}
                        </td>
                    <td class="px-4 py-3 text-center">
                        <!-- prettier-ignore -->
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
            @php $total = array_sum(array_column($items, 'subtotal')); $disc =
            (int) ($discount ?: 0); $grandTotal = max(0, $total - $disc);
            @endphp
            <!-- prettier-ignore -->
            <div class="text-brand-secondary mt-1 text-3xl font-black dark:text-teal-400">
                Rp. {{ number_format($grandTotal) }}
            </div>
        </div>
    </div>

    @error('items')
    <p class="text-red-550 mt-2 text-right text-xs font-bold dark:text-red-400">
        {{ $message }}
    </p>
    @enderror

    <!-- Submit Action -->
    <div class="mt-6 text-right">
        <button
            id="btn-submit-order"
            type="button"
            wire:click="submitOrder"
            class="bg-brand-primary hover:bg-brand-primary/95 ml-auto flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl px-8 py-3 text-sm font-bold text-white shadow-md transition md:w-auto"
            data-loading:class="opacity-50 pointer-events-none"
        >
            <span
                data-loading:inline-block
                class="hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
            ></span>
            Submit Draft Order to Cashier & Print Queues
        </button>
    </div>
</div>
@endif
