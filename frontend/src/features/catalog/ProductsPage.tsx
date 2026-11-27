import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useState } from 'react';

import { api } from '../../api/queries';
import type { Material, Product } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { Badge } from '../../components/ui/Badge';
import { Card, CardBody } from '../../components/ui/Card';
import { Input } from '../../components/ui/Input';
import { Select } from '../../components/ui/Select';
import { unitLabel, unitOptions } from '../../lib/units';
import { productCompositionLabels } from './productOptions';

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
    const queryClient = useQueryClient();
    const categories = useQuery({ queryKey: ['categories'], queryFn: api.categories });
    const materials = useQuery({
        queryKey: ['materials', 'all-options'],
        queryFn: fetchAllMaterials,
        enabled: !readOnly,
    });
    const attributeTypes = useQuery({
        queryKey: ['attribute-types', 'product-options'],
        queryFn: api.attributeTypes,
        enabled: !readOnly,
    });
    const attributes = useQuery({
        queryKey: ['attributes', 'product-options'],
        queryFn: () => api.attributes(),
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
        label: category.is_active ? category.name : `${category.name} (nonaktif)`,
    }));
    const materialOptions = (materials.data ?? []).map((material) => ({
        value: material.id,
        label: material.name,
    }));
    const activeTypes = (attributeTypes.data?.data ?? []).filter((type) => type.is_active);
    const attributeTypeOptions = [...activeTypes]
        .sort((a, b) => a.sort_order - b.sort_order || a.name.localeCompare(b.name))
        .map((type) => ({ value: type.id, label: type.name }));
    const activeTypeSlugs = new Set(activeTypes.map((type) => type.slug));
    const attributeOptions = (attributes.data?.data ?? [])
        .filter((attribute) => attribute.is_active && activeTypeSlugs.has(attribute.type))
        .map((attribute) => ({
            value: attribute.id,
            label: `${attribute.name} · ${attribute.type_name}`,
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
            key: 'selling_unit',
            label: 'Satuan Jual',
            type: 'select',
            required: true,
            defaultValue: 'pcs',
            options: unitOptions,
            getValue: (product) => product.selling_unit,
        },
        {
            key: 'requires_material',
            label: 'Perlu bahan',
            type: 'checkbox',
            defaultValue: true,
            getValue: (product) => product.requires_material,
        },
        {
            key: 'uses_attributes',
            label: 'Gunakan atribut',
            type: 'checkbox',
            defaultValue: true,
            getValue: (product) => product.uses_attributes,
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
            key: 'attribute_type_ids',
            label: 'Tipe Atribut yang Kompatibel',
            type: 'multiselect',
            defaultValue: [],
            options: attributeTypeOptions,
            getValue: (product) => product.attribute_type_ids ?? [],
        },
        {
            key: 'attribute_ids',
            label: 'Atribut yang Kompatibel',
            type: 'multiselect',
            defaultValue: [],
            options: attributeOptions,
            getValue: (product) => product.attribute_ids ?? [],
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
            description="Daftar produk, kategori, satuan jual, dan status aktif."
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
                    cell: (product) => (
                        <div>
                            <span className="block font-medium">{product.name}</span>
                            <div className="mt-1 flex flex-col items-start gap-1 sm:hidden">
                                <span className="text-light-muted text-xs dark:text-gray-400">
                                    {product.category?.name ?? '-'}
                                </span>
                                {product.category && !product.category.is_active ? (
                                    <Badge tone="warning">Kategori nonaktif</Badge>
                                ) : null}
                            </div>
                        </div>
                    ),
                },
                {
                    header: 'Kategori',
                    className: 'hidden sm:table-cell',
                    cell: (product) => (
                        <div className="flex flex-col items-start gap-1">
                            <span>{product.category?.name ?? '-'}</span>
                            {product.category && !product.category.is_active ? (
                                <Badge tone="warning">Kategori nonaktif</Badge>
                            ) : null}
                        </div>
                    ),
                },
                {
                    header: 'Satuan Jual',
                    className: 'hidden md:table-cell',
                    cell: (product) => unitLabel(product.selling_unit),
                },
                {
                    header: 'Bahan',
                    className: 'hidden lg:table-cell',
                    cell: (product) => productCompositionLabels(product).materials,
                },
                {
                    header: 'Tipe Atribut',
                    className: 'hidden xl:table-cell',
                    cell: (product) => productCompositionLabels(product).types,
                },
                {
                    header: 'Atribut',
                    className: 'hidden xl:table-cell',
                    cell: (product) => productCompositionLabels(product).attributes,
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
                    : async (values) => {
                          const result = await api.admin.createProduct({
                              ...values,
                              is_active: Boolean(values.is_active),
                              requires_material: Boolean(values.requires_material),
                              uses_attributes: Boolean(values.uses_attributes),
                              material_ids: Array.isArray(values.material_ids)
                                  ? values.material_ids
                                  : [],
                              attribute_type_ids: Array.isArray(values.attribute_type_ids)
                                  ? values.attribute_type_ids
                                  : [],
                              attribute_ids: Array.isArray(values.attribute_ids)
                                  ? values.attribute_ids
                                  : [],
                          });
                          void queryClient.invalidateQueries({ queryKey: ['quote-options'] });

                          return result;
                      }
            }
            update={
                readOnly
                    ? undefined
                    : async (id, values) => {
                          const result = await api.admin.updateProduct(id, {
                              ...values,
                              is_active: Boolean(values.is_active),
                              requires_material: Boolean(values.requires_material),
                              uses_attributes: Boolean(values.uses_attributes),
                              material_ids: Array.isArray(values.material_ids)
                                  ? values.material_ids
                                  : [],
                              attribute_type_ids: Array.isArray(values.attribute_type_ids)
                                  ? values.attribute_type_ids
                                  : [],
                              attribute_ids: Array.isArray(values.attribute_ids)
                                  ? values.attribute_ids
                                  : [],
                          });
                          void queryClient.invalidateQueries({ queryKey: ['quote-options'] });

                          return result;
                      }
            }
            createLabel="Tambah produk"
        />
    );
}
