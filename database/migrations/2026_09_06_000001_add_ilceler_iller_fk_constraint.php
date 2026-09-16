<?php

/**
 * P4 — ilceler → iller FK Constraint
 *
 * Adds the missing foreign key constraint from ilceler.il_id → iller.id.
 *
 * Pre-requisites verified (2026-09-06):
 * - iller.id: bigint unsigned, NOT NULL, PRI
 * - ilceler.il_id: bigint unsigned, NOT NULL, MUL (index exists)
 * - Orphan count: 0 (all 13 ilceler records reference valid iller.id)
 * - No existing FK on ilceler table
 *
 * Safety:
 * - onDelete('restrict') — prevents orphan parent deletion
 * - Idempotent — checks if FK already exists before adding
 * - SQLite-compatible (Laravel Schema Builder handles dialect)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: skip if FK already exists (idempotent)
        // Use try/catch for SQLite compatibility (INFORMATION_SCHEMA is MySQL-only)
        try {
            $existingFk = \Illuminate\Support\Facades\DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'ilceler'
                   AND REFERENCED_TABLE_NAME = 'iller'
                   AND COLUMN_NAME = 'il_id'"
            );
            if (!empty($existingFk)) {
                return; // FK already exists — skip
            }
        } catch (\Exception $e) {
            // SQLite or unsupported driver — proceed with Schema Builder
        }

        Schema::table('ilceler', function (Blueprint $table) {
            $table->foreign('il_id')
                ->references('id')
                ->on('iller')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('ilceler', function (Blueprint $table) {
            $table->dropForeign(['il_id']);
        });
    }
};
