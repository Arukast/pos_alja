import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { ProductionQueueItem } from '../../api/types';
import { Button } from '../../components/ui/Button';
import { Modal } from '../../components/ui/Modal';
import { Textarea } from '../../components/ui/Textarea';
import { errorMessage, mapApiErrors } from '../../lib/errors';

export function FailProductionModal({
    order,
    onClose,
    onFailed,
}: {
    order: ProductionQueueItem | null;
    onClose: () => void;
    onFailed: (order: ProductionQueueItem) => void;
}) {
    const queryClient = useQueryClient();
    const [note, setNote] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);

    const orderId = order?.id ?? null;

    const fail = useMutation({
        mutationFn: () => {
            if (!orderId) {
                throw new Error('Tidak ada pesanan dipilih.');
            }

            return api.failOrder(orderId, { note: note.trim() });
        },
        onSuccess: (data) => {
            void queryClient.invalidateQueries({ queryKey: ['production'] });
            void queryClient.invalidateQueries({ queryKey: ['waste'] });
            void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
            void queryClient.invalidateQueries({ queryKey: ['materials'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'materials', 'ledgers'] });
            void queryClient.invalidateQueries({ queryKey: ['manager', 'ledgers'] });
            onFailed(data.order);
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const canSubmit = note.trim().length >= 3;

    const close = () => {
        if (!fail.isPending) {
            onClose();
        }
    };

    return (
        <Modal
            open={order !== null}
            onClose={close}
            title="Tandai Pesanan Gagal"
            description={order?.invoice ? `Invoice ${order.invoice.invoice_number}` : undefined}
            initialFocusSelector="textarea"
            footer={
                <>
                    <Button variant="secondary" disabled={fail.isPending} onClick={close}>
                        Batal
                    </Button>
                    <Button
                        variant="danger"
                        disabled={!canSubmit}
                        loading={fail.isPending}
                        onClick={() => fail.mutate()}
                    >
                        Tandai Gagal
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
                <Textarea
                    label="Catatan"
                    hint="Minimal 3 karakter."
                    maxLength={255}
                    value={note}
                    error={errors.note}
                    onChange={(event) => setNote(event.target.value)}
                />
            </div>
        </Modal>
    );
}
