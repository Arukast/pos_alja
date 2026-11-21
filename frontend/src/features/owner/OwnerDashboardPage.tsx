import { useQuery } from '@tanstack/react-query';
import { useSearchParams } from 'react-router';

import { api } from '../../api/queries';
import { PageHeader } from '../../components/PageHeader';
import { Sparkline } from '../../components/Sparkline';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { formatMoney } from '../../lib/money';

const periodLabels: Record<string, string> = {
    daily: 'Harian',
    weekly: 'Mingguan',
    monthly: 'Bulanan',
};

export function OwnerDashboardPage() {
    const [params, setParams] = useSearchParams();
    const period = params.get('period') ?? 'daily';
    const branchId = params.get('branch_id') ?? 'all';

    const updateParam = (key: string, value: string) => {
        const next = new URLSearchParams(params);
        next.set(key, value);
        setParams(next);
    };

    const branchesQuery = useQuery({
        queryKey: ['branches', 'owner-selector'],
        queryFn: () => api.branches(),
    });

    const dashboardQuery = useQuery({
        queryKey: ['dashboard', 'owner', period, branchId],
        queryFn: () => api.dashboard({ period, branch_id: branchId }),
    });

    const data = dashboardQuery.data?.role === 'owner' ? dashboardQuery.data : null;

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Pemilik"
                title="Dasbor Pemilik"
                description="Pendapatan, biaya, laba, dan pengeluaran lintas cabang."
                actions={
                    <>
                        <Select
                            aria-label="Cabang"
                            value={branchId}
                            onChange={(event) => updateParam('branch_id', event.target.value)}
                            className="h-9 py-1.5"
                        >
                            <option value="all">Semua cabang</option>
                            {(branchesQuery.data?.data ?? []).map((branch) => (
                                <option key={branch.id} value={branch.id}>
                                    {branch.name}
                                </option>
                            ))}
                        </Select>
                        {(['daily', 'weekly', 'monthly'] as const).map((value) => (
                            <Button
                                key={value}
                                size="sm"
                                variant={period === value ? 'primary' : 'secondary'}
                                onClick={() => updateParam('period', value)}
                            >
                                {periodLabels[value]}
                            </Button>
                        ))}
                    </>
                }
            />

            {dashboardQuery.isLoading ? (
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {data ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            label="Pendapatan Bruto"
                            value={formatMoney(data.finance.gross_revenue)}
                            hint="Pembayaran yang diterima pada periode ini"
                        />
                        <StatCard
                            label="Biaya Dasar"
                            value={formatMoney(data.finance.total_cost)}
                            hint="HPP invoice ditambah sisa bahan"
                        />
                        <StatCard
                            label="Laba Bersih"
                            value={formatMoney(data.finance.net_profit)}
                            hint="Pendapatan dikurangi HPP dan pengeluaran"
                        />
                        <StatCard
                            label="Pengeluaran"
                            value={formatMoney(data.finance.expenses)}
                            hint="Biaya operasional"
                        />
                    </div>

                    <Card>
                        <CardHeader>
                            <div>
                                <h2 className="font-semibold">Pergerakan laba bersih</h2>
                                <p className="text-light-muted text-sm dark:text-gray-400">
                                    {period === 'daily'
                                        ? 'Enam blok empat jam untuk hari ini'
                                        : period === 'weekly'
                                          ? 'Tujuh hari terakhir'
                                          : 'Empat minggu terakhir'}
                                </p>
                            </div>
                        </CardHeader>
                        <CardBody>
                            <Sparkline
                                points={data.sparkline.points}
                                labels={data.sparkline.labels}
                            />
                        </CardBody>
                    </Card>
                </>
            ) : null}
        </div>
    );
}
