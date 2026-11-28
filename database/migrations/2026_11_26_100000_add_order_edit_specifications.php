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
            $table->json('selected_attributes')->nullable();
        });
    }

    /** Remove the order quote inputs. */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['width', 'length', 'selected_attributes']);
        });
    }
};
