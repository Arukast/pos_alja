import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Material } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Select } from '../../components/ui/Select';
import { errorMessage } from '../../lib/errors';

const MAX_RESTOCK = 999999.99;

export function RestockMaterialButton({ material }: { material: Material }) {
    const { user } = useAuth();
    const queryClient = useQueryClient();
    const isOwner = user?.role === 'owner';
    const [open, setOpen] = useState(false);
    const [quantity, setQuantity] = useState('1');
    const [branchId, setBranchId] = useState('');
    const [notice, setNotice] = useState<string | null>(null);

    const restock = useMutation({
        mutationFn: () =>
            api.admin.restockMaterial(material.id, {
                ...(isOwner ? { branch_id: branchId } : {}),
                quantity: Number(quantity),
                notes: 'Restocked from admin screen',
            }),
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['owner', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials', 'ledgers'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'ledgers'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setOpen(false);
        },
        onError: (error) => setNotice(errorMessage(error)),
    });

    const parsedQuantity = Number(quantity);
    const canSubmit =
        parsedQuantity > 0 &&
        parsedQuantity <= MAX_RESTOCK &&
        (!isOwner || branchId !== '');

    const close = () => {
        if (!restock.isPending) {
            setOpen(false);
        }
    };

    return (
        <>
            <Button
                size="sm"
                variant="secondary"
                onClick={() => {
                    setNotice(null);
                    setOpen(true);
                }}
            >
                Tambah Stok
            </Button>
            <Modal
                open={open}
                onClose={close}
                title={`Tambah Stok ${material.name}`}
                footer={
                    <>
                        <Button variant="secondary" disabled={restock.isPending} onClick={close}>
                            Batal
                        </Button>
                        <Button
                            loading={restock.isPending}
                            disabled={!canSubmit}
                            onClick={() => restock.mutate()}
                        >
                            Tambah Stok
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
                    {isOwner ? (
                        <Select
                            label="Cabang"
                            value={branchId}
                            onChange={(event) => setBranchId(event.target.value)}
                        >
                            <option value="">Pilih cabang</option>
                            {material.stocks.map((stock) => (
                                <option key={stock.branch_id} value={stock.branch_id}>
                                    {stock.branch_name ?? '-'}
                                </option>
                            ))}
                        </Select>
                    ) : null}
                    <Input
                        label="Jml"
                        type="number"
                        min="0.01"
                        max={String(MAX_RESTOCK)}
                        value={quantity}
                        onChange={(event) => setQuantity(event.target.value)}
                    />
                </div>
            </Modal>
        </>
    );
}
