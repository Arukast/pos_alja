import type { ReactNode, ThHTMLAttributes } from 'react';

import { cn } from '../../lib/cn';

export function Table({ className, children }: { className?: string; children: ReactNode }) {
    return (
        <div className="overflow-x-auto">
            <table className={cn('w-full min-w-[560px] text-left text-sm tabular-nums sm:min-w-[720px]', className)}>{children}</table>
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

export function TableRow({ children }: { children: ReactNode }) {
    return <tr className="hover:bg-light-surface-2/70 dark:hover:bg-surface-600/60">{children}</tr>;
}

export function TableHeaderCell({
    className,
    children,
    ...props
}: ThHTMLAttributes<HTMLTableCellElement> & { children: ReactNode }) {
    return (
        <th scope="col" className={cn('px-3 py-2', className)} {...props}>
            {children}
        </th>
    );
}

export function TableCell({ className, children }: { className?: string; children: ReactNode }) {
    return <td className={cn('px-3 py-2 align-middle', className)}>{children}</td>;
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
