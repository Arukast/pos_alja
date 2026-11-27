import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';

import { api } from '../../api/queries';
import type { Branch, Material, ProductQuantityDiscount, UserRecord } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { ActiveToggleButton } from '../../components/admin/ActiveToggleButton';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { RoleBadge } from '../../components/RoleBadge';
import { AttributesPage } from '../catalog/AttributesPage';
import { CategoriesPage } from '../catalog/CategoriesPage';
import { ProductsPage } from '../catalog/ProductsPage';
import { AdjustMaterialModal } from '../catalog/AdjustMaterialModal';
import { MaterialActiveToggle } from '../catalog/MaterialActiveToggle';
import { MinimumStockInline } from '../catalog/MinimumStockInline';
import { RestockMaterialButton } from '../catalog/RestockMaterialButton';
import { Badge } from '../../components/ui/Badge';
import { Card, CardBody } from '../../components/ui/Card';
import { Checkbox } from '../../components/ui/Checkbox';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { useBranches } from '../../hooks/useBranches';
import { errorMessage } from '../../lib/errors';
import { roleLabel } from '../../lib/labels';
import { formatMoney } from '../../lib/money';
import { quantityWithUnit, unitLabel, unitOptions } from '../../lib/units';

const branchOptions = (branches: Array<{ id: string; name: string; is_active: boolean }>) =>
    branches.map((branch) => ({
        value: branch.id,
        label: branch.is_active ? branch.name : `${branch.name} (nonaktif)`,
    }));

const roleOptions = ['owner', 'manager', 'cashier', 'designer', 'production'].map((role) => ({
    value: role,
    label: roleLabel(role),
}));

const unitLabelFor = (values: Record<string, unknown>) => unitLabel(String(values.unit ?? ''));

export function OwnerUsersPage() {
    const { user: authUser } = useAuth();
    const queryClient = useQueryClient();
    const branches = useBranches();
    const options = branchOptions(branches.data?.data ?? []);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [role, setRole] = useState('');
    const [notice, setNotice] = useState<{ message: string; tone: 'success' | 'error' } | null>(
        null
    );

    useEffect(() => {
        const timer = window.setTimeout(() => setSearch(searchInput.trim()), 300);

        return () => window.clearTimeout(timer);
    }, [searchInput]);

    const toggleStatus = useMutation({
        mutationFn: (id: string) => api.admin.toggleUser(id),
        onSuccess: (response) => {
            void queryClient.invalidateQueries({ queryKey: ['owner', 'users'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setNotice({
                message: `Akun ${response.user.name} ${response.user.is_active ? 'diaktifkan' : 'dinonaktifkan'}.`,
                tone: 'success',
            });
        },
        onError: (error) => setNotice({ message: errorMessage(error), tone: 'error' }),
    });

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
        <>
            <CrudPage
                pageResetKey={`${search}:${role}`}
                eyebrow="Pemilik"
                title="Manajemen Pengguna"
                description="Akun staf, peran, dan penugasan cabang untuk semua cabang."
                queryKey={['owner', 'users', search, role]}
                queryFn={(page) => api.users({ page, search, role })}
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
                    {
                        header: 'Nama',
                        cell: (user) => (
                            <div>
                                <span className="block font-medium break-words">{user.name}</span>
                                <span className="mt-1 block md:hidden">
                                    <span className="sr-only">Peran: </span>
                                    <RoleBadge role={user.role} />
                                </span>
                                {user.id === authUser?.id ? (
                                    <span className="text-light-muted mt-1 block text-xs dark:text-gray-400">
                                        Akun Anda — kelola dari Profil.
                                    </span>
                                ) : null}
                            </div>
                        ),
                    },
                    {
                        header: 'Nama Pengguna',
                        className: 'hidden md:table-cell',
                        cell: (user) => (
                            <span className="block max-w-[12rem] truncate" title={user.username}>
                                {user.username}
                            </span>
                        ),
                    },
                    {
                        header: 'Peran',
                        className: 'hidden md:table-cell',
                        cell: (user) => <RoleBadge role={user.role} />,
                    },
                    {
                        header: 'Status',
                        cell: (user) => (
                            <Badge tone={user.is_active ? 'success' : 'neutral'}>
                                {user.is_active ? 'Aktif' : 'Nonaktif'}
                            </Badge>
                        ),
                    },
                    {
                        header: 'Cabang',
                        className: 'hidden md:table-cell',
                        cell: (user) => (
                            <span
                                className="block max-w-[12rem] truncate"
                                title={user.branch?.name ?? 'Global'}
                            >
                                {user.branch?.name ?? 'Global'}
                            </span>
                        ),
                    },
                ]}
                fields={fields}
                rowLabel={(user) => user.name}
                create={(values) => api.admin.createUser(values)}
                update={(id, values) => api.admin.updateUser(id, values)}
                rowActions={(user) =>
                    user.id === authUser?.id ? null : (
                        <ActiveToggleButton
                            active={user.is_active}
                            pending={toggleStatus.isPending && toggleStatus.variables === user.id}
                            confirmMessage={`Nonaktifkan ${user.name}? Pengguna akan langsung kehilangan akses pada permintaan berikutnya dan tidak dapat masuk sampai diaktifkan kembali.`}
                            ariaLabel={`${user.is_active ? 'Nonaktifkan' : 'Aktifkan'} ${user.name}`}
                            onClick={() => toggleStatus.mutate(user.id)}
                        />
                    )
                }
                createLabel="Tambah pengguna"
                notice={notice}
                onNoticeChange={setNotice}
            />
        </>
    );
}

