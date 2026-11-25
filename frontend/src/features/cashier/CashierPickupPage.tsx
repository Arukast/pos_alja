import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Link } from 'react-router';

import { api } from '../../api/queries';
import type { Invoice } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { PickupStatusBadge } from '../../components/StatusBadge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Pagination } from '../../components/ui/Pagination';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast, type ToastTone } from '../../components/ui/Toast';
import { formatDate } from '../../lib/dates';
import { errorMessage } from '../../lib/errors';
import { formatMoney } from '../../lib/money';

/**
 * State notifikasi toast beserta tingkat nadanya.
 */
interface NoticeState {
    message: string;
    tone: ToastTone;
}

/**
 * Halaman antrean pengambilan pesanan lunas untuk peran kasir (FR21).
 *
 * Kasir dapat mencari invoice lunas, memeriksa status pengambilan, mencetak nota,
 * serta menandai pesanan sebagai sudah diambil melalui dialog konfirmasi tanpa mengubah item pesanan.
 */
export function CashierPickupPage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [pickupStatus, setPickupStatus] = useState<0 | 1>(0);
    const [confirmTarget, setConfirmTarget] = useState<Invoice | null>(null);
    const [notice, setNotice] = useState<NoticeState | null>(null);

    const trimmedSearch = search.trim();

    const query = useQuery({
        queryKey: ['cashier', 'pickup', page, pickupStatus, trimmedSearch],
        queryFn: () =>
            api.invoices({
                page,
                status: 2,
                pickup_status: pickupStatus,
                search: trimmedSearch === '' ? undefined : trimmedSearch,
            }),
    });

    const pickup = useMutation({
        mutationFn: (invoiceId: string) => api.pickup(invoiceId),
        onSuccess: (response) => {
            void queryClient.invalidateQueries({ queryKey: ['cashier', 'pickup'] });
            void queryClient.invalidateQueries({ queryKey: ['cashier', 'invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['designer', 'invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setConfirmTarget(null);
            setNotice({
                message: `Pesanan ${response.invoice.invoice_number} ditandai sudah diambil.`,
                tone: 'success',
            });
        },
        onError: (error) => {
            setConfirmTarget(null);
            setNotice({ message: errorMessage(error), tone: 'error' });
        },
    });

    /**
     * Memperbarui kata kunci pencarian dan mengembalikan halaman paginasi ke awal.
     */
    const handleSearchChange = (value: string) => {
        setSearch(value);
        setPage(1);
    };

    /**
     * Mengganti filter status pengambilan dan mengembalikan halaman paginasi ke awal.
     */
    const handlePickupFilterChange = (value: string) => {
        setPickupStatus(value === '1' ? 1 : 0);
        setPage(1);
    };

    const invoices = query.data?.data ?? [];

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Kasir"
                title="Antrean Pengambilan"
                description="Pesanan lunas yang menunggu pengambilan atau sudah diambil pelanggan."
            />
            <Card>
                <CardBody className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px]">
                        <Input
                            label="Cari invoice atau pelanggan"
                            placeholder="Nomor invoice, nama, atau telepon..."
                            value={search}
                            onChange={(event) => handleSearchChange(event.target.value)}
                        />
                        <Select
                            label="Status pengambilan"
                            value={String(pickupStatus)}
                            onChange={(event) => handlePickupFilterChange(event.target.value)}
                        >
                            <option value="0">Belum Diambil</option>
                            <option value="1">Sudah Diambil</option>
                        </Select>
                    </div>

                    {query.isLoading ? (
                        <div role="status" className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Antrean pengambilan gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Table ariaLabel="Antrean Pengambilan">
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">
                                            Pelanggan
                                        </TableHeaderCell>
                                        <TableHeaderCell className="hidden md:table-cell">
                                            {pickupStatus === 1 ? 'Diambil' : 'Tenggat'}
                                        </TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Dibayar</TableHeaderCell>
                                        <TableHeaderCell className="bg-light-surface border-light-border dark:border-surface-300 dark:bg-surface-700 border-l lg:sticky lg:right-0">
                                            Aksi
                                        </TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {invoices.length === 0 ? (
                                        <TableEmpty colSpan={6}>
                                            {pickupStatus === 1
                                                ? 'Belum ada riwayat pesanan yang sudah diambil.'
                                                : 'Belum ada pesanan menunggu pengambilan.'}
                                        </TableEmpty>
                                    ) : (
                                        invoices.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell className="font-medium">
                                                    {invoice.invoice_number}
                                                </TableCell>
                                                <TableCell className="hidden sm:table-cell">
                                                    <p>{invoice.customer?.name ?? '-'}</p>
                                                    {invoice.customer?.phone ? (
                                                        <p className="text-light-muted-strong text-xs dark:text-gray-400">
                                                            {invoice.customer.phone}
                                                        </p>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell className="hidden md:table-cell">
                                                    {pickupStatus === 1
                                                        ? formatDate(invoice.pickup_date ?? '-')
                                                        : formatDate(invoice.deadline ?? '-')}
                                                </TableCell>
                                                <TableCell>
                                                    <PickupStatusBadge status={invoice.pickup_status} />
                                                </TableCell>
                                                <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                    {formatMoney(invoice.grand_total)}
                                                </TableCell>
                                                <TableCell className="bg-light-surface border-light-border dark:border-surface-300 dark:bg-surface-700 border-l lg:sticky lg:right-0">
                                                    <div className="flex flex-wrap items-center gap-3">
                                                        {invoice.pickup_status === 0 ? (
                                                            <Button
                                                                size="sm"
                                                                loading={
                                                                    pickup.isPending &&
                                                                    pickup.variables === invoice.id
                                                                }
                                                                onClick={() => setConfirmTarget(invoice)}
                                                            >
                                                                Tandai diambil
                                                            </Button>
                                                        ) : null}
                                                        <Link
                                                            className="text-brand-fill-hover dark:text-brand-primary-light focus-visible:ring-brand-fill dark:focus-visible:ring-brand-primary-light rounded-sm text-sm font-semibold hover:underline focus-visible:ring-2 focus-visible:outline-none"
                                                            to={`/orders/${invoice.id}/receipt`}
                                                        >
                                                            Nota
                                                        </Link>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
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

            <Modal
                open={confirmTarget !== null}
                onClose={() => setConfirmTarget(null)}
                size="sm"
                title="Konfirmasi Pengambilan"
                description={confirmTarget?.invoice_number}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setConfirmTarget(null)}>
                            Batal
                        </Button>
                        <Button
                            loading={pickup.isPending}
                            onClick={() => {
                                if (confirmTarget) {
                                    pickup.mutate(confirmTarget.id);
                                }
                            }}
                        >
                            Konfirmasi Diambil
                        </Button>
                    </>
                }
            >
                {confirmTarget ? (
                    <dl className="border-light-border bg-light-surface-2 dark:border-surface-300 dark:bg-surface-600 space-y-2 rounded-xl border px-4 py-3 text-sm">
                        <div className="flex justify-between gap-4">
                            <dt className="text-light-muted-strong dark:text-gray-400">Pelanggan</dt>
                            <dd className="text-right font-medium">
                                {confirmTarget.customer?.name ?? '-'}
                            </dd>
                        </div>
                        {confirmTarget.customer?.phone ? (
                            <div className="flex justify-between gap-4">
                                <dt className="text-light-muted-strong dark:text-gray-400">Telepon</dt>
                                <dd className="text-right">{confirmTarget.customer.phone}</dd>
                            </div>
                        ) : null}
                        <div className="flex justify-between gap-4">
                            <dt className="text-light-muted-strong dark:text-gray-400">Total Lunas</dt>
                            <dd className="text-right font-semibold tabular-nums">
                                {formatMoney(confirmTarget.grand_total)}
                            </dd>
                        </div>
                    </dl>
                ) : null}
            </Modal>

            {notice ? (
                <Toast message={notice.message} tone={notice.tone} onClose={() => setNotice(null)} />
            ) : null}
        </div>
    );
}
