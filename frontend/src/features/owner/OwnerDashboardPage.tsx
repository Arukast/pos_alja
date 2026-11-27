import { useQuery } from '@tanstack/react-query';
import { ChevronRight } from 'lucide-react';
import { Link, useSearchParams } from 'react-router';

import { api } from '../../api/queries';
import { PageBanner } from '../../components/PageBanner';
import { Sparkline } from '../../components/Sparkline';
import { Button } from '../../components/ui/Button';
import { Card, CardBody, CardHeader } from '../../components/ui/Card';
import { EmptyState } from '../../components/ui/EmptyState';
import { Select } from '../../components/ui/Select';
import { Spinner } from '../../components/ui/Spinner';
import { StatCard } from '../../components/ui/StatCard';
import { formatMoney } from '../../lib/money';

const periodLabels: Record<string, string> = {
    daily: 'Harian',
    weekly: 'Mingguan',
    monthly: 'Bulanan',
};

// Ratio only: amounts keep going through formatMoney. Zero revenue has no share.
function shareOfRevenue(part: string, gross: string, suffix: string): string {
    const grossValue = Number(gross);
    const partValue = Number(part);

    if (!Number.isFinite(grossValue) || !Number.isFinite(partValue) || grossValue === 0) {
        return '—';
    }

    const percent = new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format((partValue / grossValue) * 100);

    return `${percent}${suffix}`;
}

