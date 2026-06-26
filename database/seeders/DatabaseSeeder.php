<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PricingMatrix;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Branches
        $branchMain = Branch::create([
            'name' => 'Main Branch (Jakarta)',
            'address' => 'Jl. Sudirman No. 1, Jakarta',
            'phone' => '021-123456',
        ]);

        $branchSub = Branch::create([
            'name' => 'Sub Branch (Bandung)',
            'address' => 'Jl. Dago No. 10, Bandung',
            'phone' => '022-654321',
        ]);

        // 2. Users (Staff)
        $roles = [
            ['name' => 'Owner Account', 'email' => 'owner@example.com', 'username' => 'owner', 'role' => 'owner', 'branch_id' => null],
            ['name' => 'Manager Account', 'email' => 'manager@example.com', 'username' => 'manager', 'role' => 'manager', 'branch_id' => $branchMain->id],
            ['name' => 'Cashier Account', 'email' => 'cashier@example.com', 'username' => 'cashier', 'role' => 'cashier', 'branch_id' => $branchMain->id],
            ['name' => 'Designer Account', 'email' => 'designer@example.com', 'username' => 'designer', 'role' => 'designer', 'branch_id' => $branchMain->id],
            ['name' => 'Production Account', 'email' => 'production@example.com', 'username' => 'production', 'role' => 'production', 'branch_id' => $branchMain->id],
        ];

        foreach ($roles as $roleData) {
            User::create([
                'name' => $roleData['name'],
                'email' => $roleData['email'],
                'username' => $roleData['username'],
                'role' => $roleData['role'],
                'branch_id' => $roleData['branch_id'],
                'password' => bcrypt('password123'),
            ]);
        }

        // 3. Products
        $products = [
            ['name' => 'Merchandise', 'pricing_type' => 'unit'],
            ['name' => 'Jasa Desain', 'pricing_type' => 'unit'],
            ['name' => 'Display', 'pricing_type' => 'unit'],
            ['name' => 'Print Kertas', 'pricing_type' => 'unit'],
            ['name' => 'Large Format', 'pricing_type' => 'area'],
            ['name' => 'ID Card', 'pricing_type' => 'unit'],
            ['name' => 'Stempel', 'pricing_type' => 'unit'],
            ['name' => 'Kartu Nama', 'pricing_type' => 'unit'],
            ['name' => 'Produk Jadi', 'pricing_type' => 'unit'],
            ['name' => 'NOTA', 'pricing_type' => 'unit'],
            ['name' => 'DTF Large Format', 'pricing_type' => 'unit'],
        ];

        $productModels = [];
        foreach ($products as $p) {
            $productModels[$p['name']] = Product::create($p);
        }

        // 4. Materials
        $materials = [
            ['name' => 'Flexi China 280g', 'category' => 'meter', 'type' => 'Outdoor', 'dimensions' => '3.2m', 'cost' => 8000, 'price' => 15000, 'remaining_stock' => 500, 'min_stock' => 50, 'branch_id' => $branchMain->id],
            ['name' => 'Flexi Korea 440g', 'category' => 'meter', 'type' => 'Outdoor', 'dimensions' => '3.2m', 'cost' => 15000, 'price' => 30000, 'remaining_stock' => 300, 'min_stock' => 30, 'branch_id' => $branchMain->id],
            ['name' => 'Art Paper 260g A3+', 'category' => 'lembar', 'type' => 'Kertas', 'dimensions' => '32x48cm', 'cost' => 1200, 'price' => 3500, 'remaining_stock' => 1000, 'min_stock' => 100, 'branch_id' => $branchMain->id],
            ['name' => 'HVS 80g A4', 'category' => 'lembar', 'type' => 'Kertas', 'dimensions' => '21x29.7cm', 'cost' => 150, 'price' => 500, 'remaining_stock' => 5000, 'min_stock' => 500, 'branch_id' => $branchMain->id],
        ];

        $materialModels = [];
        foreach ($materials as $m) {
            $m['initial_stock'] = $m['remaining_stock'];
            $m['current_stock'] = $m['remaining_stock'];
            $materialModels[$m['name']] = Material::create($m);
        }

        // 5. Machines
        $machines = [
            ['name' => 'Konica Minolta C1085', 'branch_id' => $branchMain->id],
            ['name' => 'Challenger FY-3208R', 'branch_id' => $branchMain->id],
        ];

        $machineModels = [];
        foreach ($machines as $mac) {
            $machineModels[$mac['name']] = Machine::create($mac);
        }

        // 6. Attributes (Finishing, Cuts, Displays)
        $attributes = [
            ['name' => 'Laminating Glossy', 'type' => 'finishing', 'branch_id' => $branchMain->id],
            ['name' => 'Laminating Doff', 'type' => 'finishing', 'branch_id' => $branchMain->id],
            ['name' => 'Mata Ayam (Eyelets)', 'type' => 'finishing', 'branch_id' => $branchMain->id],
            ['name' => 'Half Cut (Stiker)', 'type' => 'cut', 'branch_id' => $branchMain->id],
            ['name' => 'Die Cut (Stiker)', 'type' => 'cut', 'branch_id' => $branchMain->id],
            ['name' => 'X-Banner Stand', 'type' => 'display', 'branch_id' => $branchMain->id],
        ];

        $attributeModels = [];
        foreach ($attributes as $a) {
            $attributeModels[$a['name']] = Attribute::create($a);
        }

        // 7. Customers
        $customerReguler = Customer::create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 5, Jakarta',
            'email' => 'budi@example.com',
            'type' => 'Reguler',
        ]);

        $customerMitra = Customer::create([
            'name' => 'Alja Print Shop Partner',
            'phone' => '089876543210',
            'address' => 'Jl. Gatot Subroto No. 50, Bandung',
            'email' => 'partner@example.com',
            'type' => 'Mitra',
        ]);

        // 8. Pricing Matrices
        // Large Format (Pricing type = area)
        PricingMatrix::create([
            'branch_id' => $branchMain->id,
            'product_id' => $productModels['Large Format']->id,
            'material_id' => $materialModels['Flexi China 280g']->id,
            'machine_id' => $machineModels['Challenger FY-3208R']->id,
            'finishing_id' => $attributeModels['Mata Ayam (Eyelets)']->id,
            'price' => 20000,
            'cost' => 10000,
            'profit' => 10000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        // Large Format Bulk Discount (Tiered)
        PricingMatrix::create([
            'branch_id' => $branchMain->id,
            'product_id' => $productModels['Large Format']->id,
            'material_id' => $materialModels['Flexi China 280g']->id,
            'machine_id' => $machineModels['Challenger FY-3208R']->id,
            'finishing_id' => $attributeModels['Mata Ayam (Eyelets)']->id,
            'price' => 15000, // Discounted from 20000 to 15000 for 100+ meters
            'cost' => 10000,
            'profit' => 5000,
            'min_qty' => 100,
            'customer_type' => 'Reguler',
        ]);

        // Print Kertas (A3+ Art Paper)
        PricingMatrix::create([
            'branch_id' => $branchMain->id,
            'product_id' => $productModels['Print Kertas']->id,
            'material_id' => $materialModels['Art Paper 260g A3+']->id,
            'machine_id' => $machineModels['Konica Minolta C1085']->id,
            'finishing_id' => $attributeModels['Laminating Glossy']->id,
            'price' => 5000,
            'cost' => 2000,
            'profit' => 3000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        // Print Kertas Bulk Discount (Tiered)
        PricingMatrix::create([
            'branch_id' => $branchMain->id,
            'product_id' => $productModels['Print Kertas']->id,
            'material_id' => $materialModels['Art Paper 260g A3+']->id,
            'machine_id' => $machineModels['Konica Minolta C1085']->id,
            'finishing_id' => $attributeModels['Laminating Glossy']->id,
            'price' => 4000, // Discounted for 100+ sheets
            'cost' => 2000,
            'profit' => 2000,
            'min_qty' => 100,
            'customer_type' => 'Reguler',
        ]);

        // 9. Sample Transactions
        $designerUser = User::where('role', 'designer')->first();
        $cashierUser = User::where('role', 'cashier')->first();

        // Sample Completed Invoice
        $invoice1 = Invoice::create([
            'invoice_number' => 'INV-MN-'.date('Ymd').'-0001',
            'branch_id' => $branchMain->id,
            'created_date' => now()->toDateString(),
            'customer_id' => $customerReguler->id,
            'designer_id' => $designerUser->id,
            'status' => 2, // Lunas
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 0,
            'grand_total' => 100000,
            'total_cost' => 50000,
            'total_profit' => 50000,
            'deadline' => 'Hari Ini',
            'pickup_status' => 1,
            'pickup_date' => now()->toDateString(),
        ]);

        $matrixLargeFormat = PricingMatrix::where('product_id', $productModels['Large Format']->id)->first();
        Order::create([
            'invoice_id' => $invoice1->id,
            'pricing_matrix_id' => $matrixLargeFormat->id,
            'file_name' => 'spanduk_toko.pdf',
            'area' => 5.0, // 5 sqm
            'quantity' => 1,
            'unit_price' => 20000,
            'subtotal' => 100000,
            'cost' => 50000,
            'profit' => 50000,
            'ordered_at' => now()->toDateString(),
            'status' => 2, // Telah Beres
        ]);

        Payment::create([
            'branch_id' => $branchMain->id,
            'invoice_id' => $invoice1->id,
            'user_id' => $cashierUser->id,
            'amount' => 100000,
            'payment_method' => 'Cash',
            'type' => 'settlement',
            'cashier_name' => $cashierUser->name,
        ]);
    }
}
