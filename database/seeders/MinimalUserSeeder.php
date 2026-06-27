<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MinimalUserSeeder extends Seeder
{
    public function run(): void
    {
        // Create 1 Main Branch
        $branch = Branch::create([
            'name' => 'Main Branch (Jakarta)',
            'address' => 'Jl. Sudirman No. 1, Jakarta',
            'phone' => '021-123456',
        ]);

        $password = Hash::make('password');

        // Owner (No branch assigned)
        User::create([
            'name' => 'Owner Account',
            'email' => 'owner@example.com',
            'username' => 'owner',
            'role' => 'owner',
            'branch_id' => null,
            'password' => $password,
        ]);

        // Manager
        User::create([
            'name' => 'Manager Account',
            'email' => 'manager@example.com',
            'username' => 'manager',
            'role' => 'manager',
            'branch_id' => $branch->id,
            'password' => $password,
        ]);

        // Cashier
        User::create([
            'name' => 'Cashier Account',
            'email' => 'cashier@example.com',
            'username' => 'cashier',
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'password' => $password,
        ]);

        // Designer
        User::create([
            'name' => 'Designer Account',
            'email' => 'designer@example.com',
            'username' => 'designer',
            'role' => 'designer',
            'branch_id' => $branch->id,
            'password' => $password,
        ]);

        // Production
        User::create([
            'name' => 'Production Account',
            'email' => 'production@example.com',
            'username' => 'production',
            'role' => 'production',
            'branch_id' => $branch->id,
            'password' => $password,
        ]);
    }
}
