<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * WAVE1 — Gmail Communications Intelligence
 *
 * MIGRATION SAFETY:
 *   - Replay-safe: columns/indexes added only once (IF NOT EXISTS guards)
 *   - Data backfill runs AFTER all schema changes complete (SQLite-safe)
 *   - MySQL CONCAT() used in PHP; SQLite || used in DB::raw (driver-aware)
 *   - down() drops all added columns in reverse order.
 *
 * @see 2026_08_21_certification_recovery_notes.md
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Phase 1: Schema changes (all columns + index) ────────────────────
        Schema::table('communications', function (Blueprint $table) {

            // ── Tenant isolation ─────────────────────────────────────────────
            if (! Schema::hasColumn('communications', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')
                      ->constrained('tenants')->nullOnDelete();
                $table->index('tenant_id', 'comm_tenant_id_idx');
            }

            // ── Idempotency (Gmail Message-ID) ─────────────────────────────
            if (! Schema::hasColumn('communications', 'external_message_id')) {
                $table->string('external_message_id', 255)->nullable()->after('sender_id');
            }

            // ── Severity ───────────────────────────────────────────────────
            if (! Schema::hasColumn('communications', 'severity')) {
                $table->string('severity', 10)->nullable()->after('ai_analysis')
                      ->comment('P0|P1|P2 — deterministic PHP policy');
            }

            // ── AI extraction ──────────────────────────────────────────────
            if (! Schema::hasColumn('communications', 'ai_extracted_data')) {
                $table->json('ai_extracted_data')->nullable()->after('severity')
                      ->comment('LLM: intent, language, source_platform, guest_name, reservation_ref, sentiment, is_urgent');
            }

            // ── Platform ───────────────────────────────────────────────────
            if (! Schema::hasColumn('communications', 'platform')) {
                $table->string('platform', 50)->nullable()->after('sender_email')
                      ->comment('airbnb|booking.com|direct|unknown');
            }

            // ── Subject ────────────────────────────────────────────────────
            if (! Schema::hasColumn('communications', 'subject')) {
                $table->string('subject', 500)->nullable()->after('message')
                      ->comment('Email konusu');
            }

            // ── Reservation link ────────────────────────────────────────────
            if (! Schema::hasColumn('communications', 'reservation_id')) {
                $table->foreignId('reservation_id')->nullable()->after('communicable_id')
                      ->constrained('property_reservations')->nullOnDelete();
                $table->index('reservation_id', 'comm_reservation_idx');
            }

            // ── Resolve audit ──────────────────────────────────────────────
            if (! Schema::hasColumn('communications', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('replied_at');
            }
            if (! Schema::hasColumn('communications', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()->after('resolved_at')
                      ->constrained('users')->nullOnDelete();
            }
        });

        // ── Phase 2: Data backfill (AFTER all columns exist) ─────────────────
        // Legacy Telegram/WhatsApp rows: tenant_id IS NULL AND external_message_id IS NULL.
        // Assign sentinel 'legacy-{id}' so the unique index (tenant_id, external_message_id)
        // doesn't have NULL/NULL collisions.
        if (Schema::hasColumn('communications', 'external_message_id')
            && ! $this->hasUniqueIndex('communications', 'comm_tenant_msgid_unique')) {
            $affected = $this->backfillLegacyMessageIds();
            if ($affected > 0) {
                \Illuminate\Support\Facades\Log::info(
                    "[Migration] Backfilled {$affected} legacy rows with sentinel external_message_id"
                );
            }
        }

        // ── Phase 3: Add unique index (MySQL only — SQLite avoids unique on nullable) ──
        if ($this->driverIsMySQL()) {
            Schema::table('communications', function (Blueprint $table) {
                if (! $this->hasUniqueIndex('communications', 'comm_tenant_msgid_unique')) {
                    $table->unique(
                        ['tenant_id', 'external_message_id'],
                        'comm_tenant_msgid_unique'
                    );
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('communications', function (Blueprint $table) {
            // Drop FKs first (MySQL requires explicit FK drop before column drop)
            if (Schema::hasColumn('communications', 'resolved_by')) {
                $table->dropForeign(['resolved_by']);
            }
            if (Schema::hasColumn('communications', 'reservation_id')) {
                $table->dropForeign(['reservation_id']);
            }
            if (Schema::hasColumn('communications', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
            }

            if (Schema::hasColumn('communications', 'resolved_by')) {
                $table->dropColumn('resolved_by');
            }
            if (Schema::hasColumn('communications', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }
            if (Schema::hasColumn('communications', 'reservation_id')) {
                $table->dropIndex('comm_reservation_idx');
                $table->dropColumn('reservation_id');
            }
            if (Schema::hasColumn('communications', 'subject')) {
                $table->dropColumn('subject');
            }
            if (Schema::hasColumn('communications', 'platform')) {
                $table->dropColumn('platform');
            }
            if (Schema::hasColumn('communications', 'ai_extracted_data')) {
                $table->dropColumn('ai_extracted_data');
            }
            if (Schema::hasColumn('communications', 'severity')) {
                $table->dropColumn('severity');
            }
            if (Schema::hasColumn('communications', 'external_message_id')) {
                if ($this->hasUniqueIndex('communications', 'comm_tenant_msgid_unique')) {
                    $table->dropUnique('comm_tenant_msgid_unique');
                }
                $table->dropColumn('external_message_id');
            }
            if (Schema::hasColumn('communications', 'tenant_id')) {
                $table->dropIndex('comm_tenant_id_idx');
                $table->dropColumn('tenant_id');
            }
        });
    }

    /**
     * Backfill legacy rows that have both tenant_id and external_message_id as NULL.
     * Uses CONCAT('legacy-', id) for MySQL and 'legacy-' || id for SQLite.
     */
    private function backfillLegacyMessageIds(): int
    {
        if ($this->driverIsMySQL()) {
            return DB::affectingStatement(
                "UPDATE communications
                 SET external_message_id = CONCAT('legacy-', id)
                 WHERE tenant_id IS NULL
                   AND external_message_id IS NULL"
            );
        }

        // SQLite: use || operator via DB::select + update
        $rows = DB::select(
            "SELECT id FROM communications
             WHERE tenant_id IS NULL
               AND external_message_id IS NULL"
        );

        foreach ($rows as $row) {
            DB::table('communications')
                ->where('id', $row->id)
                ->update(['external_message_id' => "legacy-{$row->id}"]);
        }

        return count($rows);
    }

    private function hasUniqueIndex(string $table, string $index): bool
    {
        if (! $this->driverIsMySQL()) {
            // SQLite: no information_schema, skip unique index on nullable cols for now
            return false;
        }

        try {
            $indexes = DB::select(
                "SELECT INDEX_NAME FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND INDEX_NAME = ?
                   AND NON_UNIQUE = 0",
                [$table, $index]
            );
            return count($indexes) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function driverIsMySQL(): bool
    {
        try {
            return DB::connection()->getDriverName() === 'mysql';
        } catch (\Throwable) {
            return false;
        }
    }
};
