import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import type { Branch, Category, Machine, Material, UserRecord } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { Badge } from '../../components/ui/Badge';
import { useBranches } from '../../hooks/useBranches';
import { collectionAsPage } from '../../lib/pagination';

const branchOptions = (branches: Array<{ id: string; name: string }>) =>
    branches.map((branch) => ({ value: branch.id, label: branch.name }));

export function OwnerUsersPage() {
    const branches = useBranches();
    const options = branchOptions(branches.data?.data ?? []);

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
                { value: 'owner', label: 'Owner' },
                { value: 'manager', label: 'Manager' },
                { value: 'cashier', label: 'Cashier' },
                { value: 'designer', label: 'Designer' },
                { value: 'production', label: 'Production' },
            ],
        },
        { key: 'branch_id', label: 'Branch', type: 'select', options, getValue: (user) => user.branch?.id ?? '' },
        { key: 'phone', label: 'Phone' },
        { key: 'address', label: 'Address' },
    ];

    return (
        <CrudPage
            eyebrow="Administration"
            title="User Management"
            description="Owner-wide staff accounts, roles, and branch assignments."
            queryKey={['owner', 'users']}
            queryFn={(page) => api.users({ page, include_deleted: 1 })}
            columns={[
                { header: 'Name', cell: (user) => <span className="font-medium">{user.name}</span> },
                { header: 'Username', cell: (user) => user.username },
                { header: 'Role', cell: (user) => <Badge tone="brand">{user.role}</Badge> },
                { header: 'Branch', cell: (user) => user.branch?.name ?? 'Global' },
            ]}
            fields={fields}
            create={(values) => api.admin.createUser(values)}
            update={(id, values) => api.admin.updateUser(id, values)}
            remove={(id) => api.admin.deleteUser(id)}
            createLabel="New user"
        />
    );
}

export function BranchesPage() {
    const fields: Array<CrudField<Branch>> = [
        { key: 'name', label: 'Name', required: true },
        { key: 'code', label: 'Code' },
        { key: 'phone', label: 'Phone' },
        { key: 'address', label: 'Address' },
        { key: 'is_active', label: 'Active', type: 'checkbox', defaultValue: true },
    ];

    return (
        <CrudPage
            eyebrow="Administration"
            title="Branch Management"
            description="Branch records, contact details, and active status."
            queryKey={['owner', 'branches']}
            queryFn={(page) => api.branches({ page, include_deleted: 1 })}
            columns={[
                { header: 'Name', cell: (branch) => <span className="font-medium">{branch.name}</span> },
                { header: 'Code', cell: (branch) => branch.code ?? '-' },
                { header: 'Phone', cell: (branch) => branch.phone ?? '-' },
                {
                    header: 'Status',
                    cell: (branch) => (
                        <Badge tone={branch.is_active ? 'success' : 'neutral'}>
                            {branch.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    ),
                },
            ]}
            fields={fields}
            create={(values) => api.admin.createBranch(values)}
            update={(id, values) => api.admin.updateBranch(id, values)}
            remove={(id) => api.admin.deleteBranch(id)}
            createLabel="New branch"
        />
    );
}

export function OwnerMaterialsPage() {
    const categories = useQuery({ queryKey: ['categories'], queryFn: api.categories });
    const categoryOptions = (categories.data?.data ?? []).map((category) => ({
        value: category.id,
        label: category.name,
    }));

    const fields: Array<CrudField<Material>> = [
        { key: 'name', label: 'Name', required: true },
        {
            key: 'product_category_id',
            label: 'Category',
            type: 'select',
            options: categoryOptions,
            getValue: (material) => material.category?.id ?? '',
        },
        { key: 'type', label: 'Type' },
        { key: 'dimensions', label: 'Dimensions' },
        { key: 'cost', label: 'Cost', type: 'number' },
        { key: 'price', label: 'Price', type: 'number' },
        { key: 'initial_stock', label: 'Initial stock', type: 'number', hideOnEdit: true },
        { key: 'min_stock', label: 'Minimum stock', type: 'number', hideOnEdit: true },
    ];

    return (
        <CrudPage
            eyebrow="Administration"
            title="Materials & Stock"
            description="Global materials, branch stock levels, and pricing."
            queryKey={['owner', 'materials']}
            queryFn={(page) => api.materials({ page, branch_id: 'all' })}
            columns={[
                { header: 'Material', cell: (material) => <span className="font-medium">{material.name}</span> },
                { header: 'Category', cell: (material) => material.category?.name ?? '-' },
                {
                    header: 'Stock by branch',
                    cell: (material) =>
                        material.stocks.length === 0
                            ? '-'
                            : material.stocks
                                  .map((stock) => `${stock.branch_name ?? '-'}: ${stock.current_stock}`)
                                  .join(', '),
                },
                { header: 'Cost', cell: (material) => material.cost },
                { header: 'Price', cell: (material) => material.price },
            ]}
            fields={fields}
            create={(values) => api.admin.createMaterial(values)}
            update={(id, values) => api.admin.updateMaterial(id, values)}
            remove={(id) => api.admin.deleteMaterial(id)}
            createLabel="New material"
        />
    );
}

export function OwnerCategoriesPage() {
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
            eyebrow="Administration"
            title="Product Categories"
            description="Category archetypes and allowed attribute types."
            queryKey={['owner', 'categories']}
            queryFn={() => api.categories().then((response) => collectionAsPage(response.data))}
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

export function OwnerMachinesPage() {
    const branches = useBranches();
    const options = branchOptions(branches.data?.data ?? []);

    const fields: Array<CrudField<Machine>> = [
        { key: 'name', label: 'Name', required: true },
        { key: 'branch_id', label: 'Branch', type: 'select', options, getValue: (machine) => machine.branch?.id ?? '' },
        { key: 'cost', label: 'Base cost', type: 'number' },
    ];

    return (
        <CrudPage
            eyebrow="Administration"
            title="Printer Machines"
            description="Machine records and base cost."
            queryKey={['owner', 'machines']}
            queryFn={(page) => api.machines({ page, include_deleted: 1 })}
            columns={[
                { header: 'Machine', cell: (machine) => <span className="font-medium">{machine.name}</span> },
                { header: 'Branch', cell: (machine) => machine.branch?.name ?? 'Global' },
                { header: 'Base cost', cell: (machine) => machine.cost },
            ]}
            fields={fields}
            create={(values) => api.admin.createMachine(values)}
            update={(id, values) => api.admin.updateMachine(id, values)}
            remove={(id) => api.admin.deleteMachine(id)}
            createLabel="New machine"
        />
    );
}
