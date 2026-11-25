import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import { OrderStatusBadge } from '../../components/StatusBadge';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { wasteByUnitLabel } from '../../lib/units';

export function ProductionDashboardPage() {
    const query = useQuery({
        queryKey: ['dashboard', 'production'],
        queryFn: () => api.dashboard(),
    });
    const data = query.data?.role === 'production' ? query.data : null;

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Produksi"
                title="Dasbor Produksi"
                description="Antrean cetak aktif, pekerjaan selesai, dan sisa bahan."
            />

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
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard label="Pekerjaan dalam Antrean" value={String(data.queued)} />
                        <StatCard label="Sedang Dicetak" value={String(data.processing)} />
                        <StatCard label="Selesai Hari Ini" value={String(data.completed_today)} />
                        <StatCard label="Sisa Bahan Hari Ini" value={wasteByUnitLabel(data.waste_today_by_unit)} />
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Antrean cetak aktif</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Pelanggan</TableHeaderCell>
                                        <TableHeaderCell>File</TableHeaderCell>
                                        <TableHeaderCell>Jml</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell>Tenggat</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.queue.length === 0 ? (
                                        <TableEmpty colSpan={6}>Antrean cetak kosong.</TableEmpty>
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
