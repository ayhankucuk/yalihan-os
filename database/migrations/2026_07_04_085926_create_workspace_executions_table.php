<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WorkspaceExecution — Sprint 4.7
 *
 * Every long-running workspace operation gets its own execution record.
 * Execution lifecycle: queued → running → succeeded/failed/retrying/cancelled/timed_out
 *
 * Replay: creates a NEW execution record (never mutates the failed one).
 * Retry:  creates a NEW execution record with same payload.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')
                ->constrained('portfolio_drive_workspaces')
                ->cascadeOnDelete();
            $table->foreignId('ilan_id')->nullable()->constrained('ilanlar')->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->index();

            // ── Execution Identity ──────────────────────────────────────────
            $table->string('execution_type', 60); // e.g. 'photo_agent', 'drive_sync', 'description_agent'
            $table->string('execution_label', 120); // Human-readable: "Fotoğraf Analizi Çalıştır"
            $table->string('chain_id', 80)->nullable()->index(); // Groups related executions

            // ── State Machine ──────────────────────────────────────────────
            $table->string('state', 20)->default('queued')->index();
            // queued | running | waiting | retrying | succeeded | failed | cancelled | timed_out

            // ── Timestamps ──────────────────────────────────────────────────
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable(); // Calculated on completion

            // ── Retry / Replay ─────────────────────────────────────────────
            $table->unsignedInteger('attempt_number')->default(1);
            $table->unsignedInteger('max_attempts')->default(3);
            $table->unsignedInteger('retry_count')->default(0);
            $table->string('backoff_intervals', 200)->nullable(); // JSON: [10, 60, 300]
            $table->unsignedBigInteger('original_execution_id')->nullable()
                ->index(); // Self-referential: points to original execution for retry/replay
                // Points to original execution when this is a retry/replay

            // ── Failure Tracking ───────────────────────────────────────────
            $table->text('failure_reason')->nullable(); // Stored PERMANENTLY
            $table->json('failure_context')->nullable(); // Stack trace, partial results

            // ── Payload / Result ───────────────────────────────────────────
            $table->json('input_payload')->nullable();  // What was dispatched
            $table->json('output_result')->nullable();   // What the agent returned
            $table->unsignedInteger('progress_pct')->nullable();

            // ── Queue ───────────────────────────────────────────────────────
            $table->string('queue_name', 40)->default('workspace');
            $table->string('job_id', 80)->nullable()->index(); // Laravel job UUID
            $table->unsignedInteger('timeout_seconds')->default(300);

            // ── Audit ───────────────────────────────────────────────────────
            $table->string('triggered_by', 40)->nullable(); // 'hermes', 'manual', 'schedule', 'replay'
            $table->foreignId('triggered_by_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes(); // Soft delete for audit trail

            // ── Indexes ────────────────────────────────────────────────────
            $table->index(['workspace_id', 'state']);
            // Explicit short name: max 64 chars; default 'workspace_executions_workspace_id_execution_type_created_at_index' = 65 chars
            $table->index(['workspace_id', 'execution_type', 'created_at'], 'ws_exec_wsid_type_created_at_idx');
            $table->index(['state', 'created_at']); // For worker queries
            $table->index(['chain_id', 'created_at']);
            $table->index(['ilan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_executions');
    }
};
