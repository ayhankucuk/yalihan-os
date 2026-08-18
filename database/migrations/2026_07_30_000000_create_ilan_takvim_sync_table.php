<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 13: ilan_takvim_sync table migration
     *
     * Note: This table exists in production but the migration file was not
     * committed to the repository. This migration recreates the schema
     * for test DB (SQLite) and fresh installations.
     *
     * Uses hasTable guard to be idempotent — safe for production
     * where the table already exists.
     */
    public function up(): void
    {
        if (Schema::hasTable('ilan_takvim_sync')) {
            return;
        }

        Schema::create('ilan_takvim_sync', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ilan_id');
            $table->string('platform', 30); // airbnb | booking | sahibinden
            $table->string('external_calendar_id')->nullable();
            $table->string('external_listing_id')->nullable();
            $table->boolean('is_sync_active')->default(false);
            $table->boolean('auto_sync')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('next_sync_at')->nullable();
            $table->integer('sync_interval_minutes')->nullable();
            $table->json('sync_settings')->nullable();
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('senkron_durumu', 30)->default('inactive');
            $table->text('last_error')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->unsignedInteger('sync_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamps();

            $table->index(['ilan_id', 'platform']);
            $table->index(['senkron_durumu', 'is_sync_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ilan_takvim_sync');
    }
};
