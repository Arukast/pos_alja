import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router';

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

const panelLink =
    'text-brand-fill-hover dark:text-brand-primary-light focus-visible:ring-brand-fill dark:focus-visible:ring-brand-primary-light rounded-sm text-sm font-semibold hover:underline focus-visible:ring-2 focus-visible:outline-none';

export function ManagerDashboardPage() {
    const query = useQuery({
        queryKey: ['dashboard', 'manager'],
        queryFn: () => api.dashboard(),
    });
    const data = query.data?.role === 'manager' ? query.data : null;

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Manajer Cabang"
                title="Dasbor Cabang"
                description="Inventaris, harga, staf, dan kinerja desainer bulan ini."
            />

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
                        <StatCard label="Bahan Tersedia" value={String(data.counts.materials)} />
                        <StatCard label="Mesin Cetak" value={String(data.counts.machines)} />
                        <StatCard label="Staf Aktif" value={String(data.counts.staff)} />
                        <StatCard label="Baris Harga" value={String(data.counts.pricing)} />
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
                                <Table>
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
                                                    <TableCell>{formatDate(ledger.logged_at)}</TableCell>
                                                    <TableCell>{ledger.material?.name ?? '-'}</TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {ledger.added_stock}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {ledger.used_stock}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {ledger.remaining_stock}
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
                                                    <TableCell className="text-right tabular-nums">
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
                                        <TableHeaderCell className="text-right">Catatan Sisa Bahan</TableHeaderCell>
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
                                                <TableCell className="text-right tabular-nums">
                                                    {formatMoney(leader.revenue)}
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
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
