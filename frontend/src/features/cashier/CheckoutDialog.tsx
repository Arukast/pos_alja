import { useMutation, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';

import { api } from '../../api/queries';
import type { Invoice } from '../../api/types';
import { Button } from '../../components/ui/Button';
import { Input } from '../../components/ui/Input';
import { Modal } from '../../components/ui/Modal';
import { Select } from '../../components/ui/Select';
import { errorMessage, mapApiErrors } from '../../lib/errors';
import { paymentMethodLabel } from '../../lib/labels';
import { formatMoney } from '../../lib/money';

type Mode = 'down_payment' | 'settlement';

const modes: { value: Mode; label: string }[] = [
    { value: 'down_payment', label: 'DP' },
    { value: 'settlement', label: 'Pelunasan' },
];

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
        setNotice(null);
        if (mode === 'down_payment') {
            downPayment.mutate();
        } else {
            settle.mutate();
        }
    };

    const switchMode = (next: Mode) => {
        setMode(next);
        setErrors({});
        setNotice(null);
    };

    // Display only: the server recomputes the settlement amount from receivable minus discount.
    const receivable = Number(invoice.accounts_receivable);
    const discountValue = Number(discount) || 0;
    const settlementAmount = Math.max(0, receivable - discountValue);
    const cashValue = Number(cashReceived);
    // A discount larger than the receivable is rejected by the server, so there is no honest change to show.
    const change =
        cashReceived !== '' && Number.isFinite(cashValue) && discountValue <= receivable
            ? cashValue - settlementAmount
            : null;

    return (
        <Modal
            open
            onClose={onClose}
            title={`Pembayaran ${invoice.invoice_number}`}
            description={invoice.customer?.name ?? undefined}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Batal
                    </Button>
                    <Button
                        loading={downPayment.isPending || settle.isPending}
                        onClick={submit}
                    >
                        Konfirmasi
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

                <dl className="space-y-2 rounded-xl border border-light-border bg-light-surface-2 px-4 py-3 text-sm dark:border-surface-300 dark:bg-surface-600">
                    <div className="flex items-center justify-between gap-4">
                        <dt className="text-light-muted-strong dark:text-gray-400">Total</dt>
                        <dd className="text-light-text font-semibold tabular-nums dark:text-gray-100">
                            {formatMoney(invoice.grand_total)}
                        </dd>
                    </div>
                    {Number(invoice.dp_amount) > 0 ? (
                        <div className="flex items-center justify-between gap-4">
                            <dt className="text-light-muted-strong dark:text-gray-400">DP</dt>
                            <dd className="text-light-text font-semibold tabular-nums dark:text-gray-100">
                                {formatMoney(invoice.dp_amount)}
                            </dd>
                        </div>
                    ) : null}
                    <div className="flex items-center justify-between gap-4 border-t border-light-border pt-2 dark:border-surface-300">
                        <dt className="text-light-text font-medium dark:text-gray-100">Sisa Tagihan</dt>
                        <dd className="text-light-text font-semibold tabular-nums dark:text-gray-100">
                            {formatMoney(invoice.accounts_receivable)}
                        </dd>
                    </div>
                </dl>

                <div
                    role="group"
                    aria-label="Jenis pembayaran"
                    className="inline-flex rounded-xl border border-light-border bg-light-surface-2 p-1 dark:border-surface-300 dark:bg-surface-600"
                >
                    {modes.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            aria-pressed={mode === option.value}
                            onClick={() => switchMode(option.value)}
                            className={
                                mode === option.value
                                    ? 'bg-light-surface text-light-text rounded-lg px-4 py-1.5 text-sm font-semibold shadow-sm dark:bg-surface-500 dark:text-gray-100'
                                    : 'text-light-muted hover:text-light-text rounded-lg px-4 py-1.5 text-sm font-medium transition dark:text-gray-400 dark:hover:text-gray-100'
                            }
                        >
                            {option.label}
                        </button>
                    ))}
                </div>

                {mode === 'down_payment' ? (
                    <Input
                        label="Jumlah Pembayaran"
                        type="number"
                        min="0"
                        inputMode="numeric"
                        className="text-right tabular-nums"
                        value={amount}
                        error={errors.amount}
                        onChange={(event) => setAmount(event.target.value)}
                    />
                ) : (
                    <>
                        <Input
                            label="Diskon"
                            type="number"
                            min="0"
                            inputMode="numeric"
                            className="text-right tabular-nums"
                            value={discount}
                            error={errors.discount}
                            onChange={(event) => setDiscount(event.target.value)}
                        />
                        <Input
                            label="Uang diterima"
                            type="number"
                            min="0"
                            inputMode="numeric"
                            className="text-right tabular-nums"
                            value={cashReceived}
                            error={errors.cash_received}
                            onChange={(event) => setCashReceived(event.target.value)}
                            hint="Opsional."
                        />
                        {change !== null ? (
                            <div className="flex items-center justify-between rounded-xl border border-light-border bg-light-surface-2 px-4 py-2.5 text-sm dark:border-surface-300 dark:bg-surface-600">
                                <span
                                    className={
                                        change < 0
                                            ? 'text-red-700 dark:text-red-300'
                                            : 'text-light-muted-strong dark:text-gray-400'
                                    }
                                >
                                    {change < 0 ? 'Kurang' : 'Kembalian'}
                                </span>
                                <span
                                    className={
                                        change < 0
                                            ? 'text-red-700 font-semibold tabular-nums dark:text-red-300'
                                            : 'text-light-text font-semibold tabular-nums dark:text-gray-100'
                                    }
                                >
                                    {formatMoney(Math.abs(change))}
                                </span>
                            </div>
                        ) : null}
                    </>
                )}

                <Select
                    label="Metode pembayaran"
                    value={method}
                    onChange={(event) => setMethod(event.target.value)}
                >
                    <option value="Cash">{paymentMethodLabel('Cash')}</option>
                    <option value="Transfer">{paymentMethodLabel('Transfer')}</option>
                    <option value="QRIS">{paymentMethodLabel('QRIS')}</option>
                </Select>
            </div>
        </Modal>
    );
}
