<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class MaterialForm extends Form
{
    public string $name = '';

    public string $category = '';

    public string $type = '';

    public string $dimensions = '';

    public float $cost = 0.0;

    public float $price = 0.0;

    public float $current_stock = 0.0;

    public float $min_stock = 0.0;

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'category' => 'required|string|in:lembar,meter,pcs,display,finishing,potong,stempel',
            'type' => 'nullable|string|max:100',
            'dimensions' => 'nullable|string|max:100',
            'cost' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'min_stock' => 'required|numeric|min:0',
        ];
    }

    /**
     * Reset the form fields.
     */
    public function resetFields(): void
    {
        $this->name = '';
        $this->category = '';
        $this->type = '';
        $this->dimensions = '';
        $this->cost = 0.0;
        $this->price = 0.0;
        $this->current_stock = 0.0;
        $this->min_stock = 0.0;
    }

    /**
     * Validate and return payload for the service.
     */
    public function validateAndGetPayload(): array
    {
        $this->validate($this->rules());

        return [
            'name' => $this->name,
            'category' => $this->category,
            'type' => $this->type ?: null,
            'dimensions' => $this->dimensions ?: null,
            'cost' => (float) $this->cost,
            'price' => (float) $this->price,
            'current_stock' => (float) $this->current_stock,
            'min_stock' => (float) $this->min_stock,
        ];
    }
}
