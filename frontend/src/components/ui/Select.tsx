import { useId, type ReactNode, type SelectHTMLAttributes } from 'react';

import { cn } from '../../lib/cn';

export interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label?: string;
    error?: string;
    hint?: string;
    children: ReactNode;
}

export function Select({ label, error, hint, className, id, children, ...props }: SelectProps) {
    const generatedId = useId();
    const selectId = id ?? generatedId;
    const describedBy = error ? `${selectId}-error` : hint ? `${selectId}-hint` : undefined;

    return (
        <div className="space-y-1.5">
            {label ? (
                <label
                    htmlFor={selectId}
                    className="text-light-muted block text-[10px] font-semibold uppercase tracking-wider dark:text-gray-400"
                >
                    {label}
                </label>
            ) : null}
            <select
                id={selectId}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy}
                className={cn(
                    'text-light-text block h-10 w-full rounded-xl border bg-light-surface px-3 text-sm shadow-none transition focus:outline-none focus:ring-2 dark:bg-surface-700 dark:text-gray-100',
                    error
                        ? 'border-red-500 focus:border-red-500 focus:ring-red-500/30'
                        : 'border-light-border focus:border-brand-fill focus:ring-brand-fill/30 dark:border-surface-300',
                    className
                )}
                {...props}
            >
                {children}
            </select>
            {hint && !error ? (
                <p id={`${selectId}-hint`} className="text-light-muted text-xs dark:text-gray-400">
                    {hint}
                </p>
            ) : null}
            {error ? (
                <p id={`${selectId}-error`} className="text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
