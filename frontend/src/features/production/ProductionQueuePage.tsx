import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { ProductionQueueItem } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { OrderStatusBadge } from '../../components/StatusBadge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { apiFetch } from '../../lib/api';
import { errorMessage } from '../../lib/errors';
import { formatDate } from '../../lib/dates';
import { FailProductionModal } from './FailProductionModal';
import { ProductionWasteModal } from './ProductionWasteModal';

interface ProductionSummary {
    queued: number;
    processing: number;
    completed_today: number;
    failed: number;
}

export function ProductionQueuePage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [notice, setNotice] = useState<{
        message: string;
        tone: 'success' | 'error';
    } | null>(null);
    const [wasteOrder, setWasteOrder] = useState<ProductionQueueItem | null>(null);
    const [failOrder, setFailOrder] = useState<ProductionQueueItem | null>(null);

    const query = useQuery({
        queryKey: ['production', 'queue', page],
        queryFn: () => api.productionQueue({ page }),
    });
    const summary = useQuery({
        queryKey: ['production', 'summary'],
        queryFn: () => apiFetch<ProductionSummary>('/production/summary'),
    });

    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['production'] });
        void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const start = useMutation({
        mutationFn: (id: string) => api.startOrder(id),
        onSuccess: () => {
            refresh();
            setNotice({ message: 'Produksi dimulai dan stok telah dikurangi.', tone: 'success' });
        },
        onError: (error) => setNotice({ message: errorMessage(error), tone: 'error' }),
    });

    const complete = useMutation({
        mutationFn: (id: string) => api.completeOrder(id),
        onSuccess: () => {
            refresh();
            setNotice({ message: 'Pesanan ditandai selesai.', tone: 'success' });
        },
        onError: (error) => setNotice({ message: errorMessage(error), tone: 'error' }),
    });

    const reopen = useMutation({
        mutationFn: (id: string) => api.reopenOrder(id),
        onSuccess: () => {
            refresh();
            setNotice({ message: 'Pesanan dikembalikan ke antrean.', tone: 'success' });
        },
        onError: (error) => setNotice({ message: errorMessage(error), tone: 'error' }),
    });

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Produksi"
                title="Antrean Cetak"
                description="Antrean pekerjaan cetak diurutkan berdasarkan waktu masuk."
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Antrean"
                    value={summary.isSuccess ? String(summary.data.queued) : '—'}
                />
                <StatCard
                    label="Sedang Cetak"
                    value={summary.isSuccess ? String(summary.data.processing) : '—'}
                />
                <StatCard
                    label="Selesai Hari Ini"
                    value={summary.isSuccess ? String(summary.data.completed_today) : '—'}
                />
                <StatCard
                    label="Gagal"
                    value={summary.isSuccess ? String(summary.data.failed) : '—'}
                />
            </div>

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Pekerjaan aktif</h2>
                    {query.data ? (
                        <span className="bg-light-surface-2 text-light-muted-strong rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums dark:bg-surface-600 dark:text-gray-300">
                            {query.data.meta.total} pekerjaan
                        </span>
                    ) : null}
                </CardHeader>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Antrean cetak gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Table ariaLabel="Antrean Cetak">
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell className="hidden lg:table-cell">Pelanggan</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Item</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Jml</TableHeaderCell>
                                        <TableHeaderCell className="hidden lg:table-cell">Area</TableHeaderCell>
                                        <TableHeaderCell className="hidden lg:table-cell">Operator</TableHeaderCell>
                                        <TableHeaderCell className="hidden xl:table-cell">Diterima</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Tenggat</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Status</TableHeaderCell>
                                        <TableHeaderCell className="bg-light-surface border-light-border max-lg:sticky max-lg:right-0 max-lg:shadow-[-8px_0_12px_-10px_rgb(0_0_0/0.35)] border-l dark:border-surface-300 dark:bg-surface-700">
                                            Aksi
                                        </TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(query.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={10}>Belum ada pekerjaan dalam antrean.</TableEmpty>
                                    ) : (
                                        (query.data?.data ?? []).map((order) => {
                                            const invoiceNumber =
                                                order.invoice?.invoice_number ?? '-';
                                            const itemLabel = order.file_name
                                                ? `${invoiceNumber} ${order.file_name}`
                                                : invoiceNumber;

                                            return (
                                                <TableRow key={order.id}>
                                                    <TableCell className="font-medium break-all sm:whitespace-nowrap">
                                                        {invoiceNumber}
                                                        <span className="mt-1 block sm:hidden">
                                                            <OrderStatusBadge status={order.status} />
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="hidden lg:table-cell">
                                                        {order.invoice?.customer?.name ?? '-'}
                                                    </TableCell>
                                                    <TableCell className="hidden sm:table-cell">
                                                        <span
                                                            className="block max-w-[8rem] truncate lg:max-w-none"
                                                            title={order.file_name ?? undefined}
                                                        >
                                                            {order.file_name ?? '-'}
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                        {order.quantity}
                                                    </TableCell>
                                                    <TableCell className="hidden tabular-nums whitespace-nowrap lg:table-cell">
                                                        {Number(order.area) > 0 ? `${Number(order.area)} m²` : '-'}
                                                    </TableCell>
                                                    <TableCell className="hidden lg:table-cell">
                                                        {order.operator?.name ?? '-'}
                                                    </TableCell>
                                                    <TableCell className="hidden whitespace-nowrap xl:table-cell">
                                                        {formatDate(order.ordered_at)}
                                                    </TableCell>
                                                    <TableCell className="hidden whitespace-nowrap sm:table-cell">
                                                        {order.invoice?.deadline
                                                            ? formatDate(order.invoice.deadline)
                                                            : '-'}
                                                    </TableCell>
                                                    <TableCell className="hidden sm:table-cell">
                                                        <OrderStatusBadge status={order.status} />
                                                    </TableCell>
                                                    <TableCell className="bg-light-surface border-light-border max-lg:sticky max-lg:right-0 max-lg:shadow-[-8px_0_12px_-10px_rgb(0_0_0/0.35)] border-l dark:border-surface-300 dark:bg-surface-700">
                                                        <div className="flex flex-col gap-1 sm:flex-row sm:flex-wrap sm:gap-2">
                                                            {order.status === 0 ? (
                                                                <Button
                                                                    size="sm"
                                                                    aria-label={`Mulai ${itemLabel}`}
                                                                    loading={start.isPending}
                                                                    onClick={() => start.mutate(order.id)}
                                                                >
                                                                    Mulai
                                                                </Button>
                                                            ) : null}
                                                            {order.status === 1 ? (
                                                                <Button
                                                                    size="sm"
                                                                    variant="secondary"
                                                                    aria-label={`Selesaikan ${itemLabel}`}
                                                                    loading={complete.isPending}
                                                                    onClick={() => complete.mutate(order.id)}
                                                                >
                                                                    Selesaikan
                                                                </Button>
                                                            ) : null}
                                                            {order.status === 1 ? (
                                                                <Button
                                                                    size="sm"
                                                                    variant="danger"
                                                                    aria-label={`Gagal ${itemLabel}`}
                                                                    onClick={() => setFailOrder(order)}
                                                                >
                                                                    Gagal
                                                                </Button>
                                                            ) : null}
                                                            {order.status === 3 ? (
                                                                <Button
                                                                    size="sm"
                                                                    variant="secondary"
                                                                    aria-label={`Proses Ulang ${itemLabel}`}
                                                                    loading={reopen.isPending}
                                                                    onClick={() => reopen.mutate(order.id)}
                                                                >
                                                                    Proses Ulang
                                                                </Button>
                                                            ) : null}
                                                            <Button
                                                                size="sm"
                                                                variant="secondary"
                                                                aria-label={`Sisa Bahan ${itemLabel}`}
                                                                onClick={() => setWasteOrder(order)}
                                                            >
                                                                Sisa Bahan
                                                            </Button>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    )}
                                </TableBody>
                            </Table>
                            <div className="mt-4">
                                <Pagination
                                    page={query.data?.meta.current_page ?? 1}
                                    lastPage={query.data?.meta.last_page ?? 1}
                                    total={query.data?.meta.total}
                                    perPage={query.data?.meta.per_page}
                                    onPageChange={setPage}
                                />
                            </div>
                        </>
                    )}
                </CardBody>
            </Card>
            {notice ? (
                <Toast message={notice.message} tone={notice.tone} onClose={() => setNotice(null)} />
            ) : null}
            {failOrder ? (
                <FailProductionModal
                    order={failOrder}
                    onClose={() => setFailOrder(null)}
                    onFailed={(failed) => {
                        setFailOrder(null);
                        setWasteOrder(failed);
                    }}
                />
            ) : null}
            {wasteOrder ? (
                <ProductionWasteModal order={wasteOrder} onClose={() => setWasteOrder(null)} />
            ) : null}
        </div>
    );
}
