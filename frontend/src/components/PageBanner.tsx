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
    tone,
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    tone?: RoleTone;
}) {
    const { user } = useAuth();
    const resolvedTone = tone ?? toneForRole(user?.role);

    return (
        <header
            className={cn(
                'relative overflow-hidden rounded-2xl border px-5 py-5 sm:px-6',
                toneClasses[resolvedTone],
            )}
        >
            <span
                aria-hidden="true"
                className="bg-brand-primary/10 dark:bg-brand-primary/15 pointer-events-none absolute -top-10 -right-6 h-[120px] w-[120px] rounded-full blur-2xl"
            />
            <div className="relative z-10 flex items-start gap-3">
                <div className="flex gap-1 pt-1">
                    <span aria-hidden="true" className="bg-brand-primary h-2 w-2 rounded-[1.5px]" />
                    <span aria-hidden="true" className="bg-brand-secondary h-2 w-2 rounded-[1.5px]" />
                    <span aria-hidden="true" className="bg-brand-accent h-2 w-2 rounded-[1.5px]" />
                    <span aria-hidden="true" className="bg-brand-dark h-2 w-2 rounded-[1.5px]" />
                </div>
                <div className="min-w-0">
                    <p className="text-[10px] font-semibold uppercase tracking-widest">{eyebrow}</p>
                    <h1 className="text-light-text mt-1 text-[28px] font-extrabold tracking-tight dark:text-gray-100">
                        {title}
                    </h1>
                    {description ? (
                        <p className="text-light-muted-strong mt-1 max-w-3xl text-sm dark:text-gray-400">
                            {description}
                        </p>
                    ) : null}
                </div>
            </div>
        </header>
    );
}
