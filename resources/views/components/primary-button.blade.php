@php $mergedAttributes = $attributes->merge(['type' => 'submit', 'class' =>
'inline-flex items-center px-5 py-2.5 bg-brand-primary border border-transparent
rounded-xl font-semibold text-xs text-white uppercase tracking-widest
hover:bg-brand-primary-hover focus:bg-brand-primary-hover
active:bg-brand-primary-hover focus:outline-none focus:ring-2
focus:ring-brand-primary/50 focus:ring-offset-2 focus:ring-offset-light-bg
dark:focus:ring-offset-surface-800 shadow-glow-primary hover:shadow-lg
active:scale-[0.97] transition-all ease-in-out duration-200']); @endphp
<button {{ $mergedAttributes }}>{{ $slot }}</button>
