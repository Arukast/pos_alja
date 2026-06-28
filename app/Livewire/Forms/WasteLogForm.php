<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class WasteLogForm extends Form
{
    public string $materialId = '';

    public string $machineId = '';

    public string $quantity = '';

    public string $dimensions = '';

    public string $designerId = '';

    public string $notes = '';

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'materialId' => 'required|string|exists:materials,id',
            'machineId' => 'nullable|string|exists:machines,id',
            'quantity' => 'required|numeric|min:0.01',
            'dimensions' => 'nullable|string|max:50',
            'designerId' => 'nullable|string|exists:users,id',
            'notes' => 'required|string|max:500',
        ];
    }
}
