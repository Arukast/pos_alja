<!-- Customer Pickup Queue Panel -->
<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <h3 class="text-light-text text-lg font-bold dark:text-white">
        Customer Pickup Queue
    </h3>
    <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
        Paid orders ready to be picked up by customers.
    </p>

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
                    <th class="px-4 py-3">Payment Date</th>
                    <th class="px-4 py-3">Grand Total</th>
                    <th class="rounded-r-xl px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody
                class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
            >
                @forelse ($pickupQueue as $inv)
                <tr
                    class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
                >
                    <td
                        class="text-light-text px-4 py-3 font-bold dark:text-white"
                    >
                        {{ $inv->invoice_number }}
                    </td>
                    <td class="px-4 py-3">
                        <p class="text-light-text font-bold dark:text-white">
                            {{ $inv->customer->name }}
                        </p>
                        <p
                            class="text-light-muted text-[10px] font-medium dark:text-gray-400"
                        >
                            {{ $inv->customer->phone ?: 'No phone' }}
                        </p>
                    </td>
                    <td
                        class="text-light-text px-4 py-3 font-semibold dark:text-gray-300"
                    >
                        {{ $inv->payment_date }}
                    </td>
                    <td
                        class="text-light-text px-4 py-3 font-extrabold dark:text-gray-300"
                    >
                        Rp. {{ number_format($inv->grand_total) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button
                            id="btn-pickup-{{ $inv->id }}"
                            wire:click="markAsPickedUp('{{ $inv->id }}')"
                            class="bg-brand-primary hover:bg-brand-primary/95 cursor-pointer rounded-lg px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition data-loading:cursor-not-allowed data-loading:opacity-50"
                            data-loading-disable
                        >
                            Mark Picked Up
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td
                        colspan="5"
                        class="text-light-muted py-6 text-center dark:text-gray-500"
                    >
                        No items ready for pickup.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
