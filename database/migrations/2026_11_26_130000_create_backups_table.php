<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('trigger');
            $table->string('destination');
            $table->string('status');
            $table->string('filename');
            $table->string('local_path')->nullable();
            $table->string('remote_path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->text('error')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->json('meta')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('status');
            $table->index('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
