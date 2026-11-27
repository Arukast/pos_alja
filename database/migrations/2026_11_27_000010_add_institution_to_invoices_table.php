<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add a per-invoice institution snapshot and backfill it from the customer. */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('institution', 50)->nullable();
        });

        DB::table('invoices')
            ->whereNull('institution')
            ->chunkById(200, static function (Collection $invoices): void {
                $customerIds = $invoices->pluck('customer_id')->filter()->unique()->all();
                $labels = DB::table('customers')
                    ->whereIn('id', $customerIds)
                    ->pluck('institution', 'id');

                foreach ($invoices as $invoice) {
                    $label = $labels->get($invoice->customer_id);

                    if ($label !== null) {
                        DB::table('invoices')
                            ->where('id', $invoice->id)
                            ->update(['institution' => $label]);
                    }
                }
            });
    }

    /** Remove the per-invoice institution snapshot column. */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('institution');
        });
    }
};
