import type { ButtonHTMLAttributes, ReactNode } from 'react';

import { cn } from '../../lib/cn';
import { Spinner } from './Spinner';

export type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'ghost';
export type ButtonSize = 'sm' | 'md';

export interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    variant?: ButtonVariant;
    size?: ButtonSize;
    loading?: boolean;
    children: ReactNode;
}

const variants: Record<ButtonVariant, string> = {
    primary: 'bg-brand-fill text-white shadow-glow-primary hover:bg-brand-fill-hover',
    secondary:
        'border border-light-muted bg-light-surface text-light-text hover:bg-light-surface-2 dark:bg-surface-600 dark:text-gray-100 dark:hover:bg-surface-500',
    danger: 'bg-red-600 text-white hover:bg-red-700',
    ghost:
        'text-light-muted hover:bg-light-surface-2 hover:text-light-text dark:text-gray-400 dark:hover:bg-surface-500 dark:hover:text-gray-100',
};

const sizes: Record<ButtonSize, string> = {
    sm: 'h-8 px-3 text-xs',
    md: 'h-10 px-4 text-sm',
};

export function Button({
    variant = 'primary',
    size = 'md',
    loading = false,
    className,
    children,
    disabled,
    type = 'button',
    ...props
}: ButtonProps) {
    return (
        <button
            type={type}
            disabled={disabled || loading}
            className={cn(
                'focus-visible:ring-brand-fill focus-visible:ring-offset-light-bg dark:focus-visible:ring-offset-surface-900 inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60',
                variants[variant],
                sizes[size],
                className
            )}
            {...props}
        >
            {loading ? <Spinner className="h-4 w-4" /> : null}
            {children}
        </button>
    );
}
