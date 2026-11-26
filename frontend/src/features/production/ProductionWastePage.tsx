import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { WasteByUnit } from '../../api/types';
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
import { quantityWithUnit, wasteByUnitLabel } from '../../lib/units';

export function ProductionWastePage() {
    const [page, setPage] = useState(1);

    const listQuery = useQuery({ queryKey: ['waste', 'list', page], queryFn: () => api.waste({ page }) });
    const summary = useQuery({
        queryKey: ['production', 'summary'],
        queryFn: () => apiFetch<{ waste_today_by_unit: WasteByUnit[] }>('/production/summary'),
    });

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Produksi"
                title="Riwayat Bahan Terbuang"
                description="Catatan bahan terbuang dari antrean produksi."
            />

            <div className="grid gap-4 sm:max-w-xs">
                {summary.isLoading ? (
                    <div className="flex justify-center py-10">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                ) : summary.isError ? (
                    <EmptyState
                        title="Ringkasan bahan terbuang gagal dimuat"
                        description="Periksa koneksi lalu coba lagi."
                        action={
                            <Button variant="secondary" onClick={() => summary.refetch()}>
                                Coba lagi
                            </Button>
                        }
                    />
                ) : (
                    <StatCard
                        label="Bahan Terbuang Hari Ini"
                        value={summary.data ? wasteByUnitLabel(summary.data.waste_today_by_unit) : '—'}
                    />
                )}
            </div>

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Riwayat bahan terbuang</h2>
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
                            title="Riwayat bahan terbuang gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => listQuery.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Table ariaLabel="Riwayat bahan terbuang">
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Tanggal</TableHeaderCell>
                                        <TableHeaderCell>Bahan</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Desainer</TableHeaderCell>
                                        <TableHeaderCell className="text-right whitespace-nowrap">Jml</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Dimensi</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(listQuery.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada bahan terbuang tercatat.</TableEmpty>
                                    ) : (
                                        (listQuery.data?.data ?? []).map((waste) => (
                                            <TableRow key={waste.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatDate(waste.logged_at)}
                                                </TableCell>
                                                <TableCell>
                                                    <span
                                                        className="block max-w-[9rem] truncate font-medium sm:max-w-none"
                                                        title={waste.material?.name ?? undefined}
                                                    >
                                                        {waste.material?.name ?? '-'}
                                                    </span>
                                                    {waste.notes ? (
                                                        <p
                                                            className="text-light-muted max-w-[9rem] truncate text-xs sm:max-w-[16rem] dark:text-gray-400"
                                                            title={waste.notes}
                                                        >
                                                            {waste.notes}
                                                        </p>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell className="hidden sm:table-cell">{waste.designer?.name ?? '-'}</TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-normal sm:whitespace-nowrap">
                                                    {quantityWithUnit(waste.quantity, waste.material?.unit)}
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
