<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C5.1: Settlement Domain Foundation — Canonical Tables
     *
     * SAAB Phase C5.1 — Settlement & Reconciliation
     * Authority: SAAB / Strategic AI Architecture Board
     * Baseline: 35b4e6c (C4.2 Certified)
     *
     * Tables:
     *   1. provider_settlements   — RAW immutable OTA/channel payout evidence
     *   2. settlement_allocations — per-reservation allocation from a settlement batch
     *   3. bank_transactions     — RAW immutable bank account movements
     *   4. reconciliation_executions — replay-safe reconciliation attempts
     *
     * Scope exclusions (C5.1 = foundation only):
     *   - NO settlement ledger posting (C5.5)
     *   - NO actual bank API integration (C5.3)
     *   - NO payout release (C5.6)
     *   - NO channel fee mutation (C4 snapshot immutable)
     *
     * Design rules:
     *   - RAW evidence columns are immutable (provider/raw prefix)
     *   - Tenant ownership on all tables
     *   - Idempotency keys prevent duplicate ingestion
     *   - Reconciliation executions are APPEND-ONLY (never UPDATE old execution)
     */
    public function up(): void
    {
        // ── 1. Provider Settlements ────────────────────────────────────────────────
        // RAW immutable evidence from OTA/channel payout reports.
        // One row per external payout event from the provider.
        // tenant_id + provider + external_settlement_id = unique.
        Schema::create('provider_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('provider', 50)->index(); // e.g. booking_com, airbnb, channex
            $table->string('external_settlement_id', 255)->nullable(); // OTA payout batch ID
            $table->string('external_reservation_id', 255)->nullable(); // OTA reservation ID
            $table->unsignedBigInteger('reservation_id')->nullable()->index(); // FK → property_reservations

            // RAW provider-reported amounts (immutable evidence)
            $table->decimal('gross_amount', 15, 4)->default(0);
            $table->decimal('channel_fee_amount', 15, 4)->default(0);
            $table->decimal('net_amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('TRY');

            // RAW payout metadata (immutable)
            $table->string('payout_type', 30)->nullable(); // GROSS | NET | UNKNOWN
            $table->string('payout_status', 30)->nullable(); // PENDING | PAID | PARTIALLY_PAID | CANCELLED | UNKNOWN
            $table->string('bank_transfer_reference', 255)->nullable(); // BT payout reference
            $table->date('payout_date')->nullable();
            $table->date('value_date')->nullable(); // valör

            // RAW raw payload (immutable audit trail)
            $table->json('raw_payload')->nullable();
            $table->string('raw_source', 50)->default('api'); // api | webhook | csv | manual

            // Ingestion metadata
            $table->string('settlement_status', 30)->default('pending'); // pending | allocated | reconciled | discrepancy
            $table->unsignedBigInteger('allocated_to_id')->nullable(); // FK → reconciliation_executions

            // Idempotency: prevent duplicate ingestion
            $table->string('idempotency_key', 255)->nullable()->unique();

            // Tenant-owned soft delete
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'provider', 'external_settlement_id'], 'ps_tp_ext_idx');
            $table->index(['tenant_id', 'reservation_id'], 'ps_t_rsv_idx');
            $table->index(['tenant_id', 'settlement_status'], 'ps_t_status_idx');
        });

        // ── 2. Settlement Allocations ─────────────────────────────────────────
        // Per-reservation allocation derived from a provider_settlements batch.
        // One row per reservation within a settlement batch.
        Schema::create('settlement_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('provider_settlement_id');
            $table->unsignedBigInteger('reservation_id')->index();
            $table->unsignedBigInteger('reconciliation_execution_id')->nullable()->index();

            // Allocated amounts (immutable after creation)
            $table->decimal('gross_amount', 15, 4)->default(0);
            $table->decimal('channel_fee_amount', 15, 4)->default(0);
            $table->decimal('net_amount', 15, 4)->default(0);
            $table->string('currency', 3)->default('TRY');

            // Status
            $table->string('allocation_status', 30)->default('pending');
            // pending | matched | discrepancy | reconciled

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('provider_settlement_id')
                ->references('id')->on('provider_settlements')->onDelete('cascade');
            $table->index(['tenant_id', 'allocation_status'], 'sa_t_status_idx');
            $table->index(['tenant_id', 'reservation_id'], 'sa_t_rsv_idx');
        });

        // ── 3. Bank Transactions ────────────────────────────────────────────────
        // RAW immutable bank statement entries from ingest.
        // One row per bank account movement.
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('bank_account_id')->nullable(); // FK → bank_accounts

            // RAW bank statement fields (immutable)
            $table->date('transaction_date'); // işlem tarihi
            $table->date('value_date')->nullable(); // valör
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('TRY');
            $table->string('debit_credit', 1)->default('C'); // D or C
            $table->string('reference_text', 500)->nullable(); // açıklama / dekont no
            $table->string('iban', 34)->nullable();
            $table->string('sender_name', 255)->nullable();
            $table->json('raw_payload')->nullable(); // RAW ingest evidence

            // RAW source (immutable)
            $table->string('source', 30)->default('csv'); // csv | mt940 | api | manual
            $table->string('source_reference', 255)->nullable(); // idempotency

            // Matching state
            $table->string('match_status', 30)->default('unmatched');
            // unmatched | matched | ignored
            $table->unsignedBigInteger('matched_settlement_id')->nullable();
            $table->unsignedBigInteger('reconciliation_execution_id')->nullable()->index();

            // Ingestion metadata
            $table->string('ingestion_status', 30)->default('active');
            // active | reconciled | ignored

            // Idempotency
            $table->string('idempotency_key', 255)->nullable()->unique();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'match_status'], 'bt_t_match_idx');
            $table->index(['tenant_id', 'transaction_date'], 'bt_t_date_idx');
            $table->index(['tenant_id', 'bank_account_id'], 'bt_t_bank_idx');
        });

        // ── 4. Reconciliation Executions ──────────────────────────────────────
        // APPEND-ONLY reconciliation attempt log.
        // Replays do NOT mutate old executions — they create new execution records.
        // ReconciliationExecution NEVER deletes or updates old evidence.
        Schema::create('reconciliation_executions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('execution_type', 50)->default('auto');
            // auto | manual | scheduled | retry

            // Execution scope
            $table->unsignedBigInteger('bank_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('settlement_allocation_id')->nullable()->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();

            // Execution result
            $table->string('result', 30)->nullable();
            // exact_match | within_tolerance | discrepancy | no_match
            $table->string('result_status', 30)->default('pending');
            // pending | completed | failed | discrepancy_held

            // Discrepancy details (populated when result = discrepancy)
            $table->decimal('expected_amount', 15, 4)->nullable();
            $table->decimal('actual_amount', 15, 4)->nullable();
            $table->decimal('discrepancy_amount', 15, 4)->nullable();
            $table->text('discrepancy_reason')->nullable();

            // Operator override (for manual reconciliation)
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->text('operator_notes')->nullable();

            // Execution metadata
            $table->string('execution_trigger', 50)->default('system');
            // system | api | manual | scheduled
            $table->json('execution_context')->nullable(); // replay chain, etc.
            $table->unsignedInteger('attempt_number')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'result_status'], 're_t_status_idx');
            $table->index(['tenant_id', 'result'], 're_t_result_idx');
            $table->index(['tenant_id', 'reservation_id'], 're_t_rsv_idx');
        });

        // Add this FK only after reconciliation_executions exists.
        Schema::table('settlement_allocations', function (Blueprint $table) {
            $table->foreign('reconciliation_execution_id')
                ->references('id')->on('reconciliation_executions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_executions');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('settlement_allocations');
        Schema::dropIfExists('provider_settlements');
    }
};
