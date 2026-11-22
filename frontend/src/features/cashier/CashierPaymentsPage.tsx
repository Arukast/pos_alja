import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Invoice } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { InvoiceStatusBadge } from '../../components/StatusBadge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';
import { CheckoutDialog } from './CheckoutDialog';

// The server totals expenses for Asia/Jakarta today, so the row filter must use the same calendar day.
const jakartaDate = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta' });
const hasOffset = /(?:Z|[+-]\d{2}(?::?\d{2})?)$/;

function toJakartaDate(value: string | Date): string {
    if (value instanceof Date) {
        return jakartaDate.format(value);
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return value;
    }

    // The API sends either ISO timestamps with an offset or raw Jakarta wall-clock values without one.
    const zoned = hasOffset.test(value) ? value : `${value.replace(' ', 'T')}+07:00`;

    return jakartaDate.format(new Date(zoned));
}

function jakartaToday(): string {
    return toJakartaDate(new Date());
}

export function CashierPaymentsPage() {
    const [page, setPage] = useState(1);
    const [selected, setSelected] = useState<Invoice | null>(null);

    const query = useQuery({
        queryKey: ['cashier', 'invoices', page],
        queryFn: () => api.invoices({ page }),
    });
    const dashboard = useQuery({
        queryKey: ['dashboard', 'cashier'],
        queryFn: () => api.dashboard(),
    });

    const data = dashboard.data?.role === 'cashier' ? dashboard.data : null;
    const invoices = (query.data?.data ?? []).filter((invoice) => invoice.status !== 2);
    const today = jakartaToday();
    // Server sends the latest expenses of any date; the panel and its total claim "today".
    const todaysExpenses = (data?.expenses ?? []).filter(
        (expense) => toJakartaDate(expense.incurred_at) === today,
    );

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Kasir"
                title="Antrean Pembayaran"
                description="Invoice belum dibayar dan DP yang menunggu pembayaran."
            />

            {data ? (
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Menunggu Pembayaran" value={String(data.stats.awaiting_checkout)} />
                    <StatCard
                        label="DP Diterima Hari Ini"
                        value={formatMoney(data.stats.dp_collected_today)}
                    />
                    <StatCard
                        label="Pelunasan Hari Ini"
                        value={formatMoney(data.stats.settlements_today)}
                    />
                    <StatCard
                        label="Pengeluaran Hari Ini"
                        value={formatMoney(data.stats.expenses_today)}
                    />
                </div>
            ) : null}

            <Card>
                <CardHeader>
                    <h2 className="font-semibold">Antrean tagihan</h2>
                </CardHeader>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Antrean tagihan gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Pelanggan</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell className="hidden text-right sm:table-cell">Total</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Sisa Tagihan</TableHeaderCell>
                                        <TableHeaderCell className="bg-light-surface border-light-border sticky right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                            Aksi
                                        </TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {invoices.length === 0 ? (
                                        <TableEmpty colSpan={6}>Belum ada invoice menunggu pembayaran.</TableEmpty>
                                    ) : (
                                        invoices.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell>{invoice.invoice_number}</TableCell>
                                                <TableCell className="hidden sm:table-cell">
                                                    {invoice.customer?.name ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    <InvoiceStatusBadge status={invoice.status} />
                                                </TableCell>
                                                <TableCell className="hidden text-right tabular-nums whitespace-nowrap sm:table-cell">
                                                    {formatMoney(invoice.grand_total)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatMoney(invoice.accounts_receivable)}
                                                </TableCell>
                                                <TableCell className="bg-white border-light-border sticky right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                                    <Button size="sm" onClick={() => setSelected(invoice)}>
                                                        Bayar
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                            <div className="mt-4">
                                {/* shortcut: page-level status filter is client-side, so no honest total is available; pass a server-side status filter to enable it */}
                                <Pagination
                                    page={query.data?.meta.current_page ?? 1}
                                    lastPage={query.data?.meta.last_page ?? 1}
                                    onPageChange={setPage}
                                />
                            </div>
                        </>
                    )}
                </CardBody>
            </Card>

            <div className="grid gap-6 xl:grid-cols-2">
                <Card className="min-w-0">
                    <CardHeader>
                        <h2 className="font-semibold">Siap diambil</h2>
                    </CardHeader>
                    <CardBody>
                        {dashboard.isLoading ? (
                            <div role="status" className="flex justify-center py-6">
                                <Spinner className="text-brand-primary h-5 w-5" />
                            </div>
                        ) : dashboard.isError ? (
                            <EmptyState
                                title="Daftar siap diambil gagal dimuat"
                                description="Periksa koneksi lalu coba lagi."
                                action={
                                    <Button variant="secondary" onClick={() => dashboard.refetch()}>
                                        Coba lagi
                                    </Button>
                                }
                            />
                        ) : (
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Pelanggan</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Total</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data && data.pickup_queue.length > 0 ? (
                                        data.pickup_queue.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell>{invoice.invoice_number}</TableCell>
                                                <TableCell>{invoice.customer?.name ?? '-'}</TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatMoney(invoice.grand_total)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableEmpty colSpan={3}>Belum ada pesanan siap diambil.</TableEmpty>
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardBody>
                </Card>

                <Card className="min-w-0">
                    <CardHeader>
                        <h2 className="font-semibold">Pengeluaran hari ini</h2>
                        {data ? (
                            <p className="text-sm font-semibold tabular-nums text-light-text dark:text-gray-100">
                                Total {formatMoney(data.stats.expenses_today)}
                            </p>
                        ) : null}
                    </CardHeader>
                    <CardBody>
                        {dashboard.isLoading ? (
                            <div role="status" className="flex justify-center py-6">
                                <Spinner className="text-brand-primary h-5 w-5" />
                            </div>
                        ) : dashboard.isError ? (
                            <EmptyState
                                title="Pengeluaran gagal dimuat"
                                description="Periksa koneksi lalu coba lagi."
                                action={
                                    <Button variant="secondary" onClick={() => dashboard.refetch()}>
                                        Coba lagi
                                    </Button>
                                }
                            />
                        ) : (
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Tanggal</TableHeaderCell>
                                        <TableHeaderCell>Deskripsi</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Jumlah</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {todaysExpenses.length > 0 ? (
                                        todaysExpenses.map((expense) => (
                                            <TableRow key={expense.id}>
                                                <TableCell>{formatDate(expense.incurred_at)}</TableCell>
                                                <TableCell>{expense.description ?? '-'}</TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatMoney(expense.amount)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableEmpty colSpan={3}>Belum ada pengeluaran hari ini.</TableEmpty>
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardBody>
                </Card>
            </div>

            {selected ? (
                <CheckoutDialog invoice={selected} onClose={() => setSelected(null)} />
            ) : null}
        </div>
    );
}
