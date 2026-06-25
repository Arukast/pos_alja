<div class="space-y-6 text-gray-200">
    <!-- Header banner -->
    <!-- Header banner -->
    <x-brand-header-banner
        badge="Print Shop Production Line"
        title="Active Print Queue"
        description="Track print jobs dispatched from designers, log printer machine usage, report failed prints (wasted stock), and execute completed work tickets."
        theme="production"
    />

    <!-- Alert Feedback -->
    @if ($showSuccessAlert)
    <div
        class="flex items-center justify-between rounded-xl border border-emerald-500/20 bg-emerald-950/20 p-4 backdrop-blur-sm transition"
        x-data="{ show: true }"
        x-show="show"
    >
        <div class="flex items-center">
            <svg
                class="mr-2 h-5 w-5 text-emerald-400"
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
    @endif

    <!-- Quick stats grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Stat 1 -->
        <div
            class="hover-lift shadow-card hover:shadow-card-hover dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Jobs in Queue
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        {{ $queueCount }} Jobs
                    </h3>
                </div>
                <div class="bg-brand-accent/10 rounded-lg p-3 text-orange-500">
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
                class="mt-4 flex items-center text-xs font-semibold text-orange-500"
            >
                <span
                    class="mr-2 h-2 w-2 animate-ping rounded-full bg-orange-500"
                ></span>
                <span>Ready to assign to printers</span>
            </div>
        </div>

        <!-- Stat 2 -->
        <div
            class="hover-lift shadow-card hover:shadow-card-hover dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Currently Printing
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        {{ $printingCount }} active
                    </h3>
                </div>
                <div
                    class="rounded-lg bg-amber-50 p-3 text-amber-500 dark:bg-amber-900/20"
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
                class="mt-4 flex items-center text-xs font-semibold text-amber-500"
            >
                <span>Active print machine runs</span>
            </div>
        </div>

        <!-- Stat 3 -->
        <div
            class="hover-lift shadow-card hover:shadow-card-hover dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Jobs Completed (Today)
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        {{ $completedToday }} jobs
                    </h3>
                </div>
                <div class="rounded-lg bg-emerald-500/10 p-3 text-emerald-400">
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
                class="mt-4 flex items-center text-xs font-semibold text-emerald-400"
            >
                <span>Finished today</span>
            </div>
        </div>

        <!-- Stat 4 -->
        <div
            class="hover-lift shadow-card hover:shadow-card-hover dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card relative overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm backdrop-blur-sm transition-all duration-200"
        >
            <div class="flex items-center justify-between">
                <div>
                    <p
                        class="text-xs font-medium tracking-wider text-gray-500 uppercase"
                    >
                        Failed / Waste logged
                    </p>
                    <h3 class="mt-1 text-2xl font-bold text-white">
                        {{ number_format($wasteToday, 2) }} units
                    </h3>
                </div>
                <div class="rounded-lg bg-red-500/10 p-3 text-red-400">
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
                class="mt-4 flex items-center text-xs font-semibold text-red-400"
            >
                <span>Loss registered today</span>
            </div>
        </div>
    </div>

    <!-- Active print queue board & Wasted logs -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Live print queue board -->
        <div
            class="shadow-card dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm lg:col-span-2"
        >
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-white">
                        Active Print Tickets
                    </h3>
                    <p class="mt-1 text-xs text-gray-500">
                        Work orders prioritized by job deadline.
                    </p>
                </div>
                <span
                    class="rounded-full bg-orange-50 px-3 py-1 text-[10px] font-bold tracking-wider text-orange-600 uppercase dark:bg-orange-950/30 dark:text-orange-400"
                    >Queue: {{ $queueCount + $printingCount }} active</span
                >
            </div>

            <div class="space-y-3.5">
                @forelse ($activeOrders as $oItem)
                <div
                    class="bg-gray-50/20/10 flex flex-col gap-3 rounded-lg border border-gray-100 p-4 transition hover:shadow-md sm:flex-row sm:items-center sm:justify-between dark:border-gray-700/30"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-gray-500"
                                >#{{ str_pad($oItem->invoice_id, 5, '0',
                                STR_PAD_LEFT) }}-{{ $oItem->id }}</span
                            >
                            <span
                                class="inline-block rounded bg-cyan-50 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-cyan-600 uppercase dark:bg-cyan-950/30 dark:text-cyan-400"
                            >
                                CS: {{ $oItem->invoice->customer_service }}
                            </span>
                        </div>
                        <h4 class="mt-1 text-sm font-bold text-white">
                            {{ $oItem->file_name }}
                        </h4>
                        <p class="mt-1 text-xs text-gray-400">
                            Specs: {{ $oItem->notes }}
                        </p>
                        <p
                            class="mt-0.5 text-[10px] font-semibold text-gray-400"
                        >
                            Quantity:
                            <span class="text-gray-800"
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
                            wire:click="startOrderProduction({{ $oItem->id }})"
                            class="rounded border border-orange-500 px-3 py-1.5 text-xs font-bold text-orange-500 transition hover:bg-orange-500 hover:text-white"
                        >
                            Start Printing
                        </button>
                        @else
                        <span
                            class="mr-2 inline-flex animate-pulse items-center text-[10px] font-bold text-amber-500"
                        >
                            <span
                                class="mr-1.5 h-1.5 w-1.5 rounded-full bg-amber-500"
                            ></span>
                            Printing...
                        </span>
                        <button
                            wire:click="completeOrderProduction({{ $oItem->id }})"
                            class="rounded bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-600"
                        >
                            Mark Done
                        </button>
                        @endif
                    </div>
                </div>
                @empty
                <div class="py-6 text-center text-xs text-gray-500">
                    No jobs currently in the print queue.
                </div>
                @endforelse
            </div>
        </div>

        <!-- Log failed prints / quick actions -->
        <div
            class="shadow-card dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm lg:col-span-1"
        >
            <h3 class="text-lg font-bold text-white">Log Print Waste</h3>
            <p class="mt-1 mb-4 text-xs text-gray-500">
                Report failed runs to adjust inventory.
            </p>

            <form wire:submit.prevent="logWaste" class="space-y-4 text-xs">
                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Select Material *</label
                    >
                    <select
                        wire:model="materialId"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-red-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        required
                    >
                        <option value="">-- Choose Material --</option>
                        @foreach ($allMaterials as $m)
                        <option value="{{ $m->id }}">
                            {{ $m->name }} {{ $m->dimensions ?
                            "({$m->dimensions})" : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Printer Machine</label
                    >
                    <select
                        wire:model="machineId"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-red-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                    >
                        <option value="">-- Choose Machine --</option>
                        @foreach ($allMachines as $mac)
                        <option value="{{ $mac->id }}">{{ $mac->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block font-semibold text-gray-500"
                            >Wasted Qty *</label
                        >
                        <input
                            type="number"
                            step="any"
                            min="0.01"
                            wire:model="quantity"
                            placeholder="e.g. 1.5 or 5"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-red-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                            required
                        />
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-gray-500"
                            >Dimensions (m)</label
                        >
                        <input
                            type="text"
                            wire:model="dimensions"
                            placeholder="e.g. 2x1"
                            class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-red-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Responsible Designer</label
                    >
                    <select
                        wire:model="designerName"
                        class="dark:border-surface-200 dark:bg-surface-600/50 w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-red-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                    >
                        <option value="">-- Choose Designer --</option>
                        @foreach ($designersList as $des)
                        <option value="{{ $des->name }}">
                            {{ $des->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block font-semibold text-gray-500"
                        >Failure Reason *</label
                    >
                    <textarea
                        wire:model="notes"
                        placeholder="e.g. Paper jam, head clog, color mismatch..."
                        class="dark:border-surface-200 dark:bg-surface-600/50 h-16 w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-gray-800 text-white placeholder-gray-400 focus:border-red-500 focus:outline-none dark:text-gray-100 dark:placeholder-gray-500"
                        required
                    ></textarea>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-lg bg-red-500 py-2 font-bold text-white shadow-sm transition hover:bg-red-600"
                >
                    Submit Material Waste Log
                </button>
            </form>
        </div>
    </div>

    <!-- LOG OF RECENT FAILURES/WASTED MATERIALS -->
    <div
        class="shadow-card dark:border-surface-300/40 dark:bg-surface-500/60 dark:shadow-card rounded-xl border border-gray-200 bg-white p-6 shadow-sm backdrop-blur-sm"
    >
        <h3 class="mb-4 text-lg font-bold text-white">
            Recent Logged Print Failures
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-400">
                <thead
                    class="text-gray-650 dark:bg-surface-600/80 bg-gray-100 font-semibold tracking-wider uppercase dark:text-gray-400"
                >
                    <tr>
                        <th class="px-3 py-2.5">Date</th>
                        <th class="px-3 py-2.5">Material</th>
                        <th class="px-3 py-2.5">Printer</th>
                        <th class="px-3 py-2.5">Wasted Qty</th>
                        <th class="px-3 py-2.5">Designer</th>
                        <th class="px-3 py-2.5">Operator</th>
                        <th class="px-3 py-2.5">Reason</th>
                    </tr>
                </thead>
                <tbody
                    class="dark:divide-surface-300/30 divide-y divide-gray-100"
                >
                    @forelse ($recentWaste as $wItem)
                    <tr
                        class="dark:hover:bg-surface-400/20 text-red-600 transition-all hover:bg-gray-50"
                    >
                        <td class="px-3 py-3">{{ $wItem->logged_at }}</td>
                        <td class="px-3 py-3 font-semibold text-white">
                            {{ $wItem->material->name }} {{
                            $wItem->material->dimensions ?
                            "({$wItem->material->dimensions})" : '' }}
                        </td>
                        <td class="px-3 py-3 font-medium text-gray-700">
                            {{ $wItem->machine ? $wItem->machine->name : 'N/A'
                            }}
                        </td>
                        <td class="px-3 py-3 font-bold">
                            {{ $wItem->quantity }} {{ $wItem->material->category
                            }}
                        </td>
                        <td class="px-3 py-3 text-gray-400">
                            {{ $wItem->designer_name ?: '-' }}
                        </td>
                        <td class="px-3 py-3 font-semibold text-gray-400">
                            {{ $wItem->operator_name }}
                        </td>
                        <td class="px-3 py-3 font-medium italic">
                            {{ $wItem->notes }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-4 text-center text-gray-500">
                            No print failures logged.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
