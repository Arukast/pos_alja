import { useQuery } from '@tanstack/react-query';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeaderCell, TableRow } from '../../components/ui/Table';
import { formatDate } from '../../lib/dates';
import { formatMoney } from '../../lib/money';

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
                <div className="flex justify-center py-16">
                    <Spinner className="text-brand-primary h-6 w-6" />
                </div>
            ) : null}

            {data ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard label="Bahan Tersedia" value={String(data.counts.materials)} />
                        <StatCard label="Mesin Cetak" value={String(data.counts.machines)} />
                        <StatCard label="Staf Aktif" value={String(data.counts.staff)} />
                        <StatCard label="Baris Harga" value={String(data.counts.pricing)} />
                    </div>

                    <div className="grid gap-6 xl:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Pergerakan stok terbaru</h2>
                            </CardHeader>
                            <CardBody>
                                <Table>
                                    <TableHead>
                                        <TableRow>
                                            <TableHeaderCell>Bahan</TableHeaderCell>
                                            <TableHeaderCell>Ditambahkan</TableHeaderCell>
                                            <TableHeaderCell>Digunakan</TableHeaderCell>
                                            <TableHeaderCell>Tersisa</TableHeaderCell>
                                            <TableHeaderCell>Tanggal</TableHeaderCell>
                                        </TableRow>
                                    </TableHead>
                                    <TableBody>
                                        {data.stock_ledgers.length === 0 ? (
                                            <TableEmpty colSpan={5}>Belum ada pergerakan stok.</TableEmpty>
                                        ) : (
                                            data.stock_ledgers.map((ledger) => (
                                                <TableRow key={ledger.id}>
                                                    <TableCell>{ledger.material?.name ?? '-'}</TableCell>
                                                    <TableCell>{ledger.added_stock}</TableCell>
                                                    <TableCell>{ledger.used_stock}</TableCell>
                                                    <TableCell>{ledger.remaining_stock}</TableCell>
                                                    <TableCell>{formatDate(ledger.logged_at)}</TableCell>
                                                </TableRow>
                                            ))
                                        )}
                                    </TableBody>
                                </Table>
                            </CardBody>
                        </Card>

                        <Card>
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
                                                    <TableCell className="text-right">
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
                            <h2 className="font-semibold">Papan Peringkat Desainer</h2>
                        </CardHeader>
                        <CardBody>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeaderCell>Desainer</TableHeaderCell>
                                        <TableHeaderCell>Pesanan</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Pendapatan</TableHeaderCell>
                                        <TableHeaderCell className="text-right">Rata-rata pesanan</TableHeaderCell>
                                        <TableHeaderCell>Catatan Sisa Bahan</TableHeaderCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {data.leaderboard.length === 0 ? (
                                        <TableEmpty colSpan={5}>Belum ada data desainer bulan ini.</TableEmpty>
                                    ) : (
                                        data.leaderboard.map((leader) => (
                                            <TableRow key={leader.id}>
                                                <TableCell className="font-medium">{leader.name}</TableCell>
                                                <TableCell>{leader.order_count}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(leader.revenue)}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatMoney(leader.average_order_value)}
                                                </TableCell>
                                                <TableCell>{leader.waste_count}</TableCell>
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
