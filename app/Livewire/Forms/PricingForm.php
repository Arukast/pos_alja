<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class PricingForm extends Form
{
    public ?string $pricingId = null;

    #[Validate('required|string|uuid|exists:products,id')]
    public string $productId = '';

    #[Validate('required|string|uuid|exists:materials,id')]
    public string $materialId = '';

    #[Validate('required|string|max:50')]
    public string $customerType = 'Reguler';

    #[Validate('required|integer|min:1')]
    public int $minQty = 1;

    #[Validate('required|numeric|min:0')]
    public string $price = '';

    #[Validate('required|numeric|min:0')]
    public string $cost = '';
}
