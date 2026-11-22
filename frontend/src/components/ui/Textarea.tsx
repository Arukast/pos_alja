import { useId, type TextareaHTMLAttributes } from 'react';

import { cn } from '../../lib/cn';

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label?: string;
    error?: string;
    hint?: string;
}

export function Textarea({ label, error, hint, className, id, ...props }: TextareaProps) {
    const generatedId = useId();
    const textareaId = id ?? generatedId;
    const describedBy = error ? `${textareaId}-error` : hint ? `${textareaId}-hint` : undefined;

    return (
        <div className="space-y-1.5">
            {label ? (
                <label
                    htmlFor={textareaId}
                    className="text-light-muted block text-[10px] font-semibold uppercase tracking-wider dark:text-gray-400"
                >
                    {label}
                </label>
            ) : null}
            <textarea
                id={textareaId}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy}
                className={cn(
                    'text-light-text block min-h-24 w-full rounded-xl border bg-light-surface px-3 py-2.5 text-sm shadow-none transition placeholder:text-gray-400 focus:outline-none focus:ring-2 dark:bg-surface-700 dark:text-gray-100',
                    error
                        ? 'border-red-500 focus:border-red-500 focus:ring-red-500/30'
                        : 'border-light-border focus:border-brand-fill focus:ring-brand-fill/30 dark:border-surface-300',
                    className
                )}
                {...props}
            />
            {hint && !error ? (
                <p
                    id={`${textareaId}-hint`}
                    className="text-light-muted text-xs dark:text-gray-400"
                >
                    {hint}
                </p>
            ) : null}
            {error ? (
                <p id={`${textareaId}-error`} className="text-xs text-red-600 dark:text-red-400">
                    {error}
                </p>
            ) : null}
        </div>
    );
}
