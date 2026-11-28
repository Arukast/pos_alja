import { useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Attribute, AttributeType } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { PageBanner } from '../../components/PageBanner';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { formatMoney } from '../../lib/money';
import { collectionAsPage } from '../../lib/pagination';

export function AttributesPage({
    eyebrow,
    queryKeyPrefix,
}: {
    eyebrow: string;
    queryKeyPrefix: 'owner' | 'manager';
}) {
    const isManager = queryKeyPrefix === 'manager';
    const queryClient = useQueryClient();
    const [tab, setTab] = useState<'attributes' | 'types'>('attributes');

    const branches = useQuery({
        queryKey: ['branches', 'options'],
        queryFn: () => api.branches({ page: 1 }),
        enabled: queryKeyPrefix === 'owner',
    });

    const attributeTypes = useQuery({
        queryKey: ['attribute-types'],
        queryFn: () => api.attributeTypes(),
    });

    const typeOptions = (currentType?: string) =>
        (attributeTypes.data?.data ?? [])
            .filter((type) => type.is_active || type.slug === currentType)
            .map((type) => ({
                value: type.slug,
                label: type.is_active ? type.name : `${type.name} (nonaktif)`,
            }));

    const branchOptions = (branches.data?.data ?? []).map((branch) => ({
        value: branch.id,
        label: branch.name,
    }));

    const branchField: CrudField<Attribute> = {
        key: 'branch_id',
        label: 'Cabang',
        type: 'select',
        options: branchOptions,
        emptyOptionLabel: 'Global (semua cabang)',
        getValue: (attribute) => attribute.branch?.id ?? '',
        hideOnEdit: true,
    };

    const attributeFields: Array<CrudField<Attribute>> = [
        { key: 'name', label: 'Nama', required: true },
        {
            key: 'type',
            label: 'Tipe',
            type: 'select',
            required: true,
            defaultValue: (attributeTypes.data?.data ?? []).find((type) => type.is_active)?.slug ?? '',
            options: (row) => typeOptions(row?.type),
            getValue: (attribute) => attribute.type,
            emptyOptionLabel: 'Pilih tipe',
        },
        ...(isManager ? [] : [branchField]),
        {
            key: 'price',
            label: 'Harga',
            type: 'number',
            defaultValue: 0,
            getValue: (attribute) => attribute.price,
        },
        {
            key: 'cost',
            label: 'Biaya',
            type: 'number',
            defaultValue: 0,
            getValue: (attribute) => attribute.cost ?? 0,
        },
        {
            key: 'is_active',
            label: 'Aktif',
            type: 'checkbox',
            defaultValue: true,
            getValue: (attribute) => attribute.is_active,
        },
    ];

    const typeFields: Array<CrudField<AttributeType>> = [
        { key: 'name', label: 'Nama', required: true },
        { key: 'sort_order', label: 'Urutan', type: 'number', defaultValue: '' },
        { key: 'is_active', label: 'Aktif', type: 'checkbox', defaultValue: true },
    ];

    const invalidateAttributeData = async () => {
        await Promise.all([
            queryClient.invalidateQueries({ queryKey: ['attribute-types'] }),
            queryClient.invalidateQueries({ queryKey: ['quote-options'] }),
            queryClient.invalidateQueries({ queryKey: ['owner', 'attributes'] }),
            queryClient.invalidateQueries({ queryKey: ['manager', 'attributes'] }),
        ]);
    };

    // The type route binds by slug, while CrudPage hands the update callback the row id.
    const resolveTypeSlug = async (id: string) => {
        const type =
            (attributeTypes.data?.data ?? []).find((item) => item.id === id) ??
            (await api.attributeTypes()).data.find((item) => item.id === id);

        if (!type) {
            throw new Error('Tipe atribut tidak ditemukan.');
        }

        return type.slug;
    };

    const tabs = (
        <div className="flex flex-wrap gap-2">
            <Button
                size="sm"
                variant={tab === 'attributes' ? 'primary' : 'secondary'}
                onClick={() => setTab('attributes')}
            >
                Atribut
            </Button>
            <Button
                size="sm"
                variant={tab === 'types' ? 'primary' : 'secondary'}
                onClick={() => setTab('types')}
            >
                Tipe Atribut
            </Button>
        </div>
    );

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow={eyebrow}
                title="Atribut"
                description="Kelola atribut dan tipe atribut."
            />

            {tab === 'attributes' ? (
                <CrudPage<Attribute>
                    eyebrow={eyebrow}
                    hideBanner
                    toolbar={tabs}
                    title="Daftar atribut"
                    queryKey={[queryKeyPrefix, 'attributes']}
                    queryFn={() => api.attributes().then((response) => collectionAsPage(response.data))}
                    columns={[
                        {
                            header: 'Nama',
                            cell: (attribute) => <span className="font-medium">{attribute.name}</span>,
                        },
                        { header: 'Tipe', className: 'hidden sm:table-cell', cell: (attribute) => attribute.type_name },
                        {
                            header: 'Cabang',
                            className: 'hidden sm:table-cell',
                            cell: (attribute) => attribute.branch?.name ?? 'Global',
                        },
                        {
                            header: 'Harga',
                            className: 'text-right tabular-nums whitespace-nowrap',
                            cell: (attribute) => formatMoney(attribute.price),
                        },
                        {
                            header: 'Biaya',
                            className: 'hidden text-right tabular-nums whitespace-nowrap md:table-cell',
                            cell: (attribute) => formatMoney(attribute.cost ?? 0),
                        },
                        {
                            header: 'Status',
                            cell: (attribute) => (
                                <Badge tone={attribute.is_active ? 'success' : 'neutral'}>
                                    {attribute.is_active ? 'Aktif' : 'Nonaktif'}
                                </Badge>
                            ),
                        },
                    ]}
                    fields={attributeFields}
                    rowLabel={(attribute) => attribute.name}
                    create={async (values) => {
                        const result = await api.admin.createAttribute({
                            ...values,
                            price: Number(values.price || 0),
                            cost: Number(values.cost || 0),
                            is_active: Boolean(values.is_active),
                            ...(isManager ? {} : { branch_id: values.branch_id || null }),
                        });
                        await invalidateAttributeData();
                        return result;
                    }}
                    update={async (id, values) => {
                        const result = await api.admin.updateAttribute(id, {
                            name: values.name,
                            type: values.type,
                            price: Number(values.price || 0),
                            cost: Number(values.cost || 0),
                            is_active: Boolean(values.is_active),
                        });
                        await invalidateAttributeData();
                        return result;
                    }}
                    canEditRow={isManager ? (attribute) => attribute.branch_id !== null : undefined}
                    createLabel="Tambah atribut"
                    paginated={false}
                />
            ) : (
                <CrudPage<AttributeType>
                    eyebrow={eyebrow}
                    hideBanner
                    toolbar={tabs}
                    title="Daftar tipe atribut"
                    queryKey={['attribute-types']}
                    queryFn={() => api.attributeTypes().then((response) => collectionAsPage(response.data))}
                    columns={[
                        {
                            header: 'Nama',
                            cell: (type) => <span className="font-medium">{type.name}</span>,
                        },
                        { header: 'Slug', className: 'hidden sm:table-cell', cell: (type) => type.slug },
                        {
                            header: 'Urutan',
                            className: 'text-right tabular-nums whitespace-nowrap',
                            cell: (type) => type.sort_order,
                        },
                        {
                            header: 'Status',
                            cell: (type) => (
                                <Badge tone={type.is_active ? 'success' : 'neutral'}>
                                    {type.is_active ? 'Aktif' : 'Nonaktif'}
                                </Badge>
                            ),
                        },
                    ]}
                    fields={isManager ? [] : typeFields}
                    rowLabel={(type) => type.name}
                    create={
                        isManager
                            ? undefined
                            : async (values) => {
                                  const result = await api.admin.createAttributeType({
                                      name: values.name,
                                      sort_order:
                                          values.sort_order === '' ? undefined : Number(values.sort_order),
                                      is_active: Boolean(values.is_active),
                                  });
                                  await invalidateAttributeData();
                                  return result;
                              }
                    }
                    update={
                        isManager
                            ? undefined
                            : async (id, values) => {
                                  const result = await api.admin.updateAttributeType(await resolveTypeSlug(id), {
                                      name: values.name,
                                      sort_order:
                                          values.sort_order === '' ? undefined : Number(values.sort_order),
                                      is_active: Boolean(values.is_active),
                                  });
                                  await invalidateAttributeData();
                                  return result;
                              }
                    }
                    createLabel="Tambah tipe atribut"
                    paginated={false}
                />
            )}
        </div>
    );
}
