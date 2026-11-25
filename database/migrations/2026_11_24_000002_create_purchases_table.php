<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('purchased_at');
            $table->string('supplier')->nullable();
            $table->decimal('total_amount', 18, 2);
            $table->text('notes')->nullable();
            $table->string('idempotency_key');
            $table->timestamps();

            $table->unique(['branch_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
