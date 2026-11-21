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
import { archetypeLabel, attributeTypeLabel, roleLabel } from '../../lib/labels';
import { formatMoney } from '../../lib/money';

export function ManagerUsersPage() {
    const { user } = useAuth();
    const fields: Array<CrudField<UserRecord>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'username', label: 'Nama Pengguna', required: true },
        { key: 'email', label: 'Email', type: 'email' },
        { key: 'password', label: 'Kata Sandi', type: 'password', hideOnEdit: true },
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
    const options = (categories.data?.data ?? []).map((category) => ({
        value: category.id,
        label: category.name,
    }));

    const fields: Array<CrudField<Material>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'product_category_id', label: 'Kategori', type: 'select', options, getValue: (material) => material.category?.id ?? '' },
        { key: 'type', label: 'Jenis' },
        { key: 'dimensions', label: 'Dimensi' },
        { key: 'cost', label: 'Biaya', type: 'number' },
        { key: 'price', label: 'Harga', type: 'number' },
        { key: 'initial_stock', label: 'Stok awal', type: 'number', hideOnEdit: true },
        { key: 'min_stock', label: 'Stok minimum', type: 'number', hideOnEdit: true },
    ];

    return (
        <CrudPage
            eyebrow="Inventaris"
            title="Bahan & Stok"
            description="Tingkat stok cabang, penambahan stok, dan aktivasi."
            queryKey={['manager', 'materials']}
            queryFn={(page) => api.materials({ page })}
            columns={[
                { header: 'Bahan', cell: (material) => <span className="font-medium">{material.name}</span> },
                { header: 'Kategori', cell: (material) => material.category?.name ?? '-' },
                { header: 'Stok', cell: (material) => material.current_stock ?? '-' },
                { header: 'Minimum', cell: (material) => material.min_stock ?? '-' },
                { header: 'Biaya', cell: (material) => formatMoney(material.cost) },
            ]}
            fields={fields}
            create={(values) => api.admin.createMaterial(values)}
            rowActions={(material) => <RestockMaterialButton material={material} />}
            createLabel="Tambah bahan"
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
                { header: 'Tanggal', cell: (ledger) => ledger.logged_at },
                { header: 'Bahan', cell: (ledger) => ledger.material?.name ?? '-' },
                { header: 'Ditambahkan', cell: (ledger) => ledger.added_stock },
                { header: 'Digunakan', cell: (ledger) => ledger.used_stock },
                { header: 'Sisa Bahan', cell: (ledger) => ledger.wasted_stock },
                { header: 'Tersisa', cell: (ledger) => ledger.remaining_stock },
            ]}
        />
    );
}

export function ManagerMachinesPage() {
    const fields: Array<CrudField<Machine>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'cost', label: 'Biaya Dasar', type: 'number' },
    ];

    return (
        <CrudPage
            eyebrow="Konfigurasi"
            title="Mesin Cetak"
            description="Data mesin dan biaya dasar untuk cabang Anda."
            queryKey={['manager', 'machines']}
            queryFn={(page) => api.machines({ page, include_deleted: 1 })}
            columns={[
                { header: 'Mesin', cell: (machine) => <span className="font-medium">{machine.name}</span> },
                { header: 'Biaya Dasar', cell: (machine) => formatMoney(machine.cost) },
            ]}
            fields={fields}
            create={(values) => api.admin.createMachine(values)}
            update={(id, values) => api.admin.updateMachine(id, values)}
            remove={(id) => api.admin.deleteMachine(id)}
            createLabel="Tambah mesin"
        />
    );
}

export function ManagerCategoriesPage() {
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
            eyebrow="Konfigurasi"
            title="Kategori Produk"
            description="Arketipe kategori dan jenis atribut yang diizinkan."
            queryKey={['manager', 'categories']}
            queryFn={() => api.categories().then((response) => collectionAsPage(response.data))}
            columns={[
                { header: 'Nama', cell: (category) => <span className="font-medium">{category.name}</span> },
                { header: 'Arketipe', cell: (category) => archetypeLabel(category.calculation_archetype) },
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
        />
    );
}

export function ManagerFinishingPage() {
    const typeFields: Array<CrudField<AttributeType>> = [
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
        },
    ];

    const attributeFields: Array<CrudField<Attribute>> = [
        { key: 'name', label: 'Nama', required: true },
        {
            key: 'type',
            label: 'Tipe',
            type: 'select',
            options: [
                { value: 'finishing', label: attributeTypeLabel('finishing') },
                { value: 'cut', label: attributeTypeLabel('cut') },
                { value: 'display', label: attributeTypeLabel('display') },
            ],
        },
    ];

    return (
        <div className="space-y-8">
            <CrudPage
                eyebrow="Konfigurasi"
                title="Display & Finishing"
                description="Kelola atribut finishing, potong, dan display."
                queryKey={['manager', 'attributes']}
                queryFn={() => api.attributes().then((response) => collectionAsPage(response.data))}
                columns={[
                    { header: 'Nama', cell: (attribute) => <span className="font-medium">{attribute.name}</span> },
                    { header: 'Tipe', cell: (attribute) => attributeTypeLabel(attribute.type) },
                ]}
                fields={attributeFields}
                create={(values) => api.admin.createAttribute(values)}
                update={(id, values) => api.admin.updateAttribute(id, values)}
                remove={(id) => api.admin.deleteAttribute(id)}
                createLabel="Tambah atribut"
            />

            <CrudPage
                eyebrow="Konfigurasi"
                title="Tipe Atribut"
                description="Tipe yang digunakan oleh perhitungan harga."
                queryKey={['manager', 'attribute-types']}
                queryFn={() => api.attributeTypes().then((response) => collectionAsPage(response.data))}
                columns={[
                    { header: 'Nama', cell: (type) => <span className="font-medium">{type.name}</span> },
                    { header: 'Arketipe', cell: (type) => archetypeLabel(type.calculation_archetype) },
                ]}
                fields={typeFields}
                create={(values) => api.admin.createAttributeType(values)}
                remove={(id) => api.admin.deleteAttributeType(id)}
                createLabel="Tambah tipe atribut"
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
                    { header: 'Jml min', cell: (pricing) => pricing.min_qty },
                    { header: 'Harga', cell: (pricing) => formatMoney(pricing.price) },
                    { header: 'Biaya', cell: (pricing) => formatMoney(pricing.cost) },
                ]}
                fields={fields}
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
                <PageHeader
                    eyebrow="Konfigurasi"
                    title="Salin tingkat harga"
                    description="Salin setiap baris harga dari satu bahan ke bahan lain di cabang Anda."
                />
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
            <PageHeader
                eyebrow="Ringkasan"
                title="Papan Peringkat Desainer"
                description="Pendapatan, jumlah pesanan, dan akurasi sisa bahan bulan ini."
            />
            <Card>
                <CardBody>
                    <h2 className="font-semibold">Papan Peringkat</h2>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableHeaderCell>Desainer</TableHeaderCell>
                                <TableHeaderCell>Pesanan</TableHeaderCell>
                                <TableHeaderCell>Pendapatan</TableHeaderCell>
                                <TableHeaderCell>Rata-rata pesanan</TableHeaderCell>
                                <TableHeaderCell>Catatan sisa bahan</TableHeaderCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {(query.data?.leaders ?? []).length === 0 ? (
                                <TableEmpty colSpan={5}>Belum ada aktivitas desainer bulan ini.</TableEmpty>
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
