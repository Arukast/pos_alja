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
                eyebrow="Owner"
                title="Owner Dashboard"
                description="Cross-branch revenue, cost, profit, and expenses."
                actions={
                    <>
                        <Select
                            aria-label="Branch"
                            value={branchId}
                            onChange={(event) => updateParam('branch_id', event.target.value)}
                            className="h-9 py-1.5"
                        >
                            <option value="all">All branches</option>
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
                                {value.charAt(0).toUpperCase() + value.slice(1)}
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
                            label="Gross Revenue"
                            value={formatMoney(data.finance.gross_revenue)}
                            hint="Payments collected in the period"
                        />
                        <StatCard
                            label="Base Cost"
                            value={formatMoney(data.finance.total_cost)}
                            hint="Invoice HPP plus waste"
                        />
                        <StatCard
                            label="Net Profit"
                            value={formatMoney(data.finance.net_profit)}
                            hint="Revenue minus HPP and expenses"
                        />
                        <StatCard
                            label="Expenses"
                            value={formatMoney(data.finance.expenses)}
                            hint="Operating expenses"
                        />
                    </div>

                    <Card>
                        <CardHeader>
                            <div>
                                <h2 className="font-semibold">Net profit movement</h2>
                                <p className="text-light-muted text-sm dark:text-gray-400">
                                    {period === 'daily'
                                        ? 'Six four-hour buckets for today'
                                        : period === 'weekly'
                                          ? 'The last seven days'
                                          : 'The last four weeks'}
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
