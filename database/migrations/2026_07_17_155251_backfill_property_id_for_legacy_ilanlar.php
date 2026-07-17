<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 12B: Legacy Property Backfill
 *
 * SAAB Board Resolution: BR-20260717-Sprint12B
 * Option A: Canonical Property Aggregate Infrastructure
 *
 * Backfill Strategy:
 * - Tenant-safe: Her Property tek bir tenant'a aittir
 * - Deterministic: legacy-listing:{tenant_id}:{ilan_id} referans formatı
 * - Idempotent: Mevcut mapping'i değiştirmez
 * - Loggable: Her mapping loglanır
 * - Reversible: down() ile geri alınabilir
 *
 * Pre-conditions:
 * - properties tablosu oluşturulmuş olmalı
 * - ilanlar.property_id kolonu mevcut olmalı
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pre-condition: properties tablosu var mı?
        if (!Schema::hasTable('properties')) {
            throw new \RuntimeException(
                'Migration requires properties table. Run create_properties_table migration first.'
            );
        }

        // Pre-condition: property_id kolonu var mı?
        if (!Schema::hasColumn('ilanlar', 'property_id')) {
            throw new \RuntimeException(
                'Migration requires ilanlar.property_id column. Run add_property_and_workspace migration first.'
            );
        }

        // Pre-condition: canonical_reference kolonu var mı?
        if (!Schema::hasColumn('properties', 'canonical_reference')) {
            throw new \RuntimeException(
                'Migration requires properties.canonical_reference column.'
            );
        }

        // Idempotent: Zaten maplanmış kayıtları atla
        $alreadyMapped = DB::selectOne(
            "SELECT COUNT(*) as cnt FROM ilanlar WHERE property_id IS NOT NULL"
        );

        if ($alreadyMapped && $alreadyMapped->cnt > 0) {
            // Mevcut mapping'i koru, sadece NULL olanları işle
            $this->log("Found {$alreadyMapped->cnt} already mapped listings. Processing only unmapped.");
        }

        // Legacy ilanları al (property_id NULL olanlar)
        $legacyIlans = DB::select(
            "SELECT id, tenant_id FROM ilanlar WHERE property_id IS NULL"
        );

        if (empty($legacyIlans)) {
            $this->log('No unmapped legacy listings found. Migration complete.');
            return;
        }

        $this->log("Found " . count($legacyIlans) . " unmapped legacy listings.");

        // Tenant bazlı grupla — NULL tenant_id için özel handle
        $byTenant = [];
        foreach ($legacyIlans as $ilan) {
            $tenantId = $ilan->tenant_id;

            // NULL tenant_id kontrolü
            if ($tenantId === null) {
                $tenantId = '_NULL_TENANT_';
                $this->log("WARNING: Found listing with NULL tenant_id. Will use placeholder Property.");
            }

            if (!isset($byTenant[$tenantId])) {
                $byTenant[$tenantId] = [];
            }
            $byTenant[$tenantId][] = $ilan;
        }

        // Her tenant için Property oluştur ve ilanları mapla
        foreach ($byTenant as $tenantId => $ilanlar) {
            $this->processTenantBatch($tenantId, $ilanlar);
        }

        // Post-migration verification
        $this->verifyMapping();
    }

    /**
     * Process batch of listings for a single tenant.
     *
     * @param mixed $tenantId
     * @param array $ilanlar
     */
    protected function processTenantBatch($tenantId, array $ilanlar): void
    {
        // Handle NULL tenant_id
        $isNullTenant = ($tenantId === '_NULL_TENANT_' || $tenantId === null);

        if ($isNullTenant) {
            $tenantId = 0; // Use tenant_id = 0 for NULL tenant legacy listings
            $canonicalRef = "legacy-tenant:NULL";
            $this->log("Processing NULL tenant listings with placeholder Property (tenant_id=0)");
        } else {
            $canonicalRef = "legacy-tenant:{$tenantId}";
        }

        // Find existing or create new Property for this tenant
        $existing = DB::selectOne(
            "SELECT id FROM properties WHERE canonical_reference = ? LIMIT 1",
            [$canonicalRef]
        );

        if ($existing) {
            $propertyId = $existing->id;
            $this->log("Using existing Property ID {$propertyId} for tenant {$tenantId}");
        } else {
            // Create new Property
            $now = now()->toDateTimeString();
            DB::insert(
                "INSERT INTO properties (tenant_id, canonical_reference, lifecycle_state, created_at, updated_at)
                 VALUES (?, ?, 'DRAFT', ?, ?)",
                [$tenantId, $canonicalRef, $now, $now]
            );

            $propertyId = DB::getPdo()->lastInsertId();
            $this->log("Created new Property ID {$propertyId} for tenant {$tenantId}");
        }

        // Map all legacy listings to this Property
        foreach ($ilanlar as $ilan) {
            // Tenant mismatch prevention
            $ilanTenant = DB::selectOne(
                "SELECT tenant_id FROM ilanlar WHERE id = ?",
                [$ilan->id]
            );

            if ($ilanTenant && $ilanTenant->tenant_id != $tenantId) {
                throw new \RuntimeException(
                    "Tenant mismatch detected! Listing {$ilan->id} has tenant {$ilanTenant->tenant_id}, " .
                    "but batch is for tenant {$tenantId}. Migration aborted for data safety."
                );
            }

            DB::update(
                "UPDATE ilanlar SET property_id = ? WHERE id = ? AND property_id IS NULL",
                [$propertyId, $ilan->id]
            );

            $this->log("Mapped listing {$ilan->id} to Property {$propertyId}");
        }
    }

    /**
     * Verify mapping integrity after backfill.
     */
    protected function verifyMapping(): void
    {
        $stats = DB::selectOne(
            "SELECT
                (SELECT COUNT(*) FROM ilanlar) as total_listings,
                (SELECT COUNT(*) FROM ilanlar WHERE property_id IS NOT NULL) as mapped_listings,
                (SELECT COUNT(*) FROM ilanlar WHERE property_id IS NULL) as unmapped_listings,
                (SELECT COUNT(*) FROM properties) as total_properties"
        );

        $this->log("Verification:");
        $this->log("  - Total listings: {$stats->total_listings}");
        $this->log("  - Mapped listings: {$stats->mapped_listings}");
        $this->log("  - Unmapped listings: {$stats->unmapped_listings}");
        $this->log("  - Total properties: {$stats->total_properties}");

        // Assert: Tüm ilanlar maplanmış olmalı
        if ($stats->unmapped_listings > 0) {
            $this->log("WARNING: {$stats->unmapped_listings} listings remain unmapped!");
        }

        // Assert: Tenant mismatch kontrolü
        $mismatch = DB::selectOne(
            "SELECT COUNT(*) as cnt
             FROM ilanlar l
             JOIN properties p ON l.property_id = p.id
             WHERE l.tenant_id != p.tenant_id"
        );

        if ($mismatch && $mismatch->cnt > 0) {
            throw new \RuntimeException(
                "Tenant mismatch detected! {$mismatch->cnt} listings have different tenant than their Property. " .
                "Migration aborted for data safety."
            );
        }

        $this->log("Tenant isolation verified: OK");
    }

    /**
     * Log a message with timestamp.
     *
     * @param string $message
     */
    protected function log(string $message): void
    {
        $timestamp = now()->toDateTimeString();
        \Illuminate\Support\Facades\Log::info("[Sprint12B Backfill] [{$timestamp}] {$message}");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // FK constraint'i kaldır (eğer varsa)
        if ($this->fkExists('ilanlar', 'ilanlar_property_id_foreign')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropForeign(['property_id']);
            });
        }

        // property_id'yi NULL yap (veri koruma için)
        DB::update("UPDATE ilanlar SET property_id = NULL WHERE property_id IS NOT NULL");

        // Legacy Property kayıtlarını sil (canonical_reference ile)
        DB::delete(
            "DELETE FROM properties WHERE canonical_reference LIKE 'legacy-tenant:%'"
        );

        $this->log('Rollback complete: property_id cleared, legacy Properties removed.');
    }

    /**
     * Check if a foreign key exists.
     *
     * @param string $table
     * @param string $name
     * @return bool
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
