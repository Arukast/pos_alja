<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Http\Requests;

use Illuminate\Validation\Rule;

final class ExportReportRequest extends ReportFilterRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->route('type'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'type' => [
                'required',
                'string',
                Rule::in(['gross-revenue', 'base-cost', 'net-profit', 'expenses']),
            ],
            'format' => ['required', 'string', Rule::in(['xlsx', 'pdf'])],
        ]);
    }

    public function reportType(): string
    {
        $type = $this->validated('type');

        return is_string($type) ? $type : 'gross-revenue';
    }

    public function exportFormat(): string
    {
        $format = $this->validated('format');

        return is_string($format) ? $format : 'xlsx';
    }
}
