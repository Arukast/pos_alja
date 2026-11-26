import { useQuery } from '@tanstack/react-query';
import { useEffect, useState } from 'react';

import { api } from '../../api/queries';
import type { Product } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { Badge } from '../../components/ui/Badge';
import { Card, CardBody } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { pricingTypeLabel } from '../../lib/labels';

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
                          api.admin.createProduct({ ...values, is_active: Boolean(values.is_active) })
            }
            update={
                readOnly
                    ? undefined
                    : (id, values) =>
                          api.admin.updateProduct(id, {
                              ...values,
                              is_active: Boolean(values.is_active),
                          })
            }
            remove={readOnly ? undefined : (id) => api.admin.deleteProduct(id)}
            createLabel="Tambah produk"
        />
    );
}
