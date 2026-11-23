import type { ReactNode } from 'react';

import { cn } from '../../lib/cn';

export function StatCard({
    label,
    value,
    hint,
    icon,
    className,
}: {
    label: string;
    value: string;
    hint?: string;
    icon?: ReactNode;
    className?: string;
}) {
    return (
        <div
            className={cn(
                'rounded-2xl border border-light-border bg-light-surface p-5 dark:border-surface-300 dark:bg-surface-700',
                className
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <p className="text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:text-gray-400">
                    {label}
                </p>
                {icon ? (
                    <span className="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-primary/10 text-brand-primary">
                        {icon}
                    </span>
                ) : null}
            </div>
            <p className="mt-2 text-[22px] font-extrabold tracking-tight tabular-nums text-light-text dark:text-gray-100">
                {value}
            </p>
            {hint ? <p className="mt-1 text-xs text-light-muted dark:text-gray-400">{hint}</p> : null}
        </div>
    );
}
