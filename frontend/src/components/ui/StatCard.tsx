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
                'border-light-border shadow-card rounded-2xl border bg-white p-4 dark:border-surface-300 dark:bg-surface-700',
                className
            )}
        >
            <div className="flex items-start justify-between gap-3">
                <p className="text-light-muted text-xs font-semibold uppercase tracking-wide dark:text-gray-400">
                    {label}
                </p>
                {icon ? <span className="text-brand-primary">{icon}</span> : null}
            </div>
            <p className="text-light-text mt-2 text-2xl font-bold dark:text-gray-100">{value}</p>
            {hint ? (
                <p className="text-light-muted mt-1 text-xs dark:text-gray-400">{hint}</p>
            ) : null}
        </div>
    );
}
