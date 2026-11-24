import { useState } from 'react';

import { api } from '../../api/queries';
import type { Purchase } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { ResourceTable, type ResourceColumn } from '../../components/ResourceTable';
import { Button } from '../../components/ui/Button';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';
import { PurchaseModal } from './PurchaseModal';

export function PurchasesPage() {
    const { user } = useAuth();
    const [open, setOpen] = useState(false);
    const isOwner = user?.role === 'owner';

    const columns: Array<ResourceColumn<Purchase>> = [
        {
            header: 'Tanggal',
            className: 'whitespace-nowrap',
            cell: (purchase) => formatDate(purchase.purchased_at),
        },
        ...(isOwner
            ? [
                  {
                      header: 'Cabang',
                      cell: (purchase: Purchase) => purchase.branch?.name ?? '-',
                  },
              ]
            : []),
        { header: 'Pemasok', cell: (purchase) => purchase.supplier ?? '-' },
        {
            header: 'Jumlah Item',
            className: 'text-right tabular-nums whitespace-nowrap',
            cell: (purchase) => purchase.items.length,
        },
        {
            header: 'Total',
            className: 'text-right tabular-nums whitespace-nowrap',
            cell: (purchase) => formatMoney(purchase.total_amount),
        },
    ];

    return (
        <>
            <ResourceTable
                eyebrow={isOwner ? 'Administrasi' : 'Inventaris'}
                title="Pembelian"
                description="Riwayat pembelian bahan dan pencatatan pembelian baru."
                queryKey={['purchases']}
                queryFn={(page) => api.purchases({ page })}
                columns={columns}
                actions={<Button onClick={() => setOpen(true)}>Catat Pembelian</Button>}
                emptyMessage="Belum ada pembelian."
            />

            {open ? <PurchaseModal onClose={() => setOpen(false)} /> : null}
        </>
    );
}
