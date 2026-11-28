import type { ReactNode } from 'react';

import { cn } from '../../lib/cn';

export type BadgeTone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info' | 'violet';

const tones: Record<BadgeTone, string> = {
    neutral: 'bg-light-surface-2 text-gray-600 dark:bg-surface-600 dark:text-gray-300',
    brand: 'bg-brand-fill/10 text-brand-fill-hover dark:bg-brand-fill/20 dark:text-brand-primary-light',
    success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    warning: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    danger: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
    info: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
};

export function Badge({
    tone = 'neutral',
    className,
    children,
}: {
    tone?: BadgeTone;
    className?: string;
    children: ReactNode;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                tones[tone],
                className
            )}
        >
            <span aria-hidden="true" className="h-1.5 w-1.5 rounded-full bg-current" />
            {children}
        </span>
    );
}
