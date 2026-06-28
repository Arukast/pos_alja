<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ExpenseForm extends Form
{
    #[Validate('required|integer|min:100')]
    public string $amount = '';

    #[Validate('required|string|in:modal,laba')]
    public string $type = 'laba';

    #[Validate('required|date')]
    public string $incurred_at = '';

    #[Validate('required|string|max:500')]
    public string $description = '';
}
