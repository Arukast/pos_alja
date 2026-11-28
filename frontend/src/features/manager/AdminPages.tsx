import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Material, UserRecord } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { ActiveToggleButton } from '../../components/admin/ActiveToggleButton';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { PageBanner } from '../../components/PageBanner';
import { ResourceTable } from '../../components/ResourceTable';
import { RoleBadge } from '../../components/RoleBadge';
import { AttributesPage } from '../catalog/AttributesPage';
import { CategoriesPage } from '../catalog/CategoriesPage';
import { ProductsPage } from '../catalog/ProductsPage';
import { AdjustMaterialModal } from '../catalog/AdjustMaterialModal';
import { MaterialActiveToggle } from '../catalog/MaterialActiveToggle';
import { MinimumStockInline } from '../catalog/MinimumStockInline';
import { RestockMaterialButton } from '../catalog/RestockMaterialButton';
import { QuantityTiersPage } from '../catalog/QuantityTiersPage';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Checkbox } from '../../components/ui/Checkbox';
import { EmptyState } from '../../components/ui/EmptyState';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { ledgerTypeLabel, roleLabel } from '../../lib/labels';
import { formatDate } from '../../lib/dates';
import { errorMessage } from '../../lib/errors';
import { formatMoney } from '../../lib/money';
import { ledgerQuantity, quantityWithUnit, unitLabel, unitOptions } from '../../lib/units';

const unitLabelFor = (values: Record<string, unknown>) => unitLabel(String(values.unit ?? ''));

export function ManagerUsersPage() {
    const { user: authUser } = useAuth();
    const queryClient = useQueryClient();
    const [notice, setNotice] = useState<{ message: string; tone: 'success' | 'error' } | null>(
        null
    );

    const toggleStatus = useMutation({
        mutationFn: (id: string) => api.admin.toggleUser(id),
        onSuccess: (response) => {
            void queryClient.invalidateQueries({ queryKey: ['manager', 'users'] });
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
        <>
            <CrudPage
                eyebrow="Administrasi"
                title="Manajemen Pengguna"
                description="Akun staf cabang dan penugasan peran."
                queryKey={['manager', 'users']}
                queryFn={(page) => api.users({ page })}
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
                ]}
                fields={fields}
                rowLabel={(user) => user.name}
                create={(values) =>
                    api.admin.createUser({ ...values, branch_id: authUser?.branch?.id })
                }
                update={(id, values) =>
                    api.admin.updateUser(id, { ...values, branch_id: authUser?.branch?.id })
                }
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
                createLabel="Tambah akun staf"
                notice={notice}
                onNoticeChange={setNotice}
            />
        </>
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
                        value={
                            materialCount.isSuccess
                                ? String(materialCount.data.available_total ?? 0)
                                : '—'
                        }
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
                            {material.is_active === false ? (
                                <Badge tone="danger" className="mt-1">
                                    Nonaktif
                                </Badge>
                            ) : null}
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
                            {material.stocks[0]?.is_active === false ? (
                                <span className="mt-1 block sm:hidden">
                                    <Badge tone="danger">Nonaktif di cabang</Badge>
                                </span>
                            ) : null}
                            <div className="text-light-muted mt-1 flex flex-wrap items-center gap-1 text-xs dark:text-gray-400 sm:hidden">
                                <span className="text-light-muted text-xs dark:text-gray-400">
                                    Min
                                </span>
                                <MinimumStockInline
                                    materialId={material.id}
                                    label="cabang ini"
                                    value={material.min_stock ?? '0'}
                                    unit={material.unit}
                                />
                            </div>
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
                    cell: (material) => (
                        <MinimumStockInline
                            materialId={material.id}
                            label="cabang ini"
                            value={material.min_stock ?? '0'}
                            unit={material.unit}
                        />
                    ),
                },
                {
                    header: 'Biaya',
                    className: 'hidden text-right tabular-nums whitespace-nowrap lg:table-cell',
                    cell: (material) => formatMoney(material.cost),
                },
                {
                    header: 'Status',
                    className: 'hidden sm:table-cell',
                    cell: (material) =>
                        material.is_active === false ||
                        (material.stocks.length > 0 &&
                            material.stocks[0].is_active === false) ? (
                            <Badge tone="danger">Nonaktif</Badge>
                        ) : material.is_low_stock === null ? (
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
                    {material.stocks.length > 0 ? (
                        <MaterialActiveToggle
                            scope="branch"
                            materialId={material.id}
                            isActive={material.stocks[0].is_active}
                            branchId={material.stocks[0].branch_id}
                            branchName={material.stocks[0].branch_name ?? 'cabang ini'}
                            materialName={material.name}
                        />
                    ) : null}
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
    return <AttributesPage eyebrow="Konfigurasi" queryKeyPrefix="manager" />;
}

export function ManagerProductsPage() {
    return <ProductsPage eyebrow="Konfigurasi" queryKeyPrefix="manager" readOnly />;
}

export function ManagerQuantityTiersPage() {
    return <QuantityTiersPage eyebrow="Konfigurasi" queryKeyPrefix="manager" readOnly />;
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
