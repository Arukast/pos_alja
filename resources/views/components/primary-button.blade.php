<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-alja-magenta border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:bg-alja-magenta-hover focus:bg-alja-magenta-hover active:bg-alja-magenta-hover focus:outline-none focus:ring-2 focus:ring-alja-magenta/50 focus:ring-offset-2 focus:ring-offset-surface-700 shadow-glow-magenta hover:shadow-lg active:scale-[0.97] transition-all ease-in-out duration-200']) }}>
    {{ $slot }}
</button>
