@props([ 'type', 'value', ]) @if ($type === 'invoice_status') @if ($value == 0)
<!-- prettier-ignore -->
<span class="dark:text-red-405 inline-block rounded-full bg-red-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-red-700 uppercase dark:bg-red-900/30">Unpaid</span>
@else
<!-- prettier-ignore -->
<span class="dark:text-amber-405 inline-block rounded-full bg-amber-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-amber-700 uppercase dark:bg-amber-900/30">Down Payment</span>
@endif @elseif ($type === 'expense_type') @if ($value === 'laba')
<!-- prettier-ignore -->
<span class="inline-block rounded-full bg-blue-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-blue-700 uppercase dark:bg-blue-900/30 dark:text-blue-400">Operational (Laba)</span>
@else
<!-- prettier-ignore -->
<span class="inline-block rounded-full bg-purple-100 px-2.5 py-0.5 text-[9px] font-black tracking-wider text-purple-700 uppercase dark:bg-purple-900/30 dark:text-purple-400">Capital / Goods (Modal)</span>
@endif @endif
