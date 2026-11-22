import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import { PageHeader } from '../../components/PageHeader';
import { ReportFilterBar, type ReportDraft } from '../../components/ReportFilterBar';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { useBranches } from '../../hooks/useBranches';
import { formatDate, todayIso } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

export function NetProfitPage() {
    const today = todayIso();
    const branchesQuery = useBranches();
    const [draft, setDraft] = useState<ReportDraft>({ start: today, end: today, branch: 'all' });
    const [applied, setApplied] = useState(draft);

    const query = useQuery({
        queryKey: ['report', 'net-profit', applied],
        queryFn: () =>
            api.report.netProfit({
                branch_id: applied.branch,
                start_date: applied.start,
                end_date: applied.end,
            }),
    });

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Analitik Pemilik"
                title="Rincian Laba Bersih"
                description="Pendapatan dikurangi HPP dan biaya operasional, beserta catatan pendukungnya."
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
                        <StatCard
                            label="Laba Bersih"
                            value={formatMoney(query.data.net_profit)}
                            hint={`${query.data.margin_rate.toFixed(2)}% margin`}
                        />
                        <StatCard label="Pendapatan Bruto" value={formatMoney(query.data.gross_revenue)} />
                        <StatCard label="Total HPP" value={formatMoney(query.data.total_hpp)} />
                        <StatCard
                            label="Pengeluaran"
                            value={formatMoney(query.data.expenses_total)}
                        />
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Catatan Invoice</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Pelanggan</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Total</TableHeaderCell>
                                        <TableHeaderCell className="text-right">HPP</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Laba</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {query.data.invoices.length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada invoice pada periode ini.</TableEmpty>
                                    ) : (
                                        query.data.invoices.map((invoice) => (
                                            <TableRow key={invoice.id}>
                                                <TableCell>{invoice.invoice_number}</TableCell>
                                                <TableCell>{invoice.customer?.name ?? '-'}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(invoice.grand_total)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(invoice.total_cost ?? '0')}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(invoice.total_profit ?? '0')}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardBody>
                    </Card>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Biaya operasional</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Tanggal</TableHeaderCell>
                                            <TableHeaderCell>Deskripsi</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Jumlah</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {query.data.expenses.length === 0 ? (
                                            <TableEmpty colSpan={3}>Belum ada pengeluaran pada periode ini.</TableEmpty>
                                        ) : (
                                            query.data.expenses.map((expense) => (
                                                <TableRow key={expense.id}>
                                                    <TableCell>{formatDate(expense.incurred_at)}</TableCell>
                                                    <TableCell>{expense.description ?? '-'}</TableCell>
                                                    <TableCell className="text-right">
                                                        {formatMoney(expense.amount)}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Sisa Bahan</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Tanggal</TableHeaderCell>
                                            <TableHeaderCell>Bahan</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Biaya</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {query.data.wasted_materials.length === 0 ? (
                                            <TableEmpty colSpan={3}>Belum ada sisa bahan pada periode ini.</TableEmpty>
                                        ) : (
                                            query.data.wasted_materials.map((waste) => (
                                                <TableRow key={waste.id}>
                                                    <TableCell>{formatDate(waste.logged_at)}</TableCell>
                                                    <TableCell>{waste.material?.name ?? '-'}</TableCell>
                                                    <TableCell className="text-right">
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
        </div>
    );
}
