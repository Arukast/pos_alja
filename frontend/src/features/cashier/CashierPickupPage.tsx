import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { PageHeader } from '../../components/PageHeader';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { errorMessage } from '../../lib/errors';
import { formatMoney } from '../../lib/money';

export function CashierPickupPage() {
    const queryClient = useQueryClient();
    const [page, setPage] = useState(1);
    const [notice, setNotice] = useState<string | null>(null);

    const query = useQuery({
        queryKey: ['cashier', 'pickup', page],
        queryFn: () => api.invoices({ page, status: 2, pickup_status: 0 }),
    });

    const pickup = useMutation({
        mutationFn: (invoiceId: string) => api.pickup(invoiceId),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['cashier', 'pickup'] });
            void queryClient.invalidateQueries({ queryKey: ['cashier', 'invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['invoices'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setNotice('Pesanan ditandai sudah diambil.');
        },
        onError: (error) => setNotice(errorMessage(error)),
    });

    const invoices = query.data?.data ?? [];

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Kasir"
                title="Antrean Pengambilan"
                description="Pesanan lunas yang menunggu pengambilan pelanggan."
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
                                        <TableHeaderCell>Dibayar</TableHeaderCell>
                                        <TableHeaderCell className="bg-light-surface border-light-border sticky right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                            Aksi
                                        </TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {invoices.length === 0 ? (
                                        <TableEmpty colSpan={4}>Belum ada pesanan menunggu pengambilan.</TableEmpty>
                                    ) : (
                                        invoices.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell>{invoice.invoice_number}</TableCell>
                                                <TableCell>{invoice.customer?.name ?? '-'}</TableCell>
                                                <TableCell>{formatMoney(invoice.grand_total)}</TableCell>
                                                <TableCell className="bg-white border-light-border sticky right-0 border-l dark:border-surface-300 dark:bg-surface-700">
                                                    <Button
                                                        size="sm"
                                                        loading={pickup.isPending}
                                                        onClick={() => pickup.mutate(invoice.id)}
                                                    >
                                                        Tandai diambil
                                                    </Button>
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
