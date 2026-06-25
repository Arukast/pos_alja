<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $roles = [
            ['name' => 'Owner Account', 'email' => 'owner@example.com', 'username' => 'admin_owner', 'role' => 'owner'],
            ['name' => 'Manager Account', 'email' => 'manager@example.com', 'username' => 'admin_manager', 'role' => 'manager'],
            ['name' => 'Cashier Account', 'email' => 'cashier@example.com', 'username' => 'user_cashier', 'role' => 'cashier'],
            ['name' => 'Designer Account', 'email' => 'designer@example.com', 'username' => 'user_designer', 'role' => 'designer'],
            ['name' => 'Production Account', 'email' => 'production@example.com', 'username' => 'user_production', 'role' => 'production'],
        ];

        foreach ($roles as $roleData) {
            User::factory()->create([
                'name' => $roleData['name'],
                'email' => $roleData['email'],
                'username' => $roleData['username'],
                'role' => $roleData['role'],
                'password' => bcrypt('password123'),
            ]);
        }
    }
}
