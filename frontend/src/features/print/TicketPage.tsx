import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router';

import { api } from '../../api/queries';
import { LogoMark } from '../../components/LogoMark';
import { Button } from '../../components/ui/Button';
import { Spinner } from '../../components/ui/Spinner';
import { formatDate } from '../../lib/dates';

const productionStages = [
    { status: 0, label: 'Antrean' },
    { status: 1, label: 'Diproses' },
    { status: 2, label: 'Selesai' },
];

export function TicketPage() {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const query = useQuery({ queryKey: ['invoice', 'ticket', id], queryFn: () => api.ticket(id) });
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
                <p>Tidak dapat memuat tiket pekerjaan.</p>
                <Button className="mt-4" onClick={() => navigate(-1)}>
                    Kembali
                </Button>
            </div>
        );
    }

    const orders = invoice.orders ?? [];
    const stageCounts = productionStages.map((stage) => ({
        ...stage,
        count: orders.filter((order) => order.status === stage.status).length,
    }));

    return (
        <main className="print-backdrop min-h-screen p-4 text-white sm:p-8 print:min-h-0 print:p-0">
            <style>{`@media print { .no-print { display: none !important; } @page { size: A4; margin: 12mm; } }`}</style>
            <div className="no-print mx-auto mb-4 flex max-w-[210mm] justify-end gap-2">
                <Button variant="secondary" onClick={() => navigate(-1)}>
                    Kembali
                </Button>
                <Button onClick={() => window.print()}>Cetak tiket</Button>
            </div>

            <div className="mx-auto w-full max-w-[210mm] bg-white text-black shadow-2xl print:max-w-none print:shadow-none">
                <div className="brand-stripe-bar print-color-exact" aria-hidden="true" />
                <div className="p-6 sm:p-8 print:p-0">
                    <header className="flex flex-wrap items-start justify-between gap-6 border-b-2 border-brand-dark pb-4">
                        <div className="flex items-start gap-3">
                            <LogoMark className="h-10 w-10 shrink-0" decorative />
                            <div>
                                <p className="text-xs font-bold uppercase tracking-[0.25em]">
                                    Alja Digital Printing
                                </p>
                                <h1 className="mt-1 text-2xl font-extrabold tracking-tight">
                                    Tiket Perintah Kerja
                                </h1>
                                <p className="text-sm text-gray-600">{invoice.branch?.name ?? '-'}</p>
                            </div>
                        </div>
                        <dl className="w-full space-y-1 text-sm sm:w-64">
                            <div className="flex justify-between gap-4">
                                <dt className="text-gray-600">Invoice</dt>
                                <dd className="text-right font-bold">{invoice.invoice_number}</dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-gray-600">Tenggat</dt>
                                <dd className="text-right font-semibold">
                                    {formatDate(invoice.deadline ?? '-')}
                                </dd>
                            </div>
                            <div className="flex justify-between gap-4">
                                <dt className="text-gray-600">Dibuat</dt>
                                <dd className="text-right">{formatDate(invoice.created_date)}</dd>
                            </div>
                        </dl>
                    </header>

                    <section className="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-600">
                                Pelanggan
                            </p>
                            <p className="text-base font-semibold">{invoice.customer?.name ?? '-'}</p>
                            <p className="text-gray-600">{invoice.customer?.phone ?? '-'}</p>
                        </div>
                        <div>
                            <p className="text-[10px] font-semibold uppercase tracking-wider text-gray-600">
                                Desainer
                            </p>
                            <p className="text-base font-semibold">{invoice.designer?.name ?? '-'}</p>
                            <p className="text-gray-600">
                                Sumber: {invoice.source === 1 ? 'Online' : 'Offline'}
                            </p>
                        </div>
                    </section>

                    <table className="mt-6 w-full table-fixed border-collapse text-sm">
                        <thead>
                            <tr className="border-y border-black text-left">
                                <th className="py-2 pr-2">Item</th>
                                <th className="w-16 py-2 pr-2 sm:w-[34%]">Catatan</th>
                                <th className="w-12 py-2 text-right sm:w-14">Jml</th>
                                <th className="w-14 py-2 text-right sm:w-20">Luas</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.length === 0 ? (
                                <tr className="border-b border-gray-300">
                                    <td colSpan={4} className="py-6 text-center text-gray-600">
                                        Belum ada item.
                                    </td>
                                </tr>
                            ) : (
                                orders.map((order) => (
                                    <tr key={order.id} className="border-b border-gray-300 align-top">
                                        <td className="break-words py-2 pr-2">
                                            {order.file_name ?? '-'}
                                        </td>
                                        <td className="break-words py-2 pr-2 text-gray-600">
                                            {order.notes ?? '-'}
                                        </td>
                                        <td className="py-2 text-right tabular-nums whitespace-nowrap">
                                            {order.quantity}
                                        </td>
                                        <td className="py-2 text-right tabular-nums whitespace-nowrap">
                                            {order.area}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>

                    <section className="mt-8 grid grid-cols-1 gap-8 text-sm sm:grid-cols-2">
                        <div>
                            <h2 className="text-[10px] font-semibold uppercase tracking-wider text-gray-600">
                                Daftar periksa produksi
                            </h2>
                            <ul className="mt-2">
                                {stageCounts.map((stage) => (
                                    <li
                                        key={stage.status}
                                        className="flex items-center justify-between gap-3 border-b border-gray-300 py-2"
                                    >
                                        <span className="flex items-center gap-2">
                                            <span
                                                aria-hidden="true"
                                                className="flex h-4 w-4 items-center justify-center rounded-[3px] border border-black text-[11px] font-bold leading-none"
                                            >
                                                {stage.count > 0 ? '✓' : ''}
                                            </span>
                                            {stage.label}
                                        </span>
                                        <span className="tabular-nums text-gray-600">
                                            {stage.count} item
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div>
                            <h2 className="text-[10px] font-semibold uppercase tracking-wider text-gray-600">
                                Tanda tangan
                            </h2>
                            <div className="mt-2 grid grid-cols-2 gap-8">
                                <div>
                                    <p className="font-semibold">Operator</p>
                                    <div className="mt-12 border-b border-black" />
                                </div>
                                <div>
                                    <p className="font-semibold">QC</p>
                                    <div className="mt-12 border-b border-black" />
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    );
}
