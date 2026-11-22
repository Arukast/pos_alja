import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Material } from '../../api/types';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { errorMessage } from '../../lib/errors';

export function RestockMaterialButton({ material }: { material: Material }) {
    const queryClient = useQueryClient();
    const [open, setOpen] = useState(false);
    const [quantity, setQuantity] = useState('1');
    const [notice, setNotice] = useState<string | null>(null);

    const restock = useMutation({
        mutationFn: () =>
            api.admin.restockMaterial(material.id, {
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
                        <Button loading={restock.isPending} onClick={() => restock.mutate()}>
                            Tambah Stok
                        </Button>
                    </div>
                </div>
            </Modal>
            {notice ? <p className="text-sm text-red-600 dark:text-red-400">{notice}</p> : null}
        </>
    );
}
