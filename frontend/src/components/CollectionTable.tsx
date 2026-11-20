import { useQuery } from '@tanstack/react-query';
import type { ReactNode } from 'react';

import type { CollectionResponse } from '../api/types';
import { PageHeader } from './PageHeader';
import { Card, CardBody } from './ui/Card';
import { EmptyState } from './ui/EmptyState';
import { Spinner } from './ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from './ui/Table';

export interface CollectionColumn<T> {
    header: string;
    cell: (row: T) => ReactNode;
    className?: string;
}

export function CollectionTable<T extends { id: string }>({
    title,
    description,
    eyebrow,
    queryKey,
    queryFn,
    columns,
    actions,
    emptyMessage = 'No records found.',
}: {
    title: string;
    description?: string;
    eyebrow?: string;
    queryKey: readonly unknown[];
    queryFn: () => Promise<CollectionResponse<T>>;
    columns: CollectionColumn<T>[];
    actions?: ReactNode;
    emptyMessage?: string;
}) {
    const query = useQuery({ queryKey, queryFn });

    return (
        <div className="space-y-6">
            <PageHeader eyebrow={eyebrow} title={title} description={description} actions={actions} />

            <Card>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : null}

                    {query.isError ? (
                        <p role="alert" className="py-6 text-sm text-red-600 dark:text-red-300">
                            Unable to load this screen.
                        </p>
                    ) : null}

                    {query.data ? (
                        <Table>
                            <TableHead>
                                <TableRow>
                                    {columns.map((column) => (
                                        <TableHeaderCell key={column.header} className={column.className}>
                                            {column.header}
                                        </TableHeaderCell>
                                    ))}
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {query.data.data.length === 0 ? (
                                    <TableEmpty colSpan={columns.length}>{emptyMessage}</TableEmpty>
                                ) : (
                                    query.data.data.map((row) => (
                                        <TableRow key={row.id}>
                                            {columns.map((column) => (
                                                <TableCell key={column.header} className={column.className}>
                                                    {column.cell(row)}
                                                </TableCell>
                                            ))}
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    ) : null}
                </CardBody>
            </Card>

            {query.data && query.data.data.length === 0 ? (
                <EmptyState title="Nothing here yet" description={emptyMessage} />
            ) : null}
        </div>
    );
}
