import { useQuery } from '@tanstack/react-query';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { Fragment, useState, type ReactNode } from 'react';

import type { Paginated } from '../api/types';
import { PageBanner } from './PageBanner';
import { Button } from './ui/Button';
import { Card, CardBody } from './ui/Card';
import { EmptyState } from './ui/EmptyState';
import { Pagination } from './ui/Pagination';
import { Spinner } from './ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from './ui/Table';

export interface ResourceColumn<T> {
    header: string;
    cell: (row: T) => ReactNode;
    className?: string;
}

export function ResourceTable<T extends { id: string }>({
    title,
    description,
    eyebrow,
    queryKey,
    queryFn,
    columns,
    actions,
    emptyMessage = 'Belum ada data.',
    expandedRow,
}: {
    title: string;
    description?: string;
    eyebrow?: string;
    queryKey: readonly unknown[];
    queryFn: (page: number) => Promise<Paginated<T>>;
    columns: ResourceColumn<T>[];
    actions?: ReactNode;
    emptyMessage?: string;
    expandedRow?: (row: T) => ReactNode;
}) {
    const [page, setPage] = useState(1);
    const [expanded, setExpanded] = useState<string | null>(null);
    const columnCount = columns.length + (expandedRow ? 1 : 0);
    const query = useQuery({
        queryKey: [...queryKey, page],
        queryFn: () => queryFn(page),
    });

    return (
        <div className="space-y-6">
            <PageBanner eyebrow={eyebrow} title={title} description={description} actions={actions} />

            <Card>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : null}

                    {query.isError ? (
                        <EmptyState
                            title="Data gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : null}

                    {query.data ? (
                        <>
                            <Table ariaLabel={title}>
                                <TableHead>
                                    <TableRow>
                                        {expandedRow ? (
                                            <TableHeaderCell>
                                                <span className="sr-only">Rincian</span>
                                            </TableHeaderCell>
                                        ) : null}
                                        {columns.map((column) => (
                                            <TableHeaderCell key={column.header} className={column.className}>
                                                {column.header}
                                            </TableHeaderCell>
                                        ))}
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {query.data.data.length === 0 ? (
                                        <TableEmpty colSpan={columnCount}>{emptyMessage}</TableEmpty>
                                    ) : (
                                        query.data.data.map((row) => {
                                            const isExpanded = expanded === row.id;

                                            return (
                                                <Fragment key={row.id}>
                                                    <TableRow>
                                                        {expandedRow ? (
                                                            <TableCell>
                                                                <Button
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    aria-expanded={isExpanded}
                                                                    aria-label={
                                                                        isExpanded
                                                                            ? 'Sembunyikan rincian'
                                                                            : 'Tampilkan rincian'
                                                                    }
                                                                    onClick={() =>
                                                                        setExpanded(isExpanded ? null : row.id)
                                                                    }
                                                                >
                                                                    {isExpanded ? (
                                                                        <ChevronDown className="h-4 w-4" />
                                                                    ) : (
                                                                        <ChevronRight className="h-4 w-4" />
                                                                    )}
                                                                </Button>
                                                            </TableCell>
                                                        ) : null}
                                                        {columns.map((column) => (
                                                            <TableCell key={column.header} className={column.className}>
                                                                {column.cell(row)}
                                                            </TableCell>
                                                        ))}
                                                    </TableRow>
                                                    {isExpanded ? (
                                                        <tr className="bg-light-surface-2/40 dark:bg-surface-600/30">
                                                            <TableCell colSpan={columns.length + 1}>
                                                                {expandedRow ? expandedRow(row) : null}
                                                            </TableCell>
                                                        </tr>
                                                    ) : null}
                                                </Fragment>
                                            );
                                        })
                                    )}
                                </TableBody>
                            </Table>
                            <Pagination
                                page={query.data.meta.current_page}
                                lastPage={query.data.meta.last_page}
                                total={query.data.meta.total}
                                perPage={query.data.meta.per_page}
                                onPageChange={setPage}
                            />
                        </>
                    ) : null}
                </CardBody>
            </Card>

        </div>
    );
}
