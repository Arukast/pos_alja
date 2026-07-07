<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Machine;
use Livewire\Form;

class MachineForm extends Form
{
    public string $name = '';

    public ?string $branch_id = null;

    public ?float $cost = null;

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'cost' => 'required|numeric|min:0',
        ];
    }

    /**
     * Set form fields from a Machine model.
     */
    public function setMachine(Machine $machine): void
    {
        $this->name = $machine->name;
        $this->branch_id = $machine->branch_id;
        $this->cost = (float) $machine->cost;
    }

    /**
     * Reset the form fields.
     */
    public function resetFields(): void
    {
        $this->name = '';
        $this->branch_id = null;
        $this->cost = null;
    }

    /**
     * Validate and return payload for the service.
     */
    public function validateAndGetPayload(): array
    {
        $this->validate();

        return [
            'name' => $this->name,
            'branch_id' => $this->branch_id ? (string) $this->branch_id : null,
            'cost' => (float) $this->cost,
        ];
    }
}
