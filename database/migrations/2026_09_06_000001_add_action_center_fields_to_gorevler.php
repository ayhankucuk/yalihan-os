<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 15 — Action Center: Additive fields to gorevler table.
 *
 * Adds AI provenance, lifecycle tracking, and tenant isolation columns
 * to support the Action Center event-to-action mapping architecture.
 *
 * All columns are nullable and additive — existing data is not affected.
 *
 * Idempotent: each column is added only if it doesn't already exist.
 * SQLite guard: FK constraints and composite index are MySQL-only.
 *
 * Architecture: docs/architecture/sprint-15-action-center-architecture.md §7.1
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('gorevler')) {
            return;
        }

        Schema::table('gorevler', function (Blueprint $table) {
            // ── Tenant isolation (required for Action Center cross-tenant safety) ──
            if (!Schema::hasColumn('gorevler', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')
                    ->nullable()
                    ->after('id');
            }

            // ── AI Provenance fields (Sprint 16 prerequisite — added now for Phase 1) ──
            if (!Schema::hasColumn('gorevler', 'source_event')) {
                $table->string('source_event')
                    ->nullable()
                    ->after('gorev_tipi');
            }

            if (!Schema::hasColumn('gorevler', 'source_module')) {
                $table->string('source_module')
                    ->nullable()
                    ->after('source_event');
            }

            if (!Schema::hasColumn('gorevler', 'ai_confidence_score')) {
                $table->float('ai_confidence_score')
                    ->nullable()
                    ->after('source_module');
            }

            if (!Schema::hasColumn('gorevler', 'ai_reasoning')) {
                $table->text('ai_reasoning')
                    ->nullable()
                    ->after('ai_confidence_score');
            }

            if (!Schema::hasColumn('gorevler', 'ai_model_version')) {
                $table->string('ai_model_version')
                    ->nullable()
                    ->after('ai_reasoning');
            }

            // ── Lifecycle tracking timestamps ──
            if (!Schema::hasColumn('gorevler', 'assigned_at')) {
                $table->timestamp('assigned_at')
                    ->nullable()
                    ->after('atanan_user_id');
            }

            if (!Schema::hasColumn('gorevler', 'started_at')) {
                $table->timestamp('started_at')
                    ->nullable()
                    ->after('assigned_at');
            }

            if (!Schema::hasColumn('gorevler', 'completed_at')) {
                $table->timestamp('completed_at')
                    ->nullable()
                    ->after('started_at');
            }

            // ── Cancellation tracking ──
            if (!Schema::hasColumn('gorevler', 'cancel_reason')) {
                $table->string('cancel_reason')
                    ->nullable()
                    ->after('gorev_durumu');
            }
        });

        // ── Indexes (MySQL only — SQLite doesn't support composite index in all versions) ──
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Composite index for Action Center queue queries
            if (!Schema::hasIndex('gorevler', 'action_center_queue_idx')) {
                Schema::table('gorevler', function (Blueprint $table) {
                    $table->index(
                        ['gorev_durumu', 'oncelik', 'bitis_tarihi'],
                        'action_center_queue_idx'
                    );
                });
            }

            // Tenant isolation index
            if (!Schema::hasIndex('gorevler', 'gorevler_tenant_id_idx')) {
                Schema::table('gorevler', function (Blueprint $table) {
                    $table->index('tenant_id', 'gorevler_tenant_id_idx');
                });
            }

            // Source event index for idempotency checks
            if (!Schema::hasIndex('gorevler', 'gorevler_source_event_idx')) {
                Schema::table('gorevler', function (Blueprint $table) {
                    $table->index('source_event', 'gorevler_source_event_idx');
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('gorevler')) {
            return;
        }

        Schema::table('gorevler', function (Blueprint $table) {
            // Drop MySQL-only indexes first
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                try {
                    $table->dropIndex('action_center_queue_idx');
                } catch (\Throwable) {}

                try {
                    $table->dropIndex('gorevler_tenant_id_idx');
                } catch (\Throwable) {}

                try {
                    $table->dropIndex('gorevler_source_event_idx');
                } catch (\Throwable) {}
            }

            // Drop columns added by this migration
            $columns = [
                'cancel_reason',
                'completed_at',
                'started_at',
                'assigned_at',
                'ai_model_version',
                'ai_reasoning',
                'ai_confidence_score',
                'source_module',
                'source_event',
                'tenant_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('gorevler', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
