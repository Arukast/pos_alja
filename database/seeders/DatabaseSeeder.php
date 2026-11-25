<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributePricing;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Pricing;
use App\Models\Product;
use App\Models\ProductCategory;
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
            ['name' => 'Merchandise', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['Merchandise']->id],
            ['name' => 'Jasa Desain', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['Jasa Desain']->id],
            ['name' => 'Display', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['Display']->id],
            ['name' => 'Print Kertas', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['Print Kertas']->id],
            ['name' => 'Large Format', 'pricing_type' => 'area', 'product_category_id' => $categoriesMap['Large Format']->id],
            ['name' => 'ID Card', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['ID Card']->id],
            ['name' => 'Stempel', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['Stempel']->id],
            ['name' => 'Kartu Nama', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['Kartu Nama']->id],
            ['name' => 'Produk Jadi', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['Produk Jadi']->id],
            ['name' => 'NOTA', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['NOTA']->id],
            ['name' => 'DTF Large Format', 'pricing_type' => 'unit', 'product_category_id' => $categoriesMap['DTF Large Format']->id],
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

        // 5. Attributes (Finishing, Cuts, Displays)
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

        // 7. Pricing
        // Large Format (Pricing type = area)
        $p1 = Pricing::create([
            'branch_id' => $branchMain->id,
            'product_id' => $productModels['Large Format']->id,
            'material_id' => $materialModels['Flexi China 280g']->id,
            'price' => 20000,
            'cost' => 10000,
            'profit' => 10000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        // Attribute pricing modifier
        AttributePricing::create([
            'branch_id' => $branchMain->id,
            'pricing_id' => $p1->id,
            'attribute_id' => $attributeModels['Mata Ayam (Eyelets)']->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        // Large Format Bulk Discount (Tiered)
        $p2 = Pricing::create([
            'branch_id' => $branchMain->id,
            'product_id' => $productModels['Large Format']->id,
            'material_id' => $materialModels['Flexi China 280g']->id,
            'price' => 15000, // Discounted from 20000 to 15000 for 100+ meters
            'cost' => 10000,
            'profit' => 5000,
            'min_qty' => 100,
            'customer_type' => 'Reguler',
        ]);

        AttributePricing::create([
            'branch_id' => $branchMain->id,
            'pricing_id' => $p2->id,
            'attribute_id' => $attributeModels['Mata Ayam (Eyelets)']->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        // Print Kertas (A3+ Art Paper)
        $p3 = Pricing::create([
            'branch_id' => $branchMain->id,
            'product_id' => $productModels['Print Kertas']->id,
            'material_id' => $materialModels['Art Paper 260g A3+']->id,
            'price' => 5000,
            'cost' => 2000,
            'profit' => 3000,
            'min_qty' => 1,
            'customer_type' => 'Reguler',
        ]);

        AttributePricing::create([
            'branch_id' => $branchMain->id,
            'pricing_id' => $p3->id,
            'attribute_id' => $attributeModels['Laminating Glossy']->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
        ]);

        // Print Kertas Bulk Discount (Tiered)
        $p4 = Pricing::create([
            'branch_id' => $branchMain->id,
            'product_id' => $productModels['Print Kertas']->id,
            'material_id' => $materialModels['Art Paper 260g A3+']->id,
            'price' => 4000, // Discounted for 100+ sheets
            'cost' => 2000,
            'profit' => 2000,
            'min_qty' => 100,
            'customer_type' => 'Reguler',
        ]);

        AttributePricing::create([
            'branch_id' => $branchMain->id,
            'pricing_id' => $p4->id,
            'attribute_id' => $attributeModels['Laminating Glossy']->id,
            'price' => 0,
            'cost' => 0,
            'profit' => 0,
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

        $pricingLargeFormat = Pricing::where('product_id', $productModels['Large Format']->id)->first();
        Order::create([
            'invoice_id' => $invoice1->id,
            'pricing_id' => $pricingLargeFormat->id,
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
