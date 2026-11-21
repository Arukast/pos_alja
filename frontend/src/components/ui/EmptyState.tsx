import type { ReactNode } from 'react';

export function EmptyState({
    title,
    description,
    action,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="border-light-border flex flex-col items-center justify-center rounded-2xl border border-dashed px-6 py-10 text-center dark:border-surface-300">
            <p className="text-light-text font-semibold dark:text-gray-100">{title}</p>
            {description ? (
                <p className="text-light-muted mt-1 max-w-md text-sm dark:text-gray-400">
                    {description}
                </p>
            ) : null}
            {action ? <div className="mt-4">{action}</div> : null}
        </div>
    );
}
