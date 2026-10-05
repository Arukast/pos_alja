<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\Branch;
use Illuminate\Validation\Rule;
use Livewire\Form;

class BranchForm extends Form
{
    public string $name = '';

    public string $code = '';

    public string $address = '';

    public string $phone = '';

    public bool $is_active = true;

    public float $tax_rate = 0.0;

    public string $receipt_header = '';

    public float $reseller_discount_percent = 0.0;

    /**
     * Get the validation rules.
     */
    public function rules(?string $ignoreId = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('branches', 'code')
                    ->ignore($ignoreId)
                    ->whereNull('deleted_at'),
            ],
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'tax_rate' => 'required|numeric|min:0|max:100',
            'receipt_header' => 'nullable|string|max:500',
            'reseller_discount_percent' => 'required|numeric|min:0|max:100',
        ];
    }

    /**
     * Set form fields from a Branch model.
     */
    public function setBranch(Branch $branch): void
    {
        $this->name = $branch->name;
        $this->code = $branch->code ?? '';
        $this->address = $branch->address ?? '';
        $this->phone = $branch->phone ?? '';
        $this->is_active = (bool) $branch->is_active;
        $this->tax_rate = (float) ($branch->settings['tax_rate'] ?? 0.0);
        $this->receipt_header = (string) ($branch->settings['receipt_header'] ?? '');
        $this->reseller_discount_percent = (float) ($branch->settings['reseller_discount_percent'] ?? 0.0);
    }

    /**
     * Reset the form fields.
     */
    public function resetFields(): void
    {
        $this->name = '';
        $this->code = '';
        $this->address = '';
        $this->phone = '';
        $this->is_active = true;
        $this->tax_rate = 0.0;
        $this->receipt_header = '';
        $this->reseller_discount_percent = 0.0;
    }

    /**
     * Validate and return payload for the service.
     */
    public function validateAndGetPayload(?string $ignoreId = null): array
    {
        $validated = $this->validate($this->rules($ignoreId));

        return [
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'settings' => [
                'tax_rate' => (float) $this->tax_rate,
                'receipt_header' => $this->receipt_header,
                'reseller_discount_percent' => (float) $this->reseller_discount_percent,
            ],
        ];
    }
}
