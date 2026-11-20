import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import { OrderStatusBadge } from '../../components/StatusBadge';
import { PageHeader } from '../../components/PageHeader';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';

export function ProductionDashboardPage() {
    const query = useQuery({
        queryKey: ['dashboard', 'production'],
        queryFn: () => api.dashboard(),
    });
    const data = query.data?.role === 'production' ? query.data : null;

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Production"
                title="Production Dashboard"
                description="Active print queue, completed jobs, and material waste."
            />

            {query.isLoading ? (
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {data ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard label="Jobs in Queue" value={String(data.queued)} />
                        <StatCard label="Currently Printing" value={String(data.processing)} />
                        <StatCard label="Completed Today" value={String(data.completed_today)} />
                        <StatCard label="Waste Today" value={String(data.waste_today)} />
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Active print queue</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Customer</TableHeaderCell>
                                        <TableHeaderCell>File</TableHeaderCell>
                                        <TableHeaderCell>Qty</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell>Deadline</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.queue.length === 0 ? (
                                        <TableEmpty colSpan={6}>The print queue is empty.</TableEmpty>
                                    ) : (
                                        data.queue.map((order) => (
                                            <TableRow key={order.id}>
                                                <TableCell className="font-medium">
                                                    {order.invoice?.invoice_number ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {order.invoice?.customer?.name ?? '-'}
                                                </TableCell>
                                                <TableCell>{order.file_name ?? '-'}</TableCell>
                                                <TableCell>{order.quantity}</TableCell>
                                                <TableCell>
                                                    <OrderStatusBadge status={order.status} />
                                                </TableCell>
                                                <TableCell>
                                                    {order.invoice?.deadline
                                                        ? formatDate(order.invoice.deadline)
                                                        : '-'}
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
