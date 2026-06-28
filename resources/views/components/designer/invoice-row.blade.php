@props(['inv'])

<tr
    class="even:bg-light-bg/50 dark:even:bg-surface-500/20 hover:bg-brand-primary/5 dark:hover:bg-brand-primary/10 transition-colors duration-150"
>
    <!-- prettier-ignore -->
    <td class="text-light-text px-4 py-3 font-bold dark:text-white">
        #{{ str_pad((string) $inv->id, 5, '0', STR_PAD_LEFT) }}
    </td>
    <!-- prettier-ignore -->
    <td class="text-light-text px-4 py-3 font-bold dark:text-white">
        {{ $inv->customer->name }}
    </td>
    <!-- prettier-ignore -->
    <td class="text-light-text px-4 py-3 font-extrabold dark:text-white">
        Rp. {{ number_format($inv->grand_total) }}
    </td>
    <!-- prettier-ignore -->
    <td class="text-red-550 px-4 py-3 font-bold dark:text-red-400">
        {{ $inv->deadline }}
    </td>
    <td class="px-4 py-3">
        @if ($inv->status == 0)
        <span
            class="text-red-750 inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider uppercase dark:bg-red-900/30 dark:text-red-400"
            >Unpaid</span
        >
        @elseif ($inv->status == 1)
        <span
            class="inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-amber-700 uppercase dark:bg-amber-900/30 dark:text-amber-400"
            >Down Payment</span
        >
        @elseif ($inv->status == 2)
        <span
            class="inline-block rounded-full bg-emerald-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-emerald-700 uppercase dark:bg-emerald-900/30 dark:text-emerald-400"
            >Paid Lunas</span
        >
        @endif
    </td>
    <td class="px-4 py-3 text-center">
        <!-- prettier-ignore -->
        <a id="link-ticket-{{ $inv->id }}" href="/orders/{{ $inv->id }}/ticket" target="_blank" class="text-brand-secondary hover:text-brand-secondary/85 inline-flex items-center font-bold transition">
            <svg class="text-brand-secondary mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-3a2 2 0 00-2-2H9a2 2 0 00-2 2v3a2 2 0 002 2zm5-11h.01M12 11h.01"></path>
            </svg>
            Job Ticket
        </a>
    </td>
</tr>
