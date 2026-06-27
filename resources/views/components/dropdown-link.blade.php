@php $mergedAttributes = $attributes->merge(['class' => 'block w-full px-4 py-2
text-start text-sm leading-5 text-light-text dark:text-gray-300
hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:text-white
focus:outline-none focus:bg-brand-primary/10 focus:text-brand-primary
dark:focus:text-white transition duration-150 ease-in-out']); @endphp
<a {{ $mergedAttributes }}>{{ $slot }}</a>
