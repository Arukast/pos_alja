<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-5 py-2.5 bg-surface-500 border border-surface-200 rounded-xl font-semibold text-xs text-gray-300 uppercase tracking-widest shadow-sm hover:bg-surface-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-alja-cyan/30 focus:ring-offset-2 focus:ring-offset-surface-700 disabled:opacity-25 active:scale-[0.97] transition-all ease-in-out duration-200']) }}>
    {{ $slot }}
</button>
