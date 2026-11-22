import { Link } from 'react-router';

import { api } from '../../api/queries';
import { ResourceTable } from '../../components/ResourceTable';
import { InvoiceStatusBadge } from '../../components/StatusBadge';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

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
                    header: 'Total',
                    cell: (invoice) => formatMoney(invoice.grand_total),
                },
                {
                    header: 'Cetak',
                    cell: (invoice) => (
                        <Link
                            className="text-brand-primary font-medium hover:underline"
                            to={`/orders/${invoice.id}/ticket`}
                        >
                            Tiket
                        </Link>
                    ),
                },
            ]}
        />
    );
}
