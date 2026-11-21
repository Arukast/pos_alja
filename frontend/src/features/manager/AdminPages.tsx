import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Attribute, AttributeType, Category, Machine, Material, Pricing, UserRecord } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { PageHeader } from '../../components/PageHeader';
import { ResourceTable } from '../../components/ResourceTable';
import { RestockMaterialButton } from '../catalog/RestockMaterialButton';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { Select } from '../../components/ui/Select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { collectionAsPage } from '../../lib/pagination';
import { formatMoney } from '../../lib/money';

export function ManagerUsersPage() {
    const { user } = useAuth();
    const fields: Array<CrudField<UserRecord>> = [
        { key: 'name', label: 'Name', required: true },
        { key: 'username', label: 'Username', required: true },
        { key: 'email', label: 'Email', type: 'email' },
        { key: 'password', label: 'Password', type: 'password', hideOnEdit: true },
        {
            key: 'role',
            label: 'Role',
            type: 'select',
            options: [
                { value: 'cashier', label: 'Cashier' },
                { value: 'designer', label: 'Designer' },
                { value: 'production', label: 'Production' },
            ],
        },
        { key: 'phone', label: 'Phone' },
        { key: 'address', label: 'Address' },
    ];

    return (
        <CrudPage
            eyebrow="Administration"
            title="User Management"
            description="Branch staff accounts and role assignments."
            queryKey={['manager', 'users']}
            queryFn={(page) => api.users({ page, include_deleted: 1 })}
            columns={[
                { header: 'Name', cell: (user) => <span className="font-medium">{user.name}</span> },
                { header: 'Username', cell: (user) => user.username },
                { header: 'Role', cell: (user) => <Badge tone="brand">{user.role}</Badge> },
            ]}
            fields={fields}
            create={(values) =>
                api.admin.createUser({ ...values, branch_id: user?.branch?.id })
            }
            update={(id, values) =>
                api.admin.updateUser(id, { ...values, branch_id: user?.branch?.id })
            }
            remove={(id) => api.admin.deleteUser(id)}
            createLabel="New staff account"
        />
    );
}

export function ManagerMaterialsPage() {
    const categories = useQuery({ queryKey: ['categories'], queryFn: api.categories });
    const options = (categories.data?.data ?? []).map((category) => ({
        value: category.id,
        label: category.name,
    }));

    const fields: Array<CrudField<Material>> = [
        { key: 'name', label: 'Name', required: true },
        { key: 'product_category_id', label: 'Category', type: 'select', options, getValue: (material) => material.category?.id ?? '' },
        { key: 'type', label: 'Type' },
        { key: 'dimensions', label: 'Dimensions' },
        { key: 'cost', label: 'Cost', type: 'number' },
        { key: 'price', label: 'Price', type: 'number' },
        { key: 'initial_stock', label: 'Initial stock', type: 'number', hideOnEdit: true },
        { key: 'min_stock', label: 'Minimum stock', type: 'number', hideOnEdit: true },
    ];

    return (
        <CrudPage
            eyebrow="Inventory"
            title="Materials & Stock"
            description="Branch inventory levels, restocking, and activation."
            queryKey={['manager', 'materials']}
            queryFn={(page) => api.materials({ page })}
            columns={[
                { header: 'Material', cell: (material) => <span className="font-medium">{material.name}</span> },
                { header: 'Category', cell: (material) => material.category?.name ?? '-' },
                { header: 'Stock', cell: (material) => material.current_stock ?? '-' },
                { header: 'Minimum', cell: (material) => material.min_stock ?? '-' },
                { header: 'Cost', cell: (material) => formatMoney(material.cost) },
            ]}
            fields={fields}
            create={(values) => api.admin.createMaterial(values)}
            rowActions={(material) => <RestockMaterialButton material={material} />}
            createLabel="New material"
        />
    );
}

export function ManagerLedgersPage() {
    return (
        <ResourceTable
            eyebrow="Inventory"
            title="Stock Ledgers"
            description="Stock movement history and recent branch expenses."
            queryKey={['manager', 'ledgers']}
            queryFn={(page) => api.stockLedgers({ page })}
            columns={[
                { header: 'Date', cell: (ledger) => ledger.logged_at },
                { header: 'Material', cell: (ledger) => ledger.material?.name ?? '-' },
                { header: 'Added', cell: (ledger) => ledger.added_stock },
                { header: 'Used', cell: (ledger) => ledger.used_stock },
                { header: 'Wasted', cell: (ledger) => ledger.wasted_stock },
                { header: 'Remaining', cell: (ledger) => ledger.remaining_stock },
            ]}
        />
    );
}

export function ManagerMachinesPage() {
    const fields: Array<CrudField<Machine>> = [
        { key: 'name', label: 'Name', required: true },
        { key: 'cost', label: 'Base cost', type: 'number' },
    ];

    return (
        <CrudPage
            eyebrow="Configuration"
            title="Printer Machines"
            description="Machine records and base cost for your branch."
            queryKey={['manager', 'machines']}
            queryFn={(page) => api.machines({ page, include_deleted: 1 })}
            columns={[
                { header: 'Machine', cell: (machine) => <span className="font-medium">{machine.name}</span> },
                { header: 'Base cost', cell: (machine) => formatMoney(machine.cost) },
            ]}
            fields={fields}
            create={(values) => api.admin.createMachine(values)}
            update={(id, values) => api.admin.updateMachine(id, values)}
            remove={(id) => api.admin.deleteMachine(id)}
            createLabel="New machine"
        />
    );
}

