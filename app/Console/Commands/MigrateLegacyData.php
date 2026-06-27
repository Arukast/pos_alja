<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrateLegacyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:migrate-legacy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate historical data from the legacy MariaDB database to PostgreSQL';

    /*
    private function parseDate($value)
    {
        if (empty($value) || $value === '0000-00-00' || $value === '0000-00-00 00:00:00' || $value === '0' || $value === '-') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }
    */

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->error('The legacy migration command (db:migrate-legacy) is disabled/superseded in the redesign as the database has fully transitioned to the new UUIDv7-based schema.');

        return 1;

        /*
        $this->info('Starting migration from legacy MariaDB to PostgreSQL...');

        // Disable foreign key checks for clean batch imports depending on the driver
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DEFERRED');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } elseif (in_array($driver, ['mysql', 'mariadb'])) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        }

        $now = Carbon::now();

        // 0. Seed Default Main Branch
        $this->info('Seeding default Main Branch...');
        DB::table('branches')->truncate();
        DB::table('branches')->insert([
            'id' => 1,
            'name' => 'Main Branch',
            'address' => 'Kp. Sawah Menes',
            'phone' => '087871577167',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 1. Migrate admin -> users
        $this->info('Migrating admin users...');
        $legacyUsers = DB::connection('mysql_migration')->table('admin')->get();
        DB::table('users')->truncate();
        $usedUsernames = [];
        foreach ($legacyUsers as $u) {
            $username = trim($u->username);
            if (in_array(strtolower($username), $usedUsernames)) {
                $username = $username.'_'.$u->id;
            }
            $usedUsernames[] = strtolower($username);

            $role = match ($u->level) {
                'bm' => 'manager',
                'owner' => 'owner',
                'kasir' => 'cashier',
                'setting' => 'designer',
                'produksi' => 'production',
                default => 'cashier',
            };

            DB::table('users')->insert([
                'id' => $u->id,
                'name' => $u->nama,
                'username' => $username,
                'email' => strtolower($username).'@alja.com',
                'password' => Hash::make('password123'), // Default temporary password
                'phone' => $u->handphone,
                'address' => $u->alamat,
                'role' => $role,
                'branch_id' => 1, // Main Branch
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->info('Users migrated: '.count($legacyUsers));

        // 2. Migrate kostumer -> customers (chunked for performance)
        $this->info('Migrating customers...');
        $legacyCustomers = DB::connection('mysql_migration')->table('kostumer')->get();
        DB::table('customers')->truncate();
        $customersData = [];
        foreach ($legacyCustomers as $c) {
            $customersData[] = [
                'id' => $c->kostumer_id,
                'name' => $c->kostumer_nama,
                'phone' => $c->kostumer_telp,
                'address' => $c->kostumer_alamat,
                'email' => $c->kostumer_email !== '-' ? $c->kostumer_email : null,
                'type' => $c->kostumer_jenis ?: 'Reguler',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (count($customersData) >= 500) {
                DB::table('customers')->insert($customersData);
                $customersData = [];
            }
        }
        if (count($customersData) > 0) {
            DB::table('customers')->insert($customersData);
        }
        $this->info('Customers migrated: '.count($legacyCustomers));

        // 3. Migrate produk -> products
        $this->info('Migrating products...');
        $legacyProducts = DB::connection('mysql_migration')->table('produk')->get();
        DB::table('products')->truncate();
        foreach ($legacyProducts as $p) {
            DB::table('products')->insert([
                'id' => $p->produk_id,
                'name' => $p->produk_nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->info('Products migrated: '.count($legacyProducts));

        // 4. Migrate bahan -> materials
        $this->info('Migrating materials...');
        $legacyMaterials = DB::connection('mysql_migration')->table('bahan')->get();
        DB::table('materials')->truncate();
        foreach ($legacyMaterials as $m) {
            DB::table('materials')->insert([
                'id' => $m->bahan_id,
                'branch_id' => 1,
                'name' => $m->bahan_nama,
                'category' => $m->bahan_kategori,
                'type' => $m->bahan_tipe,
                'dimensions' => $m->bahan_ukuran,
                'cost' => $m->bahan_hpp,
                'price' => $m->bahan_harga,
                'initial_stock' => floatval($m->bahan_stok_awal),
                'added_stock' => floatval($m->bahan_stok_tambah),
                'current_stock' => floatval($m->bahan_stok),
                'sold_stock' => floatval($m->bahan_stok_terjual),
                'wasted_stock' => floatval($m->bahan_stok_gagal),
                'remaining_stock' => floatval($m->bahan_stok_sisa),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->info('Materials migrated: '.count($legacyMaterials));

        // 5. Migrate mesin -> machines
        $this->info('Migrating machines...');
        $legacyMachines = DB::connection('mysql_migration')->table('mesin')->get();
        DB::table('machines')->truncate();
        foreach ($legacyMachines as $mc) {
            DB::table('machines')->insert([
                'id' => $mc->mesin_id,
                'branch_id' => 1,
                'name' => $mc->mesin_nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->info('Machines migrated: '.count($legacyMachines));

        // 6. Migrate jenis_finishing -> finishings
        $this->info('Migrating finishings...');
        $legacyFinishings = DB::connection('mysql_migration')->table('jenis_finishing')->get();
        DB::table('finishings')->truncate();
        foreach ($legacyFinishings as $f) {
            DB::table('finishings')->insert([
                'id' => $f->jenis_finishing_id,
                'name' => $f->jenis_finishing_nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->info('Finishings migrated: '.count($legacyFinishings));

        // 7. Migrate jenis_potong -> cuts
        $this->info('Migrating cuts...');
        $legacyCuts = DB::connection('mysql_migration')->table('jenis_potong')->get();
        DB::table('cuts')->truncate();
        foreach ($legacyCuts as $ct) {
            DB::table('cuts')->insert([
                'id' => $ct->jenis_potong_id,
                'name' => $ct->jenis_potong_nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->info('Cuts migrated: '.count($legacyCuts));

        // 8. Migrate jenis_display -> displays
        $this->info('Migrating displays...');
        $legacyDisplays = DB::connection('mysql_migration')->table('jenis_display')->get();
        DB::table('displays')->truncate();
        foreach ($legacyDisplays as $d) {
            DB::table('displays')->insert([
                'id' => $d->jenis_display_id,
                'name' => $d->jenis_display_nama,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->info('Displays migrated: '.count($legacyDisplays));

        // 9. Migrate harga_jual -> pricing_matrices
        $this->info('Migrating pricing matrices...');
        $legacyMatrices = DB::connection('mysql_migration')->table('harga_jual')->get();
        DB::table('pricing_matrices')->truncate();
        $validProductIds = DB::table('products')->pluck('id')->flip()->toArray();
        $validMaterialIds = DB::table('materials')->pluck('id')->flip()->toArray();

        $migratedCount = 0;
        foreach ($legacyMatrices as $hm) {
            if (! isset($validProductIds[$hm->hj_produk]) || ! isset($validMaterialIds[$hm->hj_bahan])) {
                continue;
            }
            DB::table('pricing_matrices')->insert([
                'id' => $hm->hj_id,
                'branch_id' => 1,
                'product_id' => $hm->hj_produk,
                'material_id' => $hm->hj_bahan,
                'machine_id' => $hm->hj_mesin ?: 0,
                'finishing_id' => $hm->hj_finishing ?: 0,
                'cut_id' => $hm->hj_potong ?: 0,
                'dimensions' => $hm->hj_ukuran,
                'display_id' => $hm->hj_display ?: 0,
                'price' => $hm->hj_harga,
                'cost' => $hm->hj_hpp,
                'profit' => $hm->hj_profit,
                'min_qty' => $hm->hj_min_qty,
                'sides' => $hm->hj_sisi,
                'finishing_sides' => $hm->hj_sisi_finishing,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $migratedCount++;
        }
        $this->info('Pricing matrices migrated: '.$migratedCount);

        // 10. Migrate invoice -> invoices
        $this->info('Migrating invoices (this may take a few seconds)...');
        $legacyInvoices = DB::connection('mysql_migration')->table('invoice')->get();
        DB::table('invoices')->truncate();

        $validCustomerIds = DB::table('customers')->pluck('id')->flip()->toArray();
        $insertedInvoiceIds = [];

        $invoicesData = [];
        foreach ($legacyInvoices as $inv) {
            if (! isset($validCustomerIds[$inv->invoice_kostumer])) {
                continue;
            }
            $invoicesData[] = [
                'id' => $inv->invoice_id,
                'branch_id' => 1,
                'created_date' => $this->parseDate($inv->invoice_tgl) ?: $now->toDateString(),
                'payment_date' => $this->parseDate($inv->invoice_tgl_pemb),
                'dp_date' => $this->parseDate($inv->invoice_tgl_dp),
                'customer_id' => $inv->invoice_kostumer,
                'status' => $inv->invoice_status,
                'discount' => $inv->invoice_diskon,
                'dp_amount' => $inv->invoice_dp,
                'accounts_receivable' => $inv->invoice_ar,
                'dp_paid' => $inv->invoice_bayar_dp,
                'settlement_amount' => $inv->invoice_pelunasan,
                'grand_total' => $inv->invoice_total,
                'payment_method' => $inv->invoice_payment,
                'payment_amount' => $inv->invoice_pembayaran,
                'change_amount' => $inv->invoice_kembalian,
                'total_cost' => $inv->invoice_hpp,
                'total_profit' => $inv->invoice_profit,
                'customer_service' => $inv->customer_service,
                'deadline' => $inv->invoice_dateline,
                'pickup_status' => $inv->invoice_pengambilan,
                'pickup_date' => $this->parseDate($inv->invoice_tanggal_pengambilan),
                'source' => $inv->invoice_sumber,
                'cashier_name' => $inv->invoice_kasir,
                'cashier_dp_name' => $inv->invoice_kasir_dp,
                'payment_dp_method' => $inv->invoice_payment_dp,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $insertedInvoiceIds[] = $inv->invoice_id;

            // Batch insert in chunks of 500 to optimize PostgreSQL performance
            if (count($invoicesData) >= 500) {
                DB::table('invoices')->insert($invoicesData);
                $invoicesData = [];
            }
        }
        if (count($invoicesData) > 0) {
            DB::table('invoices')->insert($invoicesData);
        }
        $this->info('Invoices migrated: '.count($legacyInvoices));

        // 11. Migrate orderan -> orders
        $this->info('Migrating order items...');
        $legacyOrders = DB::connection('mysql_migration')->table('orderan')->get();
        DB::table('orders')->truncate();

        $validInvoiceIds = DB::table('invoices')->pluck('id')->flip()->toArray();

        $ordersData = [];
        $migratedOrdersCount = 0;
        foreach ($legacyOrders as $ord) {
            if (! isset($validInvoiceIds[$ord->order_invoice])) {
                continue;
            }
            $ordersData[] = [
                'id' => $ord->order_id,
                'invoice_id' => $ord->order_invoice,
                'pricing_matrix_id' => $ord->order_hj ?: 0,
                'file_name' => $ord->nama_file,
                'notes' => $ord->order_keterangan,
                'area' => floatval($ord->order_luas),
                'quantity' => floatval($ord->order_qty),
                'unit_price' => $ord->order_harga_satuan,
                'subtotal' => $ord->order_harga_sub_total,
                'cost' => $ord->order_hpp,
                'profit' => $ord->order_profit,
                'ordered_at' => $this->parseDate($ord->order_datetime) ?: $now->toDateString(),
                'status' => $ord->order_status,
                'pickup_status' => $ord->order_pengambilan,
                'pickup_date' => $this->parseDate($ord->order_tgl_pengambilan),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $migratedOrdersCount++;

            if (count($ordersData) >= 500) {
                DB::table('orders')->insert($ordersData);
                $ordersData = [];
            }
        }
        if (count($ordersData) > 0) {
            DB::table('orders')->insert($ordersData);
        }
        $this->info('Orders migrated: '.$migratedOrdersCount);

        // 12. Migrate pengeluaran -> expenses
        $this->info('Migrating expenses...');
        $legacyExpenses = DB::connection('mysql_migration')->table('pengeluaran')->get();
        DB::table('expenses')->truncate();
        foreach ($legacyExpenses as $e) {
            DB::table('expenses')->insert([
                'id' => $e->pengeluaran_id,
                'branch_id' => 1,
                'incurred_at' => $this->parseDate($e->tanggal_pengeluaran) ?: $now->toDateString(),
                'amount' => $e->nominal_pengeluaran,
                'description' => $e->keterangan_pengeluaran,
                'cashier_name' => $e->session,
                'type' => $e->dana_pengeluaran ?: 'laba',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $this->info('Expenses migrated: '.count($legacyExpenses));

        // 13. Migrate bahan_gagal -> wasted_materials
        $this->info('Migrating wasted materials...');
        $legacyWasted = DB::connection('mysql_migration')->table('bahan_gagal')->get();
        DB::table('wasted_materials')->truncate();
        $wastedCount = 0;
        foreach ($legacyWasted as $w) {
            if (! isset($validMaterialIds[$w->gagal_bahan_id])) {
                continue;
            }
            DB::table('wasted_materials')->insert([
                'id' => $w->gagal_id,
                'branch_id' => 1,
                'logged_at' => $this->parseDate($w->gagal_tanggal) ?: $now->toDateString(),
                'material_id' => $w->gagal_bahan_id,
                'machine_id' => 0,
                'quantity' => floatval($w->gagal_qty),
                'dimensions' => null,
                'designer_name' => $w->gagal_pic,
                'operator_name' => null,
                'notes' => $w->gagal_keterangan,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $wastedCount++;
        }
        $this->info('Wasted materials migrated: '.$wastedCount);

        // 14. Migrate stok -> stock_ledgers
        $this->info('Migrating stock ledgers...');
        $legacyStock = DB::connection('mysql_migration')->table('stok')->get();
        DB::table('stock_ledgers')->truncate();

        $stockData = [];
        $migratedStockCount = 0;
        foreach ($legacyStock as $s) {
            if (! isset($validMaterialIds[$s->stok_bahan_id])) {
                continue;
            }
            $stockData[] = [
                'id' => $s->stok_id,
                'branch_id' => 1,
                'material_id' => $s->stok_bahan_id,
                'logged_at' => $this->parseDate($s->stok_tanggal) ?: $now->toDateString(),
                'initial_stock' => floatval($s->stok_awal),
                'added_stock' => floatval($s->stok_tambah),
                'used_stock' => floatval($s->stok_terjual),
                'wasted_stock' => floatval($s->stok_gagal),
                'remaining_stock' => floatval($s->stok_sisa),
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $migratedStockCount++;

            if (count($stockData) >= 500) {
                DB::table('stock_ledgers')->insert($stockData);
                $stockData = [];
            }
        }
        if (count($stockData) > 0) {
            DB::table('stock_ledgers')->insert($stockData);
        }
        $this->info('Stock ledgers migrated: '.$migratedStockCount);

        $this->info('All legacy data successfully migrated to PostgreSQL!');
        */
    }
}
