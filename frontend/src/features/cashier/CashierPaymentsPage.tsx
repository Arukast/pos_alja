import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Invoice } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Pagination } from '../../components/ui/Pagination';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { InvoiceStatusBadge } from '../../components/StatusBadge';
import { formatMoney } from '../../lib/money';
import { CheckoutDialog } from './CheckoutDialog';

export function CashierPaymentsPage() {
    const [page, setPage] = useState(1);
    const [selected, setSelected] = useState<Invoice | null>(null);

    const query = useQuery({
        queryKey: ['cashier', 'invoices', page],
        queryFn: () => api.invoices({ page }),
    });

    const invoices = (query.data?.data ?? []).filter((invoice) => invoice.status !== 2);

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Kasir"
                title="Antrean Pembayaran"
                description="Invoice belum dibayar dan DP yang menunggu pembayaran."
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
                                        <TableHeaderCell>Status</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Total</TableHeaderCell>
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

            {selected ? (
                <CheckoutDialog invoice={selected} onClose={() => setSelected(null)} />
            ) : null}
        </div>
    );
}
