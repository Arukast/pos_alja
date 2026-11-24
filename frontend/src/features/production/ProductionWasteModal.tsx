import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { ProductionQueueItem } from '../../api/types';
import { Button } from '../../components/ui/Button';
import { EmptyState } from '../../components/ui/EmptyState';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { errorMessage, mapApiErrors } from '../../lib/errors';

const emptyForm = {
    material_id: '',
    designer_id: '',
    quantity: '1',
    dimensions: '',
    notes: '',
};

export function ProductionWasteModal({
    order,
    onClose,
}: {
    order: ProductionQueueItem | null;
    onClose: () => void;
}) {
    const queryClient = useQueryClient();
    const [form, setForm] = useState(emptyForm);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);

    const orderId = order?.id ?? null;

    const optionsQuery = useQuery({
        queryKey: ['waste', 'options'],
        queryFn: api.wasteOptions,
        enabled: orderId !== null,
    });

    const create = useMutation({
        mutationFn: () => {
            if (!orderId) {
                throw new Error('Tidak ada pesanan dipilih.');
            }

            return api.createWaste({
                order_id: orderId,
                material_id: form.material_id,
                quantity: Number(form.quantity),
                dimensions: form.dimensions || null,
                designer_id: form.designer_id || null,
                notes: form.notes || null,
            });
        },
        onSuccess: () => {
            void queryClient.invalidateQueries({ queryKey: ['production'] });
            void queryClient.invalidateQueries({ queryKey: ['waste'] });
            void queryClient.invalidateQueries({ queryKey: ['materials'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            void queryClient.invalidateQueries({ queryKey: ['stock-ledgers'] });
            onClose();
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const materials = optionsQuery.data?.materials ?? [];
    const designers = optionsQuery.data?.designers ?? [];
    const canSubmit = optionsQuery.isSuccess && materials.length > 0;

    return (
        <Modal
            open={order !== null}
            onClose={onClose}
            title="Catat Sisa Bahan"
            description={order?.invoice ? `Invoice ${order.invoice.invoice_number}` : undefined}
            footer={
                canSubmit ? (
                    <>
                        <Button variant="secondary" onClick={onClose}>
                            Batal
                        </Button>
                        <Button
                            disabled={!form.material_id}
                            loading={create.isPending}
                            onClick={() => create.mutate()}
                        >
                            Simpan
                        </Button>
                    </>
                ) : undefined
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
                {optionsQuery.isLoading ? (
                    <div role="status" className="flex justify-center py-10">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                ) : optionsQuery.isError ? (
                    <EmptyState
                        title="Pilihan bahan gagal dimuat"
                        description="Periksa koneksi lalu coba lagi."
                        action={
                            <Button variant="secondary" onClick={() => optionsQuery.refetch()}>
                                Coba lagi
                            </Button>
                        }
                    />
                ) : materials.length === 0 ? (
                    <EmptyState
                        title="Belum ada bahan di cabang ini"
                        description="Tambahkan stok bahan lewat inventaris sebelum mencatat sisa."
                    />
                ) : (
                    <>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <Select
                                label="Bahan"
                                value={form.material_id}
                                error={errors.material_id}
                                onChange={(event) => setForm({ ...form, material_id: event.target.value })}
                            >
                                <option value="">Pilih bahan</option>
                                {materials.map((material) => (
                                    <option key={material.id} value={material.id}>
                                        {material.name}
                                    </option>
                                ))}
                            </Select>
                            <Select
                                label="Desainer"
                                value={form.designer_id}
                                error={errors.designer_id}
                                onChange={(event) => setForm({ ...form, designer_id: event.target.value })}
                            >
                                <option value="">Tanpa desainer</option>
                                {designers.map((designer) => (
                                    <option key={designer.id} value={designer.id}>
                                        {designer.name}
                                    </option>
                                ))}
                            </Select>
                            <Input
                                label="Jml"
                                type="number"
                                min="0.01"
                                step="any"
                                value={form.quantity}
                                error={errors.quantity}
                                onChange={(event) => setForm({ ...form, quantity: event.target.value })}
                            />
                            <Input
                                label="Dimensi"
                                value={form.dimensions}
                                error={errors.dimensions}
                                onChange={(event) => setForm({ ...form, dimensions: event.target.value })}
                            />
                        </div>
                        <Input
                            label="Catatan"
                            value={form.notes}
                            error={errors.notes}
                            onChange={(event) => setForm({ ...form, notes: event.target.value })}
                        />
                    </>
                )}
            </div>
        </Modal>
    );
}
