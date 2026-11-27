<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductQuantityDiscount;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $type = 'minimal';

        if (isset($this->command)) {
            $type = $this->command->choice(
                'Which seeder type would you like to run?',
                [
                    'minimal' => 'Minimal (1 user per role, 1 branch)',
                    'huge' => 'Huge Dataset (3 branches, larger team, 5000+ invoices/payments/expenses/waste logs)',
                    'original' => 'Original Standard (Jakarta & Bandung, 1 user per role, some demo data)',
                ],
                'minimal'
            );
        }

        if ($type === 'huge') {
            $this->call(HugeDatasetSeeder::class);
        } elseif ($type === 'original') {
            $this->seedOriginal();
        } else {
            $this->call(MinimalUserSeeder::class);
        }
    }

    public function seedOriginal(): void
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

        // 3a. Product Categories
        $categoriesMap = [
            'Merchandise' => ProductCategory::create(['name' => 'Merchandise', 'calculation_archetype' => 'per_piece']),
            'Jasa Desain' => ProductCategory::create(['name' => 'Jasa Desain', 'calculation_archetype' => 'per_piece']),
            'Display' => ProductCategory::create(['name' => 'Display', 'calculation_archetype' => 'per_piece']),
            'Print Kertas' => ProductCategory::create(['name' => 'Print Kertas', 'calculation_archetype' => 'per_piece']),
            'Large Format' => ProductCategory::create(['name' => 'Large Format', 'calculation_archetype' => 'surface_area']),
            'ID Card' => ProductCategory::create(['name' => 'ID Card', 'calculation_archetype' => 'per_piece']),
            'Stempel' => ProductCategory::create(['name' => 'Stempel', 'calculation_archetype' => 'per_piece']),
            'Kartu Nama' => ProductCategory::create(['name' => 'Kartu Nama', 'calculation_archetype' => 'per_piece']),
            'Produk Jadi' => ProductCategory::create(['name' => 'Produk Jadi', 'calculation_archetype' => 'per_piece']),
            'NOTA' => ProductCategory::create(['name' => 'NOTA', 'calculation_archetype' => 'per_piece']),
            'DTF Large Format' => ProductCategory::create(['name' => 'DTF Large Format', 'calculation_archetype' => 'surface_area']),
        ];

        // 3. Products
        $products = [
            ['name' => 'Merchandise', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['Merchandise']->id],
            ['name' => 'Jasa Desain', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['Jasa Desain']->id],
            ['name' => 'Display', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['Display']->id],
            ['name' => 'Print Kertas', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['Print Kertas']->id],
            ['name' => 'Large Format', 'selling_unit' => 'm2', 'product_category_id' => $categoriesMap['Large Format']->id],
            ['name' => 'ID Card', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['ID Card']->id],
            ['name' => 'Stempel', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['Stempel']->id],
            ['name' => 'Kartu Nama', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['Kartu Nama']->id],
            ['name' => 'Produk Jadi', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['Produk Jadi']->id],
            ['name' => 'NOTA', 'selling_unit' => 'pcs', 'product_category_id' => $categoriesMap['NOTA']->id],
            ['name' => 'DTF Large Format', 'selling_unit' => 'm2', 'product_category_id' => $categoriesMap['DTF Large Format']->id],
        ];

        $productModels = [];
        foreach ($products as $p) {
            $productModels[$p['name']] = Product::create($p);
        }

        // 4. Materials
        $materials = [
            ['name' => 'Flexi China 280g', 'product_category_id' => $categoriesMap['Large Format']->id, 'type' => 'Outdoor', 'dimensions' => '3.2m', 'unit' => 'm2', 'cost' => 8000, 'price' => 15000],
            ['name' => 'Flexi Korea 440g', 'product_category_id' => $categoriesMap['Large Format']->id, 'type' => 'Outdoor', 'dimensions' => '3.2m', 'unit' => 'm2', 'cost' => 15000, 'price' => 30000],
            ['name' => 'Art Paper 260g A3+', 'product_category_id' => $categoriesMap['Print Kertas']->id, 'type' => 'Kertas', 'dimensions' => '32x48cm', 'unit' => 'lembar', 'cost' => 1200, 'price' => 3500],
            ['name' => 'HVS 80g A4', 'product_category_id' => $categoriesMap['Print Kertas']->id, 'type' => 'Kertas', 'dimensions' => '21x29.7cm', 'unit' => 'lembar', 'cost' => 150, 'price' => 500],
        ];

        $stocksMap = [
            'Flexi China 280g' => ['current' => 500, 'min' => 50],
            'Flexi Korea 440g' => ['current' => 300, 'min' => 30],
            'Art Paper 260g A3+' => ['current' => 1000, 'min' => 100],
            'HVS 80g A4' => ['current' => 5000, 'min' => 500],
        ];

        $materialModels = [];
        foreach ($materials as $m) {
            $mat = Material::create($m);
            $materialModels[$m['name']] = $mat;

            MaterialBranchStock::create([
                'id' => (string) Str::uuid(),
                'material_id' => $mat->id,
                'branch_id' => $branchMain->id,
                'current_stock' => $stocksMap[$m['name']]['current'],
                'min_stock' => $stocksMap[$m['name']]['min'],
            ]);
        }

        foreach ($productModels as $productModel) {
            $matchingMaterialIds = collect($materialModels)
                ->where('product_category_id', $productModel->product_category_id)
                ->pluck('id')
                ->values()
                ->all();

            if ($matchingMaterialIds !== []) {
                $productModel->materials()->sync($matchingMaterialIds);
            }
        }

        // 5. Attributes (Finishing, Cuts, Displays)
        $attributes = [
            ['name' => 'Laminating Glossy', 'type' => 'finishing', 'branch_id' => $branchMain->id, 'price' => 3000, 'cost' => 1500],
            ['name' => 'Laminating Doff', 'type' => 'finishing', 'branch_id' => $branchMain->id, 'price' => 0, 'cost' => 0],
            ['name' => 'Mata Ayam (Eyelets)', 'type' => 'finishing', 'branch_id' => $branchMain->id, 'price' => 0, 'cost' => 0],
            ['name' => 'Half Cut (Stiker)', 'type' => 'cut', 'branch_id' => $branchMain->id, 'price' => 0, 'cost' => 0],
            ['name' => 'Die Cut (Stiker)', 'type' => 'cut', 'branch_id' => $branchMain->id, 'price' => 0, 'cost' => 0],
            ['name' => 'X-Banner Stand', 'type' => 'display', 'branch_id' => $branchMain->id, 'price' => 0, 'cost' => 0],
        ];

        foreach ($attributes as $a) {
            Attribute::create($a);
        }

        // 6. Customers
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

        // 7. Product quantity tiers
        ProductQuantityDiscount::create([
            'product_id' => $productModels['Large Format']->id,
            'min_qty' => 100,
            'discount_percent' => 10,
        ]);

        ProductQuantityDiscount::create([
            'product_id' => $productModels['Print Kertas']->id,
            'min_qty' => 100,
            'discount_percent' => 5,
        ]);

        // 8. Sample Transactions
        $designerUser = User::where('role', 'designer')->first();
        $cashierUser = User::where('role', 'cashier')->first();

        // Sample Completed Invoice
        $invoice1 = Invoice::create([
            'invoice_number' => 'INV-MN-'.date('Ymd').'-0001',
            'branch_id' => $branchMain->id,
            'created_date' => now()->toIso8601String(),
            'customer_id' => $customerReguler->id,
            'designer_id' => $designerUser->id,
            'status' => 2, // Lunas
            'discount' => 0,
            'dp_amount' => 0,
            'accounts_receivable' => 0,
            'grand_total' => 100000,
            'total_cost' => 50000,
            'total_profit' => 50000,
            'deadline' => now()->addDay()->toIso8601String(),
            'pickup_status' => 1,
            'pickup_date' => now()->toIso8601String(),
        ]);

        Order::create([
            'invoice_id' => $invoice1->id,
            'product_id' => $productModels['Large Format']->id,
            'material_id' => $materialModels['Flexi China 280g']->id,
            'customer_type' => 'Reguler',
            'reseller_discount_percent' => 0,
            'quantity_discount_percent' => 0,
            'discount_amount' => 0,
            'file_name' => 'spanduk_toko.pdf',
            'area' => 5.0, // 5 sqm
            'quantity' => 1,
            'unit_price' => 20000,
            'subtotal' => 100000,
            'cost' => 50000,
            'profit' => 50000,
            'ordered_at' => now()->toIso8601String(),
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