export function BranchesPage() {
    const queryClient = useQueryClient();
    const [notice, setNotice] = useState<{ message: string; tone: 'success' | 'error' } | null>(
        null
    );

    const toggleBranch = useMutation({
        mutationFn: (id: string) => api.admin.toggleBranch(id),
        onSuccess: (response) => {
            void queryClient.invalidateQueries({ queryKey: ['owner', 'branches'] });
            void queryClient.invalidateQueries({ queryKey: ['branches', 'selector'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setNotice({
                message: `${response.branch.name} ${response.branch.is_active ? 'diaktifkan' : 'dinonaktifkan'}.`,
                tone: 'success',
            });
        },
        onError: (error) => setNotice({ message: errorMessage(error), tone: 'error' }),
    });

    const fields: Array<CrudField<Branch>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'code', label: 'Kode' },
        { key: 'phone', label: 'Telepon' },
        { key: 'address', label: 'Alamat' },
        {
            key: 'reseller_discount_percent',
            label: 'Diskon Reseller (%)',
            type: 'number',
            defaultValue: 0,
            getValue: (branch) => branch.reseller_discount_percent,
        },
    ];

    return (
        <>
            <CrudPage
                eyebrow="Administrasi"
                title="Manajemen Cabang"
                description="Data cabang, detail kontak, diskon reseller, dan status aktif."
                queryKey={['owner', 'branches']}
                queryFn={(page) => api.branches({ page })}
                columns={[
                    {
                        header: 'Nama',
                        cell: (branch) => (
                            <span className="block font-medium break-words">{branch.name}</span>
                        ),
                    },
                    { header: 'Kode', className: 'hidden md:table-cell', cell: (branch) => branch.code ?? '-' },
                    { header: 'Telepon', className: 'hidden md:table-cell', cell: (branch) => branch.phone ?? '-' },
                    {
                        header: 'Diskon Reseller',
                        className: 'hidden text-right tabular-nums whitespace-nowrap md:table-cell',
                        cell: (branch) => `${Number(branch.reseller_discount_percent)}%`,
                    },
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
                rowLabel={(branch) => branch.name}
                create={(values) =>
                    api.admin.createBranch({
                        ...values,
                        reseller_discount_percent:
                            values.reseller_discount_percent === ''
                                ? 0
                                : Number(values.reseller_discount_percent),
                    })
                }
                update={(id, values) =>
                    api.admin.updateBranch(id, {
                        ...values,
                        reseller_discount_percent:
                            values.reseller_discount_percent === ''
                                ? 0
                                : Number(values.reseller_discount_percent),
                    })
                }
                rowActions={(branch) => (
                    <ActiveToggleButton
                        active={branch.is_active}
                        pending={toggleBranch.isPending && toggleBranch.variables === branch.id}
                        confirmMessage={`Nonaktifkan cabang ${branch.name}? Semua staf cabang ini akan langsung kehilangan akses pada permintaan berikutnya sampai cabang diaktifkan kembali.`}
                        ariaLabel={`${branch.is_active ? 'Nonaktifkan' : 'Aktifkan'} ${branch.name}`}
                        onClick={() => toggleBranch.mutate(branch.id)}
                    />
                )}
                createLabel="Tambah cabang"
                notice={notice}
                onNoticeChange={setNotice}
            />
        </>
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
        { key: 'unit', label: 'Satuan', type: 'select', options: unitOptions, defaultValue: 'pcs' },
        {
            key: 'cost',
            label: 'Biaya',
            type: 'number',
            labelFor: (values) => `Biaya per ${unitLabelFor(values)}`,
        },
        {
            key: 'price',
            label: 'Harga',
            type: 'number',
            labelFor: (values) => `Harga per ${unitLabelFor(values)}`,
        },
        { key: 'initial_stock', label: 'Stok awal', type: 'number', hideOnEdit: true },
        { key: 'min_stock', label: 'Stok minimum', type: 'number', hideOnEdit: true },
    ];

    const renderStocks = (material: Material) =>
        material.stocks.length === 0 ? (
            '-'
        ) : (
            <ul className="min-w-[9rem] space-y-2">
                {material.stocks.map((stock) => (
                    <li key={stock.branch_id}>
                        <span className="block font-medium">
                            {stock.branch_name ?? '-'}
                        </span>
                        <span className="block tabular-nums">
                            {quantityWithUnit(stock.current_stock, material.unit)}
                        </span>
                        <div className="mt-1 flex flex-wrap items-center gap-2">
                            <Badge tone={stock.is_active ? 'success' : 'danger'}>
                                {stock.is_active ? 'Aktif' : 'Nonaktif'}
                            </Badge>
                            <MaterialActiveToggle
                                scope="branch"
                                materialId={material.id}
                                isActive={stock.is_active}
                                branchId={stock.branch_id}
                                branchName={stock.branch_name ?? 'cabang'}
                                materialName={material.name}
                            />
                        </div>
                        <div className="text-light-muted flex flex-wrap items-center gap-1 text-xs dark:text-gray-400">
                            <span>min</span>
                            <MinimumStockInline
                                materialId={material.id}
                                branchId={stock.branch_id}
                                label={stock.branch_name ?? 'cabang'}
                                value={stock.min_stock}
                                unit={material.unit}
                            />
                        </div>
                        {stock.is_low_stock ? (
                            <span className="block font-semibold text-amber-700 dark:text-amber-300">
                                menipis
                            </span>
                        ) : null}
                    </li>
                ))}
            </ul>
        );

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
                        <span
                            className="block font-medium break-words"
                            title={material.name}
                        >
                            {material.name}
                        </span>
                        <Badge
                            tone={material.is_active ? 'success' : 'danger'}
                            className="mt-1"
                        >
                            {material.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                        {material.category && !material.category.is_active ? (
                            <Badge tone="warning" className="mt-1">
                                Kategori nonaktif
                            </Badge>
                        ) : null}
                        <p className="text-light-muted text-xs dark:text-gray-400">
                            {[material.type, material.dimensions, unitLabel(material.unit)]
                                .filter(Boolean)
                                .join(' · ')}
                        </p>
                        <div className="mt-2 sm:hidden">{renderStocks(material)}</div>
                    </div>
                ) },
                { header: 'Kategori', className: 'hidden sm:table-cell', cell: (material) => material.category?.name ?? '-' },
                {
                    header: 'Stok per cabang',
                    className: 'hidden sm:table-cell',
                    cell: (material) => renderStocks(material),
                },
                { header: 'Biaya', className: 'hidden text-right tabular-nums whitespace-nowrap lg:table-cell', cell: (material) => formatMoney(material.cost) },
                { header: 'Harga', className: 'hidden text-right tabular-nums whitespace-nowrap lg:table-cell', cell: (material) => formatMoney(material.price) },
            ]}
            fields={fields}
            rowLabel={(material) => material.name}
            create={(values) => api.admin.createMaterial(values)}
            update={(id, values) => api.admin.updateMaterial(id, values)}
            rowActions={(material) => (
                <>
                    <MaterialActiveToggle
                        scope="global"
                        materialId={material.id}
                        isActive={material.is_active}
                        materialName={material.name}
                    />
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

export function OwnerAttributesPage() {
    return <AttributesPage eyebrow="Administrasi" queryKeyPrefix="owner" />;
}

export function QuantityTiersPage({
    eyebrow,
    queryKeyPrefix,
    readOnly = false,
}: {
    eyebrow: string;
    queryKeyPrefix: string;
    readOnly?: boolean;
}) {
    const products = useQuery({
        queryKey: ['products', 'options'],
        queryFn: () => api.products({ is_active: 1 }),
    });
    const productOptions = (products.data?.data ?? []).map((product) => ({
        value: product.id,
        label: product.name,
    }));

    const fields: Array<CrudField<ProductQuantityDiscount>> = [
        {
            key: 'product_id',
            label: 'Produk',
            type: 'select',
            options: productOptions,
            getValue: (tier) => tier.product?.id ?? tier.product_id,
            hideOnEdit: true,
            required: true,
        },
        { key: 'min_qty', label: 'Jml min', type: 'number', defaultValue: 1, required: true },
        { key: 'discount_percent', label: 'Diskon (%)', type: 'number', defaultValue: 0, required: true },
    ];

    return (
        <CrudPage<ProductQuantityDiscount>
            eyebrow={eyebrow}
            title="Tier Jumlah"
            description="Diskon otomatis berdasarkan jumlah cetak untuk setiap produk."
            queryKey={[queryKeyPrefix, 'quantity-tiers']}
            queryFn={(page) => api.productQuantityDiscounts({ page })}
            columns={[
                { header: 'Produk', cell: (tier) => tier.product?.name ?? '-' },
                {
                    header: 'Jml min',
                    className: 'text-right tabular-nums whitespace-nowrap',
                    cell: (tier) => tier.min_qty,
                },
                {
                    header: 'Diskon',
                    className: 'text-right tabular-nums whitespace-nowrap',
                    cell: (tier) => `${Number(tier.discount_percent)}%`,
                },
            ]}
            fields={fields}
            rowLabel={(tier) => tier.product?.name ?? 'Tier'}
            create={
                readOnly
                    ? undefined
                    : (values) =>
                          api.admin.createProductQuantityDiscount({
                              product_id: String(values.product_id),
                              min_qty: Number(values.min_qty),
                              discount_percent: Number(values.discount_percent),
                          })
            }
            update={
                readOnly
                    ? undefined
                    : (id, values) =>
                          api.admin.updateProductQuantityDiscount(id, {
                              min_qty: Number(values.min_qty),
                              discount_percent: Number(values.discount_percent),
                          })
            }
            remove={readOnly ? undefined : (id) => api.admin.deleteProductQuantityDiscount(id)}
            createLabel="Tambah tier"
        />
    );
}

export function OwnerQuantityTiersPage() {
    return <QuantityTiersPage eyebrow="Administrasi" queryKeyPrefix="owner" />;
}
