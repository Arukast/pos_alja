<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Customer;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\CustomerInstitution;
use Illuminate\Support\Facades\DB;

final class UpdateCustomer
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{name: string, phone?: string|null, email?: string|null, address?: string|null, institution?: string, institutions?: list<string>, type?: string|null}  $data
     */
    public function execute(Customer $customer, array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($customer, $data, $actor): Customer {
            $values = $data['institutions'] ?? (isset($data['institution']) ? [$data['institution']] : []);
            $labels = CustomerInstitution::normalize($values);
            if ($labels === []) {
                throw new \InvalidArgumentException('At least one customer institution label is required.');
            }

            $customer->fill([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'institution' => $labels[0],
                'institutions' => $labels,
                'type' => $data['type'] ?? 'Reguler',
            ]);
            $customer->save();

            $this->audit->record(
                $actor,
                'UPDATE',
                "Memperbarui pelanggan: {$customer->name}",
                $customer,
                [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'institution' => $customer->institution,
                    'institutions' => $customer->institutions,
                    'type' => $customer->type,
                ],
            );

            return $customer;
        });
    }
}
