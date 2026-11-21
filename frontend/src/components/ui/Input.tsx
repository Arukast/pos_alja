import { useId, type InputHTMLAttributes } from 'react';

import { cn } from '../../lib/cn';

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
    label?: string;
    error?: string;
    hint?: string;
}

export function Input({ label, error, hint, className, id, ...props }: InputProps) {
    const generatedId = useId();
    const inputId = id ?? generatedId;
    const describedBy = error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined;

    return (
        <div className="space-y-1.5">
            {label ? (
                <label
                    htmlFor={inputId}
                    className="text-light-text block text-sm font-medium dark:text-gray-200"
                >
                    {label}
                </label>
            ) : null}
            <input
                id={inputId}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy}
                className={cn(
                    'text-light-text block w-full rounded-lg border bg-white px-3 py-2 text-sm shadow-sm transition placeholder:text-gray-400 focus:outline-none focus:ring-2 dark:bg-surface-700 dark:text-gray-100',
                    error
                        ? 'border-red-500 focus:border-red-500 focus:ring-red-500/30'
                        : 'border-light-border focus:border-brand-primary focus:ring-brand-primary/30 dark:border-surface-300',
                    className
                )}
                {...props}
            />
            {hint && !error ? (
                <p id={`${inputId}-hint`} className="text-light-muted text-xs dark:text-gray-400">
                    {hint}
                </p>
            ) : null}
            {error ? (
                <p id={`${inputId}-error`} className="text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
