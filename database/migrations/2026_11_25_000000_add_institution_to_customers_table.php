<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add the customer institution classification. */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('institution', 50)->default('Masyarakat/Retail');
        });
    }

    /** Remove the customer institution classification. */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('institution');
        });
    }
};
