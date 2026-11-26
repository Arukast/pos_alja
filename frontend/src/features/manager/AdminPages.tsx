import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Material, Pricing, UserRecord } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { PageBanner } from '../../components/PageBanner';
import { ResourceTable } from '../../components/ResourceTable';
import { AttributesPage } from '../catalog/AttributesPage';
import { CategoriesPage } from '../catalog/CategoriesPage';
import { ProductsPage } from '../catalog/ProductsPage';
import { AdjustMaterialModal } from '../catalog/AdjustMaterialModal';
import { RestockMaterialButton } from '../catalog/RestockMaterialButton';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Checkbox } from '../../components/ui/Checkbox';
import { EmptyState } from '../../components/ui/EmptyState';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { ledgerTypeLabel, roleLabel } from '../../lib/labels';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';
import { ledgerQuantity, quantityWithUnit, unitLabel, unitOptions } from '../../lib/units';

const unitLabelFor = (values: Record<string, unknown>) => unitLabel(String(values.unit ?? ''));

export function ManagerUsersPage() {
    const { user } = useAuth();
    const fields: Array<CrudField<UserRecord>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'username', label: 'Nama Pengguna', required: true },
        { key: 'email', label: 'Email', type: 'email' },
        { key: 'password', label: 'Kata Sandi', type: 'password', hideOnEdit: true },
        { key: 'password_confirmation', label: 'Konfirmasi Kata Sandi', type: 'password', hideOnEdit: true },
        {
            key: 'role',
            label: 'Peran',
            type: 'select',
            options: [
                { value: 'cashier', label: roleLabel('cashier') },
                { value: 'designer', label: roleLabel('designer') },
                { value: 'production', label: roleLabel('production') },
            ],
        },
        { key: 'phone', label: 'Telepon' },
        { key: 'address', label: 'Alamat' },
    ];

    return (
        <CrudPage
            eyebrow="Administrasi"
            title="Manajemen Pengguna"
            description="Akun staf cabang dan penugasan peran."
            queryKey={['manager', 'users']}
            queryFn={(page) => api.users({ page, include_deleted: 1 })}
            columns={[
                { header: 'Nama', cell: (user) => <span className="font-medium">{user.name}</span> },
                { header: 'Nama Pengguna', cell: (user) => user.username },
                { header: 'Peran', cell: (user) => <Badge tone="brand">{roleLabel(user.role)}</Badge> },
            ]}
            fields={fields}
            rowLabel={(user) => user.name}
            create={(values) =>
                api.admin.createUser({ ...values, branch_id: user?.branch?.id })
            }
            update={(id, values) =>
                api.admin.updateUser(id, { ...values, branch_id: user?.branch?.id })
            }
            remove={(id) => api.admin.deleteUser(id)}
            createLabel="Tambah akun staf"
        />
    );
}

