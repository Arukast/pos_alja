import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import { PageHeader } from '../../components/PageHeader';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

export function ManagerDashboardPage() {
    const query = useQuery({
        queryKey: ['dashboard', 'manager'],
        queryFn: () => api.dashboard(),
    });
    const data = query.data?.role === 'manager' ? query.data : null;

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Branch Manager"
                title="Branch Dashboard"
                description="Inventory, pricing, staff, and monthly designer performance."
            />

            {query.isLoading ? (
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {data ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard label="Stocked Materials" value={String(data.counts.materials)} />
                        <StatCard label="Printer Machines" value={String(data.counts.machines)} />
                        <StatCard label="Active Staff" value={String(data.counts.staff)} />
                        <StatCard label="Pricing Rows" value={String(data.counts.pricing)} />
                    </div>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Recent stock movements</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Material</TableHeaderCell>
                                            <TableHeaderCell>Added</TableHeaderCell>
                                            <TableHeaderCell>Used</TableHeaderCell>
                                            <TableHeaderCell>Remaining</TableHeaderCell>
                                            <TableHeaderCell>Date</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.stock_ledgers.length === 0 ? (
                                            <TableEmpty colSpan={5}>No stock movements yet.</TableEmpty>
                                        ) : (
                                            data.stock_ledgers.map((ledger) => (
                                                <TableRow key={ledger.id}>
                                                    <TableCell>{ledger.material?.name ?? '-'}</TableCell>
                                                    <TableCell>{ledger.added_stock}</TableCell>
                                                    <TableCell>{ledger.used_stock}</TableCell>
                                                    <TableCell>{ledger.remaining_stock}</TableCell>
                                                    <TableCell>{formatDate(ledger.logged_at)}</TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Recent expenses</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Date</TableHeaderCell>
                                            <TableHeaderCell>Description</TableHeaderCell>
                                            <TableHeaderCell>Category</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Amount</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.expenses.length === 0 ? (
                                            <TableEmpty colSpan={4}>No expenses yet.</TableEmpty>
                                        ) : (
                                            data.expenses.map((expense) => (
                                                <TableRow key={expense.id}>
                                                    <TableCell>{formatDate(expense.incurred_at)}</TableCell>
                                                    <TableCell>{expense.description ?? '-'}</TableCell>
                                                    <TableCell>{expense.category}</TableCell>
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
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">Designer leaderboard</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Designer</TableHeaderCell>
                                        <TableHeaderCell>Orders</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Revenue</TableHeaderCell>
                                        <TableHeaderCell className="text-right">AOV</TableHeaderCell>
                                        <TableHeaderCell>Waste logs</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.leaderboard.length === 0 ? (
                                        <TableEmpty colSpan={5}>No designer data this month.</TableEmpty>
                                    ) : (
                                        data.leaderboard.map((leader) => (
                                            <TableRow key={leader.id}>
                                                <TableCell className="font-medium">{leader.name}</TableCell>
                                                <TableCell>{leader.order_count}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(leader.revenue)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(leader.average_order_value)}
                                                </TableCell>
                                                <TableCell>{leader.waste_count}</TableCell>
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
