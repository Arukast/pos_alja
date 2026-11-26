import { useQuery } from '@tanstack/react-query';
import { useEffect, useState } from 'react';

import { api } from '../../api/queries';
import type { Material, Product } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { Badge } from '../../components/ui/Badge';
import { Card, CardBody } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { pricingTypeLabel } from '../../lib/labels';

async function fetchAllMaterials(): Promise<Material[]> {
    const first = await api.materials({ page: 1 });
    const rows = [...first.data];

    for (let nextPage = 2; nextPage <= first.meta.last_page; nextPage++) {
        const pageResponse = await api.materials({ page: nextPage });
        rows.push(...pageResponse.data);
    }

    return rows;
}

export function ProductsPage({
    eyebrow,
    queryKeyPrefix,
    readOnly = false,
}: {
    eyebrow: string;
    queryKeyPrefix: 'owner' | 'manager';
    readOnly?: boolean;
}) {
    const categories = useQuery({ queryKey: ['categories'], queryFn: api.categories });
    const materials = useQuery({
        queryKey: ['materials', 'all-options'],
        queryFn: fetchAllMaterials,
        enabled: !readOnly,
    });
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [categoryId, setCategoryId] = useState('');
    const [status, setStatus] = useState('');

    useEffect(() => {
        const timer = window.setTimeout(() => setSearch(searchInput.trim()), 300);

        return () => window.clearTimeout(timer);
    }, [searchInput]);

    const categoryOptions = (categories.data?.data ?? []).map((category) => ({
        value: category.id,
        label: category.name,
    }));
    const materialOptions = (materials.data ?? []).map((material) => ({
        value: material.id,
        label: material.name,
    }));

    const fields: Array<CrudField<Product>> = [
        { key: 'name', label: 'Nama', required: true },
        {
            key: 'product_category_id',
            label: 'Kategori',
            type: 'select',
            required: true,
            options: categoryOptions,
            getValue: (product) => product.category?.id ?? '',
        },
        {
            key: 'pricing_type',
            label: 'Tipe Harga',
            type: 'select',
            required: true,
            defaultValue: 'unit',
            options: [
                { value: 'unit', label: pricingTypeLabel('unit') },
                { value: 'area', label: pricingTypeLabel('area') },
            ],
            getValue: (product) => product.pricing_type,
        },
        {
            key: 'material_ids',
            label: 'Bahan yang Kompatibel',
            type: 'multiselect',
            defaultValue: [],
            options: materialOptions,
            getValue: (product) => product.material_ids ?? [],
        },
        {
            key: 'is_active',
            label: 'Aktif',
            type: 'checkbox',
            defaultValue: true,
            getValue: (product) => product.is_active,
        },
    ];

    return (
        <CrudPage<Product>
            pageResetKey={`${search}:${categoryId}:${status}`}
            eyebrow={eyebrow}
            title="Manajemen Produk"
            description="Daftar produk, kategori, tipe harga, dan status aktif."
            queryKey={[queryKeyPrefix, 'products', search, categoryId, status]}
            queryFn={(page) =>
                api.products({
                    page,
                    search,
                    category_id: categoryId,
                    is_active: status,
                })
            }
            summary={
                <Card>
                    <CardBody>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div className="w-full sm:max-w-xs">
                                <Input
                                    label="Cari produk"
                                    type="search"
                                    placeholder="Nama produk atau kategori"
                                    value={searchInput}
                                    onChange={(event) => setSearchInput(event.target.value)}
                                />
                            </div>
                            <div className="w-full sm:w-52">
                                <Select
                                    label="Kategori"
                                    value={categoryId}
                                    onChange={(event) => setCategoryId(event.target.value)}
                                >
                                    <option value="">Semua kategori</option>
                                    {categoryOptions.map((option) => (
                                        <option key={option.value} value={option.value}>
                                            {option.label}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                            <div className="w-full sm:w-44">
                                <Select
                                    label="Status"
                                    value={status}
                                    onChange={(event) => setStatus(event.target.value)}
                                >
                                    <option value="">Semua status</option>
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </Select>
                            </div>
                        </div>
                    </CardBody>
                </Card>
            }
            columns={[
                {
                    header: 'Nama',
                    cell: (product) => <span className="font-medium">{product.name}</span>,
                },
                {
                    header: 'Kategori',
                    className: 'hidden sm:table-cell',
                    cell: (product) => product.category?.name ?? '-',
                },
                {
                    header: 'Tipe Harga',
                    className: 'hidden md:table-cell',
                    cell: (product) => pricingTypeLabel(product.pricing_type),
                },
                {
                    header: 'Bahan',
                    className: 'hidden lg:table-cell',
                    cell: (product) =>
                        (product.materials ?? []).length === 0
                            ? 'Semua bahan'
                            : product.materials.map((material) => material.name).join(', '),
                },
                {
                    header: 'Status',
                    cell: (product) => (
                        <Badge tone={product.is_active ? 'success' : 'neutral'}>
                            {product.is_active ? 'Aktif' : 'Nonaktif'}
                        </Badge>
                    ),
                },
            ]}
            fields={fields}
            rowLabel={(product) => product.name}
            create={
                readOnly
                    ? undefined
                    : (values) =>
                          api.admin.createProduct({
                              ...values,
                              is_active: Boolean(values.is_active),
                              material_ids: Array.isArray(values.material_ids)
                                  ? values.material_ids
                                  : [],
                          })
            }
            update={
                readOnly
                    ? undefined
                    : (id, values) =>
                          api.admin.updateProduct(id, {
                              ...values,
                              is_active: Boolean(values.is_active),
                              material_ids: Array.isArray(values.material_ids)
                                  ? values.material_ids
                                  : [],
                          })
            }
            remove={readOnly ? undefined : (id) => api.admin.deleteProduct(id)}
            createLabel="Tambah produk"
        />
    );
}
