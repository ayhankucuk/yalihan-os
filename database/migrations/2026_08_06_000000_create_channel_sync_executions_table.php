<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Channel Manager Phase 1 — Adds new columns to channel_sync_executions.
 *
 * The table was created by 2026_07_29_000001 (Sprint 13 E02) with a different
 * schema. This migration adds the Channel Manager capability columns without
 * dropping the existing table.
 *
 * PRR-R002 fix: converted from duplicate create to addColumn migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            // Add Channel Manager columns if they don't already exist
            if (!Schema::hasColumn('channel_sync_executions', 'channel')) {
                $table->string('channel', 50)->nullable()->after('property_id');
            }
            if (!Schema::hasColumn('channel_sync_executions', 'direction')) {
                $table->string('direction', 20)->nullable()->after('channel');
            }
            if (!Schema::hasColumn('channel_sync_executions', 'state')) {
                $table->string('state', 20)->nullable()->after('idempotency_key');
            }
            if (!Schema::hasColumn('channel_sync_executions', 'attempt')) {
                $table->unsignedTinyInteger('attempt')->default(1)->after('state');
            }
            if (!Schema::hasColumn('channel_sync_executions', 'error_code')) {
                $table->string('error_code', 50)->nullable()->after('attempt');
            }
            if (!Schema::hasColumn('channel_sync_executions', 'external_ref')) {
                $table->string('external_ref', 255)->nullable()->after('error_message');
            }
            if (!Schema::hasColumn('channel_sync_executions', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('processed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            $table->dropColumnIfExists('channel');
            $table->dropColumnIfExists('direction');
            $table->dropColumnIfExists('state');
            $table->dropColumnIfExists('attempt');
            $table->dropColumnIfExists('error_code');
            $table->dropColumnIfExists('external_ref');
            $table->dropColumnIfExists('completed_at');
        });
    }
};
