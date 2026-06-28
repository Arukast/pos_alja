@if ($activeTab === 'waste')
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
                    <th class="rounded-r-xl px-4 py-3 text-right">Reason</th>
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
                        <!-- prettier-ignore -->
                        {{ $wItem->material->name }} {{ $wItem->material->dimensions ? '(' . $wItem->material->dimensions . ')' : '' }}
                    </td>
                    <td
                        class="text-light-text px-4 py-3 font-bold dark:text-gray-300"
                    >
                        <!-- prettier-ignore -->
                        {{ $wItem->machine ? $wItem->machine->name : 'N/A' }}
                    </td>
                    <td class="px-4 py-3 font-extrabold">
                        <!-- prettier-ignore -->
                        {{ $wItem->quantity }} {{ $wItem->material->category }}
                    </td>
                    <td
                        class="text-light-muted px-4 py-3 font-semibold dark:text-gray-400"
                    >
                        <!-- prettier-ignore -->
                        {{ $wItem->designer ? $wItem->designer->name : '-' }}
                    </td>
                    <td
                        class="text-light-muted px-4 py-3 font-bold dark:text-gray-400"
                    >
                        <!-- prettier-ignore -->
                        {{ $wItem->operator ? $wItem->operator->name : '-' }}
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
@endif
