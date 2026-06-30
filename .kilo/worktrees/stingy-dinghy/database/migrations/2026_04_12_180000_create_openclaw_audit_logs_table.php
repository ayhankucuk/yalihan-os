<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OpenClaw Audit Log — Structured, queryable audit trail for ALL agent interactions.
 *
 * Purpose: Every agent request (pass/block/violation) gets a DB record.
 * File logs (storage/logs/security.log) = human-readable backup.
 * This table = queryable, aggregatable, anomaly-detectable.
 *
 * Context7 Compliance:
 * - basarili (NOT success/ok)
 * - http_durum_kodu (NOT http_status_code)
 * - olusturma_tarihi (NOT created_at for canonical usage)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openclaw_audit_logs', function (Blueprint $table) {
            $table->id();

            // Event classification
            $table->string('event_type', 50);
            // gateway_open, gateway_blocked, scope_rejected, token_invalid,
            // boundary_rejected, request_passed, write_violation

            // Agent identity
            $table->string('agent_source', 50)->nullable();
            $table->string('agent_scope', 100)->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->string('token_hash', 64)->nullable();

            // Request context
            $table->string('route', 255)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->unsignedSmallInteger('http_durum_kodu')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->unsignedInteger('payload_size')->nullable();

            // Performance
            $table->float('duration_ms')->nullable();

            // Outcome
            $table->boolean('basarili')->default(false);
            $table->string('rejection_reason', 255)->nullable();

            // Write violation details (only for event_type = 'write_violation')
            $table->string('service_class', 255)->nullable();
            $table->string('service_method', 100)->nullable();

            // Extensible context
            $table->json('metadata')->nullable();

            $table->timestamp('olusturma_tarihi')->useCurrent();

            // === Performance Indexes ===
            $table->index('correlation_id');
            $table->index(['event_type', 'olusturma_tarihi']);
            $table->index(['agent_source', 'olusturma_tarihi']);
            $table->index(['basarili', 'olusturma_tarihi']);
            $table->index(['token_hash', 'olusturma_tarihi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openclaw_audit_logs');
    }
};
