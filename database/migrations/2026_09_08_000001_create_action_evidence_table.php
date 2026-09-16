<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 15 Phase 3 — Action Center: action_evidence table.
 *
 * Tracks completion evidence for Action Center Gorev records.
 * Each Gorev can have multiple evidence entries (photos, notes, logs).
 *
 * Evidence types:
 *   - note:       Human-written text note from agent
 *   - photo:       Photo upload with file path and hash
 *   - system_log:  Automated system action (event triggered, assignment made)
 *   - screenshot:  Browser screenshot of completion state
 *
 * Cascade delete: when a Gorev is deleted, all its evidence is deleted.
 * Tenant isolation: enforced at service layer via Gorev→tenant_id chain.
 *
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §7.2
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gorevler')) {
            return;
        }

        Schema::create('action_evidence', function (Blueprint $table) {
            $table->id();

            // Gorev linkage — cascade on delete
            $table->foreignId('gorev_id')
                ->constrained('gorevler')
                ->cascadeOnDelete();

            // Evidence type — determines how evidence_data is interpreted
            $table->string('evidence_type', 30); // note | photo | system_log | screenshot

            // JSON payload — structure depends on evidence_type
            //   note:         { "text": "...", "mentions": [] }
            //   photo:        { "path": "...", "hash": "...", "mime": "..." }
            //   system_log:   { "event": "...", "actor": "...", "message": "..." }
            //   screenshot:   { "path": "...", "hash": "...", "mime": "..." }
            $table->json('evidence_data')->nullable();

            // Who recorded this evidence
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Index for fast lookup by Gorev
            $table->index('gorev_id');

            // Index for audit by recorder
            $table->index('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_evidence');
    }
};
