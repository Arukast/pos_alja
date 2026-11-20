import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import { CollectionTable } from '../../components/CollectionTable';
import { PageHeader } from '../../components/PageHeader';
import { ResourceTable } from '../../components/ResourceTable';
import { Badge } from '../../components/ui/Badge';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Spinner } from '../../components/ui/Spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatMoney } from '../../lib/money';

export function ManagerUsersPage() {
    return (
        <ResourceTable
            eyebrow="Administration"
            title="User Management"
            description="Branch staff accounts and role assignments."
            queryKey={['manager', 'users']}
            queryFn={(page) => api.users({ page, include_deleted: 1 })}
            columns={[
                { header: 'Name', cell: (user) => <span className="font-medium">{user.name}</span> },
                { header: 'Username', cell: (user) => user.username },
                { header: 'Role', cell: (user) => <Badge tone="brand">{user.role}</Badge> },
                { header: 'Email', cell: (user) => user.email ?? '-' },
            ]}
        />
    );
}

export function ManagerMaterialsPage() {
    return (
        <ResourceTable
            eyebrow="Inventory"
            title="Materials & Stock"
            description="Branch stock levels and replenishment thresholds."
            queryKey={['manager', 'materials']}
            queryFn={(page) => api.materials({ page })}
            columns={[
                { header: 'Material', cell: (material) => <span className="font-medium">{material.name}</span> },
                { header: 'Category', cell: (material) => material.category?.name ?? '-' },
                { header: 'Current', cell: (material) => material.current_stock ?? '0.00' },
                { header: 'Minimum', cell: (material) => material.min_stock ?? '0.00' },
                { header: 'Cost', cell: (material) => formatMoney(material.cost) },
            ]}
        />
    );
}

export function ManagerLedgersPage() {
    return (
        <ResourceTable
            eyebrow="Inventory"
            title="Stock Ledgers"
            description="Stock movement history for your branch."
            queryKey={['manager', 'stock-ledgers']}
            queryFn={(page) => api.stockLedgers({ page })}
            columns={[
                { header: 'Material', cell: (ledger) => ledger.material?.name ?? '-' },
                { header: 'Added', cell: (ledger) => ledger.added_stock },
                { header: 'Used', cell: (ledger) => ledger.used_stock },
                { header: 'Wasted', cell: (ledger) => ledger.wasted_stock },
                { header: 'Remaining', cell: (ledger) => ledger.remaining_stock },
                { header: 'Notes', cell: (ledger) => ledger.notes ?? '-' },
            ]}
        />
    );
}

export function ManagerFinishingPage() {
    return (
        <CollectionTable
            eyebrow="Configuration"
            title="Display & Finishing"
            description="Finishing, cut, and display attributes for your branch."
            queryKey={['manager', 'attributes']}
            queryFn={() => api.attributes()}
            columns={[
                { header: 'Name', cell: (attribute) => <span className="font-medium">{attribute.name}</span> },
                { header: 'Type', cell: (attribute) => attribute.type },
                { header: 'Branch', cell: (attribute) => attribute.branch?.name ?? 'Global' },
            ]}
        />
    );
}

export function ManagerPricingPage() {
    return (
        <ResourceTable
            eyebrow="Configuration"
            title="Pricing Matrices"
            description="Tiered pricing rows and modifier rules for your branch."
            queryKey={['manager', 'pricing']}
            queryFn={(page) => api.pricing({ page })}
            columns={[
                { header: 'Product', cell: (pricing) => pricing.product?.name ?? '-' },
                { header: 'Material', cell: (pricing) => pricing.material?.name ?? '-' },
                { header: 'Customer', cell: (pricing) => pricing.customer_type },
                { header: 'Min qty', cell: (pricing) => pricing.min_qty },
                { header: 'Price', cell: (pricing) => formatMoney(pricing.price) },
                { header: 'Cost', cell: (pricing) => formatMoney(pricing.cost) },
                { header: 'Profit', cell: (pricing) => formatMoney(pricing.profit) },
            ]}
        />
    );
}

export function ManagerCategoriesPage() {
    return (
        <CollectionTable
            eyebrow="Configuration"
            title="Product Categories"
            description="Category archetypes and allowed attribute types."
            queryKey={['manager', 'categories']}
            queryFn={() => api.categories()}
            columns={[
                { header: 'Name', cell: (category) => <span className="font-medium">{category.name}</span> },
                { header: 'Archetype', cell: (category) => category.calculation_archetype },
                {
                    header: 'Allowed attributes',
                    cell: (category) =>
                        category.allowed_attribute_types.length === 0
                            ? 'All'
                            : category.allowed_attribute_types.join(', '),
                },
            ]}
        />
    );
}

export function ManagerMachinesPage() {
    return (
        <ResourceTable
            eyebrow="Configuration"
            title="Printer Machines"
            description="Machine records and base cost for your branch."
            queryKey={['manager', 'machines']}
            queryFn={(page) => api.machines({ page, include_deleted: 1 })}
            columns={[
                { header: 'Machine', cell: (machine) => <span className="font-medium">{machine.name}</span> },
                { header: 'Branch', cell: (machine) => machine.branch?.name ?? '-' },
                { header: 'Base cost', cell: (machine) => formatMoney(machine.cost) },
            ]}
        />
    );
}

export function ManagerLeaderboardPage() {
    const query = useQuery({
        queryKey: ['report', 'designer-leaderboard'],
        queryFn: () => api.report.leaderboard(),
    });

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Overview"
                title="Designer Leaderboard"
                description="Designer revenue, order count, AOV, and waste accuracy for the current month."
            />

            {query.isLoading ? (
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {query.data ? (
                <Card>
                    <CardHeader>
                        <h2 className="font-semibold">Current month</h2>
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
                                {query.data.leaders.length === 0 ? (
                                    <TableEmpty colSpan={5}>No designer data yet.</TableEmpty>
                                ) : (
                                    query.data.leaders.map((leader) => (
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
            ) : null}
        </div>
    );
}
