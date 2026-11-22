import { useQuery } from '@tanstack/react-query';
import { useEffect, useState } from 'react';

import { api } from '../../api/queries';
import type { Branch, Category, Machine, Material, UserRecord } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { RestockMaterialButton } from '../catalog/RestockMaterialButton';
import { Badge } from '../../components/ui/Badge';
import { Card, CardBody } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { useBranches } from '../../hooks/useBranches';
import { collectionAsPage } from '../../lib/pagination';
import { archetypeLabel, roleLabel } from '../../lib/labels';
import { formatMoney } from '../../lib/money';

const branchOptions = (branches: Array<{ id: string; name: string }>) =>
    branches.map((branch) => ({ value: branch.id, label: branch.name }));

const roleOptions = ['owner', 'manager', 'cashier', 'designer', 'production'].map((role) => ({
    value: role,
    label: roleLabel(role),
}));

export function OwnerUsersPage() {
    const branches = useBranches();
    const options = branchOptions(branches.data?.data ?? []);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [role, setRole] = useState('');

    useEffect(() => {
        const timer = window.setTimeout(() => setSearch(searchInput.trim()), 300);

        return () => window.clearTimeout(timer);
    }, [searchInput]);

    const fields: Array<CrudField<UserRecord>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'username', label: 'Nama Pengguna', required: true },
        { key: 'email', label: 'Email', type: 'email' },
        { key: 'password', label: 'Kata Sandi', type: 'password', hideOnEdit: true },
        { key: 'password_confirmation', label: 'Konfirmasi Kata Sandi', type: 'password', hideOnEdit: true },
        { key: 'role', label: 'Peran', type: 'select', options: roleOptions },
        { key: 'branch_id', label: 'Cabang', type: 'select', options, getValue: (user) => user.branch?.id ?? '' },
        { key: 'phone', label: 'Telepon' },
        { key: 'address', label: 'Alamat' },
    ];

    return (
        <CrudPage
            pageResetKey={`${search}:${role}`}
            eyebrow="Pemilik"
            title="Manajemen Pengguna"
            description="Akun staf, peran, dan penugasan cabang untuk semua cabang."
            queryKey={['owner', 'users', search, role]}
            queryFn={(page) =>
                api.users({ page, include_deleted: 1, search, role })
            }
            summary={
                <Card>
                    <CardBody>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div className="w-full sm:max-w-xs">
                                <Input
                                    label="Cari pengguna"
                                    type="search"
                                    placeholder="Nama atau nama pengguna"
                                    value={searchInput}
                                    onChange={(event) => setSearchInput(event.target.value)}
                                />
                            </div>
                            <div className="w-full sm:w-48">
                                <Select
                                    label="Peran"
                                    value={role}
                                    onChange={(event) => setRole(event.target.value)}
                                >
                                    <option value="">Semua peran</option>
                                    {roleOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                        </div>
                    </CardBody>
                </Card>
            }
            columns={[
                { header: 'Nama', cell: (user) => <span className="font-medium">{user.name}</span> },
                { header: 'Nama Pengguna', className: 'hidden sm:table-cell', cell: (user) => user.username },
                { header: 'Peran', cell: (user) => <Badge tone="neutral">{roleLabel(user.role)}</Badge> },
                { header: 'Cabang', className: 'hidden sm:table-cell', cell: (user) => user.branch?.name ?? 'Global' },
            ]}
            fields={fields}
            create={(values) => api.admin.createUser(values)}
            update={(id, values) => api.admin.updateUser(id, values)}
            remove={(id) => api.admin.deleteUser(id)}
            createLabel="Tambah pengguna"
        />
    );
}

