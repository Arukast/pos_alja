<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class CalculatorForm extends Form
{
    public string $productId = '';

    public string $materialId = '';

    public string $machineId = '';

    public string $finishingId = '';

    public string $cutId = '';

    public string $displayId = '';

    public int $sides = 1;

    public int $finishingSides = 0;

    public int $quantity = 1;

    public float $width = 1.0;

    public float $length = 1.0;

    public string $fileName = '';

    public string $notes = '';
}
