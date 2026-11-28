<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Material;
use App\Models\MaterialBranchStock;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductQuantityDiscount;
use App\Models\StockLedger;
use App\Models\User;
use App\Models\WastedMaterial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HugeDatasetSeeder extends Seeder
{
    public function run(): void
    {
        // Prevent event dispatching during massive seed for performance
        DB::connection()->unsetEventDispatcher();

        $password = Hash::make('password');

        // 1. Branches
        $branches = [
            Branch::create([
                'name' => 'Jakarta Main',
                'address' => 'Jl. Sudirman No. 1, Jakarta',
                'phone' => '021-111111',
            ]),
            Branch::create([
                'name' => 'Bandung Branch',
                'address' => 'Jl. Dago No. 10, Bandung',
                'phone' => '022-222222',
            ]),
            Branch::create([
                'name' => 'Surabaya Branch',
                'address' => 'Jl. Tunjungan No. 5, Surabaya',
                'phone' => '031-333333',
            ]),
        ];

        // 2. Users (Staff) - 3 Owners, and for each branch: 3 Managers, 10 Cashiers, 10 Designers, 10 Production
        $users = [];
        $designersByBranch = [];
        $cashiersByBranch = [];
        $operatorsByBranch = [];

        // 3 Owners (no branch)
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'name' => "Owner Account {$i}",
                'email' => "owner{$i}@example.com",
                'username' => "owner{$i}",
                'role' => 'owner',
                'branch_id' => null,
                'password' => $password,
            ]);
        }

        foreach ($branches as $index => $branch) {
            $branchCode = ($index === 0) ? 'JKT' : (($index === 1) ? 'BDG' : 'SUB');
            $designersByBranch[$branch->id] = [];
            $cashiersByBranch[$branch->id] = [];
            $operatorsByBranch[$branch->id] = [];

            // 3 Managers per branch
            for ($i = 1; $i <= 3; $i++) {
                User::create([
                    'name' => "Manager {$branchCode} {$i}",
                    'email' => "manager.{$branchCode}{$i}@example.com".($index === 0 && $i === 1 ? '' : rand(1, 1000)),
                    'username' => "manager_{$branchCode}_{$i}",
                    'role' => 'manager',
                    'branch_id' => $branch->id,
                    'password' => $password,
                ]);
            }

            // 10 Cashiers per branch
            for ($i = 1; $i <= 10; $i++) {
                $u = User::create([
                    'name' => "Cashier {$branchCode} {$i}",
                    'email' => "cashier.{$branchCode}{$i}@example.com".rand(1, 100000),
                    'username' => "cashier_{$branchCode}_{$i}",
                    'role' => 'cashier',
                    'branch_id' => $branch->id,
                    'password' => $password,
                ]);
                $cashiersByBranch[$branch->id][] = $u;
            }

            // 10 Designers per branch
            for ($i = 1; $i <= 10; $i++) {
                $u = User::create([
                    'name' => "Designer {$branchCode} {$i}",
                    'email' => "designer.{$branchCode}{$i}@example.com".rand(1, 100000),
                    'username' => "designer_{$branchCode}_{$i}",
                    'role' => 'designer',
                    'branch_id' => $branch->id,
                    'password' => $password,
                ]);
                $designersByBranch[$branch->id][] = $u;
            }

            // 10 Production Operators per branch
            for ($i = 1; $i <= 10; $i++) {
                $u = User::create([
                    'name' => "Production {$branchCode} {$i}",
                    'email' => "production.{$branchCode}{$i}@example.com".rand(1, 100000),
                    'username' => "production_{$branchCode}_{$i}",
                    'role' => 'production',
                    'branch_id' => $branch->id,
                    'password' => $password,
                ]);
                $operatorsByBranch[$branch->id][] = $u;
            }
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

        // Create global materials first
        $globalMaterials = [
            ['name' => 'Flexi China 280g', 'product_category_id' => $categoriesMap['Large Format']->id, 'type' => 'Outdoor', 'dimensions' => '3.2m', 'unit' => 'm2', 'cost' => 8000, 'price' => 15000],
            ['name' => 'Flexi Korea 440g', 'product_category_id' => $categoriesMap['Large Format']->id, 'type' => 'Outdoor', 'dimensions' => '3.2m', 'unit' => 'm2', 'cost' => 15000, 'price' => 30000],
            ['name' => 'Art Paper 260g A3+', 'product_category_id' => $categoriesMap['Print Kertas']->id, 'type' => 'Kertas', 'dimensions' => '32x48cm', 'unit' => 'lembar', 'cost' => 1200, 'price' => 3500],
            ['name' => 'HVS 80g A4', 'product_category_id' => $categoriesMap['Print Kertas']->id, 'type' => 'Kertas', 'dimensions' => '21x29.7cm', 'unit' => 'lembar', 'cost' => 150, 'price' => 500],
        ];

        $materialModels = [];
        foreach ($globalMaterials as $m) {
            $materialModels[$m['name']] = Material::create($m);
        }

        // 4. Materials and Attributes per branch
        $materialsByBranch = [];
        $printOptionsByBranch = [];

        foreach ($branches as $branch) {
            // Materials
            $materialsStock = [
                'Flexi China 280g' => ['current_stock' => 5000, 'min_stock' => 50],
                'Flexi Korea 440g' => ['current_stock' => 3000, 'min_stock' => 30],
                'Art Paper 260g A3+' => ['current_stock' => 10000, 'min_stock' => 100],
                'HVS 80g A4' => ['current_stock' => 50000, 'min_stock' => 500],
            ];

            $materialsByBranch[$branch->id] = [];
            foreach ($materialsStock as $name => $stockInfo) {
                $globalMat = $materialModels[$name];
                $materialsByBranch[$branch->id][$name] = $globalMat;

                MaterialBranchStock::create([
                    'id' => (string) Str::uuid(),
                    'material_id' => $globalMat->id,
                    'branch_id' => $branch->id,
                    'current_stock' => $stockInfo['current_stock'],
                    'min_stock' => $stockInfo['min_stock'],
                ]);
            }

            // Attributes
            $attributes = [
                ['name' => 'Laminating Glossy', 'type' => 'finishing', 'branch_id' => $branch->id, 'price' => 3000, 'cost' => 1500],
                ['name' => 'Laminating Doff', 'type' => 'finishing', 'branch_id' => $branch->id, 'price' => 0, 'cost' => 0],
                ['name' => 'Mata Ayam (Eyelets)', 'type' => 'finishing', 'branch_id' => $branch->id, 'price' => 0, 'cost' => 0],
                ['name' => 'Half Cut (Stiker)', 'type' => 'cut', 'branch_id' => $branch->id, 'price' => 0, 'cost' => 0],
                ['name' => 'Die Cut (Stiker)', 'type' => 'cut', 'branch_id' => $branch->id, 'price' => 0, 'cost' => 0],
                ['name' => 'X-Banner Stand', 'type' => 'display', 'branch_id' => $branch->id, 'price' => 0, 'cost' => 0],
            ];

            foreach ($attributes as $a) {
                Attribute::create($a);
            }

            $printOptionsByBranch[$branch->id] = [
                [
                    'product' => $productModels['Large Format'],
                    'material' => $materialsByBranch[$branch->id]['Flexi China 280g'],
                ],
                [
                    'product' => $productModels['Print Kertas'],
                    'material' => $materialsByBranch[$branch->id]['Art Paper 260g A3+'],
                ],
            ];
        }

        foreach ([
            ['product_id' => $productModels['Large Format']->id, 'min_qty' => 100, 'discount_percent' => 10],
            ['product_id' => $productModels['Print Kertas']->id, 'min_qty' => 100, 'discount_percent' => 5],
        ] as $tier) {
            ProductQuantityDiscount::create($tier);
        }

        // 5. Customers
        $customers = [];
        for ($i = 1; $i <= 150; $i++) {
            $customers[] = Customer::create([
                'name' => 'Customer '.($i % 2 === 0 ? 'Partner ' : 'Retail ').$i,
                'phone' => '0812'.rand(10000000, 99999999),
                'address' => 'Jl. Example No. '.$i.', City',
                'email' => "customer{$i}@example.com",
                'type' => $i % 5 === 0 ? 'Mitra' : 'Reguler',
            ]);
        }

        // 6. Generate Massive Historical Data (last 90 days)
        $startDate = now()->subDays(90);
        $totalInvoices = 0;

        // Pre-create some random files/names
        $fileNames = ['banner_promo.pdf', 'brosur_flyer.pdf', 'sticker_label.tiff', 'kartu_nama_final.cdr', 'spanduk_warung.jpg', 'baliho_perumahan.png', 'id_card_karyawan.pdf'];

        // We will seed in a transaction block for speed
        DB::transaction(function () use ($branches, $designersByBranch, $cashiersByBranch, $operatorsByBranch, $printOptionsByBranch, $customers, $startDate, $fileNames, &$totalInvoices, $productModels) {
            for ($day = 0; $day <= 90; $day++) {
                $currentSimulatedDate = (clone $startDate)->addDays($day);

                foreach ($branches as $branch) {
                    // Random number of invoices per branch per day: 18 - 25
                    $invoiceCount = rand(18, 25);

                    for ($inv = 1; $inv <= $invoiceCount; $inv++) {
                        $totalInvoices++;
                        $createdTime = (clone $currentSimulatedDate)->setHour(rand(8, 20))->setMinute(rand(0, 59));

                        $branchCode = ($branch->name === 'Jakarta Main') ? 'JKT' : (($branch->name === 'Bandung Branch') ? 'BDG' : 'SUB');
                        $invoiceNumber = 'INV-'.$branchCode.'-'.$createdTime->format('Ymd').'-'.str_pad($inv, 4, '0', STR_PAD_LEFT);

                        $customer = $customers[array_rand($customers)];
                        $designer = $designersByBranch[$branch->id][array_rand($designersByBranch[$branch->id])];
                        $cashier = $cashiersByBranch[$branch->id][array_rand($cashiersByBranch[$branch->id])];

                        // Determine payment status
                        // 85% paid, 10% down payment, 5% unpaid
                        $statusRand = rand(1, 100);
                        if ($statusRand <= 85) {
                            $status = 2; // Paid
                        } elseif ($statusRand <= 95) {
                            $status = 1; // Down Payment
                        } else {
                            $status = 0; // Unpaid
                        }

                        // Determine pickup status
                        $pickupStatus = ($status === 2 && rand(1, 10) <= 9) ? 1 : 0;
                        $pickupDate = $pickupStatus ? (clone $createdTime)->addHours(rand(2, 48))->toIso8601String() : null;

                        // Create invoice placeholder first
                        $invoice = Invoice::create([
                            'invoice_number' => $invoiceNumber,
                            'branch_id' => $branch->id,
                            'created_date' => $createdTime->toIso8601String(),
                            'customer_id' => $customer->id,
                            'designer_id' => $designer->id,
                            'status' => $status,
                            'discount' => 0,
                            'dp_amount' => 0,
                            'accounts_receivable' => 0,
                            'grand_total' => 0,
                            'total_cost' => 0,
                            'total_profit' => 0,
                            'deadline' => (clone $createdTime)->addDays(rand(1, 3))->toIso8601String(),
                            'pickup_status' => $pickupStatus,
                            'pickup_date' => $pickupDate,
                        ]);

                        // Generate 1 to 3 orders for this invoice
                        $itemCount = rand(1, 3);
                        $invoiceGrandTotal = 0;
                        $invoiceTotalCost = 0;
                        $invoiceTotalProfit = 0;

                        for ($item = 1; $item <= $itemCount; $item++) {
                            // Pick a product and one of its compatible materials
                            $printOption = $printOptionsByBranch[$branch->id][array_rand($printOptionsByBranch[$branch->id])];
                            $product = $printOption['product'];
                            $material = $printOption['material'];

                            $qty = rand(1, 50);
                            $area = 0.0;
                            $unitPrice = (float) $material->price;
                            $unitCost = (float) $material->cost;

                            // Area calculation if large format
                            if ($product->id === $productModels['Large Format']->id) {
                                $width = rand(1, 5);
                                $length = rand(1, 10);
                                $area = $width * $length;
                                $subtotal = $unitPrice * $qty * $area;
                                $cost = $unitCost * $qty * $area;
                            } else {
                                $subtotal = $unitPrice * $qty;
                                $cost = $unitCost * $qty;
                            }

                            $profit = $subtotal - $cost;

                            $invoiceGrandTotal += $subtotal;
                            $invoiceTotalCost += $cost;
                            $invoiceTotalProfit += $profit;

                            $orderStatus = ($status === 2) ? 2 : rand(0, 2);
                            $operator = ($orderStatus > 0) ? $operatorsByBranch[$branch->id][array_rand($operatorsByBranch[$branch->id])] : null;

                            $order = Order::create([
                                'invoice_id' => $invoice->id,
                                'product_id' => $product->id,
                                'material_id' => $material->id,
                                'customer_type' => 'Reguler',
                                'reseller_discount_percent' => 0,
                                'quantity_discount_percent' => 0,
                                'discount_amount' => 0,
                                'operator_id' => $operator?->id,
                                'file_name' => $fileNames[array_rand($fileNames)],
                                'area' => $area,
                                'quantity' => $qty,
                                'unit_price' => $unitPrice,
                                'subtotal' => $subtotal,
                                'cost' => $cost,
                                'profit' => $profit,
                                'ordered_at' => $createdTime->toIso8601String(),
                                'status' => $orderStatus,
                                'pickup_status' => $pickupStatus,
                                'pickup_date' => $pickupDate,
                            ]);

                            // 2% chance of Wasted Material
                            if (rand(1, 100) <= 2) {
                                WastedMaterial::create([
                                    'branch_id' => $branch->id,
                                    'logged_at' => $createdTime->toIso8601String(),
                                    'material_id' => $material->id,
                                    'order_id' => $order->id,
                                    'designer_id' => $designer->id,
                                    'operator_id' => $operator?->id,
                                    'quantity' => rand(1, 3),
                                    'dimensions' => '1x1m',
                                    'notes' => 'Print failed/color misaligned',
                                ]);
                            }

                            // Add Stock Ledgers logging occasionally
                            if (rand(1, 50) === 1) {
                                StockLedger::create([
                                    'branch_id' => $branch->id,
                                    'material_id' => $material->id,
                                    'logged_at' => $createdTime->toIso8601String(),
                                    'initial_stock' => 1000,
                                    'added_stock' => rand(1, 10) * 100,
                                    'used_stock' => $qty,
                                    'wasted_stock' => 0,
                                    'remaining_stock' => 1000 + (rand(1, 10) * 100) - $qty,
                                    'notes' => 'Restock & Auto adjustment',
                                ]);
                            }
                        }

                        // Set payments details
                        $dpAmount = 0;
                        $ar = $invoiceGrandTotal;
                        if ($status === 2) {
                            $dpAmount = 0;
                            $ar = 0;

                            // Create Payment
                            Payment::create([
                                'branch_id' => $branch->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => $cashier->id,
                                'amount' => $invoiceGrandTotal,
                                'payment_method' => ['Cash', 'Transfer', 'QRIS'][rand(0, 2)],
                                'type' => 'settlement',
                                'cashier_name' => $cashier->name,
                                'created_at' => $createdTime->toIso8601String(),
                            ]);
                        } elseif ($status === 1) {
                            $dpAmount = round($invoiceGrandTotal * (rand(30, 60) / 100), 2);
                            $ar = $invoiceGrandTotal - $dpAmount;

                            // Create DP Payment
                            Payment::create([
                                'branch_id' => $branch->id,
                                'invoice_id' => $invoice->id,
                                'user_id' => $cashier->id,
                                'amount' => $dpAmount,
                                'payment_method' => ['Cash', 'Transfer', 'QRIS'][rand(0, 2)],
                                'type' => 'down_payment',
                                'cashier_name' => $cashier->name,
                                'created_at' => $createdTime->toIso8601String(),
                            ]);
                        }

                        // Update invoice final metrics
                        $invoice->update([
                            'grand_total' => $invoiceGrandTotal,
                            'total_cost' => $invoiceTotalCost,
                            'total_profit' => $invoiceTotalProfit,
                            'dp_amount' => $dpAmount,
                            'accounts_receivable' => $ar,
                            'dp_date' => $status === 1 ? $createdTime->toIso8601String() : null,
                            'payment_date' => $status === 2 ? $createdTime->toIso8601String() : null,
                        ]);
                    }

                    // Create 1-2 branch expenses per day
                    $expenseCount = rand(1, 2);
                    for ($exp = 1; $exp <= $expenseCount; $exp++) {
                        Expense::create([
                            'branch_id' => $branch->id,
                            'incurred_at' => $currentSimulatedDate->setHour(rand(9, 17))->toIso8601String(),
                            'amount' => rand(10000, 150000),
                            'description' => ['Buying coffee', 'Office supplies', 'Staples', 'Cleaning supplies', 'Keyboard replacement'][rand(0, 4)],
                            'cashier_name' => $cashiersByBranch[$branch->id][array_rand($cashiersByBranch[$branch->id])]->name,
                            'type' => 'modal',
                            'category' => ['Office', 'Supplies', 'Maintenance'][rand(0, 2)],
                        ]);
                    }
                }
            }
        });
    }
}
