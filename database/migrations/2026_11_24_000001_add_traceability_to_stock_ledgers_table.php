<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->string('type')->nullable()->index();
            $table->string('reason')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->uuid('reference_id')->nullable();
            $table->index(['reference_type', 'reference_id']);
        });

        // Best-effort legacy classification from the notes signature; unclassifiable rows stay null.
        DB::table('stock_ledgers')->where('notes', 'like', 'Initial stock%')->update(['type' => 'initial']);
        DB::table('stock_ledgers')->where('notes', 'like', 'Printed order item%')->update(['type' => 'usage']);
        DB::table('stock_ledgers')->where('notes', 'like', 'Failed Print%')->update(['type' => 'waste']);
        DB::table('stock_ledgers')->whereNull('type')->where('added_stock', '>', 0)->update(['type' => 'restock']);
    }

    public function down(): void
    {
        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->dropIndex(['reference_type', 'reference_id']);
        });

        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });

        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('stock_ledgers', function (Blueprint $table) {
            $table->dropColumn(['type', 'reason', 'user_id', 'reference_type', 'reference_id']);
        });
    }
};
