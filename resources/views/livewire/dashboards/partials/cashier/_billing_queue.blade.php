<!-- Billing Queue Panel -->
<div
    class="{{ $activeTab === 'payments' ? 'w-full' : 'lg:col-span-2' }} border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <h3 class="text-light-text text-lg font-bold dark:text-white">
        Active Billing Queue
    </h3>
    <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
        Invoices ready for Down Payment (DP) or final settlement.
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
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Grand Total</th>
                    <th class="px-4 py-3">Remaining AR</th>
                    <th class="rounded-r-xl px-4 py-3 text-right">Actions</th>
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
                    <td class="px-4 py-3">
                        <x-cashier.status-badge
                            type="invoice_status"
                            :value="$inv->status"
                        />
                    </td>
                    <td
                        class="text-light-text dark:text-gray-250 px-4 py-3 font-semibold"
                    >
                        Rp. {{ number_format($inv->grand_total) }}
                    </td>
                    <td
                        class="text-red-550 px-4 py-3 font-extrabold dark:text-red-400"
                    >
                        Rp. {{ number_format($inv->accounts_receivable) }}
                    </td>
                    <td
                        class="flex items-center justify-end gap-2 px-4 py-3 text-right"
                    >
                        @if ($inv->status == 0)
                        <button
                            id="btn-dp-{{ $inv->id }}"
                            wire:click="selectInvoice('{{ $inv->id }}', 'dp')"
                            class="bg-brand-secondary hover:bg-brand-secondary/95 cursor-pointer rounded-lg px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition"
                        >
                            Register DP
                        </button>
                        @endif
                        <button
                            id="btn-settle-{{ $inv->id }}"
                            wire:click="selectInvoice('{{ $inv->id }}', 'settle')"
                            class="cursor-pointer rounded-lg bg-emerald-500 px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition hover:bg-emerald-600"
                        >
                            Checkout
                        </button>
                        <a
                            id="link-receipt-{{ $inv->id }}"
                            href="/orders/{{ $inv->id }}/receipt"
                            target="_blank"
                            class="border-brand-primary text-brand-primary hover:bg-brand-primary/10 rounded-lg border px-3 py-1 text-[11px] font-bold transition"
                            >Receipt</a
                        >
                    </td>
                </tr>
                @empty
                <tr>
                    <td
                        colspan="6"
                        class="text-light-muted py-6 text-center dark:text-gray-500"
                    >
                        No active invoices in the checkout queue.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
