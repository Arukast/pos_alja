import { useQuery } from '@tanstack/react-query';
import { useEffect, useState } from 'react';

import { api } from '../../api/queries';
import type { Branch, Material, UserRecord } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { CategoriesPage } from '../catalog/CategoriesPage';
import { ProductsPage } from '../catalog/ProductsPage';
import { AdjustMaterialModal } from '../catalog/AdjustMaterialModal';
import { RestockMaterialButton } from '../catalog/RestockMaterialButton';
import { Badge } from '../../components/ui/Badge';
import { Card, CardBody } from '../../components/ui/Card';
import { Checkbox } from '../../components/ui/Checkbox';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { useBranches } from '../../hooks/useBranches';
import { roleLabel } from '../../lib/labels';
import { formatMoney } from '../../lib/money';
import { quantityWithUnit, unitLabel } from '../../lib/units';

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
    const [lowStock, setLowStock] = useState(false);
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
            pageResetKey={lowStock}
            queryKey={['owner', 'materials', lowStock]}
            queryFn={(page) =>
                api.materials({
                    page,
                    branch_id: 'all',
                    low_stock: lowStock ? 1 : undefined,
                })
            }
            toolbar={
                <Card>
                    <CardBody>
                        <Checkbox
                            label="Hanya stok minimum"
                            checked={lowStock}
                            onChange={(event) => setLowStock(event.target.checked)}
                        />
                    </CardBody>
                </Card>
            }
            columns={[
                { header: 'Bahan', cell: (material) => (
                    <div>
                        <span className="font-medium">{material.name}</span>
                        <p className="text-light-muted text-xs dark:text-gray-400">
                            {[material.type, material.dimensions, unitLabel(material.unit)]
                                .filter(Boolean)
                                .join(' · ')}
                        </p>
                    </div>
                ) },
                { header: 'Kategori', cell: (material) => material.category?.name ?? '-' },
                {
                    header: 'Stok per cabang',
                    cell: (material) =>
                        material.stocks.length === 0 ? (
                            '-'
                        ) : (
                            <ul className="flex flex-wrap gap-x-3 gap-y-1">
                                {material.stocks.map((stock) => (
                                    <li key={stock.branch_id}>
                                        <span>
                                            {stock.branch_name ?? '-'}:{' '}
                                            {quantityWithUnit(stock.current_stock, material.unit)} /{' '}
                                            {quantityWithUnit(stock.min_stock, material.unit)}
                                        </span>
                                        {stock.is_low_stock ? (
                                            <span className="ml-1 font-semibold text-amber-700 dark:text-amber-300">
                                                menipis
                                            </span>
                                        ) : null}
                                    </li>
                                ))}
                            </ul>
                        ),
                },
                { header: 'Biaya', className: 'text-right tabular-nums whitespace-nowrap', cell: (material) => formatMoney(material.cost) },
                { header: 'Harga', className: 'text-right tabular-nums whitespace-nowrap', cell: (material) => formatMoney(material.price) },
            ]}
            fields={fields}
            create={(values) => api.admin.createMaterial(values)}
            update={(id, values) => api.admin.updateMaterial(id, values)}
            remove={(id) => api.admin.deleteMaterial(id)}
            rowActions={(material) => (
                <>
                    <RestockMaterialButton material={material} />
                    <AdjustMaterialModal material={material} />
                </>
            )}
            createLabel="Tambah bahan"
        />
    );
}

export function OwnerCategoriesPage() {
    return <CategoriesPage eyebrow="Administrasi" queryKeyPrefix="owner" />;
}

export function OwnerProductsPage() {
    return <ProductsPage eyebrow="Administrasi" queryKeyPrefix="owner" />;
}
