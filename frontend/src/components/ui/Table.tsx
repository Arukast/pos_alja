import type { ReactNode, ThHTMLAttributes } from 'react';

import { cn } from '../../lib/cn';

export function Table({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div className="overflow-x-auto">
            <table className={cn('min-w-full text-left text-sm', className)}>{children}</table>
        </div>
    );
}

export function TableHead({ children }: { children: ReactNode }) {
    return (
        <thead className="text-light-muted border-light-border border-b text-xs uppercase tracking-wide dark:border-surface-300 dark:text-gray-400">
            {children}
        </thead>
    );
}

export function TableBody({ children }: { children: ReactNode }) {
    return (
        <tbody className="divide-light-border divide-y dark:divide-surface-300">
            {children}
        </tbody>
    );
}

export function TableRow({ children }: { children: ReactNode }) {
    return <tr className="hover:bg-light-surface-2/70 dark:hover:bg-surface-600/60">{children}</tr>;
}

export function TableHeaderCell({
    className,
    children,
    ...props
}: ThHTMLAttributes<HTMLTableCellElement> & { children: ReactNode }) {
    return (
        <th scope="col" className={cn('px-3 py-2 font-semibold', className)} {...props}>
            {children}
        </th>
    );
}

export function TableCell({ className, children }: { className?: string; children: ReactNode }) {
    return <td className={cn('px-3 py-3 align-top', className)}>{children}</td>;
}

export function TableEmpty({ colSpan, children }: { colSpan: number; children: ReactNode }) {
    return (
        <tr>
            <td
                colSpan={colSpan}
                className="text-light-muted px-3 py-8 text-center text-sm dark:text-gray-400"
            >
                {children}
            </td>
        </tr>
    );
}
