@props(['disabled' => false]) @php $mergedAttributes = $attributes->merge([
'class' => 'border-light-border dark:border-surface-300/50 bg-white
dark:bg-surface-500 text-light-text dark:text-gray-100 placeholder-light-muted
dark:placeholder-gray-400 focus:border-brand-primary focus:ring-2
focus:ring-brand-primary/30 rounded-xl shadow-sm transition-colors duration-200'
]); @endphp

<input @disabled($disabled) {{ $mergedAttributes }} />
