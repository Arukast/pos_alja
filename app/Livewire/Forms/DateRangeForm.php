<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class DateRangeForm extends Form
{
    public string $startDate = '';

    public string $endDate = '';

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'startDate' => 'required|date',
            'endDate' => 'required|date|after_or_equal:startDate',
        ];
    }
}