export function ManagerMaterialsPage() {
    const categories = useQuery({ queryKey: ['categories'], queryFn: api.categories });
    const [lowStock, setLowStock] = useState(false);
    const materialCount = useQuery({
        queryKey: ['manager', 'materials', 'count'],
        queryFn: () => api.materials({ page: 1, per_page: 1 }),
    });
    const ledgers = useQuery({
        queryKey: ['manager', 'materials', 'ledgers'],
        queryFn: () => api.stockLedgers({ page: 1 }),
    });

    const options = (categories.data?.data ?? []).map((category) => ({
        value: category.id,
        label: category.name,
    }));

    const fields: Array<CrudField<Material>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'product_category_id', label: 'Kategori', type: 'select', options, getValue: (material) => material.category?.id ?? '' },
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

    const ledgerRows = ledgers.data?.data ?? [];

    return (
        <CrudPage
            eyebrow="Manajer Cabang"
            title="Bahan & Stok"
            description="Tingkat stok cabang, penambahan stok, dan aktivasi."
            pageResetKey={lowStock}
            queryKey={['manager', 'materials', lowStock]}
            queryFn={(page) => api.materials({ page, low_stock: lowStock ? 1 : undefined })}
            summary={
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <StatCard
                        label="Bahan Aktif"
                        value={materialCount.isSuccess ? String(materialCount.data.meta.total) : '—'}
                    />
                    <StatCard
                        label="Kategori"
                        value={categories.isSuccess ? String(categories.data.data.length) : '—'}
                    />
                </div>
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
            rowClassName={(material) =>
                material.is_low_stock === true
                    ? 'bg-amber-50/60 dark:bg-amber-500/10'
                    : undefined
            }
            columns={[
                {
                    header: 'Bahan',
                    cell: (material) => (
                        <div>
                            <span className="block font-medium">{material.name}</span>
                            <p className="text-light-muted text-xs dark:text-gray-400">
                                {[material.type, material.dimensions, unitLabel(material.unit)]
                                    .filter(Boolean)
                                    .join(' · ')}
                                <span className="sm:hidden">
                                    {' · '}Min {quantityWithUnit(material.min_stock, material.unit)}
                                </span>
                            </p>
                            {material.is_low_stock === true ? (
                                <span className="mt-1 block sm:hidden">
                                    <Badge tone="warning">Menipis</Badge>
                                </span>
                            ) : null}
                        </div>
                    ),
                },
                { header: 'Kategori', className: 'hidden sm:table-cell', cell: (material) => material.category?.name ?? '-' },
                {
                    header: 'Stok',
                    className: 'text-right tabular-nums whitespace-nowrap',
                    cell: (material) => quantityWithUnit(material.current_stock, material.unit),
                },
                {
                    header: 'Minimum',
                    className: 'hidden text-right tabular-nums whitespace-nowrap sm:table-cell',
                    cell: (material) => quantityWithUnit(material.min_stock, material.unit),
                },
                {
                    header: 'Biaya',
                    className: 'hidden text-right tabular-nums whitespace-nowrap sm:table-cell',
                    cell: (material) => formatMoney(material.cost),
                },
                {
                    header: 'Status',
                    className: 'hidden sm:table-cell',
                    cell: (material) =>
                        material.is_low_stock === null ? (
                            '-'
                        ) : material.is_low_stock ? (
                            <Badge tone="warning">Menipis</Badge>
                        ) : (
                            <Badge tone="success">Tersedia</Badge>
                        ),
                },
            ]}
            fields={fields}
            rowLabel={(material) => material.name}
            create={(values) => api.admin.createMaterial(values)}
            rowActions={(material) => (
                <>
                    <RestockMaterialButton material={material} />
                    <AdjustMaterialModal material={material} />
                </>
            )}
            createLabel="Tambah bahan"
            footer={
                <Card>
                    <CardHeader>
                        <h2 className="font-semibold">Pergerakan stok terbaru</h2>
                    </CardHeader>
                    <CardBody>
                        {ledgers.isLoading ? (
                            <div role="status" className="flex items-center justify-center py-6">
                                <Spinner className="text-brand-primary h-5 w-5" />
                            </div>
                        ) : ledgers.isError ? (
                            <EmptyState
                                title="Pergerakan stok gagal dimuat"
                                description="Periksa koneksi lalu coba lagi."
                                action={
                                    <Button variant="secondary" onClick={() => ledgers.refetch()}>
                                        Coba lagi
                                    </Button>
                                }
                            />
                        ) : (
                            <Table ariaLabel="Pergerakan stok terbaru">
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Tanggal</TableHeaderCell>
                                        <TableHeaderCell>Bahan</TableHeaderCell>
                                        <TableHeaderCell className="hidden sm:table-cell">Ditambahkan</TableHeaderCell>
                                        <TableHeaderCell>Digunakan</TableHeaderCell>
                                        <TableHeaderCell>Tersisa</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {ledgerRows.length === 0 ? (
                                        <TableEmpty colSpan={5}>
                                            Belum ada pergerakan stok.
                                        </TableEmpty>
                                    ) : (
                                        ledgerRows.map((ledger) => (
                                            <TableRow key={ledger.id}>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatDate(ledger.logged_at)}
                                                </TableCell>
                                                <TableCell>{ledger.material?.name ?? '-'}</TableCell>
                                                <TableCell className="hidden sm:table-cell">{ledgerQuantity(ledger.added_stock, ledger.material?.unit)}</TableCell>
                                                <TableCell>{ledgerQuantity(ledger.used_stock, ledger.material?.unit)}</TableCell>
                                                <TableCell>{quantityWithUnit(ledger.remaining_stock, ledger.material?.unit)}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        )}
                    </CardBody>
                </Card>
            }
        />
    );
}

export function ManagerLedgersPage() {
    return (
        <ResourceTable
            eyebrow="Inventaris"
            title="Buku Stok"
            description="Riwayat pergerakan stok dan pengeluaran cabang terbaru."
            queryKey={['manager', 'ledgers']}
            queryFn={(page) => api.stockLedgers({ page })}
            columns={[
                { header: 'Tanggal', className: 'hidden whitespace-nowrap sm:table-cell', cell: (ledger) => formatDate(ledger.logged_at) },
                {
                    header: 'Bahan',
                    cell: (ledger) => (
                        <div>
                            <span
                                className="block max-w-[7rem] truncate font-medium sm:max-w-none"
                                title={ledger.material?.name ?? undefined}
                            >
                                {ledger.material?.name ?? '-'}
                            </span>
                            <span className="text-light-muted text-xs whitespace-nowrap sm:hidden">
                                {formatDate(ledger.logged_at)}
                            </span>
                        </div>
                    ),
                },
                { header: 'Tipe', cell: (ledger) => ledgerTypeLabel(ledger.type) },
                {
                    header: 'Alasan',
                    className: 'hidden md:table-cell',
                    cell: (ledger) => (
                        <span className="block max-w-[9rem] truncate" title={ledger.reason ?? undefined}>
                            {ledger.reason ?? '-'}
                        </span>
                    ),
                },
                { header: 'Pengguna', className: 'hidden whitespace-nowrap sm:table-cell', cell: (ledger) => ledger.user?.name ?? '-' },
                { header: 'Ditambahkan', className: 'hidden text-right tabular-nums whitespace-nowrap lg:table-cell', cell: (ledger) => ledgerQuantity(ledger.added_stock, ledger.material?.unit) },
                { header: 'Digunakan', className: 'hidden text-right tabular-nums whitespace-nowrap lg:table-cell', cell: (ledger) => ledgerQuantity(ledger.used_stock, ledger.material?.unit) },
                { header: 'Bahan Terbuang', className: 'hidden text-right tabular-nums whitespace-nowrap lg:table-cell', cell: (ledger) => ledgerQuantity(ledger.wasted_stock, ledger.material?.unit) },
                { header: 'Tersisa', className: 'text-right tabular-nums sm:whitespace-nowrap', cell: (ledger) => quantityWithUnit(ledger.remaining_stock, ledger.material?.unit) },
            ]}
        />
    );
}

export function ManagerCategoriesPage() {
    return <CategoriesPage eyebrow="Konfigurasi" queryKeyPrefix="manager" readOnly />;
}

export function ManagerFinishingPage() {
    return <AttributesPage eyebrow="Konfigurasi" queryKeyPrefix="manager" readOnly />;
}

export function ManagerProductsPage() {
    return <ProductsPage eyebrow="Konfigurasi" queryKeyPrefix="manager" readOnly />;
}

export function ManagerPricingPage() {
    const products = useQuery({
        queryKey: ['products', 'options'],
        queryFn: () => api.products({ is_active: 1 }),
    });
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
        { key: 'product_id', label: 'Produk', type: 'select', options: productOptions, getValue: (pricing) => pricing.product?.id ?? '' },
        { key: 'material_id', label: 'Bahan', type: 'select', options: materialOptions, getValue: (pricing) => pricing.material?.id ?? '' },
        {
            key: 'customer_type',
            label: 'Tipe Pelanggan',
            type: 'select',
            options: [
                { value: 'Reguler', label: 'Reguler' },
                { value: 'Reseller', label: 'Reseller' },
            ],
            getValue: (pricing) => pricing.customer_type,
        },
        { key: 'min_qty', label: 'Jml min', type: 'number', defaultValue: 1, getValue: (pricing) => pricing.min_qty },
        { key: 'price', label: 'Harga jual', type: 'number', getValue: (pricing) => pricing.price },
        { key: 'cost', label: 'Biaya', type: 'number', getValue: (pricing) => pricing.cost },
    ];

    return (
        <div className="space-y-8">
            <CrudPage
                eyebrow="Konfigurasi"
                title="Matriks Harga"
                description="Baris harga bertingkat dan aturan penyesuaian."
                queryKey={['manager', 'pricing']}
                queryFn={(page) => api.pricing({ page })}
                columns={[
                    { header: 'Produk', cell: (pricing) => pricing.product?.name ?? '-' },
                    { header: 'Bahan', cell: (pricing) => pricing.material?.name ?? '-' },
                    { header: 'Tipe', cell: (pricing) => pricing.customer_type },
                    { header: 'Jml min', className: 'text-right tabular-nums whitespace-nowrap', cell: (pricing) => pricing.min_qty },
                    { header: 'Harga', className: 'text-right tabular-nums whitespace-nowrap', cell: (pricing) => formatMoney(pricing.price) },
                    { header: 'Biaya', className: 'text-right tabular-nums whitespace-nowrap', cell: (pricing) => formatMoney(pricing.cost) },
                ]}
                fields={fields}
                rowLabel={(pricing) =>
                    `${pricing.product?.name ?? '-'} / ${pricing.material?.name ?? '-'}`
                }
                create={(values) => api.admin.createPricing(values)}
                update={(id, values) => api.admin.updatePricing(id, values)}
                remove={(id) => api.admin.deletePricing(id)}
                createLabel="Tambah baris harga"
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
            setNotice('Tingkat harga disalin.');
        },
        onError: () => setNotice('Gagal menyalin tingkat harga.'),
    });

    const options = materials.data?.data ?? [];

    return (
        <Card>
            <CardBody className="space-y-4">
                <div className="space-y-1">
                    <p className="text-light-muted text-[10px] font-semibold uppercase tracking-widest dark:text-gray-400">
                        Konfigurasi
                    </p>
                    <h2 className="text-light-text text-lg font-semibold dark:text-gray-100">
                        Salin tingkat harga
                    </h2>
                    <p className="text-light-muted text-sm dark:text-gray-400">
                        Salin setiap baris harga dari satu bahan ke bahan lain di cabang Anda.
                    </p>
                </div>
                <div className="flex flex-wrap items-end gap-3">
                    <Select label="Dari bahan" value={from} onChange={(event) => setFrom(event.target.value)}>
                        <option value="">Pilih</option>
                        {options.map((material) => (
                            <option key={material.id} value={material.id}>
                                {material.name}
                            </option>
                        ))}
                    </Select>
                    <Select label="Ke bahan" value={to} onChange={(event) => setTo(event.target.value)}>
                        <option value="">Pilih</option>
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
                        Salin tingkat
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
            <PageBanner
                eyebrow="Ringkasan"
                title="Papan Peringkat Desainer"
                description="Pendapatan, jumlah pesanan, dan akurasi bahan terbuang bulan ini."
            />
            <Card>
                <CardBody>
                    <h2 className="font-semibold">Papan Peringkat</h2>
                    {query.isLoading ? (
                        <div role="status" className="flex items-center justify-center py-6">
                            <Spinner className="text-brand-primary h-5 w-5" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Papan peringkat gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : query.data ? (
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableHeaderCell>Desainer</TableHeaderCell>
                                    <TableHeaderCell>Pesanan</TableHeaderCell>
                                    <TableHeaderCell className="text-right whitespace-nowrap">Pendapatan</TableHeaderCell>
                                    <TableHeaderCell className="text-right whitespace-nowrap">Rata-rata pesanan</TableHeaderCell>
                                    <TableHeaderCell>Catatan bahan terbuang</TableHeaderCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {query.data.leaders.length === 0 ? (
                                    <TableEmpty colSpan={5}>Belum ada aktivitas desainer bulan ini.</TableEmpty>
                                ) : (
                                    query.data.leaders.map((leader) => (
                                        <TableRow key={leader.id}>
                                            <TableCell>{leader.name}</TableCell>
                                            <TableCell>{leader.order_count}</TableCell>
                                            <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                {formatMoney(leader.revenue)}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                {formatMoney(leader.average_order_value)}
                                            </TableCell>
                                            <TableCell>{leader.waste_count}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    ) : null}
                </CardBody>
            </Card>
        </div>
    );
}
