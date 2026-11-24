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
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            setOpen(false);
        },
        onError: (error) => setNotice(errorMessage(error)),
    });

    return (
        <>
            <Button size="sm" variant="secondary" onClick={() => setOpen(true)}>
                Tambah Stok
            </Button>
            <Modal open={open} onClose={() => setOpen(false)} title={`Tambah Stok ${material.name}`}>
                <div className="space-y-4">
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
                        min="0"
                        value={quantity}
                        onChange={(event) => setQuantity(event.target.value)}
                    />
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" onClick={() => setOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            loading={restock.isPending}
                            disabled={isOwner && branchId === ''}
                            onClick={() => restock.mutate()}
                        >
                            Tambah Stok
                        </Button>
                    </div>
                </div>
            </Modal>
            {notice ? <p className="text-sm text-red-600 dark:text-red-400">{notice}</p> : null}
        </>
    );
}
