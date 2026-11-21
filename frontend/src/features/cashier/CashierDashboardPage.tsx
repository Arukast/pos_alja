import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import { InvoiceStatusBadge } from '../../components/StatusBadge';
import { PageHeader } from '../../components/PageHeader';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

export function CashierDashboardPage() {
    const query = useQuery({
        queryKey: ['dashboard', 'cashier'],
        queryFn: () => api.dashboard(),
    });
    const data = query.data?.role === 'cashier' ? query.data : null;

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Kasir"
                title="Dasbor Kasir"
                description="Antrean pembayaran, pengambilan, dan pengeluaran harian."
            />

            {query.isLoading ? (
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {data ? (
                <>
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

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Antrean tagihan</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Pelanggan</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Total</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Sisa Tagihan</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.billing_queue.length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada invoice menunggu pembayaran.</TableEmpty>
                                    ) : (
                                        data.billing_queue.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell className="font-medium">
                                                    {invoice.invoice_number}
                                                </TableCell>
                                                <TableCell>{invoice.customer?.name ?? '-'}</TableCell>
                                                <TableCell>
                                                    <InvoiceStatusBadge status={invoice.status} />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(invoice.grand_total)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(invoice.accounts_receivable)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardBody>
                    </Card>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Antrean Pengambilan</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Invoice</TableHeaderCell>
                                            <TableHeaderCell>Pelanggan</TableHeaderCell>
                                            <TableHeaderCell>Dibayar</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.pickup_queue.length === 0 ? (
                                            <TableEmpty colSpan={3}>Belum ada pesanan menunggu pengambilan.</TableEmpty>
                                        ) : (
                                            data.pickup_queue.map((invoice) => (
                                                <TableRow key={invoice.id}>
                                                    <TableCell className="font-medium">
                                                        {invoice.invoice_number}
                                                    </TableCell>
                                                    <TableCell>{invoice.customer?.name ?? '-'}</TableCell>
                                                    <TableCell>
                                                        {invoice.pickup_date
                                                            ? formatDate(invoice.pickup_date)
                                                            : 'Siap'}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Pengeluaran Terbaru</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Tanggal</TableHeaderCell>
                                            <TableHeaderCell>Deskripsi</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Jumlah</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.expenses.length === 0 ? (
                                            <TableEmpty colSpan={3}>Belum ada pengeluaran.</TableEmpty>
                                        ) : (
                                            data.expenses.map((expense) => (
                                                <TableRow key={expense.id}>
                                                    <TableCell>{formatDate(expense.incurred_at)}</TableCell>
                                                    <TableCell>{expense.description ?? '-'}</TableCell>
                                                    <TableCell className="text-right">
                                                        {formatMoney(expense.amount)}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardBody>
                        </Card>
                    </div>
                </>
            ) : null}
        </div>
    );
}
