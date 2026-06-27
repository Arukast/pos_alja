<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Create a customer and log the activity.
     */
    public function createCustomer(array $data, User $creator): Customer
    {
        return DB::transaction(function () use ($data, $creator) {
            $customer = Customer::create([
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'email' => $data['email'] ?? null,
                'type' => $data['type'] ?? 'Reguler',
            ]);

            ActivityLog::create([
                'branch_id' => $creator->branch_id,
                'user_id' => $creator->id,
                'username' => $creator->username,
                'role' => $creator->role,
                'action' => 'CREATE',
                'description' => "Created customer: {$customer->name} ({$customer->type})",
                'model_type' => Customer::class,
                'model_id' => $customer->id,
                'payload' => $customer->toArray(),
            ]);

            return $customer;
        });
    }
}