export function ManagerCategoriesPage() {
    const fields: Array<CrudField<Category>> = [
        { key: 'name', label: 'Name', required: true },
        {
            key: 'calculation_archetype',
            label: 'Archetype',
            type: 'select',
            options: [
                { value: 'per_piece', label: 'Per piece' },
                { value: 'surface_area', label: 'Surface area' },
                { value: 'linear_dimension', label: 'Linear dimension' },
                { value: 'tiered_volume', label: 'Tiered volume' },
            ],
            getValue: (category) => category.calculation_archetype,
        },
        {
            key: 'allowed_attribute_types',
            label: 'Allowed attribute types (comma separated)',
            getValue: (category) => category.allowed_attribute_types.join(','),
        },
    ];

    return (
        <CrudPage
            eyebrow="Configuration"
            title="Product Categories"
            description="Category archetypes and allowed attribute types."
            queryKey={['manager', 'categories']}
            queryFn={() => api.categories().then((response) => collectionAsPage(response.data))}
            columns={[
                { header: 'Name', cell: (category) => <span className="font-medium">{category.name}</span> },
                { header: 'Archetype', cell: (category) => category.calculation_archetype },
            ]}
            fields={fields}
            create={(values) =>
                api.admin.createCategory({
                    ...values,
                    allowed_attribute_types: String(values.allowed_attribute_types ?? '')
                        .split(',')
                        .map((value) => value.trim())
                        .filter(Boolean),
                })
            }
            update={(id, values) =>
                api.admin.updateCategory(id, {
                    ...values,
                    allowed_attribute_types: String(values.allowed_attribute_types ?? '')
                        .split(',')
                        .map((value) => value.trim())
                        .filter(Boolean),
                })
            }
            remove={(id) => api.admin.deleteCategory(id)}
            createLabel="New category"
        />
    );
}

export function ManagerFinishingPage() {
    const typeFields: Array<CrudField<AttributeType>> = [
        { key: 'name', label: 'Name', required: true },
        {
            key: 'calculation_archetype',
            label: 'Archetype',
            type: 'select',
            options: [
                { value: 'per_piece', label: 'Per piece' },
                { value: 'surface_area', label: 'Surface area' },
                { value: 'linear_dimension', label: 'Linear dimension' },
                { value: 'tiered_volume', label: 'Tiered volume' },
            ],
        },
    ];

    const attributeFields: Array<CrudField<Attribute>> = [
        { key: 'name', label: 'Name', required: true },
        {
            key: 'type',
            label: 'Type',
            type: 'select',
            options: [
                { value: 'finishing', label: 'Finishing' },
                { value: 'cut', label: 'Cut' },
                { value: 'display', label: 'Display' },
            ],
        },
    ];

    return (
        <div className="space-y-8">
            <CrudPage
                eyebrow="Configuration"
                title="Display & Finishing"
                description="Manage finishing, cut, and display attributes."
                queryKey={['manager', 'attributes']}
                queryFn={() => api.attributes().then((response) => collectionAsPage(response.data))}
                columns={[
                    { header: 'Name', cell: (attribute) => <span className="font-medium">{attribute.name}</span> },
                    { header: 'Type', cell: (attribute) => attribute.type },
                ]}
                fields={attributeFields}
                create={(values) => api.admin.createAttribute(values)}
                update={(id, values) => api.admin.updateAttribute(id, values)}
                remove={(id) => api.admin.deleteAttribute(id)}
                createLabel="New attribute"
            />

            <CrudPage
                eyebrow="Configuration"
                title="Attribute Types"
                description="Types used by the pricing engine."
                queryKey={['manager', 'attribute-types']}
                queryFn={() => api.attributeTypes().then((response) => collectionAsPage(response.data))}
                columns={[
                    { header: 'Name', cell: (type) => <span className="font-medium">{type.name}</span> },
                    { header: 'Archetype', cell: (type) => type.calculation_archetype },
                ]}
                fields={typeFields}
                create={(values) => api.admin.createAttributeType(values)}
                remove={(id) => api.admin.deleteAttributeType(id)}
                createLabel="New attribute type"
            />
        </div>
    );
}

