import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import type { Attribute } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';
import { Badge } from '../../components/ui/Badge';
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

    return (
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
            create={(values) =>
                api.admin.createAttribute({
                    ...values,
                    price: Number(values.price || 0),
                    cost: Number(values.cost || 0),
                    is_active: Boolean(values.is_active),
                    ...(isManager ? {} : { branch_id: values.branch_id || null }),
                })
            }
            update={(id, values) =>
                api.admin.updateAttribute(id, {
                    name: values.name,
                    type: values.type,
                    price: Number(values.price || 0),
                    cost: Number(values.cost || 0),
                    is_active: Boolean(values.is_active),
                })
            }
            canEditRow={isManager ? (attribute) => attribute.branch_id !== null : undefined}
            createLabel="Tambah atribut"
            paginated={false}
        />
    );
}
