import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router';

import { api } from '../../api/queries';
import { Button } from '../../components/ui/Button';
import { Spinner } from '../../components/ui/Spinner';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

export function ReceiptPage() {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const query = useQuery({ queryKey: ['invoice', 'receipt', id], queryFn: () => api.receipt(id) });
    const invoice = query.data?.invoice;

    if (query.isLoading) {
        return (
            <div className="flex min-h-screen items-center justify-center">
                <Spinner className="text-brand-primary h-6 w-6" />
            </div>
        );
    }

    if (!invoice) {
        return (
            <div className="p-8">
                <p>Unable to load the receipt.</p>
                <Button className="mt-4" onClick={() => navigate(-1)}>
                    Back
                </Button>
            </div>
        );
    }

    return (
        <main className="min-h-screen bg-gray-100 p-6">
            <div className="no-print mx-auto mb-4 flex max-w-[80mm] justify-end gap-2">
                <Button variant="secondary" onClick={() => navigate(-1)}>
                    Back
                </Button>
                <Button onClick={() => window.print()}>Print receipt</Button>
            </div>

            <article className="mx-auto w-[80mm] bg-white p-4 text-[11px] text-black shadow print:shadow-none">
                <style>{`@media print { .no-print { display: none !important; } @page { size: 80mm auto; margin: 0; } }`}</style>
                <header className="text-center">
                    <p className="text-base font-bold">ALJA DIGITAL PRINTING</p>
                    <p>{invoice.branch?.name ?? '-'}</p>
                    <p>{invoice.branch?.phone ?? '-'}</p>
                </header>

                <div className="my-2 border-y border-dashed border-black py-1">
                    <p>Invoice: {invoice.invoice_number}</p>
                    <p>Date: {formatDate(invoice.created_date)}</p>
                    <p>Customer: {invoice.customer?.name ?? '-'}</p>
                </div>

                <table className="w-full border-collapse">
                    <thead>
                        <tr className="border-b border-black text-left">
                            <th>Item</th>
                            <th className="text-right">Qty</th>
                            <th className="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {(invoice.orders ?? []).map((order) => (
                            <tr key={order.id}>
                                <td>{order.file_name ?? 'Item'}</td>
                                <td className="text-right">{order.quantity}</td>
                                <td className="text-right">{formatMoney(order.subtotal ?? '0')}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                <div className="mt-2 space-y-1 border-t border-dashed border-black pt-2">
                    <p className="flex justify-between">
                        <span>Grand total</span>
                        <span>{formatMoney(invoice.grand_total)}</span>
                    </p>
                    <p className="flex justify-between">
                        <span>Discount</span>
                        <span>{formatMoney(invoice.discount)}</span>
                    </p>
                    <p className="flex justify-between">
                        <span>Paid</span>
                        <span>
                            {formatMoney(
                                (invoice.payments ?? []).reduce(
                                    (total, payment) => total + Number(payment.amount),
                                    0
                                )
                            )}
                        </span>
                    </p>
                    <p className="flex justify-between font-bold">
                        <span>Receivable</span>
                        <span>{formatMoney(invoice.accounts_receivable)}</span>
                    </p>
                </div>

                <div className="mt-2 border-t border-dashed border-black pt-2">
                    <p className="font-semibold">Payments</p>
                    {(invoice.payments ?? []).length === 0 ? (
                        <p>No payments yet.</p>
                    ) : (
                        (invoice.payments ?? []).map((payment) => (
                            <p key={payment.id} className="flex justify-between">
                                <span>
                                    {payment.type === 'down_payment' ? 'DP' : 'Settlement'} (
                                    {payment.payment_method})
                                </span>
                                <span>{formatMoney(payment.amount)}</span>
                            </p>
                        ))
                    )}
                </div>

                <footer className="mt-4 border-t border-dashed border-black pt-2 text-center">
                    <p>Status: {invoice.status === 2 ? 'PAID' : invoice.status === 1 ? 'DP' : 'UNPAID'}</p>
                    <p className="mt-1">Thank you for your order.</p>
                </footer>
            </article>
        </main>
    );
}
