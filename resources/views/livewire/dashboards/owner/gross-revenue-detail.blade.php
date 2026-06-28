<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <!-- Header Controls and Date Range Filter -->
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Gross Revenue (Omset) Detail
            </h3>
            <p class="text-light-muted text-xs dark:text-gray-400">
                Detailed overview of received payments and payment methods.
            </p>
        </div>

        <form
            wire:submit.prevent="applyCustomRange"
            class="flex flex-wrap items-end gap-3"
        >
            <div>
                <label
                    for="start-date-input"
                    class="text-light-text block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >Start Date</label
                >
                <input
                    id="start-date-input"
                    type="date"
                    wire:model="form.startDate"
                    class="border-light-border dark:border-surface-300/50 text-light-text dark:bg-surface-600 focus:ring-brand-primary/20 block rounded-xl border bg-white px-3 py-1.5 text-xs focus:ring-2 focus:outline-none dark:text-white"
                />
            </div>
            <div>
                <label
                    for="end-date-input"
                    class="text-light-text block text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >End Date</label
                >
                <input
                    id="end-date-input"
                    type="date"
                    wire:model="form.endDate"
                    class="border-light-border dark:border-surface-300/50 text-light-text dark:bg-surface-600 focus:ring-brand-primary/20 block rounded-xl border bg-white px-3 py-1.5 text-xs focus:ring-2 focus:outline-none dark:text-white"
                />
            </div>
            <button
                type="submit"
                class="bg-brand-primary hover:bg-brand-primary/95 block rounded-xl px-4 py-1.5 text-xs font-semibold text-white shadow-xs transition-all"
            >
                Apply Filter
            </button>
        </form>
    </div>

    <!-- Livewire Error Messages -->
    @if ($errors->any())
    <div
        class="text-red-650 mt-4 rounded-xl bg-red-500/10 p-3 text-xs dark:text-red-400"
    >
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Payment Methods Distribution Summary -->
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div
            class="border-light-border dark:border-surface-300/20 dark:bg-surface-500/30 rounded-xl border bg-gray-50/50 p-4"
        >
            <span
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >Total Collected</span
            >
            <p
                class="text-light-text mt-1 text-xl font-extrabold dark:text-white"
            >
                Rp. {{ number_format($grand_total, 0, ',', '.') }}
            </p>
        </div>
        <div
            class="border-light-border dark:border-surface-300/20 dark:bg-surface-500/30 rounded-xl border bg-gray-50/50 p-4"
        >
            <span
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >Cash Payments</span
            >
            <p
                class="text-light-text mt-1 text-xl font-extrabold text-emerald-600 dark:text-emerald-400"
            >
                Rp. {{ number_format($cash_total, 0, ',', '.') }}
            </p>
        </div>
        <div
            class="border-light-border dark:border-surface-300/20 dark:bg-surface-500/30 rounded-xl border bg-gray-50/50 p-4"
        >
            <span
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >Transfer Payments</span
            >
            <p
                class="text-light-text mt-1 text-xl font-extrabold text-blue-600 dark:text-blue-400"
            >
                Rp. {{ number_format($transfer_total, 0, ',', '.') }}
            </p>
        </div>
        <div
            class="border-light-border dark:border-surface-300/20 dark:bg-surface-500/30 rounded-xl border bg-gray-50/50 p-4"
        >
            <span
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >QRIS Payments</span
            >
            <p
                class="text-light-text mt-1 text-xl font-extrabold text-purple-600 dark:text-purple-400"
            >
                Rp. {{ number_format($qris_total, 0, ',', '.') }}
            </p>
        </div>
    </div>

    <!-- Transaction Ledger Table -->
    <div class="mt-6 overflow-x-auto">
        <table
            class="text-light-text w-full text-left text-xs dark:text-gray-200"
        >
            <thead>
                <tr
                    class="border-light-border dark:border-surface-300/20 text-light-muted border-b text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >
                    <th class="px-4 py-3">Date/Time</th>
                    <th class="px-4 py-3">Invoice No</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Branch</th>
                    <th class="px-4 py-3">Method</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                </tr>
            </thead>
            <tbody
                class="divide-light-border dark:divide-surface-300/10 divide-y"
            >
                @forelse ($payments as $payment)
                <tr
                    class="dark:hover:bg-surface-500/20 transition-colors hover:bg-gray-50/40"
                >
                    <td class="px-4 py-3.5 font-medium">
                        {{ $payment->created_at->format('Y-m-d H:i') }}
                    </td>
                    <!-- prettier-ignore -->
                    <td class="py-3.5 px-4 font-semibold text-brand-primary dark:text-brand-secondary">
                            {{ $payment->invoice ? $payment->invoice->invoice_number : '-' }}
                        </td>
                    <!-- prettier-ignore -->
                    <td class="py-3.5 px-4">
                            {{ $payment->invoice && $payment->invoice->customer ? $payment->invoice->customer->name : 'Walk-in Customer' }}
                        </td>
                    <td class="px-4 py-3.5">
                        {{ $payment->branch ? $payment->branch->name : '-' }}
                    </td>
                    <td class="px-4 py-3.5">
                        @php $method = $payment->payment_method; $badgeClass =
                        $method === 'Cash' ? 'bg-emerald-500/10 text-emerald-600
                        dark:text-emerald-400' : ($method === 'Transfer' ?
                        'bg-blue-500/10 text-blue-600 dark:text-blue-400' :
                        'bg-purple-500/10 text-purple-600
                        dark:text-purple-400'); @endphp
                        <span
                            class="{{ $badgeClass }} rounded-md px-2 py-0.5 font-semibold text-[10px]"
                            >{{ $method }}</span
                        >
                    </td>
                    <td class="px-4 py-3.5 text-right font-extrabold">
                        Rp. {{ number_format((int) $payment->amount, 0, ',',
                        '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td
                        colspan="6"
                        class="text-light-muted py-8 text-center dark:text-gray-400"
                    >
                        No transactions recorded within this range.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
