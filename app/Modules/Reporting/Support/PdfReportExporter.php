<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;

final class PdfReportExporter
{
    private const MAX_DETAIL_ROWS = 200;

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
    public function render(array $report, string $filename): Response
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->buildHtml($report), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $content = (string) $dompdf->output();

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

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
    private function buildHtml(array $report): string
    {
        $summaryRows = '';

        foreach ($report['summary'] as [$label, $value]) {
            $summaryRows .= '<tr>'
                .'<td class="label">'.$this->escape($label).'</td>'
                .'<td class="value">'.$this->escape($value).'</td>'
                .'</tr>';
        }

        $sectionsHtml = '';

        foreach ($report['sections'] as $section) {
            $headersHtml = '';

            foreach ($section['headers'] as $header) {
                $headersHtml .= '<th>'.$this->escape($header).'</th>';
            }

            $totalRows = count($section['rows']);
            $visibleRows = array_slice($section['rows'], 0, self::MAX_DETAIL_ROWS);
            $rowsHtml = '';

            if ($visibleRows === []) {
                $colspan = max(1, count($section['headers']));
                $rowsHtml = '<tr><td colspan="'.$colspan.'" class="empty">Tidak ada data pada periode ini.</td></tr>';
            } else {
                foreach ($visibleRows as $row) {
                    $cellsHtml = '';

                    foreach ($row as $cell) {
                        $cellsHtml .= '<td>'.$this->escape($cell).'</td>';
                    }

                    $rowsHtml .= '<tr>'.$cellsHtml.'</tr>';
                }
            }

            $note = $totalRows > self::MAX_DETAIL_ROWS
                ? '<p class="note">Menampilkan '.self::MAX_DETAIL_ROWS.' dari '.$totalRows.' baris pertama. Gunakan ekspor Excel untuk seluruh data.</p>'
                : '';

            $sectionsHtml .= '<h2>'.$this->escape($section['title']).'</h2>'
                .'<table><thead><tr>'.$headersHtml.'</tr></thead><tbody>'.$rowsHtml.'</tbody></table>'
                .$note;
        }

        return '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><style>'
            .'body{font-family:"DejaVu Sans",sans-serif;font-size:10px;color:#111827;margin:20px;}'
            .'h1{font-size:16px;margin:0 0 6px 0;}'
            .'h2{font-size:12px;margin:16px 0 6px 0;color:#1f2937;}'
            .'.meta{margin:0 0 12px 0;color:#4b5563;font-size:9px;line-height:1.5;}'
            .'table{width:100%;border-collapse:collapse;margin-bottom:8px;}'
            .'th,td{border:1px solid #d1d5db;padding:5px 6px;text-align:left;vertical-align:top;}'
            .'th{background:#f3f4f6;font-weight:bold;}'
            .'.summary td.label{width:45%;background:#f9fafb;font-weight:bold;}'
            .'.summary td.value{font-weight:bold;}'
            .'.empty{text-align:center;color:#6b7280;font-style:italic;}'
            .'.note{margin:4px 0 0 0;font-size:8.5px;color:#6b7280;}'
            .'</style></head><body>'
            .'<h1>'.$this->escape($report['title']).'</h1>'
            .'<p class="meta">'
            .'Cabang: '.$this->escape($report['branch']).'<br>'
            .'Periode: '.$this->escape($report['period']).'<br>'
            .'Dicetak: '.$this->escape($report['generated_at'])
            .'</p>'
            .'<h2>Ringkasan</h2>'
            .'<table class="summary"><tbody>'.$summaryRows.'</tbody></table>'
            .$sectionsHtml
            .'</body></html>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
