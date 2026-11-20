import { useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from 'react-router';

import { api } from '../../api/queries';
import { Button } from '../../components/ui/Button';
import { Spinner } from '../../components/ui/Spinner';
import { formatDate } from '../../lib/dates';

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
                <p>Unable to load the job ticket.</p>
                <Button className="mt-4" onClick={() => navigate(-1)}>
                    Back
                </Button>
            </div>
        );
    }

    return (
        <main className="print-page min-h-screen bg-white p-8 text-black">
            <style>{`@media print { .no-print { display: none !important; } @page { size: A4; margin: 12mm; } }`}</style>
            <div className="no-print mb-6 flex justify-end gap-2">
                <Button variant="secondary" onClick={() => navigate(-1)}>
                    Back
                </Button>
                <Button onClick={() => window.print()}>Print ticket</Button>
            </div>

            <header className="border-brand-dark flex items-start justify-between border-b-2 pb-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.3em]">
                        Alja Digital Printing
                    </p>
                    <h1 className="text-2xl font-bold">Work Order Ticket</h1>
                    <p className="text-sm">{invoice.branch?.name ?? '-'}</p>
                </div>
                <div className="text-right text-sm">
                    <p className="font-bold">{invoice.invoice_number}</p>
                    <p>Deadline: {formatDate(invoice.deadline ?? '-')}</p>
                    <p>Created: {formatDate(invoice.created_date)}</p>
                </div>
            </header>

            <section className="mt-4 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p className="text-xs uppercase tracking-wider">Customer</p>
                    <p className="font-medium">{invoice.customer?.name ?? '-'}</p>
                    <p>{invoice.customer?.phone ?? '-'}</p>
                </div>
                <div>
                    <p className="text-xs uppercase tracking-wider">Designer</p>
                    <p className="font-medium">{invoice.designer?.name ?? '-'}</p>
                    <p>Source: {invoice.source === 1 ? 'Online' : 'Offline'}</p>
                </div>
            </section>

            <table className="mt-6 w-full border-collapse text-sm">
                <thead>
                    <tr className="border-y border-black text-left">
                        <th className="py-2">Item</th>
                        <th className="py-2">Notes</th>
                        <th className="py-2 text-right">Qty</th>
                        <th className="py-2 text-right">Area</th>
                    </tr>
                </thead>
                <tbody>
                    {(invoice.orders ?? []).map((order) => (
                        <tr key={order.id} className="border-b border-gray-300">
                            <td className="py-2">{order.file_name ?? '-'}</td>
                            <td className="py-2">{order.notes ?? '-'}</td>
                            <td className="py-2 text-right">{order.quantity}</td>
                            <td className="py-2 text-right">{order.area}</td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <section className="mt-8 grid grid-cols-2 gap-8 text-sm">
                <div>
                    <p className="font-semibold">Production checklist</p>
                    <ul className="mt-2 space-y-2">
                        <li>☐ File checked</li>
                        <li>☐ Material prepared</li>
                        <li>☐ Print completed</li>
                        <li>☐ Finishing completed</li>
                    </ul>
                </div>
                <div>
                    <p className="font-semibold">Signatures</p>
                    <div className="mt-8 space-y-8">
                        <div className="border-t border-black pt-1 text-xs">Designer</div>
                        <div className="border-t border-black pt-1 text-xs">Production</div>
                    </div>
                </div>
            </section>
        </main>
    );
}
