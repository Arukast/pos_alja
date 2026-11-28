import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';
import { Link, useNavigate } from 'react-router';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { InvoiceStatusBadge, OrderStatusBadge, PickupStatusBadge } from '../../components/StatusBadge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Pagination } from '../../components/ui/Pagination';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { paymentMethodLabel, paymentTypeLabel } from '../../lib/labels';
import { formatMoney, formatQuantity, quantityDiscountLabel } from '../../lib/money';
import { selectedAttributeNames } from './attributeOptions';
import { editEligibility } from './editEligibility';

/**
 * Halaman daftar pesanan dan invoice cabang untuk peran desainer (FR8 / PB-02).
 *
 * Menyediakan pencarian berdasarkan nomor invoice/pelanggan, penyaringan status pembayaran,
 * pratinjau detail item pesanan beserta harga jual, serta tautan cetak tiket dan nota.
 */
export function DesignerOrdersPage() {
    const navigate = useNavigate();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [selectedInvoiceId, setSelectedInvoiceId] = useState<string | null>(null);

    const trimmedSearch = search.trim();
    const parsedStatus = statusFilter === '' ? undefined : Number(statusFilter);

    const query = useQuery({
        queryKey: ['designer', 'invoices', page, trimmedSearch, statusFilter],
        queryFn: () =>
            api.invoices({
                page,
                search: trimmedSearch === '' ? undefined : trimmedSearch,
                status: parsedStatus,
            }),
    });

    const detailQuery = useQuery({
        queryKey: ['invoice', 'detail', selectedInvoiceId],
        queryFn: () => api.invoice(selectedInvoiceId ?? ''),
        enabled: selectedInvoiceId !== null,
    });

    // Shares the edit page's cache; inactive attributes must still resolve for saved orders.
    const optionsQuery = useQuery({
        queryKey: ['quote-options', 'edit'],
        queryFn: () => api.quoteOptions({ include_inactive_attributes: 1 }),
    });

    /**
     * Memperbarui kata kunci pencarian dan mengembalikan halaman paginasi ke awal.
     */
    const handleSearchChange = (value: string) => {
        setSearch(value);
        setPage(1);
    };

    /**
     * Memperbarui filter status pembayaran dan mengembalikan halaman paginasi ke awal.
     */
    const handleStatusChange = (value: string) => {
        setStatusFilter(value);
        setPage(1);
    };

    const invoices = query.data?.data ?? [];
    const detail = detailQuery.data?.invoice;

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Desainer"
                title="Daftar Pesanan"
                description="Invoice yang dibuat oleh cabang Anda."
            />

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Pesanan</h2>
                    <Button size="sm" className="ml-auto" onClick={() => navigate('/designer/new-order')}>
                        Pesanan Baru
                    </Button>
                </CardHeader>
                <CardBody className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_220px]">
                        <Input
                            label="Cari invoice atau pelanggan"
                            placeholder="Nomor invoice, nama, atau telepon..."
                            value={search}
                            onChange={(event) => handleSearchChange(event.target.value)}
                        />
                        <Select
                            label="Status pembayaran"
                            value={statusFilter}
                            onChange={(event) => handleStatusChange(event.target.value)}
                        >
                            <option value="">Semua status</option>
                            <option value="0">Belum Dibayar</option>
                            <option value="1">DP</option>
                            <option value="2">Lunas</option>
                        </Select>
                    </div>

                    {query.isLoading ? (
                        <div role="status" className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Daftar pesanan gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Table ariaLabel="Daftar Pesanan">
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">
                                            Pelanggan
                                        </TableHeaderCell>
                                        <TableHeaderCell className="hidden md:table-cell">
                                            Tenggat
                                        </TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell>Ubah Item</TableHeaderCell>
                                        <TableHeaderCell className="hidden lg:table-cell">
                                            Pengambilan
                                        </TableHeaderCell>
                                        <TableHeaderCell className="text-right">Total</TableHeaderCell>
                                        <TableHeaderCell className="bg-light-surface border-light-border dark:border-surface-300 dark:bg-surface-700 border-l lg:sticky lg:right-0">
                                            Aksi
                                        </TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {invoices.length === 0 ? (
                                        <TableEmpty colSpan={8}>Belum ada pesanan yang cocok.</TableEmpty>
                                    ) : (
                                        invoices.map((invoice) => {
                                            const eligibility = editEligibility({ status: invoice.status, orders: invoice.orders });

                                            return (
                                                <TableRow key={invoice.id}>
                                                    <TableCell>
                                                        <button
                                                            type="button"
                                                            className="text-brand-fill-hover dark:text-brand-primary-light focus-visible:ring-brand-fill rounded-sm text-left font-medium hover:underline focus-visible:ring-2 focus-visible:outline-none"
                                                            onClick={() => setSelectedInvoiceId(invoice.id)}
                                                        >
                                                            {invoice.invoice_number}
                                                        </button>
                                                    </TableCell>
                                                    <TableCell className="hidden sm:table-cell">
                                                        {invoice.customer?.name ?? '-'}
                                                    </TableCell>
                                                    <TableCell className="hidden md:table-cell">
                                                        {formatDate(invoice.deadline ?? '-')}
                                                    </TableCell>
                                                    <TableCell>
                                                        <InvoiceStatusBadge status={invoice.status} />
                                                    </TableCell>
                                                    <TableCell>
                                                        {eligibility.editable ? (
                                                            <Link
                                                                className="text-brand-fill-hover dark:text-brand-primary-light focus-visible:ring-brand-fill rounded-sm text-sm font-semibold hover:underline focus-visible:ring-2 focus-visible:outline-none"
                                                                to={`/designer/orders/${invoice.id}/edit`}
                                                            >
                                                                Edit
                                                            </Link>
                                                        ) : (
                                                            <span className="text-light-muted text-sm dark:text-gray-400">Terkunci{eligibility.reason ? ` — ${eligibility.reason}` : ''}</span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="hidden lg:table-cell">
                                                        <PickupStatusBadge status={invoice.pickup_status} />
                                                    </TableCell>
                                                    <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                        {formatMoney(invoice.grand_total)}
                                                    </TableCell>
                                                    <TableCell className="bg-light-surface border-light-border dark:border-surface-300 dark:bg-surface-700 border-l lg:sticky lg:right-0">
                                                        <div className="flex flex-wrap items-center gap-3">
                                                            <button
                                                                type="button"
                                                                className="text-light-text dark:text-gray-200 focus-visible:ring-brand-fill rounded-sm text-sm font-semibold hover:underline focus-visible:ring-2 focus-visible:outline-none"
                                                                onClick={() => setSelectedInvoiceId(invoice.id)}
                                                            >
                                                                Detail
                                                            </button>
                                                            <Link
                                                                className="text-brand-fill-hover dark:text-brand-primary-light focus-visible:ring-brand-fill dark:focus-visible:ring-brand-primary-light rounded-sm text-sm font-semibold hover:underline focus-visible:ring-2 focus-visible:outline-none"
                                                                to={`/orders/${invoice.id}/receipt`}
                                                            >
                                                                Nota
                                                            </Link>
                                                        </div>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    )}
                                </TableBody>
                            </Table>
                            <Pagination
                                page={query.data?.meta.current_page ?? 1}
                                lastPage={query.data?.meta.last_page ?? 1}
                                total={query.data?.meta.total}
                                perPage={query.data?.meta.per_page}
                                onPageChange={setPage}
                            />
                        </>
                    )}
                </CardBody>
            </Card>

            <Modal
                open={selectedInvoiceId !== null}
                onClose={() => setSelectedInvoiceId(null)}
                size="lg"
                title={detail ? `Detail ${detail.invoice_number}` : 'Detail Pesanan'}
                description={detail?.customer?.name ?? undefined}
                footer={
                    <>
                        {detail ? (
                            <>
                                <Link
                                    className="border-light-muted bg-light-surface text-light-text hover:bg-light-surface-2 dark:bg-surface-600 dark:hover:bg-surface-500 inline-flex h-10 items-center rounded-xl border px-4 text-sm font-semibold dark:text-gray-100"
                                    to={`/orders/${detail.id}/receipt`}
                                >
                                    Cetak Nota
                                </Link>
                            </>
                        ) : null}
                        <Button variant="secondary" onClick={() => setSelectedInvoiceId(null)}>
                            Tutup
                        </Button>
                    </>
                }
            >
                {detailQuery.isLoading ? (
                    <div role="status" className="flex justify-center py-8">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                ) : detailQuery.isError || !detail ? (
                    <EmptyState
                        title="Detail invoice gagal dimuat"
                        description="Periksa koneksi lalu coba lagi."
                        action={
                            <Button variant="secondary" size="sm" onClick={() => detailQuery.refetch()}>
                                Coba lagi
                            </Button>
                        }
                    />
                ) : (
                    <div className="space-y-4">
                        <dl className="border-light-border bg-light-surface-2 dark:border-surface-300 dark:bg-surface-600 grid gap-3 rounded-xl border px-4 py-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-light-muted text-xs dark:text-gray-400">Pelanggan</dt>
                                <dd className="font-medium">
                                    {detail.customer?.name ?? '-'}{' '}
                                    {detail.customer?.phone ? `(${detail.customer.phone})` : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-light-muted text-xs dark:text-gray-400">Tanggal</dt>
                                <dd className="font-medium">
                                    {detail.created_date ? formatDate(detail.created_date) : '-'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-light-muted text-xs dark:text-gray-400">Desainer</dt>
                                <dd className="font-medium">{detail.designer?.name ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-light-muted text-xs dark:text-gray-400">Tenggat</dt>
                                <dd className="font-medium">{formatDate(detail.deadline ?? '-')}</dd>
                            </div>
                            <div>
                                <dt className="text-light-muted text-xs dark:text-gray-400">Sumber</dt>
                                <dd className="font-medium">
                                    {detail.source === 1 ? 'Online' : 'Offline'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-light-muted text-xs dark:text-gray-400">Status</dt>
                                <dd className="mt-0.5 flex flex-wrap gap-2">
                                    <InvoiceStatusBadge status={detail.status} />
                                    <PickupStatusBadge status={detail.pickup_status} />
                                </dd>
                            </div>
                        </dl>

                        <Table ariaLabel="Item pesanan">
                            <TableHead>
                                <TableRow>
                                    <TableHeaderCell>Item</TableHeaderCell>
                                    <TableHeaderCell>Status</TableHeaderCell>
                                    <TableHeaderCell className="text-right">Jml</TableHeaderCell>
                                    <TableHeaderCell className="text-right">Harga satuan</TableHeaderCell>
                                    <TableHeaderCell className="text-right">Subtotal</TableHeaderCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {(detail.orders ?? []).length === 0 ? (
                                    <TableEmpty colSpan={5}>Belum ada item.</TableEmpty>
                                ) : (
                                    (detail.orders ?? []).map((order) => {
                                        const dimensions = order.length != null && order.width != null
                                            ? `${formatQuantity(order.length)} x ${formatQuantity(order.width)}`
                                            : formatQuantity(order.area);
                                        const attributeNames = selectedAttributeNames(
                                            optionsQuery.data?.attributes ?? [],
                                            order.selected_attributes ?? []
                                        );

                                        return (
                                            <TableRow key={order.id}>
                                                <TableCell className="[overflow-wrap:anywhere]">
                                                    <p className="font-medium">{order.file_name ?? 'Item'}</p>
                                                    {order.product || order.material ? (
                                                        <p className="text-light-muted-strong text-xs dark:text-gray-400">
                                                            {[order.product, order.material].filter(Boolean).join(' · ')}
                                                        </p>
                                                    ) : null}
                                                    {quantityDiscountLabel(order.quantity_discount_percent) ? (
                                                        <p className="text-light-muted-strong text-xs dark:text-gray-400">
                                                            {quantityDiscountLabel(order.quantity_discount_percent)}
                                                        </p>
                                                    ) : null}
                                                    <p className="text-light-muted-strong text-xs dark:text-gray-400">
                                                        {dimensions}
                                                    </p>
                                                    {attributeNames.length > 0 ? (
                                                        <p className="text-light-muted-strong text-xs dark:text-gray-400">
                                                            {attributeNames.join(', ')}
                                                        </p>
                                                    ) : null}
                                                    {order.notes ? (
                                                        <p className="text-light-muted-strong text-xs dark:text-gray-400">
                                                            {order.notes}
                                                        </p>
                                                    ) : null}
                                                </TableCell>
                                                <TableCell>
                                                    <OrderStatusBadge status={order.status} />
                                                </TableCell>
                                                <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                    {formatQuantity(order.quantity)}
                                                </TableCell>
                                                <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                    {order.unit_price != null ? formatMoney(order.unit_price) : '-'}
                                                </TableCell>
                                                <TableCell className="text-right whitespace-nowrap tabular-nums">
                                                    {order.subtotal != null ? formatMoney(order.subtotal) : '-'}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>

                        <Table ariaLabel="Pembayaran">
                            <TableHead>
                                <TableRow>
                                    <TableHeaderCell>Jumlah</TableHeaderCell>
                                    <TableHeaderCell>Metode</TableHeaderCell>
                                    <TableHeaderCell>Jenis</TableHeaderCell>
                                    <TableHeaderCell>Kasir</TableHeaderCell>
                                    <TableHeaderCell>Waktu</TableHeaderCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {(detail.payments ?? []).length === 0 ? (
                                    <TableEmpty colSpan={5}>Belum ada pembayaran.</TableEmpty>
                                ) : (
                                    (detail.payments ?? []).map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell className="whitespace-nowrap tabular-nums">
                                                {formatMoney(payment.amount)}
                                            </TableCell>
                                            <TableCell>{paymentMethodLabel(payment.payment_method || '-')}</TableCell>
                                            <TableCell>{paymentTypeLabel(payment.type)}</TableCell>
                                            <TableCell>{payment.cashier_name || '-'}</TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {payment.created_at ? formatDate(payment.created_at) : '-'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>

                        <div className="border-light-border dark:border-surface-300 flex flex-wrap items-center justify-between gap-2 border-t pt-3 text-sm">
                            <div className="flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span className="text-light-muted-strong dark:text-gray-400">
                                    Diskon: {formatMoney(detail.discount)}
                                </span>
                                <span className="text-light-muted-strong dark:text-gray-400">
                                    DP: {formatMoney(detail.dp_amount)}
                                </span>
                                <span className="text-light-muted-strong dark:text-gray-400">
                                    Sisa tagihan: {formatMoney(detail.accounts_receivable)}
                                </span>
                            </div>
                            <span className="text-base font-bold tabular-nums">
                                Total: {formatMoney(detail.grand_total)}
                            </span>
                        </div>
                    </div>
                )}
            </Modal>
        </div>
    );
}
