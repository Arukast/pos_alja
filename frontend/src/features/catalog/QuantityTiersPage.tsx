import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import type { ProductQuantityDiscount } from '../../api/types';
import { CrudPage, type CrudField } from '../../components/admin/CrudPage';

const tierRowLabel = (tier: ProductQuantityDiscount): string =>
    `${tier.product?.name ?? 'Tier'} ${tier.min_qty}`;

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
        queryFn: () => api.products({ is_active: 1, per_page: 100 }),
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
        { key: 'min_qty', label: 'Jml min', type: 'number', defaultValue: 1, required: true, min: 1 },
        {
            key: 'discount_percent',
            label: 'Diskon (%)',
            type: 'number',
            defaultValue: 0,
            required: true,
            min: 0,
            max: 100,
        },
    ];

    return (
        <CrudPage<ProductQuantityDiscount>
            eyebrow={eyebrow}
            title="Tier Jumlah"
            cardTitle="Daftar Tier Jumlah"
            cardNote={readOnly ? 'Hanya pemilik dapat mengubah tier.' : undefined}
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
            rowLabel={tierRowLabel}
            editTitle={(tier) => `Ubah ${tierRowLabel(tier)}`}
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
