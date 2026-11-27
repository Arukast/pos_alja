<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class ExcelReportExporter
{
    /**
     * @param  array{
     *     title: string,
     *     branch: string,
     *     period: string,
     *     generated_at: string,
     *     summary: list<array{0: string, 1: string}>,
     *     sections: list<array{title: string, headers: list<string>, rows: list<list<string>>}>
     * }  $report
     */
    public function stream(array $report, string $filename): StreamedResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'pos_report_');

        if ($tempPath === false) {
            abort(500, 'Gagal menyiapkan berkas ekspor Excel.');
        }

        $writer = new Writer;

        try {
            $writer->openToFile($tempPath);

            $writer->addRow(Row::fromValues(['Laporan', $report['title']]));
            $writer->addRow(Row::fromValues(['Cabang', $report['branch']]));
            $writer->addRow(Row::fromValues(['Periode', $report['period']]));
            $writer->addRow(Row::fromValues(['Dicetak', $report['generated_at']]));
            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValues(['Ringkasan', 'Nilai']));

            foreach ($report['summary'] as [$label, $value]) {
                $writer->addRow(Row::fromValues([$label, $value]));
            }

            foreach ($report['sections'] as $section) {
                $writer->addRow(Row::fromValues([]));
                $writer->addRow(Row::fromValues([$section['title']]));
                $writer->addRow(Row::fromValues($section['headers']));

                foreach ($section['rows'] as $row) {
                    $writer->addRow(Row::fromValues($row));
                }
            }

            $writer->close();
        } catch (Throwable $exception) {
            $writer->close();

            if (is_file($tempPath)) {
                @unlink($tempPath);
            }

            throw $exception;
        }

        return response()->stream(function () use ($tempPath): void {
            try {
                readfile($tempPath);
            } finally {
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }
            }
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
