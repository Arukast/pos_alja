<div class="text-light-text space-y-6 dark:text-gray-100">
    <!-- Header banner -->
    @if ($activeTab === 'dashboard')
    <x-brand-header-banner
        badge="Print Shop Production Line"
        title="Active Print Queue"
        description="Track print jobs dispatched from designers, log printer machine usage, report failed prints (wasted stock), and execute completed work tickets."
        theme="production"
    />
    @endif

    <!-- Alert Feedback -->
    @if ($showSuccessAlert)
    <div
        class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-50 p-4 text-emerald-800 backdrop-blur-sm transition dark:bg-emerald-950/20 dark:text-emerald-300"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center">
            <svg
                class="mr-2 h-5 w-5 text-emerald-500 dark:text-emerald-400"
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
    @endif

    <!-- Quick stats grid -->
    @if ($activeTab === 'dashboard')
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat 1 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Jobs in Queue
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ $queueCount }} Jobs
                    </h3>
                </div>
                <div
                    class="bg-brand-accent/10 text-brand-primary rounded-xl p-3"
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
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="text-brand-primary mt-4 flex items-center text-xs font-bold"
            >
                <span
                    class="bg-brand-primary mr-2 h-2 w-2 animate-ping rounded-full"
                ></span>
                <span>Ready to assign to printers</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Currently Printing
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ $printingCount }} active
                    </h3>
                </div>
                <div
                    class="rounded-xl bg-amber-100/50 p-3 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400"
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
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89M9 11l3 3L22 4"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-bold text-amber-500"
            >
                <span>Active print machine runs</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Jobs Completed (Today)
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ $completedToday }} jobs
                    </h3>
                </div>
                <div
                    class="rounded-xl bg-emerald-500/10 p-3 text-emerald-500 dark:text-emerald-400"
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
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="mt-4 flex items-center text-xs font-bold text-emerald-600 dark:text-emerald-400"
            >
                <span>Finished today</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-light-muted text-[10px] font-bold tracking-wider uppercase dark:text-gray-400"
                    >
                        Failed / Waste logged
                    </p>
                    <h3
                        class="text-light-text mt-1 text-2xl font-extrabold dark:text-white"
                    >
                        {{ number_format($wasteToday, 2) }} units
                    </h3>
                </div>
                <div
                    class="text-red-550 dark:text-red-405 rounded-xl bg-red-500/10 p-3"
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        ></path>
                    </svg>
                </div>
            </div>
            <div
                class="text-red-550 dark:text-red-405 mt-4 flex items-center text-xs font-bold"
            >
                <span>Loss registered today</span>
            </div>
        </div>
    </div>
    @endif

    <!-- Active print queue board & Wasted logs -->
    @if ($activeTab === 'dashboard' || $activeTab === 'print_queue')
    <div class="grid grid-cols-1 gap-6">
        <!-- Live print queue board -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
        >
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3
                        class="text-light-text text-lg font-bold dark:text-white"
                    >
                        Active Print Tickets
                    </h3>
                    <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                        Work orders prioritized by job deadline.
                    </p>
                </div>
                <span
                    class="bg-brand-primary/10 text-brand-primary rounded-full px-3 py-1 text-[10px] font-bold tracking-wider uppercase"
                    >Queue: {{ $queueCount + $printingCount }} active</span
                >
            </div>

            <div class="space-y-3.5">
                @forelse ($activeOrders as $oItem)
                <div
                    class="bg-light-surface-2/65 dark:bg-surface-700/35 border-light-border dark:border-surface-300/30 flex flex-col gap-3 rounded-xl border p-4 transition-all hover:shadow-md sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-light-muted text-xs font-bold dark:text-gray-400"
                                >{{ $oItem->invoice->invoice_number }}</span
                            >
                            <span
                                class="bg-brand-primary/10 text-brand-primary inline-block rounded px-1.5 py-0.5 text-[9px] font-black tracking-wider uppercase"
                            >
                                CS: {{ $oItem->invoice->designer ?
                                $oItem->invoice->designer->name : 'N/A' }}
                            </span>
                        </div>
                        <h4
                            class="text-light-text mt-1 text-sm font-extrabold dark:text-white"
                        >
                            {{ $oItem->file_name }}
                        </h4>
                        <p
                            class="text-light-muted dark:text-gray-350 mt-1 text-xs leading-relaxed font-semibold"
                        >
                            Specs: {{ $oItem->notes }}
                        </p>
                        <p
                            class="text-light-muted mt-1.5 text-[10px] font-bold dark:text-gray-400"
                        >
                            Quantity:
                            <span
                                class="text-light-text font-extrabold dark:text-white"
                                >{{ $oItem->quantity }}</span
                            >
                            &bull; Client: {{ $oItem->invoice->customer->name }}
                        </p>
                    </div>
                    <div
                        class="flex items-center gap-2 self-start sm:self-center"
                    >
                        @if ($oItem->status == 0)
                        <button
                            id="btn-start-print-{{ $oItem->id }}"
                            wire:click="startOrderProduction('{{ $oItem->id }}')"
                            class="border-brand-primary text-brand-primary hover:bg-brand-primary/10 cursor-pointer rounded-xl border px-4 py-2 text-xs font-bold transition"
                        >
                            Start Printing
                        </button>
                        @else
                        <span
                            class="mr-2 inline-flex animate-pulse items-center text-[10px] font-extrabold text-amber-500"
                        >
                            <span
                                class="mr-1.5 h-1.5 w-1.5 rounded-full bg-amber-500"
                            ></span>
                            Printing...
                        </span>
                        <button
                            id="btn-complete-print-{{ $oItem->id }}"
                            wire:click="completeOrderProduction('{{ $oItem->id }}')"
                            class="hover:bg-emerald-650 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-xs font-bold text-white shadow-sm transition"
                        >
                            Mark Done
                        </button>
                        @endif
                    </div>
                </div>
                @empty
                <div
                    class="text-light-muted dark:text-gray-550 py-8 text-center text-xs font-semibold"
                >
                    No jobs currently in the print queue.
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif @if ($activeTab === 'waste')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Log failed prints / quick actions -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-1"
        >
            <h3 class="text-light-text text-lg font-bold dark:text-white">
                Log Print Waste
            </h3>
            <p class="text-light-muted mt-1 mb-4 text-xs dark:text-gray-400">
                Report failed runs to adjust inventory.
            </p>

            <form wire:submit.prevent="logWaste" class="space-y-4 text-xs">
                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Select Material *</label
                    >
                    <select
                        id="waste-material-id"
                        wire:model="materialId"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        required
                    >
                        <option
                            value=""
                            class="text-light-muted dark:text-gray-400"
                        >
                            -- Choose Material --
                        </option>
                        @foreach ($allMaterials as $m)
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

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Printer Machine</label
                    >
                    <select
                        id="waste-machine-id"
                        wire:model="machineId"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    >
                        <option
                            value=""
                            class="text-light-muted dark:text-gray-400"
                        >
                            -- Choose Machine --
                        </option>
                        @foreach ($allMachines as $mac)
                        <option
                            value="{{ $mac->id }}"
                            class="text-light-text font-semibold dark:text-gray-200"
                        >
                            {{ $mac->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                            >Wasted Qty *</label
                        >
                        <input
                            id="waste-quantity"
                            type="number"
                            step="any"
                            min="0.01"
                            wire:model="quantity"
                            placeholder="e.g. 1.5 or 5"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                            required
                        />
                    </div>
                    <div>
                        <label
                            class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                            >Dimensions (m)</label
                        >
                        <input
                            id="waste-dimensions"
                            type="text"
                            wire:model="dimensions"
                            placeholder="e.g. 2x1"
                            class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Responsible Designer</label
                    >
                    <select
                        id="waste-designer-id"
                        wire:model="designerId"
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary w-full rounded-xl border bg-white px-3 py-2 text-xs transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                    >
                        <option
                            value=""
                            class="text-light-muted dark:text-gray-400"
                        >
                            -- Choose Designer --
                        </option>
                        @foreach ($designersList as $des)
                        <option
                            value="{{ $des->id }}"
                            class="text-light-text dark:text-gray-200"
                        >
                            {{ $des->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label
                        class="text-light-muted mb-1 block font-bold dark:text-gray-400"
                        >Failure Reason *</label
                    >
                    <textarea
                        id="waste-notes"
                        wire:model="notes"
                        placeholder="e.g. Paper jam, head clog, color mismatch..."
                        class="border-light-border dark:border-surface-300/50 dark:bg-surface-500 text-light-text dark:text-gray-150 placeholder-light-muted focus:ring-brand-primary/30 focus:border-brand-primary h-16 w-full resize-none rounded-xl border bg-white px-3 py-2 transition duration-200 focus:ring-2 focus:outline-none dark:placeholder-gray-500"
                        required
                    ></textarea>
                </div>

                <button
                    id="btn-submit-waste"
                    type="submit"
                    class="w-full cursor-pointer rounded-xl bg-red-500 py-2.5 font-bold text-white shadow-sm transition hover:bg-red-600"
                >
                    Submit Material Waste Log
                </button>
            </form>
        </div>

        <!-- LOG OF RECENT FAILURES/WASTED MATERIALS -->
        <div
            class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm lg:col-span-2"
        >
            <h3 class="text-light-text mb-4 text-lg font-bold dark:text-white">
                Recent Logged Print Failures
            </h3>

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
                            <th class="rounded-l-xl px-4 py-3">Date</th>
                            <th class="px-4 py-3">Material</th>
                            <th class="px-4 py-3">Printer</th>
                            <th class="px-4 py-3">Wasted Qty</th>
                            <th class="px-4 py-3">Designer</th>
                            <th class="px-4 py-3">Operator</th>
                            <th class="rounded-r-xl px-4 py-3 text-right">
                                Reason
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-light-border dark:divide-surface-300/30 divide-y text-[11px]"
                    >
                        @forelse ($recentWaste as $wItem)
                        <tr
                            class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 text-red-600 transition-colors duration-150 dark:text-red-400"
                        >
                            <td class="px-4 py-3 font-semibold">
                                {{ $wItem->logged_at }}
                            </td>
                            <td
                                class="text-light-text px-4 py-3 font-bold dark:text-white"
                            >
                                {{ $wItem->material->name }} {{
                                $wItem->material->dimensions ? '(' .
                                $wItem->material->dimensions . ')' : '' }}
                            </td>
                            <td
                                class="text-light-text px-4 py-3 font-bold dark:text-gray-300"
                            >
                                {{ $wItem->machine ? $wItem->machine->name :
                                'N/A' }}
                            </td>
                            <td class="px-4 py-3 font-extrabold">
                                {{ $wItem->quantity }} {{
                                $wItem->material->category }}
                            </td>
                            <td
                                class="text-light-muted px-4 py-3 font-semibold dark:text-gray-400"
                            >
                                {{ $wItem->designer ? $wItem->designer->name :
                                '-' }}
                            </td>
                            <td
                                class="text-light-muted px-4 py-3 font-bold dark:text-gray-400"
                            >
                                {{ $wItem->operator ? $wItem->operator->name :
                                '-' }}
                            </td>
                            <td
                                class="px-4 py-3 text-right leading-relaxed font-medium italic"
                            >
                                {{ $wItem->notes }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td
                                colspan="7"
                                class="text-light-muted py-6 text-center font-semibold dark:text-gray-500"
                            >
                                No print failures logged.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