export function BranchesPage() {
    const fields: Array<CrudField<Branch>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'code', label: 'Kode' },
        { key: 'phone', label: 'Telepon' },
        { key: 'address', label: 'Alamat' },
        { key: 'is_active', label: 'Aktif', type: 'checkbox', defaultValue: true },
    ];

    return (
        <CrudPage
            eyebrow="Administrasi"
            title="Manajemen Cabang"
            description="Data cabang, detail kontak, dan status aktif."
            queryKey={['owner', 'branches']}
            queryFn={(page) => api.branches({ page, include_deleted: 1 })}
            columns={[
                { header: 'Nama', cell: (branch) => <span className="font-medium">{branch.name}</span> },
                { header: 'Kode', cell: (branch) => branch.code ?? '-' },
                { header: 'Telepon', cell: (branch) => branch.phone ?? '-' },
                {
                    header: 'Status',
                    cell: (branch) => (
                        <Badge tone={branch.is_active ? 'success' : 'neutral'}>
                            {branch.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                    ),
                },
            ]}
            fields={fields}
            create={(values) => api.admin.createBranch(values)}
            update={(id, values) => api.admin.updateBranch(id, values)}
            remove={(id) => api.admin.deleteBranch(id)}
            createLabel="Tambah cabang"
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
        { key: 'name', label: 'Nama', required: true },
        {
            key: 'product_category_id',
            label: 'Kategori',
            type: 'select',
            options: categoryOptions,
            getValue: (material) => material.category?.id ?? '',
        },
        { key: 'type', label: 'Jenis' },
        { key: 'dimensions', label: 'Dimensi' },
        { key: 'cost', label: 'Biaya', type: 'number' },
        { key: 'price', label: 'Harga', type: 'number' },
        { key: 'initial_stock', label: 'Stok awal', type: 'number', hideOnEdit: true },
        { key: 'min_stock', label: 'Stok minimum', type: 'number', hideOnEdit: true },
    ];

    return (
        <CrudPage
            eyebrow="Administrasi"
            title="Bahan & Stok"
            description="Bahan global, stok per cabang, dan harga."
            queryKey={['owner', 'materials']}
            queryFn={(page) => api.materials({ page, branch_id: 'all' })}
            columns={[
                { header: 'Bahan', cell: (material) => <span className="font-medium">{material.name}</span> },
                { header: 'Kategori', cell: (material) => material.category?.name ?? '-' },
                {
                    header: 'Stok per cabang',
                    cell: (material) =>
                        material.stocks.length === 0
                            ? '-'
                            : material.stocks
                                  .map((stock) => `${stock.branch_name ?? '-'}: ${stock.current_stock}`)
                                  .join(', '),
                },
                { header: 'Biaya', className: 'text-right tabular-nums whitespace-nowrap', cell: (material) => formatMoney(material.cost) },
                { header: 'Harga', className: 'text-right tabular-nums whitespace-nowrap', cell: (material) => formatMoney(material.price) },
            ]}
            fields={fields}
            create={(values) => api.admin.createMaterial(values)}
            update={(id, values) => api.admin.updateMaterial(id, values)}
            remove={(id) => api.admin.deleteMaterial(id)}
            rowActions={(material) => <RestockMaterialButton material={material} />}
            createLabel="Tambah bahan"
        />
    );
}

export function OwnerCategoriesPage() {
    const fields: Array<CrudField<Category>> = [
        { key: 'name', label: 'Nama', required: true },
        {
            key: 'calculation_archetype',
            label: 'Arketipe',
            type: 'select',
            options: [
                { value: 'per_piece', label: archetypeLabel('per_piece') },
                { value: 'surface_area', label: archetypeLabel('surface_area') },
                { value: 'linear_dimension', label: archetypeLabel('linear_dimension') },
                { value: 'tiered_volume', label: archetypeLabel('tiered_volume') },
            ],
            getValue: (category) => category.calculation_archetype,
        },
        {
            key: 'allowed_attribute_types',
            label: 'Jenis atribut yang diizinkan (pisahkan dengan koma)',
            getValue: (category) => category.allowed_attribute_types.join(','),
        },
    ];

    return (
        <CrudPage
            eyebrow="Administrasi"
            title="Kategori Produk"
            description="Arketipe kategori dan jenis atribut yang diizinkan."
            queryKey={['owner', 'categories']}
            queryFn={() => api.categories().then((response) => collectionAsPage(response.data))}
            columns={[
                { header: 'Nama', cell: (category) => <span className="font-medium">{category.name}</span> },
                { header: 'Arketipe', cell: (category) => archetypeLabel(category.calculation_archetype) },
                {
                    header: 'Atribut yang diizinkan',
                    cell: (category) =>
                        category.allowed_attribute_types.length === 0
                            ? 'Semua'
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
            createLabel="Tambah kategori"
            paginated={false}
        />
    );
}

export function OwnerMachinesPage() {
    const branches = useBranches();
    const options = branchOptions(branches.data?.data ?? []);

    const fields: Array<CrudField<Machine>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'branch_id', label: 'Cabang', type: 'select', options, getValue: (machine) => machine.branch?.id ?? '' },
        { key: 'cost', label: 'Biaya Dasar', type: 'number' },
    ];

    return (
        <CrudPage
            eyebrow="Administrasi"
            title="Mesin Cetak"
            description="Data mesin dan biaya dasar."
            queryKey={['owner', 'machines']}
            queryFn={(page) => api.machines({ page, include_deleted: 1 })}
            columns={[
                { header: 'Mesin', cell: (machine) => <span className="font-medium">{machine.name}</span> },
                { header: 'Cabang', cell: (machine) => machine.branch?.name ?? 'Global' },
                { header: 'Biaya Dasar', className: 'text-right tabular-nums whitespace-nowrap', cell: (machine) => formatMoney(machine.cost) },
            ]}
            fields={fields}
            create={(values) => api.admin.createMachine(values)}
            update={(id, values) => api.admin.updateMachine(id, values)}
            remove={(id) => api.admin.deleteMachine(id)}
            createLabel="Tambah mesin"
        />
    );
}
