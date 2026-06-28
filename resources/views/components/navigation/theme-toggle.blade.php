<button
    @click="
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        }
    "
    class="text-light-muted hover:bg-brand-primary/10 hover:text-brand-primary dark:hover:bg-surface-400/50 flex items-center justify-center rounded-xl p-2 transition duration-200 dark:text-gray-400 dark:hover:text-white"
    title="Toggle Dark/Light Mode"
    aria-label="Toggle Dark/Light Mode"
    {{
    $attributes
    }}
>
    <x-icon name="sun" class="hidden h-5 w-5 shrink-0 dark:block" />
    <x-icon name="moon" class="block h-5 w-5 shrink-0 dark:hidden" />
</button>
