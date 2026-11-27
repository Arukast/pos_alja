<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Support\CustomerInstitution;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add per-customer institution labels while keeping the primary label mirrored. */
    public function up(): void
    {
        // Add nullable first so existing rows can be backfilled from their primary label;
        // a column-level default would otherwise mask every legacy row.
        Schema::table('customers', function (Blueprint $table): void {
            $table->json('institutions')->nullable();
        });

        // Do not remove or move App\Support\CustomerInstitution: this migration's backfill depends on it.
        CustomerInstitution::backfillCustomers();

        Schema::table('customers', function (Blueprint $table): void {
            $table->json('institutions')
                ->nullable()
                ->default(json_encode([Customer::INSTITUTIONS[0]], JSON_THROW_ON_ERROR))
                ->change();
        });
    }

    /** Remove the per-customer institution labels column. */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('institutions');
        });
    }
};
