import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { useAuth } from '../../auth/useAuth';
import { PageBanner } from '../../components/PageBanner';
import { ReportFilterBar, type ReportDraft } from '../../components/ReportFilterBar';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { useBranches } from '../../hooks/useBranches';
import { formatDate, todayIso } from '../../lib/dates';
import { paymentMethodLabel } from '../../lib/labels';
import { formatMoney } from '../../lib/money';

export function GrossRevenuePage() {
    const today = todayIso();
    const { user } = useAuth();
    const isOwner = user?.role === 'owner';
    const branchesQuery = useBranches(isOwner);
    const [draft, setDraft] = useState<ReportDraft>({
        period: 'daily',
        start: today,
        end: today,
        branch: isOwner ? 'all' : '',
    });
    const [applied, setApplied] = useState(draft);

    const query = useQuery({
        queryKey: ['report', 'gross-revenue', isOwner ? 'owner' : 'manager', applied],
        queryFn: () =>
            api.report.grossRevenue({
                period: applied.period,
                ...(isOwner ? { branch_id: applied.branch } : {}),
                start_date: applied.start,
                end_date: applied.end,
            }),
    });

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow={isOwner ? 'Analitik Pemilik' : 'Analitik Cabang'}
                title="Rincian Pendapatan Bruto"
                description="Pembayaran yang diterima berdasarkan metode, cabang, dan periode."
            />
            <ReportFilterBar
                draft={draft}
                branches={branchesQuery.data?.data ?? []}
                loading={query.isFetching}
                showBranch={isOwner}
                onChange={setDraft}
                onApply={(next) => setApplied(next ?? draft)}
            />

            {query.isLoading ? (
                <Card>
                    <div role="status" className="flex items-center justify-center px-5 py-16">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                </Card>
            ) : null}

            {query.isError ? (
                <EmptyState
                    title="Pendapatan bruto gagal dimuat"
                    description="Periksa koneksi lalu coba lagi."
                    action={
                        <Button variant="secondary" onClick={() => query.refetch()}>
                            Coba lagi
                        </Button>
                    }
                />
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
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
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
