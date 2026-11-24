import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { apiFetch } from '../../lib/api';
import { formatDate } from '../../lib/dates';

export function ProductionWastePage() {
    const [page, setPage] = useState(1);

    const listQuery = useQuery({ queryKey: ['waste', 'list', page], queryFn: () => api.waste({ page }) });
    const summary = useQuery({
        queryKey: ['production', 'summary'],
        queryFn: () => apiFetch<{ waste_today: number }>('/production/summary'),
    });

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Produksi"
                title="Riwayat Sisa Bahan"
                description="Catatan sisa bahan dari antrean produksi."
            />

            <div className="grid gap-4 sm:max-w-xs">
                {summary.isLoading ? (
                    <div className="flex justify-center py-10">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                ) : summary.isError ? (
                    <EmptyState
                        title="Ringkasan sisa bahan gagal dimuat"
                        description="Periksa koneksi lalu coba lagi."
                        action={
                            <Button variant="secondary" onClick={() => summary.refetch()}>
                                Coba lagi
                            </Button>
                        }
                    />
                ) : (
                    <StatCard
                        label="Sisa Bahan Hari Ini"
                        value={summary.data ? String(summary.data.waste_today) : '—'}
                    />
                )}
            </div>

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Riwayat sisa bahan</h2>
                    {listQuery.data ? (
                        <span className="bg-light-surface-2 text-light-muted-strong rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums dark:bg-surface-600 dark:text-gray-300">
                            {listQuery.data.meta.total} catatan
                        </span>
                    ) : null}
                </CardHeader>
                <CardBody>
                    {listQuery.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : listQuery.isError ? (
                        <EmptyState
                            title="Riwayat sisa bahan gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => listQuery.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Tanggal</TableHeaderCell>
                                        <TableHeaderCell>Bahan</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Desainer</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Jml</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Dimensi</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(listQuery.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada sisa bahan tercatat.</TableEmpty>
                                    ) : (
                                        (listQuery.data?.data ?? []).map((waste) => (
                                            <TableRow key={waste.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatDate(waste.logged_at)}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="font-medium">
                                                        {waste.material?.name ?? '-'}
                                                    </span>
                                                    {waste.notes ? (
                                                        <p className="text-light-muted text-xs dark:text-gray-400">
                                                            {waste.notes}
                                                        </p>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell className="hidden sm:table-cell">{waste.designer?.name ?? '-'}</TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {waste.quantity}
                                                </TableCell>
                                                <TableCell className="hidden sm:table-cell">{waste.dimensions ?? '-'}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <div className="mt-4">
                                <Pagination
                                    page={listQuery.data?.meta.current_page ?? 1}
                                    lastPage={listQuery.data?.meta.last_page ?? 1}
                                    total={listQuery.data?.meta.total}
                                    perPage={listQuery.data?.meta.per_page}
                                    onPageChange={setPage}
                                />
                            </div>
                        </>
                    )}
                </CardBody>
            </Card>
        </div>
    );
}
