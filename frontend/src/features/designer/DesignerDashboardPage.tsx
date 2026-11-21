import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router';

import { api } from '../../api/queries';
import { InvoiceStatusBadge } from '../../components/StatusBadge';
import { PageHeader } from '../../components/PageHeader';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

export function DesignerDashboardPage() {
    const query = useQuery({
        queryKey: ['dashboard', 'designer'],
        queryFn: () => api.dashboard(),
    });
    const data = query.data?.role === 'designer' ? query.data : null;

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Designer"
                title="Designer Dashboard"
                description="Today’s intake, draft workload, and recent invoices."
                actions={
                    <Link
                        to="/designer/new-order"
                        className="bg-brand-primary hover:bg-brand-primary-hover rounded-lg px-4 py-2 text-sm font-semibold text-white"
                    >
                        New order
                    </Link>
                }
            />

            {query.isLoading ? (
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {data ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <StatCard
                            label="Invoices Today"
                            value={String(data.stats.invoices_today)}
                        />
                        <StatCard
                            label="Grand Total Today"
                            value={formatMoney(data.stats.grand_total_today)}
                        />
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Recent invoices</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Customer</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell>Deadline</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Total</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.recent_invoices.length === 0 ? (
                                        <TableEmpty colSpan={5}>No invoices yet.</TableEmpty>
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
                                                <TableCell className="text-right">
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
