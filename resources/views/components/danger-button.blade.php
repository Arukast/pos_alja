@php $mergedAttributes = $attributes->merge(['type' => 'submit', 'class' =>
'inline-flex items-center px-5 py-2.5 bg-red-600 border border-transparent
rounded-xl font-semibold text-xs text-white uppercase tracking-widest
hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2
focus:ring-red-500/50 focus:ring-offset-2 focus:ring-offset-light-bg
dark:focus:ring-offset-surface-800 active:scale-[0.97] transition-all
ease-in-out duration-200']); @endphp
<button {{ $mergedAttributes }}>{{ $slot }}</button>
