<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // machine_pricing holds the foreign key to machines, so it goes first.
        Schema::dropIfExists('machine_pricing');
        Schema::dropIfExists('machines');
    }

    /**
     * Recreate both tables empty; the dropped rows are not recoverable.
     */
    public function down(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('cost', 15, 2)->default(0.00);
            $table->softDeletes();
            $table->timestamps();
        });

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
    }
};
