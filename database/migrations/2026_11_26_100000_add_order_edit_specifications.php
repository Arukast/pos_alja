<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Store the quote inputs needed to safely recompute editable order items. */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->foreignUuid('machine_id')->nullable()->constrained('machines')->nullOnDelete();
            $table->json('selected_attributes')->nullable();
        });
    }

    /** Remove the order quote inputs. */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['machine_id']);
            $table->dropColumn(['width', 'length', 'machine_id', 'selected_attributes']);
        });
    }
};
