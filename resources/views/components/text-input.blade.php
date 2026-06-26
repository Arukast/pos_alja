@props(['disabled' => false]) @php $mergedAttributes =
$attributes->merge(['class' => 'border-gray-300 dark:border-surface-200 bg-white
dark:bg-surface-600/50 text-gray-800 dark:text-gray-100 placeholder-gray-400
dark:placeholder-gray-500 focus:border-brand-primary focus:ring-brand-primary/30
rounded-xl shadow-sm transition-colors duration-200']); @endphp
<input @disabled($disabled) {{ $mergedAttributes }} />
