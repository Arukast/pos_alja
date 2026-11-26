<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->boolean('local_enabled')->default(true);
            $table->string('local_time', 5)->default('02:00');
            $table->boolean('remote_enabled')->default(false);
            $table->string('remote_time', 5)->default('02:30');
            $table->unsignedSmallInteger('local_daily_days')->default(30);
            $table->unsignedSmallInteger('local_weekly_weeks')->default(12);
            $table->unsignedSmallInteger('local_monthly_months')->default(12);
            $table->unsignedSmallInteger('local_yearly_years')->default(3);
            $table->unsignedSmallInteger('remote_daily_days')->default(7);
            $table->unsignedSmallInteger('remote_weekly_weeks')->default(4);
            $table->unsignedSmallInteger('remote_monthly_months')->default(12);
            $table->unsignedSmallInteger('remote_yearly_years')->default(3);
            $table->date('last_local_date')->nullable();
            $table->date('last_remote_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
