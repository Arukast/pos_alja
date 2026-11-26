import { useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router';

import { api } from '../../api/queries';
import { InvoiceStatusBadge } from '../../components/StatusBadge';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

export function DesignerDashboardPage() {
    const navigate = useNavigate();
    const query = useQuery({
        queryKey: ['dashboard', 'designer'],
        queryFn: () => api.dashboard(),
    });
    const data = query.data?.role === 'designer' ? query.data : null;

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Desainer"
                title="Dasbor Desainer"
                description="Pesanan masuk hari ini, beban draf, dan invoice terbaru."
            />

            <div className="flex justify-end">
                <Button onClick={() => navigate('/designer/new-order')}>Pesanan Baru</Button>
            </div>

            {query.isLoading ? (
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {query.isError ? (
                <EmptyState
                    title="Dasbor gagal dimuat"
                    description="Periksa koneksi lalu coba lagi."
                    action={
                        <Button variant="secondary" onClick={() => query.refetch()}>
                            Coba lagi
                        </Button>
                    }
                />
            ) : null}

            {data ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <StatCard
                            label="Invoice Hari Ini"
                            value={String(data.stats.invoices_today)}
                        />
                        <StatCard
                            label="Total Hari Ini"
                            value={formatMoney(data.stats.grand_total_today)}
                        />
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Invoice Terbaru</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Pelanggan</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell>Tenggat</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Total</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.recent_invoices.length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada invoice.</TableEmpty>
                                    ) : (
                                        data.recent_invoices.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell className="font-medium">
                                                    {invoice.invoice_number}
                                                </TableCell>
                                                <TableCell>{invoice.customer?.name ?? '-'}</TableCell>
                                                <TableCell>
                                                    <InvoiceStatusBadge status={invoice.status} />
                                                </TableCell>
                                                <TableCell>
                                                    {invoice.deadline
                                                        ? formatDate(invoice.deadline)
                                                        : '-'}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatMoney(invoice.grand_total)}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardBody>
                    </Card>
                </>
            ) : null}
        </div>
    );
}
