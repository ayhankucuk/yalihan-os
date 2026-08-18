<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SAAB Decision 4.6 — Retry/Evidence
     * Adds:
     *   - attempts: tracks job retry count for evidence
     *   - Extends status enum to include 'retry_exhausted'
     *
     * Evidence artifacts from Decision 4.6:
     *   D4.6-D: Evidence State Machine
     *   D4.6-E: Retry Exhaustion Protocol
     *   Implementation Checklist item: add attempts column
     */
    public function up(): void
    {
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            // Tracks how many times the job was attempted before exhaustion.
            // Laravel's $job->attempts() is runtime-only; this column
            // persists the count into the evidence record.
            $table->unsignedTinyInteger('attempts')
                ->default(0)
                ->after('status');

            // Extend status comment to document retry_exhausted.
            // The column type and stored values are unchanged; only
            // the documented enum grows.
            // Old: dispatched | processing | completed | completed_with_conflicts | failed
            // New:  + retry_exhausted
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channel_sync_executions', function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
