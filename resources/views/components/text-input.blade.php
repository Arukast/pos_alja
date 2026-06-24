@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-surface-200 bg-surface-600/50 text-gray-100 placeholder-gray-500 focus:border-alja-magenta focus:ring-alja-magenta/30 rounded-xl shadow-sm transition-colors duration-200']) }}>
