@if ($activeTab === 'dashboard')
<!-- Recent Placed Invoices -->
<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-light-text text-lg font-bold dark:text-white">
            Recent Placed Invoices
        </h2>
        <button
            type="button"
            wire:click="$set('activeTab', 'order_list')"
            class="text-brand-primary cursor-pointer text-xs font-bold hover:underline dark:text-teal-400"
        >
            View All Orders &rarr;
        </button>
    </div>
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
                <x-designer.invoice-row :inv="$inv" />
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
@elseif ($activeTab === 'order_list')
<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 space-y-4 rounded-2xl border bg-white p-6 shadow-sm"
>
    <div
        class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
    >
        <h2 class="text-light-text text-lg font-bold dark:text-white">
            All Placed Invoices
        </h2>
        <div class="relative w-full md:w-80">
            <input
                type="text"
                wire:model.live.debounce.300ms="invoiceSearch"
                placeholder="Search invoices by ID, customer..."
                class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
            />
        </div>
    </div>
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
                <x-designer.invoice-row :inv="$inv" />
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
    <div class="mt-4">{{ $activeInvoices->links() }}</div>
</div>
@endif
