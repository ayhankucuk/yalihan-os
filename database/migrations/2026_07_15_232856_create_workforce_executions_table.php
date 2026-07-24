<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 13 — Replay & Recovery
     *
     * Canonical execution table for all runtime execution records.
     *
     * Mimari ayrım:
     *   ListingStateTransition = Immutable domain history (değiştirilemez, hiçbir zaman)
     *   workforce_executions   = Runtime execution history (yeni record oluşturulabilir, replay her zaman yeni record üretir)
     *
     * Bu tablo: normal execution, retry, replay, scheduled run ve Hermes orchestration için
     * ortak execution metadata sağlar. Replay asla original record'u değiştirmez.
     *
     * @see ADR-042 + Sprint 13 Replay Contract
     */
    public function up(): void
    {
        Schema::create('workforce_executions', function (Blueprint $table) {
            $table->id();

            // ── Execution Identity ──────────────────────────────────────────────
            $table->uuid('uuid')->unique()->comment('Benzersiz execution kimliği (replay zincirlerinde referans)');
            $table->uuid('parent_uuid')->nullable()->index()->comment('Üst execution — retry/replay zinciri');
            $table->uuid('replay_of_uuid')->nullable()->index()->comment('Yeniden çalıştırılan orijinal execution');

            // ── Aggregate Context ──────────────────────────────────────────────
            $table->string('aggregate_type', 50)->index()->comment('Ilan, Property vs.');
            $table->unsignedBigInteger('aggregate_id')->index()->comment('Aggregate primary key');
            $table->string('capability', 100)->index()->comment('publish, archive, restore vs.');

            // ── Idempotency ───────────────────────────────────────────────────
            $table->string('idempotency_key', 120)->nullable()->unique()->comment(
                'Birden fazla çalıştırmayı engeller. Aynı key = aynı sonuç. Tenant-scope unique.'
            );

            // ── Tenant + Workspace Isolation (KURAL 1) ──────────────────────────
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();

            // ── Actor / Trigger ───────────────────────────────────────────────
            $table->string('actor_type', 30)->nullable()->index()->comment('User, Hermes, Agent, System');
            $table->unsignedBigInteger('actor_id')->nullable()->index()->comment('User veya Agent ID');
            $table->string('trigger_type', 30)->nullable()->index()->comment(
                'MANUAL | REPLAY | RETRY | SCHEDULED | WEBHOOK | HERMES'
            );

            // ── Replay Metadata ───────────────────────────────────────────────
            $table->string('replay_reason', 255)->nullable()->comment('Replay nedeni (opsiyonel not)');

            // ── Execution Status ──────────────────────────────────────────────
            $table->string('execution_status', 30)->default('REQUESTED')->index()->comment(
                'REQUESTED | RUNNING | COMPLETED | FAILED | CANCELLED'
            );

            // ── Timing ────────────────────────────────────────────────────────
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable()->comment('Milisaniye cinsinden süre');

            // ── Result ──────────────────────────────────────────────────────
            $table->string('error_code', 50)->nullable()->comment('SAB::ERROR_CODE formatı');
            $table->text('error_message')->nullable()->comment('İnsan okunabilir hata mesajı');
            $table->json('result_snapshot')->nullable()->comment('State snapshot veya output payload');

            // ── Input Snapshot ───────────────────────────────────────────────
            $table->json('input_snapshot')->nullable()->comment('Execution başlangıç input payload');

            // ── Flexible Metadata ───────────────────────────────────────────
            $table->json('metadata')->nullable()->comment('Esnek runtime bilgisi (retry count, attempt vs.)');

            // ── Audit ───────────────────────────────────────────────────────
            $table->timestamps();

            // ── Indexes ─────────────────────────────────────────────────────
            $table->index(['aggregate_type', 'aggregate_id']);
            $table->index(['tenant_id', 'execution_status']);
            $table->index(['execution_status', 'started_at']);
            $table->index(['trigger_type', 'execution_status']);
            $table->index(['actor_type', 'actor_id']);
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
