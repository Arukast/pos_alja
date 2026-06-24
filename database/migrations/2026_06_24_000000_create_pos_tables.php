<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('type', 50)->nullable(); // Reguler, Mitra, etc.
            $table->timestamps();
        });

        // 2. Products (categories like Banner, Print Kertas, etc.)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // 3. Materials (bahan)
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('category', 50); // lembar, meter, pcs, display, finishing, potong, stempel
            $table->string('type')->nullable(); // Tipe bahan (Outdoor, Indoor, Kertas, etc.)
            $table->string('dimensions')->nullable(); // Ukuran bahan
            $table->integer('cost')->default(0); // HPP Bahan
            $table->integer('price')->default(0); // Harga Bahan
            $table->decimal('initial_stock', 10, 2)->default(0);
            $table->decimal('added_stock', 10, 2)->default(0);
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('sold_stock', 10, 2)->default(0);
            $table->decimal('wasted_stock', 10, 2)->default(0);
            $table->decimal('remaining_stock', 10, 2)->default(0);
            $table->timestamps();
        });

        // 4. Machines (mesin)
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // 5. Finishings
        Schema::create('finishings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // 6. Cuts (potong)
        Schema::create('cuts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // 7. Displays (displays accessories)
        Schema::create('displays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // 8. Pricing Matrices (harga_jual lookup)
        Schema::create('pricing_matrices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignId('machine_id')->default(0);
            $table->foreignId('finishing_id')->default(0);
            $table->foreignId('cut_id')->default(0);
            $table->string('dimensions', 50)->nullable();
            $table->foreignId('display_id')->default(0);
            $table->integer('price')->default(0);
            $table->integer('cost')->default(0); // HPP
            $table->integer('profit')->default(0);
            $table->integer('min_qty')->default(1);
            $table->integer('sides')->default(1);
            $table->integer('finishing_sides')->default(0);
            $table->timestamps();
        });

        // 9. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->date('created_date');
            $table->date('payment_date')->nullable();
            $table->date('dp_date')->nullable();
            $table->foreignId('customer_id')->constrained('customers');
            $table->integer('status')->default(0); // 0: Belum Lunas, 1: Down Payment, 2: Lunas
            $table->integer('discount')->default(0);
            $table->integer('dp_amount')->default(0); // Total down payment registered
            $table->integer('accounts_receivable')->default(0); // Sisa Piutang / AR
            $table->integer('dp_paid')->default(0); // DP paid
            $table->integer('settlement_amount')->default(0); // Pelunasan
            $table->integer('grand_total')->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->integer('payment_amount')->default(0);
            $table->integer('change_amount')->default(0);
            $table->integer('total_cost')->default(0); // HPP
            $table->integer('total_profit')->default(0);
            $table->string('customer_service')->nullable(); // Designer/CS in charge
            $table->string('deadline')->nullable();
            $table->integer('pickup_status')->default(0); // 0: Belum, 1: Done
            $table->date('pickup_date')->nullable();
            $table->integer('source')->default(0);
            $table->string('cashier_name')->nullable();
            $table->string('cashier_dp_name')->nullable();
            $table->string('payment_dp_method', 50)->nullable();
            $table->timestamps();
        });

        // 10. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->integer('pricing_matrix_id')->default(0); // maps to pricing_matrices.id
            $table->string('file_name')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('area', 8, 2)->default(0); // Banner square meters area
            $table->decimal('quantity', 10, 2)->default(1);
            $table->integer('unit_price')->default(0);
            $table->integer('subtotal')->default(0);
            $table->integer('cost')->default(0); // HPP
            $table->integer('profit')->default(0);
            $table->date('ordered_at');
            $table->integer('status')->default(0); // 0: Belum di Proses, 1: Sedang di Proses, 2: Telah Beres
            $table->integer('pickup_status')->default(0);
            $table->date('pickup_date')->nullable();
            $table->timestamps();
        });

        // 11. Expenses (pengeluaran)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->date('incurred_at');
            $table->integer('amount');
            $table->text('description')->nullable();
            $table->string('cashier_name')->nullable();
            $table->string('type', 20); // modal, laba
            $table->timestamps();
        });

        // 12. Wasted Materials (bahan_gagal logs)
        Schema::create('wasted_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->date('logged_at');
            $table->foreignId('material_id')->constrained('materials');
            $table->integer('machine_id')->default(0);
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('dimensions')->nullable();
            $table->string('designer_name')->nullable();
            $table->string('operator_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 13. Stock Ledgers (stok ledger logs)
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->date('logged_at');
            $table->decimal('initial_stock', 10, 2)->default(0);
            $table->decimal('added_stock', 10, 2)->default(0);
            $table->decimal('used_stock', 10, 2)->default(0);
            $table->decimal('wasted_stock', 10, 2)->default(0);
            $table->decimal('remaining_stock', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
        Schema::dropIfExists('wasted_materials');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('pricing_matrices');
        Schema::dropIfExists('displays');
        Schema::dropIfExists('cuts');
        Schema::dropIfExists('finishings');
        Schema::dropIfExists('machines');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
    }
};
