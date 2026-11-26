<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Customer;

final class CreateCustomer
{
    /**
     * @param  array{name: string, phone?: string|null, email?: string|null, address?: string|null, institution: string, type?: string|null}  $data
     */
    public function execute(array $data): Customer
    {
        return Customer::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'institution' => $data['institution'],
            'type' => $data['type'] ?? 'Reguler',
        ]);
    }
}
