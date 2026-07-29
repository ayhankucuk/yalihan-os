<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 13 E02: Channel Sync Executions
     */
    public function up(): void
    {
        Schema::create('channel_sync_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('reservation_id')->nullable();
            $table->string('operation', 20); // block | unblock | sync
            $table->string('block_reason', 30)->nullable(); // reservation | maintenance | manual
            $table->date('date_range_start');
            $table->date('date_range_end');
            $table->boolean('target_availability'); // false = blocked
            $table->json('synced_dates')->nullable();
            $table->json('conflicts')->nullable();
            $table->string('idempotency_key', 255)->unique();
            $table->string('correlation_id', 50)->nullable();
            $table->string('status', 30)->default('dispatched'); // dispatched | processing | completed | completed_with_conflicts | failed
            $table->unsignedInteger('synced_count')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'property_id']);
            $table->index(['property_id', 'status']);
            $table->index(['tenant_id', 'reservation_id']);
            $table->index('correlation_id');
            $table->index('processed_at');

            // Foreign keys
            $table->foreign('property_id')->references('id')->on('ilans')->cascadeOnDelete();
            $table->foreign('reservation_id')->references('id')->on('property_reservations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_sync_executions');
    }
};
