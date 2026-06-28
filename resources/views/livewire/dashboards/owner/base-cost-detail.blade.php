<div
    class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
>
    <!-- Header Controls and Date Range Filter -->
    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Base Cost (HPP) Detail
            </h3>
            <p class="text-light-muted text-xs dark:text-gray-400">
                Detailed overview of invoice base costs and production material
                waste.
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

    <!-- Base Cost Distribution Summary -->
    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div
            class="border-light-border dark:border-surface-300/20 dark:bg-surface-500/30 rounded-xl border bg-gray-50/50 p-4"
        >
            <span
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >Total HPP (Base Cost)</span
            >
            <p
                class="text-light-text mt-1 text-xl font-extrabold dark:text-white"
            >
                Rp. {{ number_format($total_hpp, 0, ',', '.') }}
            </p>
        </div>
        <div
            class="border-light-border dark:border-surface-300/20 dark:bg-surface-500/30 rounded-xl border bg-gray-50/50 p-4"
        >
            <span
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >Invoice HPP</span
            >
            <p
                class="text-light-text mt-1 text-xl font-extrabold text-blue-600 dark:text-blue-400"
            >
                Rp. {{ number_format($invoice_cost_total, 0, ',', '.') }}
            </p>
        </div>
        <div
            class="border-light-border dark:border-surface-300/20 dark:bg-surface-500/30 rounded-xl border bg-gray-50/50 p-4"
        >
            <span
                class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                >Material Waste HPP</span
            >
            <p
                class="text-light-text mt-1 text-xl font-extrabold text-amber-600 dark:text-amber-400"
            >
                Rp. {{ number_format($wasted_cost_total, 0, ',', '.') }}
            </p>
        </div>
    </div>

    <!-- Detailed Sections -->
    <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
        <!-- Invoice Base Costs Ledger -->
        <div>
            <h4 class="text-light-text mb-4 text-sm font-bold dark:text-white">
                Invoice HPP Ledger
            </h4>
            <div class="overflow-x-auto">
                <table
                    class="text-light-text w-full text-left text-xs dark:text-gray-200"
                >
                    <thead>
                        <tr
                            class="border-light-border dark:border-surface-300/20 text-light-muted border-b text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Invoice No</th>
                            <th class="px-4 py-3">Branch</th>
                            <th class="px-4 py-3 text-right">Cost (HPP)</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-light-border dark:divide-surface-300/10 divide-y"
                    >
                        @forelse ($invoices as $invoice)
                        <tr
                            class="dark:hover:bg-surface-500/20 transition-colors hover:bg-gray-50/40"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ $invoice->created_date }}
                            </td>
                            <!-- prettier-ignore -->
                            <td class="py-3 px-4 font-semibold text-brand-primary dark:text-brand-secondary">
                                    {{ $invoice->invoice_number }}
                                </td>
                            <td class="px-4 py-3">
                                {{ $invoice->branch ? $invoice->branch->name :
                                '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-extrabold">
                                Rp. {{ number_format((int) $invoice->total_cost,
                                0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="4"
                                class="text-light-muted py-8 text-center dark:text-gray-400"
                            >
                                No invoices recorded within this range.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Material Waste Ledger -->
        <div>
            <h4 class="text-light-text mb-4 text-sm font-bold dark:text-white">
                Material Waste Cost Ledger
            </h4>
            <div class="overflow-x-auto">
                <table
                    class="text-light-text w-full text-left text-xs dark:text-gray-200"
                >
                    <thead>
                        <tr
                            class="border-light-border dark:border-surface-300/20 text-light-muted border-b text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                        >
                            <th class="px-4 py-3">Date/Time</th>
                            <th class="px-4 py-3">Material</th>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Branch</th>
                            <th class="px-4 py-3 text-right">Waste Cost</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-light-border dark:divide-surface-300/10 divide-y"
                    >
                        @forelse ($wasted_materials as $waste)
                        <tr
                            class="dark:hover:bg-surface-500/20 transition-colors hover:bg-gray-50/40"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{
                                \Carbon\Carbon::parse($waste->logged_at)->format('Y-m-d
                                H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $waste->material ? $waste->material->name :
                                '-' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ (float) $waste->quantity }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $waste->branch ? $waste->branch->name : '-'
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-extrabold text-amber-600 dark:text-amber-400"
                            >
                                Rp. {{ number_format((int) ($waste->quantity *
                                ($waste->material ? $waste->material->cost :
                                0)), 0, ',', '.') }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="5"
                                class="text-light-muted py-8 text-center dark:text-gray-400"
                            >
                                No material waste recorded within this range.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
