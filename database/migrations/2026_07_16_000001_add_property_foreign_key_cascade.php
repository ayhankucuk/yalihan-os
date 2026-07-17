<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 12B: Property → Listing FK Constraint
 *
 * SAAB Board Resolution: BR-20260717-Sprint12B
 *
 * SAAB Decision: ON DELETE RESTRICT (default safe choice)
 * Rationale: Canonical aggregate deletion should be explicit, not silent.
 * Cascade delete requires explicit business rule + test evidence.
 *
 * Pre-check requirements (before running this migration):
 * - all listings must be mapped (unmapped_listings = 0)
 * - no tenant mismatches (tenant_mismatch = 0)
 * - no orphan listings (orphan_listings = 0)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pre-condition: Her iki tablo da var olmalı
        if (! Schema::hasTable('ilanlar') || ! Schema::hasTable('properties')) {
            // Idempotent: Tables don't exist yet, skip
            return;
        }

        // Pre-check: Tüm ilanlar maplanmış olmalı
        $unmapped = DB::selectOne(
            "SELECT COUNT(*) as cnt FROM ilanlar WHERE property_id IS NULL"
        );

        if ($unmapped && $unmapped->cnt > 0) {
            throw new \RuntimeException(
                "Cannot add FK constraint: {$unmapped->cnt} listings are still unmapped. " .
                "Run backfill migration first."
            );
        }

        // Pre-check: Tenant mismatch kontrolü
        $mismatch = DB::selectOne(
            "SELECT COUNT(*) as cnt
             FROM ilanlar l
             JOIN properties p ON l.property_id = p.id
             WHERE l.tenant_id != p.tenant_id"
        );

        if ($mismatch && $mismatch->cnt > 0) {
            throw new \RuntimeException(
                "Cannot add FK constraint: {$mismatch->cnt} tenant mismatches detected. " .
                "Manual intervention required."
            );
        }

        // Pre-check: Orphan listings (property_id var ama properties'ta yok)
        $orphans = DB::selectOne(
            "SELECT COUNT(*) as cnt
             FROM ilanlar l
             LEFT JOIN properties p ON l.property_id = p.id
             WHERE l.property_id IS NOT NULL AND p.id IS NULL"
        );

        if ($orphans && $orphans->cnt > 0) {
            throw new \RuntimeException(
                "Cannot add FK constraint: {$orphans->cnt} orphan listings detected. " .
                "Manual intervention required."
            );
        }

        // FK constraint ekle — ON DELETE RESTRICT (SAAB kararı)
        Schema::table('ilanlar', function (Blueprint $table) {
            // Idempotent: önce varsa kaldır
            if ($this->fkExists('ilanlar', 'ilanlar_property_id_foreign')) {
                $table->dropForeign(['property_id']);
            }

            // Yeni FK ekle
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->onDelete('restrict'); // SAAB: RESTRICT default
        });

        $this->log('FK constraint ilanlar.property_id → properties.id added (ON DELETE RESTRICT)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ilanlar')) {
            return;
        }

        Schema::table('ilanlar', function (Blueprint $table) {
            if ($this->fkExists('ilanlar', 'ilanlar_property_id_foreign')) {
                $table->dropForeign(['property_id']);
            }
        });

        $this->log('FK constraint removed.');
    }

    /**
     * Log a message.
     */
    protected function log(string $message): void
    {
        \Illuminate\Support\Facades\Log::info("[Sprint12B FK] " . $message);
    }

    /**
     * Check if a foreign key exists.
     */
    protected function fkExists(string $table, string $name): bool
    {
        try {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                $fks = DB::select("PRAGMA foreign_key_list('{$table}')");
                foreach ($fks as $fk) {
                    if (($fk->from ?? '') === 'property_id') {
                        return true;
                    }
                }
                return false;
            }

            // MySQL
            $result = DB::select(
                DB::raw(
                    "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                     WHERE TABLE_SCHEMA = DATABASE()
                     AND TABLE_NAME = ?
                     AND CONSTRAINT_NAME = ?
                     AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
                ),
                [$table, $name]
            );
            return count($result) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};
