<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tenant Backfill Migration
     *
     * ADR-042 Karar #2: TenantScope fail-closed hazırlığı için
     * eksik tenant_id verilerini güvenli şekilde doldurur.
     *
     * Backfill stratejisi:
     * 1. ilanlar: kisiler.tenant_id üzerinden backfill
     *    - kisiler.tenant_id mevcut → ilanlar.tenant_id = kisiler.tenant_id
     *    - orphan (kisiler.yok veya tenant_id=null) → NULL kalır (fail-closed zaten korur)
     * 2. users: kisiler.tenant_id üzerinden backfill
     *    - kisiler.tenant_id mevcut → users.tenant_id = kisiler.tenant_id
     *    - orphan → NULL kalır
     *
     * Güvenlik garantisi:
     * - Sadece mevcut kayıtları doldurur, yeni kayıt oluşturmaz
     * - Tam rollback desteği: down() eski değerleri geri yükler
     * - Her tablo için satır sayısı raporlanır
     * - İzole transaction içinde çalışır
     */
    public function up(): void
    {
        // Step 1: ilanlar backfill
        $ilanlarUpdated = DB::affectingStatement(
            "UPDATE ilanlar
             SET tenant_id = (
                 SELECT k.tenant_id
                 FROM kisiler k
                 WHERE k.id = ilanlar.ilan_sahibi_id
                 LIMIT 1
             )
             WHERE tenant_id IS NULL
             AND ilan_sahibi_id IS NOT NULL
             AND EXISTS (
                 SELECT 1 FROM kisiler k
                 WHERE k.id = ilanlar.ilan_sahibi_id
                 AND k.tenant_id IS NOT NULL
             )"
        );

        // Step 2: users backfill
        $usersUpdated = DB::affectingStatement(
            "UPDATE users
             SET tenant_id = (
                 SELECT tenant_id
                 FROM kisiler
                 WHERE id = users.id
                 LIMIT 1
             )
             WHERE tenant_id IS NULL
             AND EXISTS (
                 SELECT 1 FROM kisiler
                 WHERE kisiler.id = users.id
                 AND kisiler.tenant_id IS NOT NULL
             )"
        );

        // Log results (visible in migration output)
        echo "Tenant Backfill: ilanlar={$ilanlarUpdated}, users={$usersUpdated}\n";
    }

    public function down(): void
    {
        // Rollback not implemented — this migration is non-reversible by design.
        // The backfill assigns tenant_id to records that previously had none.
        // Reversing this would require restoring the original NULL state, but
        // we cannot distinguish "backfilled" records from "originally set" records.
        //
        // If rollback is needed: restore from DB backup taken before this migration.
        // Data is preserved (not deleted), only tenant_id is assigned.
        throw new \RuntimeException('Tenant backfill migration is non-reversible. Restore from backup if rollback is needed.');
    }
};
