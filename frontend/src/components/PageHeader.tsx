import type { ReactNode } from 'react';

export function PageHeader({
    eyebrow = 'Ruang Kerja',
    title,
    description,
    actions,
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    actions?: ReactNode;
}) {
    return (
        <header className="flex flex-wrap items-start justify-between gap-4">
            <div className="min-w-0">
                <p className="text-brand-primary text-xs font-semibold uppercase tracking-[0.25em]">
                    {eyebrow}
                </p>
                <h1 className="mt-1 text-2xl font-bold">{title}</h1>
                {description ? (
                    <p className="text-light-muted mt-1 max-w-3xl text-sm dark:text-gray-400">
                        {description}
                    </p>
                ) : null}
            </div>
            {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
        </header>
    );
}
