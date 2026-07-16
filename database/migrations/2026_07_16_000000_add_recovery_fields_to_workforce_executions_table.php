<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 13B — Recovery Engine
     *
     * Adds retry/recovery fields to workforce_executions table.
     *
     * Failure classification:
     *   TRANSIENT   — Geçici hata (ağ, timeout). Retry ile düzelebilir.
     *   PERMANENT   — Kalıcı hata (validation, business rule). Retry faydasız.
     *   CONFIG      — Yapılandırma hatası (missing API key, misconfigured service). Düzeltmeden retry faydasız.
     *   UNKNOWN     — Sınıflandırılamayan hata. Manuel inceleme gerekebilir.
     *
     * Retry policies:
     *   EXPONENTIAL — Exponential backoff: 10s → 1m → 5m → 15m → 1h
     *   LINEAR      — Linear backoff: 30s → 1m → 2m → 5m
     *   IMMEDIATE   — Hemen tekrar (sadece TRANSIENT için)
     *
     * @see ADR-042 + Sprint 13B Recovery Contract
     */
    public function up(): void
    {
        Schema::table('workforce_executions', function (Blueprint $table) {
            // ── Retry Tracking ──────────────────────────────────────────────
            if (!Schema::hasColumn('workforce_executions', 'retry_count')) {
                $table->unsignedTinyInteger('retry_count')
                    ->default(0)
                    ->comment('Bu execution için kaç kez retry yapıldı');
            }

            if (!Schema::hasColumn('workforce_executions', 'max_retries')) {
                $table->unsignedTinyInteger('max_retries')
                    ->default(3)
                    ->comment('Maksimum retry hakkı (0 = retry yok)');
            }

            if (!Schema::hasColumn('workforce_executions', 'next_retry_at')) {
                $table->timestamp('next_retry_at')
                    ->nullable()
                    ->index()
                    ->comment('Planlanan sonraki retry zamanı');
            }

            // ── Failure Classification ──────────────────────────────────────
            if (!Schema::hasColumn('workforce_executions', 'failure_classification')) {
                $table->string('failure_classification', 30)
                    ->nullable()
                    ->index()
                    ->comment('TRANSIENT | PERMANENT | CONFIG | UNKNOWN');
            }

            // ── Retry Policy ──────────────────────────────────────────────
            if (!Schema::hasColumn('workforce_executions', 'retry_policy')) {
                $table->string('retry_policy', 30)
                    ->nullable()
                    ->comment('EXPONENTIAL | LINEAR | IMMEDIATE');
            }

            // ── Recovery Audit ────────────────────────────────────────────
            if (!Schema::hasColumn('workforce_executions', 'recovery_of_uuid')) {
                $table->uuid('recovery_of_uuid')
                    ->nullable()
                    ->index()
                    ->comment('Otomatik recovery ile oluşturulan execution, hangi FAILED execution\'dan kurtarıldı');
            }

            if (!Schema::hasColumn('workforce_executions', 'recovered_at')) {
                $table->timestamp('recovered_at')
                    ->nullable()
                    ->comment('Otomatik recovery zamanı');
            }

            // ── Composite indexes for recovery queries ─────────────────────
            // Using try/catch for environments where index already exists
            try {
                $table->index(['execution_status', 'failure_classification', 'next_retry_at'], 'exec_recovery_idx');
            } catch (\Throwable) { /* index exists */ }

            try {
                $table->index(['tenant_id', 'execution_status', 'retry_count'], 'tenant_exec_retry_idx');
            } catch (\Throwable) { /* index exists */ }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workforce_executions', function (Blueprint $table) {
            try {
                $table->dropIndex('exec_recovery_idx');
            } catch (\Throwable) { /* not exists */ }

            try {
                $table->dropIndex('tenant_exec_retry_idx');
            } catch (\Throwable) { /* not exists */ }

            $columns = [
                'retry_count',
                'max_retries',
                'next_retry_at',
                'failure_classification',
                'retry_policy',
                'recovery_of_uuid',
                'recovered_at',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('workforce_executions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
