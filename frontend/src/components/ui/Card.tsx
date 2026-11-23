import type { ReactNode } from 'react';

import { cn } from '../../lib/cn';

export function Card({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <section
            className={cn(
                'rounded-2xl border border-light-border bg-light-surface shadow-card dark:border-surface-300 dark:bg-surface-700 dark:shadow-none',
                className
            )}
        >
            {children}
        </section>
    );
}

export function CardHeader({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <header
            className={cn(
                'flex items-start justify-between gap-3 border-b border-light-border px-5 py-4 dark:border-surface-300',
                className
            )}
        >
            {children}
        </header>
    );
}

export function CardBody({ className, children }: { className?: string; children: ReactNode }) {
    return <div className={cn('px-5 py-5', className)}>{children}</div>;
}

export function CardFooter({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <footer
            className={cn(
                'border-t border-light-border px-5 py-4 dark:border-surface-300',
                className
            )}
        >
            {children}
        </footer>
    );
}
