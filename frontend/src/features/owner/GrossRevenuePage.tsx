import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { ReportFilterBar, type ReportDraft } from '../../components/ReportFilterBar';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { useBranches } from '../../hooks/useBranches';
import { formatDate, todayIso } from '../../lib/dates';
import { paymentMethodLabel } from '../../lib/labels';
import { formatMoney } from '../../lib/money';

export function GrossRevenuePage() {
    const today = todayIso();
    const branchesQuery = useBranches();
    const [draft, setDraft] = useState<ReportDraft>({ start: today, end: today, branch: 'all' });
    const [applied, setApplied] = useState(draft);

    const query = useQuery({
        queryKey: ['report', 'gross-revenue', applied],
        queryFn: () =>
            api.report.grossRevenue({
                branch_id: applied.branch,
                start_date: applied.start,
                end_date: applied.end,
            }),
    });

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Analitik Pemilik"
                title="Rincian Pendapatan Bruto"
                description="Pembayaran yang diterima berdasarkan metode, cabang, dan periode."
            />
            <ReportFilterBar
                draft={draft}
                branches={branchesQuery.data?.data ?? []}
                loading={query.isFetching}
                onChange={setDraft}
                onApply={() => setApplied(draft)}
            />

            {query.isLoading ? (
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {query.data ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard label="Total Diterima" value={formatMoney(query.data.grand_total)} />
                        <StatCard label="Tunai" value={formatMoney(query.data.cash_total)} />
                        <StatCard label="Transfer" value={formatMoney(query.data.transfer_total)} />
                        <StatCard label="QRIS" value={formatMoney(query.data.qris_total)} />
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Catatan Pembayaran</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Tanggal</TableHeaderCell>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Pelanggan</TableHeaderCell>
                                        <TableHeaderCell>Cabang</TableHeaderCell>
                                        <TableHeaderCell>Metode</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Jumlah</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {query.data.payments.length === 0 ? (
                                        <TableEmpty colSpan={6}>Belum ada pembayaran pada periode ini.</TableEmpty>
                                    ) : (
                                        query.data.payments.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell>{formatDate(payment.created_at)}</TableCell>
                                                <TableCell>{payment.invoice_number ?? '-'}</TableCell>
                                                <TableCell>{payment.customer_name ?? '-'}</TableCell>
                                                <TableCell>{payment.branch_name ?? '-'}</TableCell>
                                                <TableCell>{paymentMethodLabel(payment.payment_method)}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(payment.amount)}
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
