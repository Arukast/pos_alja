import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Category } from '../../api/types';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeaderCell,
    TableRow,
} from '../../components/ui/Table';
import { Toast } from '../../components/ui/Toast';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { archetypeLabel } from '../../lib/labels';

interface TierDraft {
    min_qty: string;
    discount_percent: string;
}

const archetypeOptions = [
    { value: 'per_piece', label: archetypeLabel('per_piece') },
    { value: 'surface_area', label: archetypeLabel('surface_area') },
    { value: 'linear_dimension', label: archetypeLabel('linear_dimension') },
    { value: 'tiered_volume', label: archetypeLabel('tiered_volume') },
];

function formatTierPercent(value: string): string {
    const numeric = Number(value);

    return Number.isFinite(numeric) ? `${numeric}%` : `${value}%`;
}

function formatDiscountTiers(category: Category): string {
    if (category.discount_tiers.length === 0) {
        return '-';
    }

    return [...category.discount_tiers]
        .sort((left, right) => left.min_qty - right.min_qty)
        .map((tier) => `≥ ${tier.min_qty}: ${formatTierPercent(tier.discount_percent)}`)
        .join(', ');
}

export function CategoriesPage({
    eyebrow,
    queryKeyPrefix,
    readOnly = false,
}: {
    eyebrow: string;
    queryKeyPrefix: 'owner' | 'manager';
    readOnly?: boolean;
}) {
    const queryClient = useQueryClient();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Category | null>(null);
    const [name, setName] = useState('');
    const [archetype, setArchetype] = useState('');
    const [allowedTypes, setAllowedTypes] = useState('');
    const [discountTiers, setDiscountTiers] = useState<TierDraft[]>([]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<{
        message: string;
        tone: 'success' | 'error';
    } | null>(null);

    const queryKey = [queryKeyPrefix, 'categories'] as const;
    const query = useQuery({
        queryKey,
        queryFn: api.categories,
    });

    const invalidate = () => {
        void queryClient.invalidateQueries({ queryKey });
        void queryClient.invalidateQueries({ queryKey: ['categories'] });
        void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const save = useMutation({
        mutationFn: () => {
            const payload = {
                name,
                calculation_archetype: archetype,
                allowed_attribute_types: allowedTypes
                    .split(',')
                    .map((value) => value.trim())
                    .filter(Boolean),
                discount_tiers: discountTiers
                    .filter(
                        (tier) =>
                            tier.min_qty.trim() !== '' || tier.discount_percent.trim() !== ''
                    )
                    .map((tier) => ({
                        min_qty: tier.min_qty.trim() === '' ? '' : Number(tier.min_qty),
                        discount_percent:
                            tier.discount_percent.trim() === ''
                                ? ''
                                : Number(tier.discount_percent),
                    })),
            };

            if (editing) {
                return api.admin.updateCategory(editing.id, payload);
            }

            return api.admin.createCategory(payload);
        },
        onSuccess: () => {
            invalidate();
            setOpen(false);
            setEditing(null);
            setNotice({
                message: editing ? 'Kategori diperbarui.' : 'Kategori ditambahkan.',
                tone: 'success',
            });
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice({ message: errorMessage(error), tone: 'error' });
        },
    });

    const destroy = useMutation({
        mutationFn: (id: string) => api.admin.deleteCategory(id),
        onSuccess: () => {
            invalidate();
            setNotice({ message: 'Kategori dihapus.', tone: 'success' });
        },
        onError: (error) => setNotice({ message: errorMessage(error), tone: 'error' }),
    });

    const openCreate = () => {
        setEditing(null);
        setName('');
        setArchetype('');
        setAllowedTypes('');
        setDiscountTiers([]);
        setErrors({});
        setOpen(true);
    };

    const openEdit = (category: Category) => {
        setEditing(category);
        setName(category.name);
        setArchetype(category.calculation_archetype);
        setAllowedTypes(category.allowed_attribute_types.join(','));
        setDiscountTiers(
            [...category.discount_tiers]
                .sort((left, right) => left.min_qty - right.min_qty)
                .map((tier) => ({
                    min_qty: String(tier.min_qty),
                    discount_percent: String(Number(tier.discount_percent)),
                }))
        );
        setErrors({});
        setOpen(true);
    };

    const addTier = () => {
        setDiscountTiers([...discountTiers, { min_qty: '', discount_percent: '' }]);
    };

    const updateTier = (index: number, field: keyof TierDraft, value: string) => {
        setDiscountTiers(
            discountTiers.map((tier, rowIndex) =>
                rowIndex === index ? { ...tier, [field]: value } : tier
            )
        );
    };

    const removeTier = (index: number) => {
        setDiscountTiers(discountTiers.filter((_, rowIndex) => rowIndex !== index));
    };

    const rows = query.data?.data ?? [];

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow={eyebrow}
                title="Kategori Produk"
                description="Arketipe kategori, atribut yang diizinkan, dan diskon kuantitas."
                actions={
                    readOnly ? null : (
                        <Button size="sm" onClick={openCreate}>
                            Tambah kategori
                        </Button>
                    )
                }
            />

            <Card>
                <CardBody>
                    {query.isLoading ? (
                        <div className="flex justify-center py-10">
                            <Spinner className="text-brand-primary h-6 w-6" />
                        </div>
                    ) : query.isError ? (
                        <EmptyState
                            title="Data gagal dimuat"
                            description="Periksa koneksi lalu coba lagi."
                            action={
                                <Button variant="secondary" onClick={() => query.refetch()}>
                                    Coba lagi
                                </Button>
                            }
                        />
                    ) : (
                        <Table ariaLabel="Kategori Produk">
                            <TableHead>
                                <TableRow>
                                    <TableHeaderCell>Nama</TableHeaderCell>
                                    <TableHeaderCell>Arketipe</TableHeaderCell>
                                    <TableHeaderCell className="hidden sm:table-cell">
                                        Atribut yang diizinkan
                                    </TableHeaderCell>
                                    <TableHeaderCell>Diskon Kuantitas</TableHeaderCell>
                                    {readOnly ? null : (
                                        <TableHeaderCell className="bg-light-surface border-light-border sticky right-0 border-l shadow-[-8px_0_12px_-10px_rgb(0_0_0/0.35)] dark:border-surface-300 dark:bg-surface-700">
                                            Aksi
                                        </TableHeaderCell>
                                    )}
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {rows.length === 0 ? (
                                    <TableEmpty colSpan={readOnly ? 4 : 5}>
                                        Belum ada data.
                                    </TableEmpty>
                                ) : (
                                    rows.map((category) => (
                                        <TableRow key={category.id}>
                                            <TableCell>
                                                <span className="font-medium">{category.name}</span>
                                            </TableCell>
                                            <TableCell>
                                                {archetypeLabel(category.calculation_archetype)}
                                            </TableCell>
                                            <TableCell className="hidden sm:table-cell">
                                                {category.allowed_attribute_types.length === 0
                                                    ? 'Semua'
                                                    : category.allowed_attribute_types.join(', ')}
                                            </TableCell>
                                            <TableCell>
                                                {formatDiscountTiers(category)}
                                            </TableCell>
                                            {readOnly ? null : (
                                                <TableCell className="border-light-border bg-light-surface sticky right-0 border-l shadow-[-8px_0_12px_-10px_rgb(0_0_0/0.35)] dark:border-surface-300 dark:bg-surface-700">
                                                    <div className="flex flex-col items-stretch gap-1 sm:flex-row sm:flex-wrap sm:gap-2">
                                                        <Button
                                                            size="sm"
                                                            variant="secondary"
                                                            onClick={() => openEdit(category)}
                                                        >
                                                            Ubah
                                                        </Button>
                                                        <Button
                                                            size="sm"
                                                            variant="danger"
                                                            onClick={() => {
                                                                if (
                                                                    window.confirm(
                                                                        'Hapus kategori ini?'
                                                                    )
                                                                ) {
                                                                    destroy.mutate(category.id);
                                                                }
                                                            }}
                                                        >
                                                            Hapus
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    )}
                </CardBody>
            </Card>

            {readOnly ? null : (
                <Modal
                    open={open}
                    onClose={() => {
                        setOpen(false);
                        setEditing(null);
                    }}
                    title={editing ? 'Ubah kategori' : 'Tambah kategori'}
                >
                    <div className="space-y-4">
                        <Input
                            label="Nama"
                            value={name}
                            error={errors.name}
                            onChange={(event) => setName(event.target.value)}
                        />
                        <Select
                            label="Arketipe"
                            value={archetype}
                            error={errors.calculation_archetype}
                            onChange={(event) => setArchetype(event.target.value)}
                        >
                            <option value="">Pilih</option>
                            {archetypeOptions.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </Select>
                        <Input
                            label="Jenis atribut yang diizinkan (pisahkan dengan koma)"
                            value={allowedTypes}
                            error={errors.allowed_attribute_types}
                            onChange={(event) => setAllowedTypes(event.target.value)}
                        />

                        <div className="space-y-3 border-t border-light-border pt-3 dark:border-surface-300">
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-light-muted text-[10px] font-semibold uppercase tracking-widest dark:text-gray-400">
                                    Tingkat Diskon Kuantitas
                                </span>
                                <Button size="sm" variant="secondary" onClick={addTier}>
                                    Tambah tingkat
                                </Button>
                            </div>

                            {errors.discount_tiers ? (
                                <p className="text-xs text-red-600 dark:text-red-400">
                                    {errors.discount_tiers}
                                </p>
                            ) : null}

                            {discountTiers.length === 0 ? (
                                <p className="text-light-muted text-xs dark:text-gray-400">
                                    Belum ada tingkat diskon kuantitas.
                                </p>
                            ) : (
                                <div className="space-y-2">
                                    {discountTiers.map((tier, index) => (
                                        <div
                                            key={index}
                                            className="grid grid-cols-1 gap-2 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                                        >
                                            <Input
                                                label="Jml minimum"
                                                type="number"
                                                min="1"
                                                value={tier.min_qty}
                                                error={errors[`discount_tiers.${index}.min_qty`]}
                                                onChange={(event) =>
                                                    updateTier(index, 'min_qty', event.target.value)
                                                }
                                            />
                                            <Input
                                                label="Diskon (%)"
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={tier.discount_percent}
                                                error={
                                                    errors[
                                                        `discount_tiers.${index}.discount_percent`
                                                    ]
                                                }
                                                onChange={(event) =>
                                                    updateTier(
                                                        index,
                                                        'discount_percent',
                                                        event.target.value
                                                    )
                                                }
                                            />
                                            <Button
                                                size="sm"
                                                variant="danger"
                                                onClick={() => removeTier(index)}
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="flex justify-end gap-2">
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    setOpen(false);
                                    setEditing(null);
                                }}
                            >
                                Batal
                            </Button>
                            <Button loading={save.isPending} onClick={() => save.mutate()}>
                                Simpan
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}

            {notice ? (
                <Toast
                    message={notice.message}
                    tone={notice.tone}
                    onClose={() => setNotice(null)}
                />
            ) : null}
        </div>
    );
}
