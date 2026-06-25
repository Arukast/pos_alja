@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-alja-magenta text-sm font-semibold leading-5 text-gray-900 dark:text-white focus:outline-none transition duration-200 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-alja-magenta/40 focus:outline-none focus:text-gray-900 dark:focus:text-white focus:border-gray-300 dark:focus:border-surface-200 transition duration-200 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
