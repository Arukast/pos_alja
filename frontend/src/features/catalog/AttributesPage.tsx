import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import type { Attribute, AttributeType } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { Badge } from '../../components/ui/Badge';
import { archetypeLabel, attributeTypeLabel } from '../../lib/labels';
import { formatMoney } from '../../lib/money';
import { collectionAsPage } from '../../lib/pagination';

export function AttributesPage({
    eyebrow,
    queryKeyPrefix,
    readOnly = false,
}: {
    eyebrow: string;
    queryKeyPrefix: 'owner' | 'manager';
    readOnly?: boolean;
}) {
    const branches = useQuery({
        queryKey: ['branches', 'options'],
        queryFn: () => api.branches({ page: 1 }),
        enabled: !readOnly,
    });

    const branchOptions = (branches.data?.data ?? []).map((branch) => ({
        value: branch.id,
        label: branch.name,
    }));

    const attributeFields: Array<CrudField<Attribute>> = [
        { key: 'name', label: 'Nama', required: true },
        {
            key: 'type',
            label: 'Tipe',
            type: 'select',
            required: true,
            defaultValue: 'finishing',
            options: [
                { value: 'finishing', label: attributeTypeLabel('finishing') },
                { value: 'display', label: attributeTypeLabel('display') },
            ],
            getValue: (attribute) => attribute.type,
        },
        {
            key: 'branch_id',
            label: 'Cabang',
            type: 'select',
            options: branchOptions,
            getValue: (attribute) => attribute.branch?.id ?? '',
        },
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
            getValue: (attribute) => attribute.cost,
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

    return (
        <div className="space-y-8">
            <CrudPage<Attribute>
                eyebrow={eyebrow}
                title="Display & Finishing"
                description="Atribut finishing dan display, harga, biaya, dan status aktif."
                queryKey={[queryKeyPrefix, 'attributes']}
                queryFn={() => api.attributes().then((response) => collectionAsPage(response.data))}
                columns={[
                    {
                        header: 'Nama',
                        cell: (attribute) => <span className="font-medium">{attribute.name}</span>,
                    },
                    { header: 'Tipe', cell: (attribute) => attributeTypeLabel(attribute.type) },
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
                        cell: (attribute) => formatMoney(attribute.cost),
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
                create={
                    readOnly
                        ? undefined
                        : (values) =>
                              api.admin.createAttribute({
                                  ...values,
                                  branch_id: values.branch_id || null,
                                  price: values.price === '' ? 0 : Number(values.price),
                                  cost: values.cost === '' ? 0 : Number(values.cost),
                                  is_active: Boolean(values.is_active),
                              })
                }
                update={
                    readOnly
                        ? undefined
                        : (id, values) =>
                              api.admin.updateAttribute(id, {
                                  ...values,
                                  branch_id: values.branch_id || null,
                                  price: values.price === '' ? 0 : Number(values.price),
                                  cost: values.cost === '' ? 0 : Number(values.cost),
                                  is_active: Boolean(values.is_active),
                              })
                }
                remove={readOnly ? undefined : (id) => api.admin.deleteAttribute(id)}
                createLabel="Tambah atribut"
                paginated={false}
            />

            <CrudPage<AttributeType>
                eyebrow={eyebrow}
                title="Tipe Atribut"
                description="Tipe yang digunakan oleh perhitungan harga."
                queryKey={[queryKeyPrefix, 'attribute-types']}
                queryFn={() =>
                    api.attributeTypes().then((response) => collectionAsPage(response.data))
                }
                columns={[
                    {
                        header: 'Nama',
                        cell: (type) => <span className="font-medium">{type.name}</span>,
                    },
                    {
                        header: 'Arketipe',
                        cell: (type) => archetypeLabel(type.calculation_archetype),
                    },
                ]}
                fields={typeFields}
                create={readOnly ? undefined : (values) => api.admin.createAttributeType(values)}
                remove={readOnly ? undefined : (id) => api.admin.deleteAttributeType(id)}
                createLabel="Tambah tipe atribut"
                paginated={false}
                hideBanner
            />
        </div>
    );
}
