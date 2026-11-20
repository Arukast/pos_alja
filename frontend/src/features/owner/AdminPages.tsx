import { api } from '../../api/queries';
import { CollectionTable } from '../../components/CollectionTable';
import { ResourceTable } from '../../components/ResourceTable';
import { Badge } from '../../components/ui/Badge';

export function OwnerUsersPage() {
    return (
        <ResourceTable
            eyebrow="Administration"
            title="User Management"
            description="Owner-wide staff accounts, roles, and branch assignments."
            queryKey={['owner', 'users']}
            queryFn={(page) => api.users({ page, include_deleted: 1 })}
            columns={[
                { header: 'Name', cell: (user) => <span className="font-medium">{user.name}</span> },
                { header: 'Username', cell: (user) => user.username },
                {
                    header: 'Role',
                    cell: (user) => <Badge tone="brand">{user.role}</Badge>,
                },
                { header: 'Branch', cell: (user) => user.branch?.name ?? 'Global' },
                { header: 'Email', cell: (user) => user.email ?? '-' },
            ]}
        />
    );
}

export function BranchesPage() {
    return (
        <ResourceTable
            eyebrow="Administration"
            title="Branch Management"
            description="Branch records, contact details, and active status."
            queryKey={['owner', 'branches']}
            queryFn={(page) => api.branches({ page, include_deleted: 1 })}
            columns={[
                { header: 'Name', cell: (branch) => <span className="font-medium">{branch.name}</span> },
                { header: 'Code', cell: (branch) => branch.code ?? '-' },
                { header: 'Phone', cell: (branch) => branch.phone ?? '-' },
                { header: 'Address', cell: (branch) => branch.address ?? '-' },
                {
                    header: 'Status',
                    cell: (branch) => (
                        <Badge tone={branch.is_active ? 'success' : 'neutral'}>
                            {branch.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                    ),
                },
            ]}
        />
    );
}

export function OwnerMaterialsPage() {
    return (
        <ResourceTable
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
        />
    );
}

export function OwnerCategoriesPage() {
    return (
        <CollectionTable
            eyebrow="Administration"
            title="Product Categories"
            description="Category archetypes and allowed attribute types."
            queryKey={['owner', 'categories']}
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

export function OwnerMachinesPage() {
    return (
        <ResourceTable
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
        />
    );
}
