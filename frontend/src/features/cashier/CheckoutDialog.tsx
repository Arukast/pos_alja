import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Invoice } from '../../api/types';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Select } from '../../components/ui/Select';
import { Toast } from '../../components/ui/Toast';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { formatMoney } from '../../lib/money';

type Mode = 'down_payment' | 'settlement';

export function CheckoutDialog({
    invoice,
    onClose,
}: {
    invoice: Invoice;
    onClose: () => void;
}) {
    const queryClient = useQueryClient();
    const [mode, setMode] = useState<Mode>(invoice.status === 0 ? 'down_payment' : 'settlement');
    const [amount, setAmount] = useState('');
    const [discount, setDiscount] = useState('0');
    const [cashReceived, setCashReceived] = useState('');
    const [method, setMethod] = useState('Cash');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [notice, setNotice] = useState<string | null>(null);

    const refresh = () => {
        void queryClient.invalidateQueries({ queryKey: ['invoices'] });
        void queryClient.invalidateQueries({ queryKey: ['cashier', 'invoices'] });
        void queryClient.invalidateQueries({ queryKey: ['designer', 'invoices'] });
        void queryClient.invalidateQueries({ queryKey: ['cashier', 'pickup'] });
        void queryClient.invalidateQueries({ queryKey: ['dashboard'] });
    };

    const downPayment = useMutation({
        mutationFn: () =>
            api.downPayment(invoice.id, {
                amount: Number(amount),
                payment_method: method,
            }),
        onSuccess: () => {
            refresh();
            onClose();
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const settle = useMutation({
        mutationFn: () =>
            api.settle(invoice.id, {
                discount: Number(discount),
                payment_method: method,
                cash_received: cashReceived === '' ? null : Number(cashReceived),
            }),
        onSuccess: () => {
            refresh();
            onClose();
        },
        onError: (error) => {
            setErrors(mapApiErrors(error));
            setNotice(errorMessage(error));
        },
    });

    const submit = () => {
        setErrors({});
        if (mode === 'down_payment') {
            downPayment.mutate();
        } else {
            settle.mutate();
        }
    };

    return (
        <>
            <Modal open onClose={onClose} title={`Checkout ${invoice.invoice_number}`}>
                <div className="space-y-4">
                    <div className="grid gap-2 text-sm sm:grid-cols-2">
                        <span className="text-light-muted dark:text-gray-400">Grand total</span>
                        <span className="text-right font-medium">{formatMoney(invoice.grand_total)}</span>
                        <span className="text-light-muted dark:text-gray-400">Receivable</span>
                        <span className="text-right font-medium">
                            {formatMoney(invoice.accounts_receivable)}
                        </span>
                    </div>

                    <div className="flex gap-2">
                        <Button
                            variant={mode === 'down_payment' ? 'primary' : 'secondary'}
                            size="sm"
                            onClick={() => setMode('down_payment')}
                        >
                            Down payment
                        </Button>
                        <Button
                            variant={mode === 'settlement' ? 'primary' : 'secondary'}
                            size="sm"
                            onClick={() => setMode('settlement')}
                        >
                            Settle
                        </Button>
                    </div>

                    {mode === 'down_payment' ? (
                        <Input
                            label="Amount"
                            type="number"
                            min="0"
                            value={amount}
                            error={errors.amount}
                            onChange={(event) => setAmount(event.target.value)}
                        />
                    ) : (
                        <>
                            <Input
                                label="Discount"
                                type="number"
                                min="0"
                                value={discount}
                                error={errors.discount}
                                onChange={(event) => setDiscount(event.target.value)}
                            />
                            <Input
                                label="Cash received"
                                type="number"
                                min="0"
                                value={cashReceived}
                                error={errors.cash_received}
                                onChange={(event) => setCashReceived(event.target.value)}
                                hint="Optional, used for change display."
                            />
                        </>
                    )}

                    <Select
                        label="Payment method"
                        value={method}
                        onChange={(event) => setMethod(event.target.value)}
                    >
                        <option value="Cash">Cash</option>
                        <option value="Transfer">Transfer</option>
                        <option value="QRIS">QRIS</option>
                    </Select>

                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button
                            loading={downPayment.isPending || settle.isPending}
                            onClick={submit}
                        >
                            Confirm
                        </Button>
                    </div>
                </div>
            </Modal>
            {notice ? <Toast message={notice} tone="error" onClose={() => setNotice(null)} /> : null}
        </>
    );
}
