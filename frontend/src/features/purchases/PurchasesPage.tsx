import { useState } from 'react';

import { api } from '../../api/queries';
import type { Purchase } from '../../api/types';
import { useAuth } from '../../auth/useAuth';
import { ResourceTable, type ResourceColumn } from '../../components/ResourceTable';
import { Button } from '../../components/ui/Button';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';
import { quantityWithUnit } from '../../lib/units';
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
            header: 'Total',
            className:
                'text-right tabular-nums whitespace-nowrap max-lg:sticky max-lg:right-0 max-lg:border-l max-lg:border-light-border max-lg:bg-light-surface max-lg:shadow-[-8px_0_12px_-10px_rgb(0_0_0/0.35)] dark:max-lg:border-surface-300 dark:max-lg:bg-surface-700',
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
                expandedRow={(purchase) => (
                    <div className="space-y-2 py-1">
                        {purchase.items.length === 0 ? (
                            <p className="text-light-muted dark:text-gray-400">Tidak ada rincian item.</p>
                        ) : (
                            <ul className="space-y-1">
                                {purchase.items.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex flex-wrap items-baseline justify-between gap-x-4"
                                    >
                                        <span className="font-medium">{item.material?.name ?? '-'}</span>
                                        <span className="text-light-muted tabular-nums dark:text-gray-400">
                                            {quantityWithUnit(item.quantity, item.material?.unit)} ×{' '}
                                            {formatMoney(item.unit_cost)} = {formatMoney(item.subtotal)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <p className="text-light-muted text-xs dark:text-gray-400">
                            Catatan: {purchase.notes ?? '-'} · Dicatat oleh {purchase.user?.name ?? '-'}
                        </p>
                    </div>
                )}
            />

            {open ? <PurchaseModal onClose={() => setOpen(false)} /> : null}
        </>
    );
}
