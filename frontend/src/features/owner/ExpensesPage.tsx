import { useQuery } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
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
import { formatMoney } from '../../lib/money';

export function ExpensesPage() {
    const today = todayIso();
    const branchesQuery = useBranches();
    const [draft, setDraft] = useState<ReportDraft>({ start: today, end: today, branch: 'all' });
    const [applied, setApplied] = useState(draft);

    const query = useQuery({
        queryKey: ['report', 'expenses', applied],
        queryFn: () =>
            api.report.expenses({
                branch_id: applied.branch,
                start_date: applied.start,
                end_date: applied.end,
            }),
    });

    const categories = Object.entries(query.data?.by_category ?? {});

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Analitik Pemilik"
                title="Rincian Pengeluaran"
                description="Biaya operasional cabang berdasarkan kategori dan periode."
            />
            <ReportFilterBar
                draft={draft}
                branches={branchesQuery.data?.data ?? []}
                loading={query.isFetching}
                onChange={setDraft}
                onApply={() => setApplied(draft)}
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
                    title="Pengeluaran gagal dimuat"
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
                        <StatCard
                            label="Total Pengeluaran"
                            value={formatMoney(query.data.expenses_total)}
                        />
                        {categories.slice(0, 3).map(([category, total]) => (
                            <StatCard key={category} label={category} value={formatMoney(total)} />
                        ))}
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Catatan Biaya Operasional</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Tanggal</TableHeaderCell>
                                        <TableHeaderCell>Kasir</TableHeaderCell>
                                        <TableHeaderCell>Kategori</TableHeaderCell>
                                        <TableHeaderCell>Deskripsi</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Jumlah</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {query.data.expenses.length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada pengeluaran pada periode ini.</TableEmpty>
                                    ) : (
                                        query.data.expenses.map((expense) => (
                                            <TableRow key={expense.id}>
                                                <TableCell>{formatDate(expense.incurred_at)}</TableCell>
                                                <TableCell>{expense.cashier_name ?? '-'}</TableCell>
                                                <TableCell>{expense.category}</TableCell>
                                                <TableCell>{expense.description ?? '-'}</TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatMoney(expense.amount)}
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
