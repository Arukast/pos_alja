import { Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';

function isDarkTheme(): boolean {
    if (typeof document === 'undefined') {
        return false;
    }

    return document.documentElement.classList.contains('dark');
}

export function ThemeToggle({ compact = false }: { compact?: boolean }) {
    const [dark, setDark] = useState(isDarkTheme);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    }, [dark]);

    return (
        <button
            type="button"
            aria-label={dark ? 'Switch to light mode' : 'Switch to dark mode'}
            aria-pressed={dark}
            title={dark ? 'Light mode' : 'Dark mode'}
            onClick={() => setDark((value) => !value)}
            className="text-light-muted hover:bg-light-surface-2 hover:text-light-text dark:text-gray-400 dark:hover:bg-surface-600 dark:hover:text-gray-100 inline-flex h-9 items-center justify-center gap-2 rounded-lg px-2.5 text-sm transition"
        >
            {dark ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
            {compact ? null : <span>{dark ? 'Light' : 'Dark'}</span>}
        </button>
    );
}
