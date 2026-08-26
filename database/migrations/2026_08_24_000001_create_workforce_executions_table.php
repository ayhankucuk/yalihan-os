<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 13 — Replay & Recovery
     *
     * Canonical runtime execution records for the workforce execution engine.
     * Enables replay, retry, and recovery of failed executions.
     */
    public function up(): void
    {
        Schema::create('workforce_executions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('parent_uuid', 36)->nullable()->index();
            $table->string('replay_of_uuid', 36)->nullable()->index();

            // Aggregate
            $table->string('aggregate_type', 50)->index();
            $table->unsignedBigInteger('aggregate_id')->index();

            // Capability
            $table->string('capability', 100)->index();

            // Idempotency
            $table->string('idempotency_key', 100)->nullable()->unique();

            // Tenant isolation (KURAL 1)
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();

            // Actor
            $table->string('actor_type', 30)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();

            // Trigger
            $table->string('trigger_type', 30)->nullable()->index();
            $table->string('replay_reason', 255)->nullable();

            // Status
            $table->string('execution_status', 20)->default('REQUESTED')->index();

            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            // Error
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();

            // Snapshots
            $table->json('result_snapshot')->nullable();
            $table->json('input_snapshot')->nullable();
            $table->json('metadata')->nullable();

            // Retry/Recovery (Sprint 13B)
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(5);
            $table->timestamp('next_retry_at')->nullable();
            $table->string('failure_classification', 30)->nullable()->index();
            $table->string('retry_policy', 30)->nullable();
            $table->string('recovery_of_uuid', 36)->nullable()->index();
            $table->timestamp('recovered_at')->nullable();

            $table->timestamps();

            // Composite indexes
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index(['tenant_id', 'execution_status']);
            $table->index(['execution_status', 'failure_classification']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workforce_executions');
    }
};
