<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CHECKOUT-D2 — Operational task extension to Gorev
     *
     * Adds ilan_id and reservation_id to gorevler table to support
     * automated operational task creation tied to a specific reservation
     * and property.
     *
     * Idempotent: columns are added only if they don't already exist.
     * (Already run in production before this commit.)
     *
     * Indexes added for fast idempotency lookups in OperationalGorevService.
     *
     * SAAB Decision CHECKOUT-D2: Extend Gorev + OperationalGorevService
     * Baseline: 88ccfc8
     */
    public function up(): void
    {
        Schema::table('gorevler', function (Blueprint $table) {
            // Add ilan_id if not exists (idempotent)
            if (!Schema::hasColumn('gorevler', 'ilan_id')) {
                $table->unsignedBigInteger('ilan_id')
                    ->nullable()
                    ->after('proje_id');
            }

            // Add reservation_id if not exists (idempotent)
            if (!Schema::hasColumn('gorevler', 'reservation_id')) {
                $table->unsignedBigInteger('reservation_id')
                    ->nullable()
                    ->after('ilan_id');
            }

            // Add indexes for fast idempotency lookups
            if (!Schema::hasIndex('gorevler', 'gorevler_reservation_id_idx')) {
                $table->index('reservation_id', 'gorevler_reservation_id_idx');
            }
            if (!Schema::hasIndex('gorevler', 'gorevler_ilan_id_idx')) {
                $table->index('ilan_id', 'gorevler_ilan_id_idx');
            }
        });

        // Add foreign key constraints only if they don't exist
        // Only check existing FKs for MySQL; SQLite doesn't support information_schema
        $existingFKs = [];
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $foreignKeys = \Illuminate\Support\Facades\DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'gorevler'
                   AND CONSTRAINT_NAME IN ('gorevler_ilan_id_foreign', 'gorevler_reservation_id_foreign')
                   AND CONSTRAINT_TYPE = 'FOREIGN KEY KEY'"
            );
            $existingFKs = array_column($foreignKeys, 'CONSTRAINT_NAME');
        }

        Schema::table('gorevler', function (Blueprint $table) use ($existingFKs) {
            if (!in_array('gorevler_ilan_id_foreign', $existingFKs)) {
                $table->foreign('ilan_id')
                    ->references('id')
                    ->on('ilanlar')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
            if (!in_array('gorevler_reservation_id_foreign', $existingFKs)) {
                $table->foreign('reservation_id')
                    ->references('id')
                    ->on('property_reservations')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gorevler', function (Blueprint $table) {
            // Only drop if the constraints/columns were added by this migration
            try {
                $table->dropForeign(['ilan_id']);
            } catch (\Throwable) {}
            try {
                $table->dropForeign(['reservation_id']);
            } catch (\Throwable) {}
            if (Schema::hasColumn('gorevler', 'reservation_id')) {
                $table->dropIndex('gorevler_reservation_id_idx');
            }
            if (Schema::hasColumn('gorevler', 'ilan_id')) {
                $table->dropIndex('gorevler_ilan_id_idx');
            }
            if (Schema::hasColumn('gorevler', 'reservation_id')) {
                $table->dropColumn('reservation_id');
            }
            if (Schema::hasColumn('gorevler', 'ilan_id')) {
                $table->dropColumn('ilan_id');
            }
        });
    }
};
