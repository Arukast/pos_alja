import { useQuery } from '@tanstack/react-query';
import { Link, useSearchParams } from 'react-router';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';
import { ledgerQuantity, quantityWithUnit } from '../../lib/units';

const panelLink =
    'text-brand-fill-hover dark:text-brand-primary-light focus-visible:ring-brand-fill dark:focus-visible:ring-brand-primary-light rounded-sm text-sm font-semibold hover:underline focus-visible:ring-2 focus-visible:outline-none';

const periodLabels: Record<string, string> = {
    daily: 'Harian',
    weekly: 'Mingguan',
    monthly: 'Bulanan',
};

export function ManagerDashboardPage() {
    const [params, setParams] = useSearchParams();
    const periodParam = params.get('period');
    const period =
        periodParam === 'weekly' || periodParam === 'monthly' ? periodParam : 'daily';

    const updatePeriod = (value: string) => {
        const next = new URLSearchParams(params);
        next.set('period', value);
        setParams(next);
    };

    const query = useQuery({
        queryKey: ['dashboard', 'manager', period],
        queryFn: () => api.dashboard({ period }),
    });
    const data = query.data?.role === 'manager' ? query.data : null;

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Manajer Cabang"
                title="Dasbor Cabang"
                description="Ringkasan keuangan, produksi, inventaris, staf, dan kinerja desainer cabang."
            />

            <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                <div className="flex flex-wrap gap-2">
                    {(['daily', 'weekly', 'monthly'] as const).map((value) => (
                        <Button
                            key={value}
                            size="sm"
                            variant={period === value ? 'primary' : 'secondary'}
                            aria-pressed={period === value}
                            onClick={() => updatePeriod(value)}
                        >
                            {periodLabels[value]}
                        </Button>
                    ))}
                </div>
            </div>

            {query.isLoading ? (
                <Card>
                    <div role="status" className="flex items-center justify-center px-5 py-16">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                </Card>
            ) : null}

            {query.isError ? (
                <EmptyState
                    title="Dasbor gagal dimuat"
                    description="Periksa koneksi lalu coba lagi."
                    action={
                        <Button variant="secondary" onClick={() => query.refetch()}>
                            Coba lagi
                        </Button>
                    }
                />
            ) : null}

            {data ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            label="Pendapatan Bruto"
                            value={formatMoney(data.finance.gross_revenue)}
                            hint="Pembayaran yang diterima pada periode ini"
                        />
                        <StatCard
                            label="Pengeluaran"
                            value={formatMoney(data.finance.expenses)}
                            hint="Biaya operasional cabang pada periode ini"
                        />
                        <StatCard
                            label="Piutang Usaha"
                            value={formatMoney(data.finance.receivables)}
                            hint="Sisa tagihan invoice belum lunas"
                        />
                        <StatCard
                            label="Jumlah Pesanan"
                            value={String(data.order_count)}
                            hint={`Pesanan cabang (${periodLabels[period] ?? period})`}
                        />
                    </div>

                    <div className="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Status Produksi Cabang</h2>
                                <span className="text-light-muted text-xs dark:text-gray-400">
                                    {periodLabels[period] ?? period}
                                </span>
                            </CardHeader>
                            <CardBody>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {[
                                        { label: 'Antre', value: data.production.queued },
                                        { label: 'Diproses', value: data.production.processing },
                                        { label: 'Selesai', value: data.production.completed },
                                        { label: 'Gagal', value: data.production.failed },
                                    ].map((item) => (
                                        <div
                                            key={item.label}
                                            className="bg-light-surface-2 dark:bg-surface-700 border-light-border dark:border-surface-300 rounded-xl border px-3 py-2.5"
                                        >
                                            <p className="text-light-muted text-xs dark:text-gray-400">
                                                {item.label}
                                            </p>
                                            <p className="text-light-text mt-1 text-xl font-extrabold tabular-nums dark:text-gray-100">
                                                {item.value}
                                            </p>
                                        </div>
                                    ))}
                                </div>
                            </CardBody>
                        </Card>

                        <div className="grid gap-4 sm:grid-cols-3">
                            <StatCard label="Bahan Tersedia" value={String(data.counts.materials)} />
                            <StatCard label="Staf Aktif" value={String(data.counts.staff)} />
                        </div>
                    </div>

                    <div className="grid gap-4 xl:grid-cols-2">
                        <Card className="min-w-0">
                            <CardHeader>
                                <h2 className="font-semibold">Pergerakan stok terbaru</h2>
                                <Link to="/manager/ledgers" className={panelLink}>
                                    Lihat buku stok
                                </Link>
                            </CardHeader>
                            <CardBody>
                                <Table ariaLabel="Pergerakan stok terbaru">
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Tanggal</TableHeaderCell>
                                            <TableHeaderCell>Bahan</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Ditambahkan</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Digunakan</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Tersisa</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.stock_ledgers.length === 0 ? (
                                            <TableEmpty colSpan={5}>Belum ada pergerakan stok.</TableEmpty>
                                        ) : (
                                            data.stock_ledgers.map((ledger) => (
                                                <TableRow key={ledger.id}>
                                                    <TableCell className="whitespace-nowrap">
                                                        {formatDate(ledger.logged_at)}
                                                    </TableCell>
                                                    <TableCell>{ledger.material?.name ?? '-'}</TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {ledgerQuantity(ledger.added_stock, ledger.material?.unit)}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {ledgerQuantity(ledger.used_stock, ledger.material?.unit)}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {quantityWithUnit(ledger.remaining_stock, ledger.material?.unit)}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardBody>
                        </Card>

                        <Card className="min-w-0">
                            <CardHeader>
                                <h2 className="font-semibold">Pengeluaran terbaru</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Tanggal</TableHeaderCell>
                                            <TableHeaderCell>Deskripsi</TableHeaderCell>
                                            <TableHeaderCell>Kategori</TableHeaderCell>
                                            <TableHeaderCell className="text-right">Jumlah</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.expenses.length === 0 ? (
                                            <TableEmpty colSpan={4}>Belum ada pengeluaran.</TableEmpty>
                                        ) : (
                                            data.expenses.map((expense) => (
                                                <TableRow key={expense.id}>
                                                    <TableCell>{formatDate(expense.incurred_at)}</TableCell>
                                                    <TableCell>{expense.description ?? '-'}</TableCell>
                                                    <TableCell>{expense.category}</TableCell>
                                                    <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                        {formatMoney(expense.amount)}
                                                    </TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardBody>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold">
                                <Link
                                    to="/manager/leaderboard"
                                    className="focus-visible:ring-brand-fill dark:focus-visible:ring-brand-primary-light rounded-sm hover:underline focus-visible:ring-2 focus-visible:outline-none"
                                >
                                    Papan Peringkat Desainer
                                </Link>
                            </h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Desainer</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Pesanan</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Pendapatan</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Rata-rata pesanan</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Catatan Bahan Terbuang</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.leaderboard.length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada data desainer bulan ini.</TableEmpty>
                                    ) : (
                                        data.leaderboard.map((leader) => (
                                            <TableRow key={leader.id}>
                                                <TableCell className="font-medium">{leader.name}</TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {leader.order_count}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatMoney(leader.revenue)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums whitespace-nowrap">
                                                    {formatMoney(leader.average_order_value)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {leader.waste_count}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardBody>
                    </Card>
                </>
            ) : null}
        </div>
    );
}
