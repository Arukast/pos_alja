import type { ReactNode } from 'react';

import { useAuth } from '../auth/useAuth';
import { cn } from '../lib/cn';

export type RoleTone = 'owner' | 'manager' | 'cashier' | 'designer' | 'production' | 'neutral';

// Literal class strings only: Tailwind cannot see interpolated classes.
const opsTone =
    'bg-tint-ops border-sky-200 text-tint-ops-ink dark:bg-brand-secondary/10 dark:border-sky-400/25 dark:text-brand-secondary-light';

const toneClasses: Record<RoleTone, string> = {
    owner:
        'bg-tint-owner border-brand-primary/20 text-brand-fill-hover dark:bg-brand-primary/10 dark:border-brand-primary/25 dark:text-brand-primary-light',
    manager: opsTone,
    cashier: opsTone,
    designer:
        'bg-tint-designer border-teal-200 text-tint-designer-ink dark:bg-teal-400/10 dark:border-teal-400/25 dark:text-teal-300',
    production: opsTone,
    neutral:
        'bg-light-surface border-light-border text-light-muted dark:bg-surface-700 dark:border-surface-300 dark:text-gray-400',
};

function toneForRole(role: string | undefined): RoleTone {
    switch (role) {
        case 'owner':
        case 'manager':
        case 'cashier':
        case 'designer':
        case 'production':
            return role;
        default:
            return 'neutral';
    }
}

export function PageBanner({
    eyebrow = 'Ruang Kerja',
    title,
    description,
    actions,
    tone,
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    actions?: ReactNode;
    tone?: RoleTone;
}) {
    const { user } = useAuth();
    const resolvedTone = tone ?? toneForRole(user?.role);

    return (
        <header
            className={cn(
                'flex flex-wrap items-start justify-between gap-4 rounded-2xl border px-5 py-5 sm:px-6',
                toneClasses[resolvedTone],
            )}
        >
            <div className="min-w-0">
                <p className="text-[10px] font-semibold uppercase tracking-widest">{eyebrow}</p>
                <h1 className="text-light-text mt-1 text-2xl font-bold tracking-tight dark:text-gray-100">
                    {title}
                </h1>
                {description ? (
                    <p className="text-light-muted-strong mt-1 max-w-3xl text-sm dark:text-gray-400">
                        {description}
                    </p>
                ) : null}
            </div>
            {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
        </header>
    );
}
