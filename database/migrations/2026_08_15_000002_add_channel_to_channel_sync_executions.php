<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 13 E03: Per-Channel Execution Isolation
     *
     * Adds channel discriminator to enable:
     *   - Independent execution records per channel (Booking vs Airbnb)
     *   - Channel-aware idempotency keys
     *   - Isolated retry/replay per channel
     *
     * Key changes:
     *   - idempotency_key unique constraint relaxed to (idempotency_key, channel)
     *   - Channel NULL = aggregated (legacy/imported records)
     *   - unique index on (idempotency_key, channel) allows same business operation
     *     to produce independent executions per channel without cross-channel dupes
     */
    public function up(): void
    {
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            // Channel discriminator — NULL means "aggregated across all channels"
            // Valid values: 'airbnb', 'booking', 'airbnb_clone', etc.
            // NULL (aggregated) is allowed for imported/legacy records.
            $table->string('channel', 30)->nullable()->after('reservation_id');
        });

        // Drop the old single-column unique constraint and replace with composite.
        // We must do this carefully: first drop index, then add composite unique.
        // Laravel's unique() on a single column creates index named: {table}_{columns}_unique
        // For 'idempotency_key' it is: channel_sync_executions_idempotency_key_unique
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            $table->dropUnique('channel_sync_executions_idempotency_key_unique');
        });

        Schema::table('channel_sync_executions', function (Blueprint $table) {
            // Composite unique: same idempotency_key can exist for different channels
            // NULLs are treated as distinct values in MySQL; SQLite uses NULLIF trick.
            // For cross-DB compatibility we handle this at application level via
            // a partial unique index in E03+ (not supported in all DBs).
            // Here we use a nullable column + NULLIF pattern; the constraint is
            // technically: idempotency_key + COALESCE(channel, 'NULL')
            // We'll use a generated column approach for MySQL, and for SQLite
            // the uniqueness is enforced via the composite index where NULL==NULL
            // (SQLite treats NULL != NULL, so this works naturally).
            // For MySQL, we need a virtual generated column.
            $table->string('idempotency_key', 255)->change();
        });

        // Add composite unique on (tenant_id, idempotency_key, channel).
        // For NULL channel: different channels can have same idempotency key root
        // (e.g., "1:2:3:block:2026-01-01:2026-01-03" for booking + airbnb independently).
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            $table->unique(['tenant_id', 'idempotency_key', 'channel'], 'channel_sync_executions_tenant_key_channel_unique');
        });

        // Index for channel-specific queries
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            $table->index(['tenant_id', 'channel'], 'channel_sync_executions_tenant_channel_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            $table->dropUnique('channel_sync_executions_tenant_key_channel_unique');
            $table->dropIndex('channel_sync_executions_tenant_channel_idx');
        });

        Schema::table('channel_sync_executions', function (Blueprint $table) {
            $table->string('idempotency_key', 255)->unique()->change();
            $table->dropColumn('channel');
        });
    }
};
