import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { ProductionQueueItem } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { OrderStatusBadge } from '../../components/StatusBadge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Select } from '../../components/ui/Select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { apiFetch } from '../../lib/api';
import { errorMessage } from '../../lib/errors';
import { formatDate } from '../../lib/dates';
import { productionStatusTransition } from '../../lib/labels';
import { ProductionWasteModal } from './ProductionWasteModal';

/** Aggregate production counters; the API deliberately omits financial fields. */
interface ProductionSummary {
    queued: number;
    processing: number;
    completed_today: number;
    waste_today: number;
}

/** Production queue with status actions, client-side search, filters, and inline waste capture. */
export function ProductionQueuePage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [notice, setNotice] = useState<string | null>(null);
    const [wasteOrder, setWasteOrder] = useState<ProductionQueueItem | null>(null);
    const [statusFilter, setStatusFilter] = useState('');
    const [search, setSearch] = useState('');

    const query = useQuery({
        queryKey: ['production', 'queue', page, statusFilter],
        queryFn: () => api.productionQueue({ page, status: statusFilter || undefined }),
    });
    const searchQuery = useQuery({
        queryKey: ['production', 'queue', 'search', search.trim(), statusFilter],
        enabled: search.trim() !== '' || statusFilter !== '',
        queryFn: async () => {
            const firstPage = await api.productionQueue({ page: 1, status: statusFilter || undefined });
            const remainingPages = await Promise.all(
                Array.from({ length: Math.max(0, firstPage.meta.last_page - 1) }, (_, index) =>
                    api.productionQueue({ page: index + 2, status: statusFilter || undefined })
                )
            );
            const orders = [firstPage, ...remainingPages].flatMap((result) => result.data);

            return {
                ...firstPage,
                data: orders,
                meta: {
                    ...firstPage.meta,
                    current_page: 1,
                    last_page: Math.max(1, Math.ceil(orders.length / firstPage.meta.per_page)),
                    total: orders.length,
                },
            };
        },
    });
    const summary = useQuery({
        queryKey: ['production', 'summary'],
        queryFn: () => apiFetch<ProductionSummary>('/production/summary'),
    });

    /** Refreshes queue, dashboard, and stock data after a production mutation. */
    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['production'] });
        void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
        void queryClient.invalidateQueries({ queryKey: ['waste'] });
    };

    const start = useMutation({
        mutationFn: (id: string) => api.startOrder(id),
        onSuccess: () => {
            refresh();
            setNotice('Produksi dimulai dan stok telah dikurangi.');
        },
        onError: (error) => setNotice(errorMessage(error)),
    });

    const complete = useMutation({
        mutationFn: (id: string) => api.completeOrder(id),
        onSuccess: () => {
            refresh();
            setNotice('Pesanan ditandai selesai.');
        },
        onError: (error) => setNotice(errorMessage(error)),
    });

    /** Starts one queued order through the production API. */
    const handleStartOrder = (orderId: string) => start.mutate(orderId);

    /** Completes one processing order through the production API. */
    const handleCompleteOrder = (orderId: string) => complete.mutate(orderId);

    /** Updates the status filter and returns the queue pagination to its first page. */
    const handleStatusChange = (status: string) => {
        setStatusFilter(status);
        resetPage();
    };

    /** Updates the search term and returns the queue pagination to its first page. */
    const handleSearchChange = (value: string) => {
        setSearch(value);
        resetPage();
    };

    /** Opens the existing waste form for the selected production order. */
    const handleRecordWaste = (order: ProductionQueueItem) => setWasteOrder(order);

    const hasQueueFilter = search.trim() !== '' || statusFilter !== '';
    const queueData = hasQueueFilter ? searchQuery.data : query.data;
    const queueLoading = hasQueueFilter ? searchQuery.isLoading : query.isLoading;
    const queueError = hasQueueFilter ? searchQuery.isError : query.isError;

    /** Applies local status and search filters to the loaded production queue. */
    const matchingOrders = (queueData?.data ?? []).filter((order) => {
        const matchesStatus = statusFilter === '' || String(order.status) === statusFilter;
        const searchable = [
            order.invoice?.invoice_number,
            order.file_name,
            order.id,
        ].filter(Boolean).join(' ').toLocaleLowerCase();

        return matchesStatus && searchable.includes(search.trim().toLocaleLowerCase());
    });

    const visibleOrders = hasQueueFilter
        ? matchingOrders.slice((page - 1) * 15, page * 15)
        : matchingOrders;

    /** Resets pagination when the operator changes a local queue filter. */
    const resetPage = () => setPage(1);

    /** Retries the active queue request after a loading error. */
    const handleRetryQueue = () => {
        void (hasQueueFilter ? searchQuery.refetch() : query.refetch());
    };

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Produksi"
                title="Antrean Cetak"
                description="Antrean pekerjaan cetak diurutkan berdasarkan tenggat."
            />

            <div className="grid gap-4 sm:grid-cols-3">
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
            </div>

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Pekerjaan aktif</h2>
                    {queueData ? (
                        <span className="bg-light-surface-2 text-light-muted-strong rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums dark:bg-surface-600 dark:text-gray-300">
                            {matchingOrders.length} pekerjaan
                        </span>
                    ) : null}
                </CardHeader>
                <CardBody>
                    <div className="mb-4 grid gap-3 sm:grid-cols-2">
                        <Select
                            label="Status antrean"
                            value={statusFilter}
                            onChange={(event) => handleStatusChange(event.target.value)}
                        >
                            <option value="">Semua status antrean</option>
                            <option value="0">Antre</option>
                            <option value="1">Diproses</option>
                            <option value="2">Selesai</option>
                        </Select>
                        <Input
                            label="Cari invoice atau pesanan"
                            placeholder="Nomor invoice, nama file, atau ID pesanan..."
                            value={search}
                            onChange={(event) => handleSearchChange(event.target.value)}
                        />
                    </div>
                    {statusFilter === '2' ? (
                        <p className="mb-4 text-sm text-light-muted-strong dark:text-gray-400">
                            Endpoint antrean saat ini hanya mengembalikan status antre dan diproses,
                            sehingga riwayat selesai belum tersedia di filter ini.
                        </p>
                    ) : null}
                    {queueLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : queueError ? (
                        <EmptyState
                            title="Antrean cetak gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={handleRetryQueue}>
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
                                        <TableHeaderCell className="hidden sm:table-cell">Pelanggan</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Item</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Jml</TableHeaderCell>
                                        <TableHeaderCell className="hidden lg:table-cell">Area</TableHeaderCell>
                                        <TableHeaderCell className="hidden lg:table-cell">Operator</TableHeaderCell>
                                        <TableHeaderCell className="hidden xl:table-cell">Diterima</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Tenggat</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell className="bg-light-surface border-light-border lg:sticky lg:right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                            Aksi
                                        </TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {visibleOrders.length === 0 ? (
                                        <TableEmpty colSpan={10}>Belum ada pekerjaan dalam antrean.</TableEmpty>
                                    ) : (
                                        visibleOrders.map((order) => {
                                            const invoiceNumber =
                                                order.invoice?.invoice_number ?? '-';
                                            const transition = productionStatusTransition(order.status);

                                            return (
                                                <TableRow key={order.id}>
                                                    <TableCell className="font-medium whitespace-nowrap">
                                                        {invoiceNumber}
                                                    </TableCell>
                                                    <TableCell className="hidden sm:table-cell">
                                                        {order.invoice?.customer?.name ?? '-'}
                                                    </TableCell>
                                                    <TableCell className="hidden sm:table-cell">
                                                        {order.file_name ?? '-'}
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
                                                    <TableCell
                                                        aria-label={`Status ${transition.label}`}
                                                    >
                                                        <OrderStatusBadge status={order.status} />
                                                    </TableCell>
                                                    <TableCell className="bg-light-surface border-light-border lg:sticky lg:right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                                        <div className="flex flex-wrap gap-2">
                                                            {transition.nextStatus === 1 ? (
                                                                <Button
                                                                    size="sm"
                                                                    aria-label={`Mulai ${invoiceNumber}`}
                                                                    loading={start.isPending}
                                                                    onClick={() => handleStartOrder(order.id)}
                                                                >
                                                                    Mulai
                                                                </Button>
                                                            ) : transition.nextStatus === 2 ? (
                                                                <Button
                                                                    size="sm"
                                                                    variant="secondary"
                                                                    aria-label={`Selesaikan ${invoiceNumber}`}
                                                                    loading={complete.isPending}
                                                                    onClick={() => handleCompleteOrder(order.id)}
                                                                >
                                                                    Selesaikan
                                                                </Button>
                                                            ) : null}
                                                            {transition.nextStatus !== null ? (
                                                                <Button
                                                                    size="sm"
                                                                    variant="secondary"
                                                                    aria-label={`Catat gagal cetak ${invoiceNumber}`}
                                                                    onClick={() => handleRecordWaste(order)}
                                                                >
                                                                    Catat Gagal Cetak
                                                                </Button>
                                                            ) : null}
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
                                    page={page}
                                    lastPage={
                                        hasQueueFilter
                                            ? Math.max(1, Math.ceil(matchingOrders.length / 15))
                                            : (query.data?.meta.last_page ?? 1)
                                    }
                                    total={hasQueueFilter ? matchingOrders.length : query.data?.meta.total}
                                    perPage={15}
                                    onPageChange={setPage}
                                />
                            </div>
                        </>
                    )}
                </CardBody>
            </Card>
            {notice ? <Toast message={notice} onClose={() => setNotice(null)} /> : null}
            {wasteOrder ? (
                <ProductionWasteModal order={wasteOrder} onClose={() => setWasteOrder(null)} />
            ) : null}
        </div>
    );
}
