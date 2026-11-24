import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Material } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { Button } from '../../components/ui/Button';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { Textarea } from '../../components/ui/Textarea';
import { todayIso } from '../../lib/dates';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { formatMoney } from '../../lib/money';
import { purchaseLineSubtotal, purchaseTotal } from '../../lib/purchase';

const MAX_LINES = 50;

interface PurchaseLine {
    material_id: string;
    quantity: string;
    unit_cost: string;
}

function emptyLine(): PurchaseLine {
    return { material_id: '', quantity: '1', unit_cost: '' };
}

function lineSubtotal(line: PurchaseLine): string | null {
    if (!line.material_id || line.quantity === '' || line.unit_cost === '') {
        return null;
    }

    try {
        return purchaseLineSubtotal(line.quantity, line.unit_cost);
    } catch {
        return null;
    }
}

export function PurchaseModal({ onClose }: { onClose: () => void }) {
    const { user } = useAuth();
    const queryClient = useQueryClient();
    const isOwner = user?.role === 'owner';
    const [idempotencyKey, setIdempotencyKey] = useState(() => crypto.randomUUID());
    const [form, setForm] = useState({
        branch_id: '',
        purchased_at: todayIso(),
        supplier: '',
        notes: '',
    });
    const [lines, setLines] = useState<PurchaseLine[]>([emptyLine()]);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);

    const branchesQuery = useQuery({
        queryKey: ['branches', 'selector'],
        queryFn: () => api.branches(),
        // BranchPolicy::viewAny is owner-only, so managers must not list branches.
        enabled: isOwner,
    });

    const materialsQuery = useQuery({
        queryKey: ['materials', 'purchase-options', isOwner ? 'all' : user?.branch?.id ?? 'none'],
        queryFn: async () => {
            const params = isOwner ? { branch_id: 'all' } : {};
            const first = await api.materials({ page: 1, ...params });
            const rest = await Promise.all(
                Array.from({ length: Math.max(first.meta.last_page - 1, 0) }, (_, index) =>
                    api.materials({ page: index + 2, ...params })
                )
            );

            return [...first.data, ...rest.flatMap((page) => page.data)];
        },
    });

    const materials: Material[] = materialsQuery.data ?? [];
    const completedLines = lines.filter((line) => lineSubtotal(line) !== null);
    const total = purchaseTotal(
        completedLines.map((line) => ({ quantity: line.quantity, unit_cost: line.unit_cost }))
    );
    const canSubmit =
        lines.length > 0 &&
        lines.every((line) => lineSubtotal(line) !== null) &&
        (!isOwner || form.branch_id !== '');

    const updateLine = (index: number, patch: Partial<PurchaseLine>) => {
        setLines((current) =>
            current.map((line, lineIndex) => (lineIndex === index ? { ...line, ...patch } : line))
        );
    };

    const addLine = () => {
        setLines((current) =>
            current.length >= MAX_LINES ? current : [...current, emptyLine()]
        );
    };

    const removeLine = (index: number) => {
        setLines((current) =>
            current.length === 1 ? current : current.filter((_, lineIndex) => lineIndex !== index)
        );
    };

    const create = useMutation({
        mutationFn: () =>
            api.createPurchase({
                ...(isOwner ? { branch_id: form.branch_id } : {}),
                purchased_at: form.purchased_at || null,
                supplier: form.supplier.trim() || null,
                notes: form.notes.trim() || null,
                idempotency_key: idempotencyKey,
                items: lines.map((line) => ({
                    material_id: line.material_id,
                    quantity: line.quantity,
                    unit_cost: line.unit_cost,
                })),
            }),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['purchases'] });
            void queryClient.invalidateQueries({ queryKey: ['materials'] });
            void queryClient.invalidateQueries({ queryKey: ['owner', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials', 'ledgers'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'ledgers'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            void queryClient.invalidateQueries({ queryKey: ['production'] });
            setIdempotencyKey(crypto.randomUUID());
            onClose();
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const close = () => {
        if (!create.isPending) {
            onClose();
        }
    };

    return (
        <Modal
            open
            onClose={close}
            title="Catat Pembelian"
            description="Pembelian bahan menambah stok cabang."
            size="lg"
            footer={
                <>
                    <Button variant="secondary" disabled={create.isPending} onClick={close}>
                        Batal
                    </Button>
                    <Button
                        loading={create.isPending}
                        disabled={!canSubmit}
                        onClick={() => create.mutate()}
                    >
                        Simpan
                    </Button>
                </>
            }
        >
            <div className="space-y-4">
                {notice ? (
                    <p
                        role="alert"
                        className="rounded-xl bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300"
                    >
                        {notice}
                    </p>
                ) : null}

                {materialsQuery.isLoading ? (
                    <div role="status" className="flex justify-center py-10">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                ) : materialsQuery.isError ? (
                    <EmptyState
                        title="Pilihan bahan gagal dimuat"
                        description="Periksa koneksi lalu coba lagi."
                        action={
                            <Button variant="secondary" onClick={() => materialsQuery.refetch()}>
                                Coba lagi
                            </Button>
                        }
                    />
                ) : materials.length === 0 ? (
                    <EmptyState
                        title="Belum ada bahan"
                        description="Tambahkan bahan lewat inventaris sebelum mencatat pembelian."
                    />
                ) : (
                    <>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Input
                                label="Tanggal"
                                type="date"
                                max={todayIso()}
                                value={form.purchased_at}
                                error={errors.purchased_at}
                                onChange={(event) =>
                                    setForm({ ...form, purchased_at: event.target.value })
                                }
                            />
                            {isOwner ? (
                                <Select
                                    label="Cabang"
                                    value={form.branch_id}
                                    error={errors.branch_id}
                                    disabled={branchesQuery.isLoading}
                                    onChange={(event) =>
                                        setForm({ ...form, branch_id: event.target.value })
                                    }
                                >
                                    <option value="">
                                        {branchesQuery.isLoading ? 'Memuat cabang...' : 'Pilih cabang'}
                                    </option>
                                    {(branchesQuery.data?.data ?? []).map((branch) => (
                                        <option key={branch.id} value={branch.id}>
                                            {branch.name}
                                        </option>
                                    ))}
                                </Select>
                            ) : null}
                            <Input
                                label="Pemasok"
                                value={form.supplier}
                                error={errors.supplier}
                                onChange={(event) =>
                                    setForm({ ...form, supplier: event.target.value })
                                }
                            />
                        </div>
                        {isOwner && branchesQuery.isError ? (
                            <div className="flex flex-wrap items-center gap-2 text-xs text-red-600 dark:text-red-400">
                                <span>Cabang gagal dimuat.</span>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={() => branchesQuery.refetch()}
                                >
                                    Coba lagi
                                </Button>
                            </div>
                        ) : null}
                        <Textarea
                            label="Catatan"
                            value={form.notes}
                            error={errors.notes}
                            onChange={(event) => setForm({ ...form, notes: event.target.value })}
                        />

                        <div className="space-y-3">
                            <div className="flex items-center justify-between gap-2">
                                <h3 className="text-sm font-semibold">Rincian bahan</h3>
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={lines.length >= MAX_LINES}
                                    onClick={addLine}
                                >
                                    <Plus className="h-4 w-4" aria-hidden="true" />
                                    Tambah baris
                                </Button>
                            </div>

                            {errors.items ? (
                                <p className="text-xs text-red-600 dark:text-red-400">
                                    {errors.items}
                                </p>
                            ) : null}

                            {lines.map((line, index) => {
                                const subtotal = lineSubtotal(line);

                                return (
                                    <div
                                        key={index}
                                        className="space-y-3 rounded-xl border border-light-border p-3 dark:border-surface-300"
                                    >
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="text-xs font-semibold">
                                                Baris {index + 1}
                                            </p>
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                aria-label={`Hapus baris ${index + 1}`}
                                                disabled={lines.length === 1}
                                                onClick={() => removeLine(index)}
                                            >
                                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                                            </Button>
                                        </div>
                                        <div className="grid gap-3 sm:grid-cols-2">
                                            <div className="sm:col-span-2">
                                                <Select
                                                    label="Bahan"
                                                    value={line.material_id}
                                                    error={
                                                        errors[`items.${index}.material_id`]
                                                    }
                                                    onChange={(event) =>
                                                        updateLine(index, {
                                                            material_id: event.target.value,
                                                        })
                                                    }
                                                >
                                                    <option value="">Pilih bahan</option>
                                                    {materials.map((material) => (
                                                        <option key={material.id} value={material.id}>
                                                            {material.name}
                                                        </option>
                                                    ))}
                                                </Select>
                                            </div>
                                            <Input
                                                label="Jumlah"
                                                type="number"
                                                min="0.01"
                                                max="999999.99"
                                                step="0.01"
                                                value={line.quantity}
                                                error={errors[`items.${index}.quantity`]}
                                                onChange={(event) =>
                                                    updateLine(index, {
                                                        quantity: event.target.value,
                                                    })
                                                }
                                            />
                                            <Input
                                                label="Harga Satuan"
                                                type="number"
                                                min="0"
                                                max="9999999.99"
                                                step="0.01"
                                                value={line.unit_cost}
                                                error={errors[`items.${index}.unit_cost`]}
                                                onChange={(event) =>
                                                    updateLine(index, {
                                                        unit_cost: event.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                        <p className="text-light-muted text-right text-sm dark:text-gray-400">
                                            Subtotal{' '}
                                            <span className="text-light-text tabular-nums dark:text-gray-100">
                                                {subtotal ? formatMoney(subtotal) : '-'}
                                            </span>
                                        </p>
                                    </div>
                                );
                            })}

                            <div className="flex items-center justify-between border-t border-light-border pt-3 font-semibold dark:border-surface-300">
                                <span>Total</span>
                                <span className="tabular-nums">{formatMoney(total)}</span>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </Modal>
    );
}
