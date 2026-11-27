<?php

declare(strict_types=1);

use App\Support\CustomerInstitution;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add a per-invoice institution snapshot and backfill it from the customer. */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('institution', 50)->nullable();
        });

        // Do not remove or move App\Support\CustomerInstitution: this migration's backfill depends on it.
        CustomerInstitution::backfillInvoices();
    }

    /** Remove the per-invoice institution snapshot column. */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('institution');
        });
    }
};