export function ManagerPricingPage() {
    const products = useQuery({ queryKey: ['products', 'options'], queryFn: () => api.products() });
    const materials = useQuery({ queryKey: ['materials', 'options'], queryFn: () => api.materials() });

    const productOptions = (products.data?.data ?? []).map((product) => ({
        value: product.id,
        label: product.name,
    }));
    const materialOptions = (materials.data?.data ?? []).map((material) => ({
        value: material.id,
        label: material.name,
    }));

    const fields: Array<CrudField<Pricing>> = [
        { key: 'product_id', label: 'Product', type: 'select', options: productOptions, getValue: (pricing) => pricing.product?.id ?? '' },
        { key: 'material_id', label: 'Material', type: 'select', options: materialOptions, getValue: (pricing) => pricing.material?.id ?? '' },
        {
            key: 'customer_type',
            label: 'Customer type',
            type: 'select',
            options: [
                { value: 'Reguler', label: 'Reguler' },
                { value: 'Reseller', label: 'Reseller' },
            ],
            getValue: (pricing) => pricing.customer_type,
        },
        { key: 'min_qty', label: 'Minimum quantity', type: 'number', defaultValue: 1, getValue: (pricing) => pricing.min_qty },
        { key: 'price', label: 'Sell price', type: 'number', getValue: (pricing) => pricing.price },
        { key: 'cost', label: 'Cost', type: 'number', getValue: (pricing) => pricing.cost },
    ];

    return (
        <div className="space-y-8">
            <CrudPage
                eyebrow="Configuration"
                title="Pricing Matrices"
                description="Tiered pricing rows and modifier rules."
                queryKey={['manager', 'pricing']}
                queryFn={(page) => api.pricing({ page })}
                columns={[
                    { header: 'Product', cell: (pricing) => pricing.product?.name ?? '-' },
                    { header: 'Material', cell: (pricing) => pricing.material?.name ?? '-' },
                    { header: 'Type', cell: (pricing) => pricing.customer_type },
                    { header: 'Min qty', cell: (pricing) => pricing.min_qty },
                    { header: 'Price', cell: (pricing) => formatMoney(pricing.price) },
                    { header: 'Cost', cell: (pricing) => formatMoney(pricing.cost) },
                ]}
                fields={fields}
                create={(values) => api.admin.createPricing(values)}
                update={(id, values) => api.admin.updatePricing(id, values)}
                remove={(id) => api.admin.deletePricing(id)}
                createLabel="New pricing row"
            />

            <PricingClonePanel />
        </div>
    );
}

function PricingClonePanel() {
    const queryClient = useQueryClient();
    const materials = useQuery({ queryKey: ['materials', 'clone'], queryFn: () => api.materials() });
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');
    const [notice, setNotice] = useState<string | null>(null);

    const clone = useMutation({
        mutationFn: () =>
            api.admin.clonePricing({ from_material_id: from, to_material_id: to }),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['manager', 'pricing'] });
            setNotice('Pricing tiers cloned.');
        },
        onError: () => setNotice('Unable to clone pricing tiers.'),
    });

    const options = materials.data?.data ?? [];

    return (
        <Card>
            <CardBody className="space-y-4">
                <PageHeader
                    eyebrow="Configuration"
                    title="Clone pricing tiers"
                    description="Copy every pricing row from one material to another in your branch."
                />
                <div className="flex flex-wrap items-end gap-3">
                    <Select label="From material" value={from} onChange={(event) => setFrom(event.target.value)}>
                        <option value="">Select</option>
                        {options.map((material) => (
                            <option key={material.id} value={material.id}>
                                {material.name}
                            </option>
                        ))}
                    </Select>
                    <Select label="To material" value={to} onChange={(event) => setTo(event.target.value)}>
                        <option value="">Select</option>
                        {options.map((material) => (
                            <option key={material.id} value={material.id}>
                                {material.name}
                            </option>
                        ))}
                    </Select>
                    <Button
                        loading={clone.isPending}
                        disabled={!from || !to || from === to}
                        onClick={() => clone.mutate()}
                    >
                        Clone tiers
                    </Button>
                </div>
                {notice ? <p className="text-sm">{notice}</p> : null}
            </CardBody>
        </Card>
    );
}

export function ManagerLeaderboardPage() {
    const query = useQuery({
        queryKey: ['manager', 'leaderboard'],
        queryFn: () => api.report.leaderboard(),
    });

    return (
        <div className="space-y-6">
            <PageHeader
                eyebrow="Overview"
                title="Designer Leaderboard"
                description="Current-month revenue, order count, and waste accuracy."
            />
            <Card>
                <CardBody>
                    <h2 className="font-semibold">Leaderboard</h2>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Designer</TableHeaderCell>
                                <TableHeaderCell>Orders</TableHeaderCell>
                                <TableHeaderCell>Revenue</TableHeaderCell>
                                <TableHeaderCell>Average order</TableHeaderCell>
                                <TableHeaderCell>Waste logs</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {(query.data?.leaders ?? []).length === 0 ? (
                                <TableEmpty colSpan={5}>No designer activity this month.</TableEmpty>
                            ) : (
                                (query.data?.leaders ?? []).map((leader) => (
                                    <TableRow key={leader.id}>
                                        <TableCell>{leader.name}</TableCell>
                                        <TableCell>{leader.order_count}</TableCell>
                                        <TableCell>{formatMoney(leader.revenue)}</TableCell>
                                        <TableCell>{formatMoney(leader.average_order_value)}</TableCell>
                                        <TableCell>{leader.waste_count}</TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardBody>
            </Card>
        </div>
    );
}
