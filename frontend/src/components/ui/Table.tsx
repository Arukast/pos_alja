import type { ReactNode, ThHTMLAttributes } from 'react';

import { cn } from '../../lib/cn';

export function Table({
    className,
    children,
    ariaLabel,
}: {
    className?: string;
    children: ReactNode;
    ariaLabel?: string;
}) {
    return (
        <div className="overflow-x-auto">
            <table
                aria-label={ariaLabel}
                className={cn('w-full min-w-full text-left text-sm tabular-nums', className)}
            >
                {children}
            </table>
        </div>
    );
}

export function TableHead({ children }: { children: ReactNode }) {
    return (
        <thead className="border-b border-light-border bg-light-surface-2/60 text-[10px] font-semibold uppercase tracking-wider text-light-muted dark:border-surface-300 dark:bg-surface-600/60 dark:text-gray-400">
            {children}
        </thead>
    );
}

export function TableBody({ children }: { children: ReactNode }) {
    return (
        <tbody className="divide-y divide-light-border dark:divide-surface-300">
            {children}
        </tbody>
    );
}

export function TableRow({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <tr className={cn('hover:bg-light-surface-2/70 dark:hover:bg-surface-600/60', className)}>
            {children}
        </tr>
    );
}

export function TableHeaderCell({
    className,
    children,
    ...props
}: ThHTMLAttributes<HTMLTableCellElement> & { children: ReactNode }) {
    return (
        <th scope="col" className={cn('px-2 py-2 sm:px-3', className)} {...props}>
            {children}
        </th>
    );
}

export function TableCell({
    className,
    children,
    colSpan,
}: {
    className?: string;
    children: ReactNode;
    colSpan?: number;
}) {
    return (
        <td colSpan={colSpan} className={cn('px-2 py-2 align-middle sm:px-3', className)}>
            {children}
        </td>
    );
}

export function TableEmpty({ colSpan, children }: { colSpan: number; children: ReactNode }) {
    return (
        <tr>
            <td
                colSpan={colSpan}
                className="px-3 py-8 text-left text-sm text-light-muted sm:text-center dark:text-gray-400"
            >
                {children}
            </td>
        </tr>
    );
}
