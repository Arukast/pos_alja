import type { ReactNode } from 'react';

export function EmptyState({
    title,
    description,
    icon,
    action,
}: {
    title: string;
    description?: string;
    icon?: ReactNode;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-light-border px-6 py-12 text-center dark:border-surface-300">
            {icon ? (
                <div className="mb-3 text-light-muted dark:text-gray-400">{icon}</div>
            ) : null}
            <p className="text-sm font-semibold text-light-text dark:text-gray-100">{title}</p>
            {description ? (
                <p className="mt-1 max-w-md text-sm text-light-muted dark:text-gray-400">
                    {description}
                </p>
            ) : null}
            {action ? <div className="mt-4">{action}</div> : null}
        </div>
    );
}
