import { useId, type InputHTMLAttributes } from 'react';

import { cn } from '../../lib/cn';

export interface CheckboxProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label: string;
    error?: string;
}

export function Checkbox({ label, error, className, id, ...props }: CheckboxProps) {
    const generatedId = useId();
    const checkboxId = id ?? generatedId;

    return (
        <div className="space-y-1">
            <label htmlFor={checkboxId} className="flex min-h-10 items-center gap-3 text-sm">
                <input
                    id={checkboxId}
                    type="checkbox"
                    className={cn(
                        'text-brand-fill focus:ring-brand-fill/30 h-4 w-4 rounded-[4px] border-light-border focus:ring-2 dark:border-surface-300 dark:bg-surface-700',
                        className
                    )}
                    {...props}
                />
                <span className="text-light-text dark:text-gray-200">{label}</span>
            </label>
            {error ? (
                <p className="text-xs text-red-600 dark:text-red-400">{error}</p>
            ) : null}
        </div>
    );
}
