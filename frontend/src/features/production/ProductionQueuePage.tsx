import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { OrderStatusBadge } from '../../components/StatusBadge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { errorMessage } from '../../lib/errors';
import { formatDate } from '../../lib/dates';

export function ProductionQueuePage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [notice, setNotice] = useState<string | null>(null);

    const query = useQuery({
        queryKey: ['production', 'queue', page],
        queryFn: () => api.productionQueue({ page }),
    });

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

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Produksi"
                title="Antrean Cetak"
                description="Mulai dan selesaikan pekerjaan cetak. Informasi uang sengaja disembunyikan."
            />
            <Card>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Pelanggan</TableHeaderCell>
                                        <TableHeaderCell>Item</TableHeaderCell>
                                        <TableHeaderCell>Jml</TableHeaderCell>
                                        <TableHeaderCell>Tenggat</TableHeaderCell>
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell className="bg-light-surface border-light-border sticky right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                            Aksi
                                        </TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {(query.data?.data ?? []).length === 0 ? (
                                        <TableEmpty colSpan={7}>Belum ada pekerjaan dalam antrean.</TableEmpty>
                                    ) : (
                                        (query.data?.data ?? []).map((order) => (
                                            <TableRow key={order.id}>
                                                <TableCell>
                                                    {order.invoice?.invoice_number ?? '-'}
                                                </TableCell>
                                                <TableCell>
                                                    {order.invoice?.customer?.name ?? '-'}
                                                </TableCell>
                                                <TableCell>{order.file_name ?? '-'}</TableCell>
                                                <TableCell>{order.quantity}</TableCell>
                                                <TableCell>
                                                    {formatDate(order.invoice?.deadline ?? '-')}
                                                </TableCell>
                                                <TableCell>
                                                    <OrderStatusBadge status={order.status} />
                                                </TableCell>
                                                <TableCell className="bg-white border-light-border sticky right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                                    {order.status === 0 ? (
                                                        <Button
                                                            size="sm"
                                                            loading={start.isPending}
                                                            onClick={() => start.mutate(order.id)}
                                                        >
                                                            Mulai
                                                        </Button>
                                                    ) : (
                                                        <Button
                                                            size="sm"
                                                            variant="secondary"
                                                            loading={complete.isPending}
                                                            onClick={() => complete.mutate(order.id)}
                                                        >
                                                            Selesaikan
                                                        </Button>
                                                    )}
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
                                    onPageChange={setPage}
                                />
                            </div>
                        </>
                    )}
                </CardBody>
            </Card>
            {notice ? <Toast message={notice} onClose={() => setNotice(null)} /> : null}
        </div>
    );
}
