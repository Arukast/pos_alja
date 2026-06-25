@props(['active']) @php $classes = ($active ?? false) ? 'block w-full ps-3 pe-4
py-2 border-l-4 border-brand-primary text-start text-base font-semibold
text-gray-900 dark:text-white bg-brand-primary/10 focus:outline-none transition
duration-200 ease-in-out' : 'block w-full ps-3 pe-4 py-2 border-l-4
border-transparent text-start text-base font-medium text-gray-600
dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100
dark:hover:bg-surface-400/30 hover:border-brand-primary/40 focus:outline-none
focus:text-gray-900 dark:focus:text-white focus:bg-gray-100
dark:focus:bg-surface-400/30 focus:border-gray-300 dark:focus:border-surface-200
transition duration-200 ease-in-out'; @endphp

<a {{ $attributes->merge(['class' => $classes]) }}> {{ $slot }} </a>
