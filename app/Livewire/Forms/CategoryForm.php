<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Form;

class CategoryForm extends Form
{
    public string $name = '';

    public string $calculation_archetype = 'per_piece';

    public array $allowed_attribute_types = [];

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
            'calculation_archetype' => [
                'required',
                'string',
                'in:per_piece,surface_area,linear_dimension,tiered_volume',
            ],
            'allowed_attribute_types' => [
                'nullable',
                'array',
            ],
            'allowed_attribute_types.*' => [
                'string',
                'exists:attribute_types,name',
            ],
        ];
    }

    /**
     * Reset the form fields.
     */
    public function resetFields(): void
    {
        $this->name = '';
        $this->calculation_archetype = 'per_piece';
        $this->allowed_attribute_types = [];
    }

    /**
     * Validate and return payload for the service.
     */
    public function validateAndGetPayload(): array
    {
        $this->validate($this->rules());

        return [
            'name' => $this->name,
            'calculation_archetype' => $this->calculation_archetype,
            'allowed_attribute_types' => ! empty($this->allowed_attribute_types) ? $this->allowed_attribute_types : null,
        ];
    }
}
