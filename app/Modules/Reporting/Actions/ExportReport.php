<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Actions;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\WastedMaterial;
use App\Modules\Reporting\Queries\BaseCostQuery;
use App\Modules\Reporting\Queries\ExpensesQuery;
use App\Modules\Reporting\Queries\GrossRevenueQuery;
use App\Modules\Reporting\Queries\NetProfitQuery;
use App\Modules\Reporting\Support\ExcelReportExporter;
use App\Modules\Reporting\Support\PdfReportExporter;
use App\Services\AuditLogger;
use App\Support\Decimal;
use DateTimeInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportReport
{
    public function __construct(
        private readonly GrossRevenueQuery $grossRevenue,
        private readonly BaseCostQuery $baseCost,
        private readonly NetProfitQuery $netProfit,
        private readonly ExpensesQuery $expenses,
        private readonly ExcelReportExporter $excelExporter,
        private readonly PdfReportExporter $pdfExporter,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(
        User $actor,
        string $type,
        string $format,
        string $period,
        string $branchId,
        string $startDate,
        string $endDate,
    ): StreamedResponse|Response {
        $branch = $branchId === 'all' ? null : Branch::query()->find($branchId);
        $branchLabel = $branchId === 'all' ? 'Semua Cabang' : ($branch->name ?? 'Cabang');
        $rawSlug = $branch !== null && is_string($branch->code) && $branch->code !== ''
            ? $branch->code
            : ($branch->name ?? 'all');
        $scopeSlug = $branchId === 'all' ? 'all' : (Str::slug($rawSlug) ?: 'cabang');
        $auditBranchId = $branchId === 'all' ? null : $branchId;

        $report = $this->buildReport($type, $branchId, $branchLabel, $startDate, $endDate);
        $filename = sprintf('laporan-%s-%s-%s_%s.%s', $type, $scopeSlug, $startDate, $endDate, $format);

        $this->audit->record(
            $actor,
            'EXPORT',
            sprintf(
                'Mengekspor %s (%s) untuk %s periode %s s.d. %s.',
                $report['title'],
                strtoupper($format),
                $branchLabel,
                $startDate,
                $endDate,
            ),
            null,
            [
                'report_type' => $type,
                'format' => $format,
                'period' => $period,
                'branch_id' => $branchId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            $auditBranchId,
        );

        return $format === 'pdf'
            ? $this->pdfExporter->render($report, $filename)
            : $this->excelExporter->stream($report, $filename);
    }

    /**
     * @return array{
     *     title: string,
     *     branch: string,
     *     period: string,
     *     generated_at: string,
     *     summary: list<array{0: string, 1: string}>,
     *     sections: list<array{title: string, headers: list<string>, rows: list<list<string>>}>
     * }
     */
    private function buildReport(
        string $type,
        string $branchId,
        string $branchLabel,
        string $startDate,
        string $endDate,
    ): array {
        $meta = [
            'branch' => $branchLabel,
            'period' => $startDate.' s.d. '.$endDate,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        return match ($type) {
            'gross-revenue' => [
                'title' => 'Laporan Pendapatan Bruto',
                ...$meta,
                ...$this->grossRevenueData($branchId, $startDate, $endDate),
            ],
            'base-cost' => [
                'title' => 'Laporan Biaya Dasar',
                ...$meta,
                ...$this->baseCostData($branchId, $startDate, $endDate),
            ],
            'net-profit' => [
                'title' => 'Laporan Laba Bersih',
                ...$meta,
                ...$this->netProfitData($branchId, $startDate, $endDate),
            ],
            default => [
                'title' => 'Laporan Pengeluaran',
                ...$meta,
                ...$this->expensesData($branchId, $startDate, $endDate),
            ],
        };
    }

    /**
     * @return array{
     *     summary: list<array{0: string, 1: string}>,
     *     sections: list<array{title: string, headers: list<string>, rows: list<list<string>>}>
     * }
     */
    private function grossRevenueData(string $branchId, string $startDate, string $endDate): array
    {
        $data = $this->grossRevenue->get($branchId, $startDate, $endDate);

        $rows = $data['payments']->map(fn (Payment $payment): array => [
            $this->formatDate($payment->created_at),
            $payment->invoice->invoice_number ?? '-',
            $payment->invoice?->customer->name ?? '-',
            $payment->branch->name ?? '-',
            (string) $payment->payment_method,
            Decimal::money($payment->amount),
        ])->values()->all();

        return [
            'summary' => [
                ['Total Diterima', Decimal::money($data['grand_total'])],
                ['Tunai', Decimal::money($data['cash_total'])],
                ['Transfer', Decimal::money($data['transfer_total'])],
                ['QRIS', Decimal::money($data['qris_total'])],
            ],
            'sections' => [[
                'title' => 'Catatan Pembayaran',
                'headers' => ['Tanggal', 'Invoice', 'Pelanggan', 'Cabang', 'Metode', 'Jumlah'],
                'rows' => $rows,
            ]],
        ];
    }

    /**
     * @return array{
     *     summary: list<array{0: string, 1: string}>,
     *     sections: list<array{title: string, headers: list<string>, rows: list<list<string>>}>
     * }
     */
    private function baseCostData(string $branchId, string $startDate, string $endDate): array
    {
        $data = $this->baseCost->get($branchId, $startDate, $endDate);

        $invoiceRows = $data['invoices']->map(fn (Invoice $invoice): array => [
            $this->formatDate($invoice->created_date),
            (string) $invoice->invoice_number,
            $invoice->customer->name ?? '-',
            $invoice->branch->name ?? '-',
            Decimal::money($invoice->total_cost),
        ])->values()->all();

        $wasteRows = $data['wasted_materials']->map(fn (WastedMaterial $waste): array => [
            $this->formatDate($waste->logged_at),
            $waste->material->name ?? '-',
            $waste->material->unit ?? '-',
            (string) $waste->quantity,
            $waste->operator->name ?? '-',
            Decimal::money((float) $waste->quantity * (float) ($waste->material ? $waste->material->cost : 0)),
        ])->values()->all();

        return [
            'summary' => [
                ['Total HPP', Decimal::money($data['total_hpp'])],
                ['Invoice HPP', Decimal::money($data['invoice_cost_total'])],
                ['HPP Bahan Terbuang', Decimal::money($data['wasted_cost_total'])],
            ],
            'sections' => [
                [
                    'title' => 'Invoice HPP',
                    'headers' => ['Tanggal', 'Invoice', 'Pelanggan', 'Cabang', 'HPP'],
                    'rows' => $invoiceRows,
                ],
                [
                    'title' => 'Bahan Terbuang',
                    'headers' => ['Tanggal', 'Bahan', 'Satuan', 'Jumlah', 'Operator', 'Biaya'],
                    'rows' => $wasteRows,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     summary: list<array{0: string, 1: string}>,
     *     sections: list<array{title: string, headers: list<string>, rows: list<list<string>>}>
     * }
     */
    private function netProfitData(string $branchId, string $startDate, string $endDate): array
    {
        $data = $this->netProfit->get($branchId, $startDate, $endDate);

        $invoiceRows = $data['invoices']->map(fn (Invoice $invoice): array => [
            $this->formatDate($invoice->created_date),
            (string) $invoice->invoice_number,
            $invoice->customer->name ?? '-',
            Decimal::money($invoice->grand_total),
            Decimal::money($invoice->total_cost),
            Decimal::money($invoice->total_profit),
        ])->values()->all();

        $expenseRows = $data['expenses']->map(fn (Expense $expense): array => [
            $this->formatDate($expense->incurred_at),
            (string) ($expense->cashier_name ?? '-'),
            (string) $expense->category,
            (string) ($expense->description ?? '-'),
            Decimal::money($expense->amount),
        ])->values()->all();

        $wasteRows = $data['wasted_materials']->map(fn (WastedMaterial $waste): array => [
            $this->formatDate($waste->logged_at),
            $waste->material->name ?? '-',
            (string) $waste->quantity,
            $waste->operator->name ?? '-',
            Decimal::money((float) $waste->quantity * (float) ($waste->material ? $waste->material->cost : 0)),
        ])->values()->all();

        return [
            'summary' => [
                ['Laba Bersih', Decimal::money($data['net_profit'])],
                ['Margin Laba', number_format($data['margin_rate'], 2, '.', '').'%'],
                ['Pendapatan Bruto', Decimal::money($data['gross_revenue'])],
                ['Total HPP', Decimal::money($data['total_hpp'])],
                ['Pengeluaran', Decimal::money($data['expenses_total'])],
            ],
            'sections' => [
                [
                    'title' => 'Catatan Invoice',
                    'headers' => ['Tanggal', 'Invoice', 'Pelanggan', 'Total', 'HPP', 'Laba'],
                    'rows' => $invoiceRows,
                ],
                [
                    'title' => 'Biaya Operasional',
                    'headers' => ['Tanggal', 'Kasir', 'Kategori', 'Deskripsi', 'Jumlah'],
                    'rows' => $expenseRows,
                ],
                [
                    'title' => 'Bahan Terbuang',
                    'headers' => ['Tanggal', 'Bahan', 'Jumlah', 'Operator', 'Biaya'],
                    'rows' => $wasteRows,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     summary: list<array{0: string, 1: string}>,
     *     sections: list<array{title: string, headers: list<string>, rows: list<list<string>>}>
     * }
     */
    private function expensesData(string $branchId, string $startDate, string $endDate): array
    {
        $data = $this->expenses->get($branchId, $startDate, $endDate);

        $summary = [
            ['Total Pengeluaran', Decimal::money($data['expenses_total'])],
        ];

        foreach ($data['by_category'] as $category => $total) {
            $summary[] = ['Kategori: '.$category, Decimal::money($total)];
        }

        $rows = $data['expenses']->map(fn (Expense $expense): array => [
            $this->formatDate($expense->incurred_at),
            (string) ($expense->cashier_name ?? '-'),
            $expense->branch->name ?? '-',
            (string) $expense->category,
            (string) ($expense->description ?? '-'),
            Decimal::money($expense->amount),
        ])->values()->all();

        return [
            'summary' => $summary,
            'sections' => [[
                'title' => 'Catatan Biaya Operasional',
                'headers' => ['Tanggal', 'Kasir', 'Cabang', 'Kategori', 'Deskripsi', 'Jumlah'],
                'rows' => $rows,
            ]],
        ];
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return is_string($value) && $value !== '' ? $value : '-';
    }
}