export function OwnerDashboardPage() {
    const [params, setParams] = useSearchParams();
    const period = params.get('period') ?? 'daily';
    const periodDescription =
        period === 'daily'
            ? 'Enam blok empat jam untuk hari ini'
            : period === 'weekly'
              ? 'Tujuh hari terakhir'
              : 'Empat minggu terakhir';
    const branchId = params.get('branch_id') ?? 'all';

    const updateParam = (key: string, value: string) => {
        const next = new URLSearchParams(params);
        next.set(key, value);
        setParams(next);
    };

    const branchesQuery = useQuery({
        queryKey: ['branches', 'owner-selector'],
        queryFn: () => api.branches(),
    });

    const dashboardQuery = useQuery({
        queryKey: ['dashboard', 'owner', period, branchId],
        queryFn: () => api.dashboard({ period, branch_id: branchId }),
    });

    const data = dashboardQuery.data?.role === 'owner' ? dashboardQuery.data : null;

    const branchLabel =
        branchId === 'all'
            ? 'Semua cabang'
            : branchesQuery.data?.data.find((branch) => branch.id === branchId)?.name;

    return (
        <div className="space-y-6">
            <PageBanner
                eyebrow="Pemilik"
                title="Dasbor Pemilik"
                description="Pendapatan, biaya, laba, dan pengeluaran lintas cabang."
            />

            <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                <div className="w-full sm:w-56">
                    <Select
                        aria-label="Cabang"
                        value={branchId}
                        onChange={(event) => updateParam('branch_id', event.target.value)}
                        className="h-9 py-1.5"
                    >
                        <option value="all">Semua cabang</option>
                        {(branchesQuery.data?.data ?? []).map((branch) => (
                            <option key={branch.id} value={branch.id}>
                                {branch.name}
                            </option>
                        ))}
                    </Select>
                </div>
                <div className="flex flex-wrap gap-2">
                    {(['daily', 'weekly', 'monthly'] as const).map((value) => (
                        <Button
                            key={value}
                            size="sm"
                            variant={period === value ? 'primary' : 'secondary'}
                            aria-pressed={period === value}
                            onClick={() => updateParam('period', value)}
                        >
                            {periodLabels[value]}
                        </Button>
                    ))}
                </div>
            </div>

            {dashboardQuery.isLoading ? (
                <Card>
                    <div role="status" className="flex items-center justify-center px-5 py-16">
                        <Spinner className="text-brand-primary h-6 w-6" />
                    </div>
                </Card>
            ) : null}

            {dashboardQuery.isError ? (
                <EmptyState
                    title="Dasbor gagal dimuat"
                    description="Periksa koneksi lalu coba lagi."
                    action={
                        <Button variant="secondary" onClick={() => dashboardQuery.refetch()}>
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
                            hint={
                                branchLabel
                                    ? `${branchLabel} · Pembayaran yang diterima pada periode ini`
                                    : 'Pembayaran yang diterima pada periode ini'
                            }
                        />
                        <StatCard
                            label="Biaya Dasar"
                            value={formatMoney(data.finance.total_cost)}
                            hint={`${shareOfRevenue(data.finance.total_cost, data.finance.gross_revenue, '% dari pendapatan bruto')} · HPP invoice ditambah bahan terbuang`}
                        />
                        <StatCard
                            label="Laba Bersih"
                            value={formatMoney(data.finance.net_profit)}
                            hint={`${shareOfRevenue(data.finance.net_profit, data.finance.gross_revenue, '% margin bersih')} · Pendapatan dikurangi HPP dan pengeluaran`}
                        />
                        <StatCard
                            label="Pengeluaran"
                            value={formatMoney(data.finance.expenses)}
                            hint={`${shareOfRevenue(data.finance.expenses, data.finance.gross_revenue, '% dari pendapatan bruto')} · Biaya operasional`}
                        />
                    </div>

                    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,2fr)]">
                        <StatCard
                            label="Piutang Usaha"
                            value={formatMoney(data.finance.receivables)}
                            hint="Sisa tagihan invoice belum lunas"
                        />
                        <StatCard
                            label="Jumlah Pesanan"
                            value={String(data.order_count)}
                            hint={
                                branchLabel
                                    ? `${branchLabel} · Pesanan pada periode ini`
                                    : 'Pesanan pada periode ini'
                            }
                        />
                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Status Produksi</h2>
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
                    </div>

                    <div className="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                        <Card>
                            <CardHeader>
                                <div className="min-w-0">
                                    <h2 className="font-semibold">Pergerakan laba bersih</h2>
                                    <p className="text-light-muted text-sm dark:text-gray-400">
                                        {periodDescription}
                                    </p>
                                </div>
                                <div className="shrink-0 text-right">
                                    <p className="text-light-muted text-[10px] font-semibold uppercase tracking-wider dark:text-gray-400">
                                        Laba bersih
                                    </p>
                                    <p className="text-light-text mt-1 text-lg font-extrabold tabular-nums dark:text-gray-100">
                                        {formatMoney(data.finance.net_profit)}
                                    </p>
                                </div>
                            </CardHeader>
                            <CardBody>
                                <Sparkline
                                    points={data.sparkline.points}
                                    labels={data.sparkline.labels}
                                    period={periodDescription}
                                />
                            </CardBody>
                        </Card>

                        <Card>
                            <CardHeader>
                                <h2 className="font-semibold">Rincian keuangan</h2>
                            </CardHeader>
                            <div className="divide-light-border dark:divide-surface-300 divide-y">
                                {[
                                    {
                                        label: 'Pendapatan Bruto',
                                        to: '/owner/revenue',
                                        value: data.finance.gross_revenue,
                                    },
                                    {
                                        label: 'Biaya Dasar',
                                        to: '/owner/base-cost',
                                        value: data.finance.total_cost,
                                    },
                                    {
                                        label: 'Laba Bersih',
                                        to: '/owner/net-profit',
                                        value: data.finance.net_profit,
                                    },
                                    {
                                        label: 'Pengeluaran',
                                        to: '/owner/expenses',
                                        value: data.finance.expenses,
                                    },
                                ].map((row) => (
                                    <Link
                                        key={row.to}
                                        to={row.to}
                                        className="hover:bg-light-surface-2 dark:hover:bg-surface-600 focus-visible:ring-brand-fill flex items-center justify-between gap-3 px-5 py-3 transition focus-visible:ring-2 focus-visible:ring-inset focus-visible:outline-none"
                                    >
                                        <span className="text-light-text text-sm font-medium dark:text-gray-100">
                                            {row.label}
                                        </span>
                                        <span className="flex items-center gap-2">
                                            <span className="text-light-text text-sm font-semibold tabular-nums dark:text-gray-100">
                                                {formatMoney(row.value)}
                                            </span>
                                            <ChevronRight
                                                aria-hidden="true"
                                                className="text-light-muted h-4 w-4 shrink-0 dark:text-gray-400"
                                            />
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        </Card>
                    </div>
                </>
            ) : null}
        </div>
    );
}
