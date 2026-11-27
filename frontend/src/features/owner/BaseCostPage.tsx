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
import { Toast } from '../../components/ui/Toast';
import { useBranches } from '../../hooks/useBranches';
import { useReportExport } from '../../hooks/useReportExport';
import { formatDate, todayIso } from '../../lib/dates';
import { formatMoney } from '../../lib/money';
import { quantityWithUnit } from '../../lib/units';

export function BaseCostPage() {
    const today = todayIso();
    const { user } = useAuth();
    const isOwner = user?.role === 'owner';
    const branchesQuery = useBranches(isOwner);
    const { exporting, notice, clearNotice, exportReport } = useReportExport('base-cost');
    const [draft, setDraft] = useState<ReportDraft>({
        period: 'daily',
        start: today,
        end: today,
        branch: isOwner ? 'all' : '',
    });
    const [applied, setApplied] = useState(draft);

    const reportParams = {
        period: applied.period,
        ...(isOwner ? { branch_id: applied.branch } : {}),
        start_date: applied.start,
        end_date: applied.end,
    };

    const query = useQuery({
        queryKey: ['report', 'base-cost', isOwner ? 'owner' : 'manager', applied],
        queryFn: () => api.report.baseCost(reportParams),
    });

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow={isOwner ? 'Analitik Pemilik' : 'Analitik Cabang'}
                title="Rincian Biaya Dasar"
                description="Rincian HPP invoice dan biaya bahan terbuang."
            />
            <ReportFilterBar
                draft={draft}
                branches={branchesQuery.data?.data ?? []}
                loading={query.isFetching}
                showBranch={isOwner}
                exporting={exporting}
                onChange={setDraft}
                onApply={(next) => setApplied(next ?? draft)}
                onExport={(format) => void exportReport(format, reportParams)}
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
                    title="Biaya dasar gagal dimuat"
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
                    <div className="grid gap-4 sm:grid-cols-3">
                        <StatCard label="Total HPP" value={formatMoney(query.data.total_hpp)} />
                        <StatCard
                            label="Invoice HPP"
                            value={formatMoney(query.data.invoice_cost_total)}
                        />
                        <StatCard
                            label="HPP Bahan Terbuang"
                            value={formatMoney(query.data.wasted_cost_total)}
                        />
                    </div>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <Card className="min-w-0">
                            <CardHeader>
                                <h2 className="font-semibold">Invoice HPP</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Invoice</TableHeaderCell>
                                            <TableHeaderCell>Pelanggan</TableHeaderCell>
                                            <TableHeaderCell className="text-right">HPP</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {query.data.invoices.length === 0 ? (
                                            <TableEmpty colSpan={3}>Belum ada invoice pada periode ini.</TableEmpty>
                                        ) : (
                                            query.data.invoices.map((invoice) => (
                                                <TableRow key={invoice.id}>
                                                    <TableCell>{invoice.invoice_number}</TableCell>
                                                    <TableCell>{invoice.customer?.name ?? '-'}</TableCell>
                                                    <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                        {formatMoney(invoice.total_cost ?? '0')}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardBody>
                        </Card>

                        <Card className="min-w-0">
                            <CardHeader>
                                <h2 className="font-semibold">Bahan Terbuang</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Tanggal</TableHeaderCell>
                                            <TableHeaderCell>Bahan</TableHeaderCell>
                                            <TableHeaderCell>Jml</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Biaya</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {query.data.wasted_materials.length === 0 ? (
                                            <TableEmpty colSpan={4}>Belum ada bahan terbuang pada periode ini.</TableEmpty>
                                        ) : (
                                            query.data.wasted_materials.map((waste) => (
                                                <TableRow key={waste.id}>
                                                    <TableCell>{formatDate(waste.logged_at)}</TableCell>
                                                    <TableCell>{waste.material?.name ?? '-'}</TableCell>
                                                    <TableCell>{quantityWithUnit(waste.quantity, waste.material?.unit)}</TableCell>
                                                    <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                        {formatMoney(waste.wasted_cost)}
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

            {notice ? (
                <Toast message={notice.message} tone={notice.tone} onClose={clearNotice} />
            ) : null}
        </div>
    );
}
