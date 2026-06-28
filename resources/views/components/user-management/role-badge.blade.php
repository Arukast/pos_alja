@props(['role']) @php $badgeClass = match ($role) { 'owner' => 'bg-red-100
text-red-700 dark:bg-red-900/30 dark:text-red-400', 'manager' => 'bg-amber-100
text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'cashier' =>
'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400', 'designer' =>
'bg-pink-100 text-pink-700 dark:bg-pink-900/30 dark:text-pink-400', 'production'
=> 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30
dark:text-emerald-400', default => 'bg-gray-100 text-gray-700
dark:bg-gray-900/30 dark:text-gray-400', }; $mergedAttributes =
$attributes->merge(['class' => "inline-block px-2 py-0.5 rounded-lg text-[9px]
font-bold uppercase tracking-wider {$badgeClass}"]); @endphp

<span {{ $mergedAttributes }}> {{ $role }} </span>
