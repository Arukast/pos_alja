import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Material } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Select } from '../../components/ui/Select';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { formatQuantity } from '../../lib/money';

type AdjustMode = 'new_quantity' | 'delta';

export function AdjustMaterialModal({ material }: { material: Material }) {
    const { user } = useAuth();
    const queryClient = useQueryClient();
    const isOwner = user?.role === 'owner';
    const [open, setOpen] = useState(false);
    const [mode, setMode] = useState<AdjustMode>('new_quantity');
    const [quantity, setQuantity] = useState('');
    const [branchId, setBranchId] = useState('');
    const [reason, setReason] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);

    const branches = useQuery({
        queryKey: ['branches', 'selector'],
        queryFn: () => api.branches(),
        // BranchPolicy::viewAny is owner-only, so managers must not list branches.
        enabled: isOwner && open,
    });

    const adjust = useMutation({
        mutationFn: () =>
            api.adjustMaterialStock(material.id, {
                ...(isOwner ? { branch_id: branchId } : {}),
                [mode]: quantity,
                reason: reason.trim(),
            }),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['owner', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials', 'ledgers'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'ledgers'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            void queryClient.invalidateQueries({ queryKey: ['production'] });
            setQuantity('');
            setReason('');
            setOpen(false);
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const resolvedStock = isOwner
        ? (material.stocks.find((stock) => stock.branch_id === branchId) ?? null)
        : material.stocks.length === 1
          ? material.stocks[0]
          : null;

    const canSubmit =
        quantity.trim() !== '' &&
        reason.trim().length >= 3 &&
        (!isOwner || branchId !== '');

    return (
        <>
            <Button
                size="sm"
                variant="secondary"
                aria-label={`Koreksi stok ${material.name}`}
                onClick={() => {
                    setErrors({});
                    setNotice(null);
                    setOpen(true);
                }}
            >
                Koreksi
            </Button>
            <Modal
                open={open}
                onClose={() => {
                    if (!adjust.isPending) {
                        setOpen(false);
                    }
                }}
                title={`Koreksi Stok ${material.name}`}
                description="Penyesuaian stok cabang dengan alasan yang tercatat."
                footer={
                    <>
                        <Button
                            variant="secondary"
                            disabled={adjust.isPending}
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            loading={adjust.isPending}
                            disabled={!canSubmit}
                            onClick={() => adjust.mutate()}
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

                    <div className="grid grid-cols-2 gap-3 rounded-xl border border-light-border bg-light-surface-2/60 px-3 py-2 dark:border-surface-300 dark:bg-surface-600/60">
                        <div>
                            <p className="text-light-muted text-[10px] font-semibold uppercase tracking-wider dark:text-gray-400">
                                Stok saat ini
                            </p>
                            <p className="font-semibold tabular-nums">
                                {formatQuantity(resolvedStock?.current_stock)}
                            </p>
                        </div>
                        <div>
                            <p className="text-light-muted text-[10px] font-semibold uppercase tracking-wider dark:text-gray-400">
                                Minimum
                            </p>
                            <p className="font-semibold tabular-nums">
                                {formatQuantity(resolvedStock?.min_stock)}
                            </p>
                        </div>
                    </div>

                    <Select
                        label="Jenis koreksi"
                        hint="Selisih positif menambah stok, negatif mengurangi."
                        value={mode}
                        disabled={adjust.isPending}
                        onChange={(event) => {
                            setQuantity('');
                            setMode(event.target.value as AdjustMode);
                        }}
                    >
                        <option value="new_quantity">Jumlah baru</option>
                        <option value="delta">Selisih</option>
                    </Select>

                    {isOwner ? (
                        <Select
                            label="Cabang"
                            value={branchId}
                            error={errors.branch_id}
                            disabled={branches.isLoading}
                            onChange={(event) => setBranchId(event.target.value)}
                        >
                            <option value="">
                                {branches.isLoading ? 'Memuat cabang...' : 'Pilih cabang'}
                            </option>
                            {(branches.data?.data ?? []).map((branch) => (
                                <option key={branch.id} value={branch.id}>
                                    {branch.name}
                                </option>
                            ))}
                        </Select>
                    ) : null}

                    {isOwner && branches.isError ? (
                        <div className="flex flex-wrap items-center gap-2 text-xs text-red-600 dark:text-red-400">
                            <span>Cabang gagal dimuat.</span>
                            <Button variant="secondary" size="sm" onClick={() => branches.refetch()}>
                                Coba lagi
                            </Button>
                        </div>
                    ) : null}

                    <Input
                        label={mode === 'new_quantity' ? 'Jumlah baru' : 'Selisih (+/-)'}
                        type="number"
                        step="0.01"
                        min={mode === 'new_quantity' ? '0' : undefined}
                        value={quantity}
                        error={errors[mode]}
                        onChange={(event) => setQuantity(event.target.value)}
                    />

                    <Input
                        label="Alasan"
                        hint="Wajib diisi, 3-255 karakter."
                        maxLength={255}
                        value={reason}
                        error={errors.reason}
                        onChange={(event) => setReason(event.target.value)}
                    />
                </div>
            </Modal>
        </>
    );
}
