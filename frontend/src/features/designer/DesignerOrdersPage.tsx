import { Link } from 'react-router';

import { api } from '../../api/queries';
import { ResourceTable } from '../../components/ResourceTable';
import { InvoiceStatusBadge } from '../../components/StatusBadge';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

/** Lists designer invoices and provides the printable invoice action. */
export function DesignerOrdersPage() {
    return (
        <ResourceTable
            eyebrow="Desainer"
            title="Daftar Pesanan"
            description="Invoice yang dibuat oleh cabang Anda."
            queryKey={['designer', 'invoices']}
            queryFn={(page) => api.invoices({ page })}
            columns={[
                {
                    header: 'Invoice',
                    cell: (invoice) => (
                        <span className="font-medium">{invoice.invoice_number}</span>
                    ),
                },
                { header: 'Pelanggan', cell: (invoice) => invoice.customer?.name ?? '-' },
                { header: 'Tenggat', cell: (invoice) => formatDate(invoice.deadline ?? '-') },
                {
                    header: 'Status',
                    cell: (invoice) => <InvoiceStatusBadge status={invoice.status} />,
                },
                {
                    header: 'Ubah Item',
                    cell: (invoice) => invoice.status === 2 ? (
                        <span className="text-light-muted text-sm">Terkunci</span>
                    ) : (
                        <Link
                            className="text-brand-fill-hover dark:text-brand-primary-light focus-visible:ring-brand-fill rounded-sm text-sm font-semibold hover:underline focus-visible:ring-2 focus-visible:outline-none"
                            to={`/designer/orders/${invoice.id}/edit`}
                        >
                            Edit
                        </Link>
                    ),
                },
                {
                    header: 'Total',
                    className: 'text-right tabular-nums whitespace-nowrap',
                    cell: (invoice) => formatMoney(invoice.grand_total),
                },
                {
                    header: 'Cetak',
                    cell: (invoice) => (
                        <Link
                            className="text-brand-fill-hover dark:text-brand-primary-light focus-visible:ring-brand-fill dark:focus-visible:ring-brand-primary-light rounded-sm text-sm font-semibold hover:underline focus-visible:ring-2 focus-visible:outline-none"
                            to={`/orders/${invoice.id}/receipt`}
                        >
                            Cetak Invoice
                        </Link>
                    ),
                },
            ]}
        />
    );
}
