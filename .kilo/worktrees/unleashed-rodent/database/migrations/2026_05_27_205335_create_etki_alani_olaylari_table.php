<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: etki_alani_olaylari (Domain Events)
 *
 * SAB Phase 15 Sprint 1: CQRS Event Sourcing Infrastructure
 * Immutable append-only event log for domain aggregate state reconstruction.
 *
 * Anayasal Kararlar:
 * - Madde 1: Immutable Event Store (No updates/deletes allowed)
 * - Madde 2: Tenant Isolation (tenant_id mandatory)
 * - Madde 3: Aggregate Root Tracking (aggregate_type + aggregate_id)
 * - Madde 4: Ordered Event Replay (sequence_number for deterministic ordering)
 * - Madde 5: Payload Encryption Support (encrypted_payload for GDPR compliance)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('etki_alani_olaylari', function (Blueprint $table) {
            $table->id();

            // Tenant Isolation (SAB Multi-Tenancy)
            $table->unsignedBigInteger('tenant_id')->index();

            // Aggregate Root Identification
            $table->string('aggregate_type', 100)->comment('Domain aggregate class (Lead, Ilan, Kisi)');
            $table->unsignedBigInteger('aggregate_id')->comment('Aggregate root entity ID');

            // Event Metadata
            $table->string('event_type', 150)->comment('Fully qualified event class name');
            $table->unsignedInteger('sequence_number')->comment('Monotonic sequence for ordered replay');

            // Event Payload (JSON)
            $table->json('payload')->comment('Serialized event data');
            $table->text('encrypted_payload')->nullable()->comment('GDPR-compliant encrypted sensitive data');

            // Audit Trail
            $table->unsignedBigInteger('user_id')->nullable()->comment('User who triggered the event');
            $table->string('ip_adresi', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Immutable Timestamp (created_at only, no updated_at)
            $table->timestamp('created_at')->useCurrent();

            // Composite Indexes for Performance
            $table->index(['tenant_id', 'aggregate_type', 'aggregate_id'], 'idx_tenant_aggregate');
            $table->index(['aggregate_type', 'aggregate_id', 'sequence_number'], 'idx_aggregate_sequence');
            $table->index('event_type');
            $table->index('created_at');

            // Foreign Keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Unique constraint: Prevent duplicate sequence numbers per aggregate
        Schema::table('etki_alani_olaylari', function (Blueprint $table) {
            $table->unique(['aggregate_type', 'aggregate_id', 'sequence_number'], 'uniq_aggregate_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etki_alani_olaylari');
    }
};
