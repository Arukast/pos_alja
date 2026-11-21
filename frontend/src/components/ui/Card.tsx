import type { ReactNode } from 'react';

import { cn } from '../../lib/cn';

export function Card({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <section
            className={cn(
                'border-light-border shadow-card rounded-2xl border bg-white dark:border-surface-300 dark:bg-surface-700',
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
                'border-light-border flex items-start justify-between gap-3 border-b px-4 py-3 dark:border-surface-300',
                className
            )}
        >
            {children}
        </header>
    );
}

export function CardBody({ className, children }: { className?: string; children: ReactNode }) {
    return <div className={cn('px-4 py-4', className)}>{children}</div>;
}

export function CardFooter({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <footer
            className={cn(
                'border-light-border border-t px-4 py-3 dark:border-surface-300',
                className
            )}
        >
            {children}
        </footer>
    );
}
