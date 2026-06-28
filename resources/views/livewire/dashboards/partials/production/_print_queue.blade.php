@if ($activeTab === 'dashboard' || $activeTab === 'print_queue')
@island('print-queue')
<div class="grid grid-cols-1 gap-6">
    <!-- Live print queue board -->
    <div
        class="border-light-border dark:border-surface-300/40 dark:bg-surface-600 rounded-2xl border bg-white p-6 shadow-sm"
    >
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-light-text text-lg font-bold dark:text-white">
                    Active Print Tickets
                </h3>
                <p class="text-light-muted mt-1 text-xs dark:text-gray-400">
                    Work orders prioritized by job deadline.
                </p>
            </div>
            <span
                class="bg-brand-primary/10 text-brand-primary rounded-full px-3 py-1 text-[10px] font-bold tracking-wider uppercase"
            >
                Queue: {{ $queueCount + $printingCount }} active
            </span>
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
                        >
                            {{ $oItem->invoice->invoice_number }}
                        </span>
                        <span
                            class="bg-brand-primary/10 text-brand-primary inline-block rounded px-1.5 py-0.5 text-[9px] font-black tracking-wider uppercase"
                        >
                            <!-- prettier-ignore -->
                            CS: {{ $oItem->invoice->designer ? $oItem->invoice->designer->name : 'N/A' }}
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
                        >
                            {{ $oItem->quantity }}
                        </span>
                        <!-- prettier-ignore -->
                        &bull; Client: {{ $oItem->invoice->customer->name }}
                    </p>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-center">
                    @if ($oItem->status == 0)
                    <button
                        id="btn-start-print-{{ $oItem->id }}"
                        wire:click="startOrderProduction('{{ $oItem->id }}')"
                        class="border-brand-primary text-brand-primary hover:bg-brand-primary/10 cursor-pointer rounded-xl border px-4 py-2 text-xs font-bold transition data-loading:cursor-not-allowed data-loading:opacity-50"
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
                        class="hover:bg-emerald-650 cursor-pointer rounded-xl bg-emerald-500 px-4 py-2 text-xs font-bold text-white shadow-sm transition data-loading:cursor-not-allowed data-loading:opacity-50"
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
@endisland @endif
