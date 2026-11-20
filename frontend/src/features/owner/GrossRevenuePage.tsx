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
            <PageHeader
                eyebrow="Owner insights"
                title="Gross Revenue Detail"
                description="Collected payments by method, branch, and period."
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
                        <StatCard label="Total Collected" value={formatMoney(query.data.grand_total)} />
                        <StatCard label="Cash" value={formatMoney(query.data.cash_total)} />
                        <StatCard label="Transfer" value={formatMoney(query.data.transfer_total)} />
                        <StatCard label="QRIS" value={formatMoney(query.data.qris_total)} />
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Payment ledger</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Date</TableHeaderCell>
                                        <TableHeaderCell>Invoice</TableHeaderCell>
                                        <TableHeaderCell>Customer</TableHeaderCell>
                                        <TableHeaderCell>Branch</TableHeaderCell>
                                        <TableHeaderCell>Method</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Amount</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {query.data.payments.length === 0 ? (
                                        <TableEmpty colSpan={6}>No payments in this period.</TableEmpty>
                                    ) : (
                                        query.data.payments.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell>{formatDate(payment.created_at)}</TableCell>
                                                <TableCell>{payment.invoice_number ?? '-'}</TableCell>
                                                <TableCell>{payment.customer_name ?? '-'}</TableCell>
                                                <TableCell>{payment.branch_name ?? '-'}</TableCell>
                                                <TableCell>{payment.payment_method}</TableCell>
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
