<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class RestockForm extends Form
{
    #[Validate('required|string|uuid|exists:materials,id')]
    public string $selectedMaterialId = '';

    #[Validate('required|numeric|min:0.01')]
    public string $addStockQuantity = '';

    #[Validate('nullable|string|max:255')]
    public string $addStockNotes = '';
}
