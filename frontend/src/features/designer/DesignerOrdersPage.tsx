import { Link } from 'react-router';

import { api } from '../../api/queries';
import { ResourceTable } from '../../components/ResourceTable';
import { InvoiceStatusBadge } from '../../components/StatusBadge';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

export function DesignerOrdersPage() {
    return (
        <ResourceTable
            eyebrow="Designer"
            title="Order List"
            description="Invoices created by your branch."
            queryKey={['designer', 'invoices']}
            queryFn={(page) => api.invoices({ page })}
            columns={[
                {
                    header: 'Invoice',
                    cell: (invoice) => (
                        <span className="font-medium">{invoice.invoice_number}</span>
                    ),
                },
                { header: 'Customer', cell: (invoice) => invoice.customer?.name ?? '-' },
                { header: 'Deadline', cell: (invoice) => formatDate(invoice.deadline ?? '-') },
                {
                    header: 'Status',
                    cell: (invoice) => <InvoiceStatusBadge status={invoice.status} />,
                },
                {
                    header: 'Total',
                    cell: (invoice) => formatMoney(invoice.grand_total),
                },
                {
                    header: 'Print',
                    cell: (invoice) => (
                        <Link
                            className="text-brand-primary font-medium hover:underline"
                            to={`/orders/${invoice.id}/ticket`}
                        >
                            Ticket
                        </Link>
                    ),
                },
            ]}
        />
    );
}
