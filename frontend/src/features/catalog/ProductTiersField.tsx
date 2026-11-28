import type { FormValues } from '../../components/admin/CrudPage';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';

export type ProductTierValue = {
    id?: string;
    min_qty: number | string;
    discount_percent: string;
};

export function ProductTiersField({
    value,
    onChange,
    errors,
    disabled = false,
}: {
    value: ProductTierValue[];
    onChange: (value: ProductTierValue[]) => void;
    errors: Record<string, string>;
    disabled?: boolean;
}) {
    const updateRow = (index: number, patch: Partial<ProductTierValue>) => {
        onChange(value.map((row, position) => (position === index ? { ...row, ...patch } : row)));
    };

    const removeRow = (index: number) => {
        onChange(value.filter((_, position) => position !== index));
    };

    const addRow = () => {
        onChange([...value, { min_qty: '', discount_percent: '' }]);
    };

    return (
        <fieldset className="space-y-3">
            <legend className="text-light-muted block text-[10px] font-semibold tracking-wider uppercase dark:text-gray-400">
                Tier Jumlah
            </legend>
            {value.length === 0 ? (
                <p className="text-light-muted text-xs dark:text-gray-400">Belum ada tier.</p>
            ) : null}
            {value.map((row, index) => (
                <div key={row.id ?? index} className="border-light-border grid gap-3 rounded-xl border p-3 sm:grid-cols-2">
                    <Input
                        label="Jumlah minimum"
                        type="number"
                        min={1}
                        max={100000}
                        value={String(row.min_qty)}
                        error={errors[`tiers.${index}.min_qty`]}
                        disabled={disabled}
                        onChange={(event) => updateRow(index, { min_qty: event.target.value })}
                    />
                    <div className="flex items-end gap-2">
                        <div className="flex-1">
                            <Input
                                label="Diskon"
                                type="number"
                                min={0}
                                max={100}
                                value={String(row.discount_percent)}
                                error={errors[`tiers.${index}.discount_percent`]}
                                disabled={disabled}
                                onChange={(event) => updateRow(index, { discount_percent: event.target.value })}
                            />
                        </div>
                        {disabled ? null : (
                            <Button variant="secondary" onClick={() => removeRow(index)}>
                                Hapus tier
                            </Button>
                        )}
                    </div>
                </div>
            ))}
            {disabled ? null : (
                <Button variant="secondary" onClick={addRow}>
                    Tambah tier
                </Button>
            )}
        </fieldset>
    );
}

export function productPayload(values: FormValues): Record<string, unknown> {
    const rows = Array.isArray(values.tiers) ? (values.tiers as ProductTierValue[]) : [];

    return {
        name: values.name,
        product_category_id: values.product_category_id,
        selling_unit: values.selling_unit,
        is_active: Boolean(values.is_active),
        requires_material: Boolean(values.requires_material),
        uses_attributes: Boolean(values.uses_attributes),
        material_ids: Array.isArray(values.material_ids) ? values.material_ids : [],
        attribute_type_ids: Array.isArray(values.attribute_type_ids) ? values.attribute_type_ids : [],
        attribute_ids: Array.isArray(values.attribute_ids) ? values.attribute_ids : [],
        tiers: rows.map((row) => ({
            ...(row.id ? { id: String(row.id) } : {}),
            min_qty: Number(row.min_qty),
            discount_percent: String(row.discount_percent),
        })),
    };
}
