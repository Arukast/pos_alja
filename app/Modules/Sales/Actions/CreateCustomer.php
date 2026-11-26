<?php

declare(strict_types=1);

namespace App\Modules\Sales\Actions;

use App\Models\Customer;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

final class CreateCustomer
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{name: string, phone?: string|null, email?: string|null, address?: string|null, institution: string, type?: string|null}  $data
     */
    public function execute(array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($data, $actor): Customer {
            $customer = Customer::create([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'institution' => $data['institution'],
                'type' => $data['type'] ?? 'Reguler',
            ]);

            $this->audit->record(
                $actor,
                'CREATE',
                "Membuat pelanggan: {$customer->name}",
                $customer,
                [
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'institution' => $customer->institution,
                    'type' => $customer->type,
                ],
            );

            return $customer;
        });
    }
}
