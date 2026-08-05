<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * RESERVATION_CORE Phase 2 E03: Rebuild execution audit trail
     *
     * Tracks all availability projection rebuild operations for:
     * - Execution history
     * - Audit compliance
     * - Debugging failed rebuilds
     * - Performance analysis
     */
    public function up(): void
    {
        if (!Schema::hasTable('rebuild_execution_logs')) {
            Schema::create('rebuild_execution_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('tenant_id')->index();
                $table->unsignedInteger('property_id')->nullable()->index(); // null = all properties
                $table->date('start_date');
                $table->date('end_date');
                $table->string('initiated_by', 100)->default('system'); // user id or 'system'
                $table->string('status', 50)->default('running'); // running, completed, completed_with_errors, failed
                $table->unsignedInteger('properties_processed')->default(0);
                $table->unsignedInteger('reservations_processed')->default(0);
                $table->unsignedInteger('blocked_days')->default(0);
                $table->json('errors')->nullable(); // [{property_id, error}]
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                // Indexes for common queries
                $table->index(['tenant_id', 'created_at']);
                $table->index(['tenant_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rebuild_execution_logs');
    }
};
