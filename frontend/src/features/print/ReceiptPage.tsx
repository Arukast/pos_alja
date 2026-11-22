import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router';

import { api } from '../../api/queries';
import { Button } from '../../components/ui/Button';
import { Spinner } from '../../components/ui/Spinner';
import { formatDate } from '../../lib/dates';
import { paymentMethodLabel } from '../../lib/labels';
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
                <p>Tidak dapat memuat nota.</p>
                <Button className="mt-4" onClick={() => navigate(-1)}>
                    Kembali
                </Button>
            </div>
        );
    }

    return (
        <main className="print-backdrop min-h-screen p-4 text-white sm:p-6 print:min-h-0 print:p-0">
            <h1 className="sr-only">Nota</h1>
            <div className="no-print mx-auto mb-4 flex max-w-[80mm] justify-end gap-2">
                <Button variant="secondary" onClick={() => navigate(-1)}>
                    Kembali
                </Button>
                <Button onClick={() => window.print()}>Cetak nota</Button>
            </div>

            <article className="mx-auto w-[80mm] break-words bg-white p-4 text-[11px] leading-snug text-black shadow-2xl print:shadow-none">
                <style>{`@media print { .no-print { display: none !important; } @page { size: 80mm auto; margin: 0; } }`}</style>
                <header className="text-center">
                    <p className="text-sm font-bold uppercase tracking-[0.2em]">ALJA DIGITAL PRINTING</p>
                    {invoice.branch?.name ? <p>{invoice.branch.name}</p> : null}
                    {invoice.branch?.phone ? <p>{invoice.branch.phone}</p> : null}
                </header>

                <div className="my-2 border-y border-dashed border-black py-1.5">
                    <p>Invoice: {invoice.invoice_number}</p>
                    <p>Tanggal: {formatDate(invoice.created_date)}</p>
                    <p>Pelanggan: {invoice.customer?.name ?? '-'}</p>
                </div>

                <table className="w-full table-fixed border-collapse">
                    <thead>
                        <tr className="border-b border-black text-left">
                            <th className="py-0.5">Item</th>
                            <th className="w-7 py-0.5 text-right">Jml</th>
                            <th className="w-[84px] py-0.5 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {(invoice.orders ?? []).length === 0 ? (
                            <tr>
                                <td colSpan={3} className="py-1 text-center">
                                    Belum ada item.
                                </td>
                            </tr>
                        ) : (
                            (invoice.orders ?? []).map((order) => (
                                <tr key={order.id} className="align-top">
                                    <td className="py-0.5 pr-1">{order.file_name ?? 'Item'}</td>
                                    <td className="py-0.5 text-right whitespace-nowrap tabular-nums">
                                        {order.quantity}
                                    </td>
                                    <td className="py-0.5 text-right whitespace-nowrap tabular-nums">
                                        {order.subtotal === undefined ? '-' : formatMoney(order.subtotal)}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>

                <div className="mt-2 space-y-0.5 border-t border-dashed border-black pt-2">
                    <p className="flex justify-between gap-2">
                        <span>Total</span>
                        <span className="text-right tabular-nums">{formatMoney(invoice.grand_total)}</span>
                    </p>
                    <p className="flex justify-between gap-2">
                        <span>Diskon</span>
                        <span className="text-right tabular-nums">{formatMoney(invoice.discount)}</span>
                    </p>
                    <p className="flex justify-between gap-2">
                        <span>Dibayar</span>
                        <span className="text-right tabular-nums">
                            {formatMoney(
                                (invoice.payments ?? []).reduce(
                                    (total, payment) => total + Number(payment.amount),
                                    0
                                )
                            )}
                        </span>
                    </p>
                    <p className="flex justify-between gap-2 border-t border-dashed border-black pt-1 font-bold">
                        <span>Sisa Tagihan</span>
                        <span className="text-right tabular-nums">
                            {formatMoney(invoice.accounts_receivable)}
                        </span>
                    </p>
                </div>

                <div className="mt-2 border-t border-dashed border-black pt-2">
                    <p className="font-semibold">Pembayaran</p>
                    {(invoice.payments ?? []).length === 0 ? (
                        <p>Belum ada pembayaran.</p>
                    ) : (
                        <ul className="space-y-0.5">
                            {(invoice.payments ?? []).map((payment) => (
                                <li key={payment.id} className="flex justify-between gap-2">
                                    <span>
                                        <span className="font-semibold">
                                            {payment.type === 'down_payment' ? 'DP' : 'Pelunasan'}
                                        </span>{' '}
                                        · {paymentMethodLabel(payment.payment_method)}
                                    </span>
                                    <span className="text-right whitespace-nowrap tabular-nums">
                                        {formatMoney(payment.amount)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                <footer className="mt-3 border-t border-dashed border-black pt-2 text-center">
                    <p className="font-semibold">
                        Status:{' '}
                        {invoice.status === 2 ? 'LUNAS' : invoice.status === 1 ? 'DP' : 'BELUM DIBAYAR'}
                    </p>
                    <p className="mt-1">Terima kasih atas pesanan Anda.</p>
                </footer>
            </article>
        </main>
    );
}
