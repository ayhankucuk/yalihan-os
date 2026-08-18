<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Sprint 12B: Add Property → Listing FK Constraint
 *
 * SAAB Board Resolution: BR-20260717-Sprint12B
 *
 * ON DELETE RESTRICT (SAAB decision):
 * Canonical aggregate deletion must be explicit, not silent cascade.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pre-conditions
        if (! Schema::hasTable('ilanlar') || ! Schema::hasTable('properties')) {
            return;
        }

        // Idempotent: property_id column must exist
        if (! Schema::hasColumn('ilanlar', 'property_id')) {
            return;
        }

        // Pre-check: Unmapped listings
        $unmapped = DB::selectOne(
            "SELECT COUNT(*) as cnt FROM ilanlar WHERE property_id IS NULL"
        );
        if ($unmapped && $unmapped->cnt > 0) {
            throw new \RuntimeException(
                "Cannot add FK: {$unmapped->cnt} unmapped listings. Run backfill first."
            );
        }

        // Pre-check: Tenant mismatch
        $mismatch = DB::selectOne(
            "SELECT COUNT(*) as cnt
             FROM ilanlar l
             JOIN properties p ON l.property_id = p.id
             WHERE l.tenant_id != p.tenant_id"
        );
        if ($mismatch && $mismatch->cnt > 0) {
            throw new \RuntimeException("Cannot add FK: tenant mismatches exist.");
        }

        // FK ekle — ON DELETE RESTRICT (idempotent)
        Schema::table('ilanlar', function (Blueprint $table) {
            if (! $this->fkExists('ilanlar', 'ilanlar_property_id_foreign')) {
                $table->foreign('property_id')
                    ->references('id')
                    ->on('properties')
                    ->onDelete('restrict');
            }
        });

        $this->log('FK added: ilanlar.property_id → properties.id (ON DELETE RESTRICT)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('ilanlar', 'property_id')) {
            return;
        }
        Schema::table('ilanlar', function (Blueprint $table) {
            if ($this->fkExists('ilanlar', 'ilanlar_property_id_foreign')) {
                $table->dropForeign(['property_id']);
            }
        });
        $this->log('FK removed.');
    }

    protected function log(string $msg): void
    {
        \Illuminate\Support\Facades\Log::info("[Sprint12B] {$msg}");
    }

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
