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
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('type', 50)->nullable(); // Reguler, Mitra, etc.
            $table->softDeletes();
            $table->timestamps();
        });

        // 1a. Product Categories
        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('calculation_archetype'); // per_piece, surface_area, linear_dimension, tiered_volume
            $table->timestamps();
        });

        // 2. Products
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_category_id')->nullable()->constrained('product_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('pricing_type', 20)->default('unit'); // unit, area
            $table->softDeletes();
            $table->timestamps();
        });

        // 3. Materials
        Schema::create('materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->nullable(); // Tipe bahan (Outdoor, Indoor, Kertas, etc.)
            $table->string('dimensions')->nullable(); // Ukuran bahan
            $table->decimal('cost', 15, 2)->default(0); // HPP Bahan
            $table->decimal('price', 15, 2)->default(0); // Harga Bahan
            $table->softDeletes();
            $table->timestamps();
        });

        // 3a. Material Branch Stock Junction
        Schema::create('material_branch_stock', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('material_id')->constrained('materials')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['material_id', 'branch_id']);
        });

        // 4. Machines
        Schema::create('machines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        // 5. Consolidated Attributes (Finishing, Cuts, Displays)
        Schema::create('attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20); // finishing, cut, display
            $table->softDeletes();
            $table->timestamps();
        });

        // 6. Pricing (replacing pricing_matrices)
        Schema::create('pricing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('material_id')->constrained('materials')->cascadeOnDelete();
            $table->string('customer_type', 50)->default('Reguler'); // Reguler, Mitra
            $table->integer('min_qty')->default(1);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0); // HPP
            $table->decimal('profit', 15, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        // 6a. Attribute Pricing Modifiers
        Schema::create('attribute_pricing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('pricing_id')->constrained('pricing')->cascadeOnDelete();
            $table->foreignUuid('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->timestamps();
        });

        // 6b. Machine Pricing Modifiers
        Schema::create('machine_pricing', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('pricing_id')->constrained('pricing')->cascadeOnDelete();
            $table->foreignUuid('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->timestamps();
        });

        // 7. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number', 50)->unique();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->timestampTz('created_date');
            $table->timestampTz('payment_date')->nullable();
            $table->timestampTz('dp_date')->nullable();
            $table->foreignUuid('customer_id')->constrained('customers');
            $table->foreignUuid('designer_id')->nullable()->constrained('users')->nullOnDelete(); // Designer/CS in charge
            $table->integer('status')->default(0); // 0: Belum Lunas, 1: Down Payment, 2: Lunas
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('dp_amount', 15, 2)->default(0); // Total down payment registered
            $table->decimal('accounts_receivable', 15, 2)->default(0); // Sisa Piutang / AR
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0); // HPP
            $table->decimal('total_profit', 15, 2)->default(0);
            $table->timestampTz('deadline')->nullable();
            $table->integer('pickup_status')->default(0); // 0: Belum, 1: Done
            $table->timestampTz('pickup_date')->nullable();
            $table->integer('source')->default(0);
            $table->timestamps();
        });

        // 8. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUuid('pricing_id')->nullable()->constrained('pricing')->nullOnDelete();
            $table->foreignUuid('operator_id')->nullable()->constrained('users')->nullOnDelete(); // Production operator in charge
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable(); // Original file location
            $table->string('preview_path')->nullable(); // Preview thumbnail location
            $table->text('notes')->nullable();
            $table->decimal('area', 8, 2)->default(0); // Banner square meters area
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0); // HPP
            $table->decimal('profit', 15, 2)->default(0);
            $table->timestampTz('ordered_at');
            $table->integer('status')->default(0); // 0: Belum di Proses, 1: Sedang di Proses, 2: Telah Beres
            $table->integer('pickup_status')->default(0);
            $table->timestampTz('pickup_date')->nullable();
            $table->boolean('is_manual_price')->default(false); // Flag if manual override is used
            $table->timestamps();
        });

        // 9. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete(); // Cashier user
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50); // Cash, Transfer, QRIS
            $table->string('type', 20); // down_payment, settlement, partial
            $table->string('cashier_name'); // cache for audit
            $table->timestamps();
        });

        // 10. Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->timestampTz('incurred_at');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('cashier_name')->nullable();
            $table->string('type', 20); // modal, laba
            $table->string('category', 50)->default('Office'); // Supplies, Maintenance, Utilities, Office, Drawer Float
            $table->timestamps();
        });

        // 11. Wasted Materials (linked to orders, desainer, and operators)
        Schema::create('wasted_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->timestampTz('logged_at');
            $table->foreignUuid('material_id')->constrained('materials');
            $table->foreignUuid('machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete(); // Optional order link
            $table->foreignUuid('designer_id')->nullable()->constrained('users')->nullOnDelete(); // Relational designer link
            $table->foreignUuid('operator_id')->nullable()->constrained('users')->nullOnDelete(); // Relational operator link
            $table->decimal('quantity', 10, 2)->default(0);
            $table->string('dimensions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 12. Stock Ledgers
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('material_id')->constrained('materials');
            $table->timestampTz('logged_at');
            $table->decimal('initial_stock', 10, 2)->default(0);
            $table->decimal('added_stock', 10, 2)->default(0);
            $table->decimal('used_stock', 10, 2)->default(0);
            $table->decimal('wasted_stock', 10, 2)->default(0);
            $table->decimal('remaining_stock', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 13. Order Status Logs
        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->integer('status'); // 0, 1, 2
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete(); // Who did the status update
            $table->string('user_name'); // cached user name
            $table->string('notes')->nullable();
            $table->timestampsTz();
        });

        // 14. Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('username');
            $table->string('role');
            $table->string('action'); // CREATE, UPDATE, DELETE, etc.
            $table->string('description');
            $table->string('model_type')->nullable();
            $table->uuid('model_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('stock_ledgers');
        Schema::dropIfExists('wasted_materials');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('machine_pricing');
        Schema::dropIfExists('attribute_pricing');
        Schema::dropIfExists('pricing');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('machines');
        Schema::dropIfExists('material_branch_stock');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('customers');
    }
};
