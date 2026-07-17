# Sprint 12B Migration Proposal

**Oturum:** 114
**Tarih:** 2026-07-17
**SAAB Board:** BR-20260717-Sprint12B
**Durum:** MIGRATION PROPOSAL — SAAB Onayı Bekleniyor
**Model:** Option A — properties tablosu + FK

---

## 1. Kesin Schema

### 1.1 properties Tablosu

```php
Schema::create('properties', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('tenant_id');
    $table->string('canonical_reference', 64)->nullable()->unique();
    $table->string('lifecycle_state')->default('DRAFT');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index(['tenant_id', 'lifecycle_state']);
    $table->index(['tenant_id', 'canonical_reference']);
});
```

### 1.2 FK Constraint (ilanlar.property_id)

```php
Schema::table('ilanlar', function (Blueprint $table) {
    $table->foreign('property_id')
        ->references('id')
        ->on('properties')
        ->onDelete('cascade');
});
```

### 1.3 İlk Migration'da EKLENMEYECEK Alanlar (SAAB Kararı)

| Alan Kategorisi | Neden | Gelecek Sprint |
|-----------------|-------|----------------|
| Yayın platformu alanları | Listing aggregate | Sprint 13+ |
| Airbnb alanları | Listing aggregate | Sprint 13+ |
| Fiyatlandırma | Listing aggregate | Sprint 13+ |
| AI çıktıları | Metadata store | Sprint 13+ |
| Medya detayları | Asset management | Sprint 13+ |
| CRM detayları | CRM domain | Sprint 13+ |
| Workspace runtime | PropertyWorkspace | Sprint 12B+ |
| Location Intelligence | ILI domain | Sprint 13+ |

---

## 2. Legacy Backfill Algoritması

### 2.1 Backfill Stratejisi

```
Her legacy ilan (property_id IS NULL)
    ↓
tenant_id tespit et
    ↓
Canonical Property oluştur (veya mevcut bul)
    ↓
ilanlar.property_id ata
    ↓
Logla ve devam et
```

### 2.2 Backfill Kuralları

1. **Tenant-Safe:** Her Property, tek bir tenant'a aittir
2. **Deterministic:** Aynı ilan aynı Property'ye maplanır (idempotency_key kullanılabilir)
3. **Idempotent:** Tekrar çalıştırılabilir — mevcut mapping'i değiştirmez
4. **Loggable:** Her mapping action loglanır
5. **Reversible:** down() migration ile geri alınabilir

### 2.3 Backfill Pseudocode

```php
// Step 1: Create Property for each unique tenant + canonical_reference
$legacyIlans = Ilan::whereNull('property_id')
    ->whereNotNull('tenant_id')
    ->get()
    ->groupBy('tenant_id');

foreach ($legacyIlans as $tenantId => $ilanlar) {
    // Find or create canonical Property for this tenant
    $property = Property::firstOrCreate(
        [
            'tenant_id' => $tenantId,
            'canonical_reference' => "legacy-tenant-{$tenantId}",
        ],
        [
            'lifecycle_state' => 'DRAFT',
        ]
    );

    // Assign to all legacy ilans of this tenant
    Ilan::whereNull('property_id')
        ->where('tenant_id', $tenantId)
        ->update(['property_id' => $property->id]);
}
```

---

## 3. Tenant Isolation Kanıtı

### 3.1 Preflight Ölçümleri

| Metric | Değer |
|--------|-------|
| Toplam ilan | 2 |
| property_id IS NULL | 2 (%100) |
| property_id IS NOT NULL | 0 (%0) |
| Unique tenant_id | 2 (tenant 1, tenant 2) |
| property_workspaces | 0 |

### 3.2 Tenant Dağılımı

| tenant_id | ilan sayısı | Mevcut Property |
|-----------|-------------|-----------------|
| 1 (Primary) | 1 | Yok |
| 2 (Secondary) | 1 | Yok |

### 3.3 Tenant Safety Kontrolleri

```sql
-- Tenant isolation kontrolü
SELECT COUNT(*)
FROM ilanlar l
JOIN properties p ON l.property_id = p.id
WHERE l.tenant_id != p.tenant_id;
-- Beklenen sonuç: 0

-- Tenant bazlı mapping doğrulama
SELECT
    l.tenant_id as ilan_tenant,
    p.tenant_id as property_tenant,
    COUNT(*) as cnt
FROM ilanlar l
JOIN properties p ON l.property_id = p.id
GROUP BY l.tenant_id, p.tenant_id
HAVING l.tenant_id != p.tenant_id;
-- Beklenen sonuç: Boş
```

---

## 4. Idempotency Stratejisi

### 4.1 Migration Idempotency

```php
public function up(): void
{
    // 1. Tablo zaten var mı?
    if (Schema::hasTable('properties')) {
        return;
    }

    // 2. FK zaten var mı?
    if ($this->fkExists('ilanlar', 'ilanlar_property_id_foreign')) {
        return;
    }

    // ... create table and FK
}
```

### 4.2 Backfill Idempotency

```php
// Sadece NULL olanları güncelle — mevcut mapping korunur
Ilan::whereNull('property_id')
    ->where('tenant_id', $tenantId)
    ->update(['property_id' => $property->id]);
```

---

## 5. Rollback Planı

### 5.1 Migration down()

```php
public function down(): void
{
    // 1. FK kaldır (eğer varsa)
    if ($this->fkExists('ilanlar', 'ilanlar_property_id_foreign')) {
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
        });
    }

    // 2. property_id sıfırla (opsiyonel — veri koruma için)
    // DB::statement('UPDATE ilanlar SET property_id = NULL WHERE property_id IS NOT NULL');

    // 3. properties tablosu kaldır
    Schema::dropIfExists('properties');
}
```

### 5.2 Rollback Tetikleyicileri

| Risk | Tetikleyici | Aksiyon |
|------|------------|---------|
| Constraint violation | FK eklenemedi | down() çalıştır |
| Data corruption | Tenant mismatch | Manuel müdahale |
| Lock timeout | Table lock > 30s | Batch retry |

---

## 6. Lock / Downtime Riski

### 6.1 Risk Analizi

| Operasyon | Lock Tipi | Tahmini Süre | Risk |
|-----------|-----------|-------------|------|
| CREATE properties | None (DDL) | < 1s | Düşük |
| UPDATE ilanlar | Row lock | < 1s (2 rows) | Düşük |
| ADD FK | Table lock (MySQL) | < 1s | Düşük |

### 6.2 Mitigation

- MySQL: `LOCK = NONE` veya minimal lock süresi
- Batch update: Chunked processing (> 1000 kayıt için)
- Online DDL: MySQL 5.6+ otomatik

### 6.3 Geçici Tablo Stratejisi (Gerekirse)

```php
// Büyük tablolar için: Geçici tablo ile bulk insert
CREATE TEMPORARY TABLE temp_property_mapping AS
SELECT tenant_id, MIN(id) as first_ilan_id
FROM ilanlar
WHERE property_id IS NULL
GROUP BY tenant_id;
```

---

## 7. Orphan Detection Sorguları

### 7.1 Pre-Migration Kontrolleri

```sql
-- Orphan ilanlar (property_id var ama properties'ta yok)
SELECT COUNT(*)
FROM ilanlar l
LEFT JOIN properties p ON l.property_id = p.id
WHERE l.property_id IS NOT NULL AND p.id IS NULL;
-- Hedef: 0

-- Tenant mismatch
SELECT COUNT(*)
FROM ilanlar l
JOIN properties p ON l.property_id = p.id
WHERE l.tenant_id != p.tenant_id;
-- Hedef: 0

-- Unmapped ilanlar
SELECT COUNT(*)
FROM ilanlar
WHERE property_id IS NULL;
-- Hedef: Tüm legacy ilanlar maplanmalı
```

### 7.2 Post-Migration Hedefleri

| Metric | Önce | Sonra (Hedef) |
|--------|-------|---------------|
| orphan_listing_count | N/A | 0 |
| tenant_mismatch_count | N/A | 0 |
| unmapped_listing_count | 2 | 0 |
| duplicate_property_mapping | N/A | 0 (her tenant = 1 property) |

---

## 8. Test Planı

### 8.1 Unit Testler

| Test | Senaryo | Beklenen Sonuç |
|------|---------|---------------|
| PropertyCrudServiceTest | Property oluştur | PASS |
| PropertyCrudServiceTest | Tenant isolation | PASS |
| IlanCrudServiceTest | property_id atama | PASS |
| PropertyStateMachineTest | State transitions | PASS |

### 8.2 Integration Testler

| Test | Senaryo | Beklenen Sonuç |
|------|---------|---------------|
| PropertyWorkspacePublishServiceTest | Workspace publish | PASS |
| SyncPropertyCalendarFeedTest | Property → Listing sync | PASS |
| DomainGuardTest | "Listing must be created from Property" | PASS |

### 8.3 Regression Testler

```bash
php artisan test --filter=Property
php artisan test --filter=SyncPropertyCalendarFeed
php artisan test --filter=PropertyWorkspace
```

---

## 9. Go / No-Go Kriterleri

### 9.1 Pre-Migration Go Kriterleri

| # | Kriter | Hedef | Öncelik |
|---|--------|-------|---------|
| 1 | Tenant isolation | Tüm ilanların tenant_id'si var | MUST |
| 2 | Legacy data assessment | Veri riski değerlendirmesi tamam | MUST |
| 3 | FK migration idempotent | Tekrar çalıştırılabilir | MUST |
| 4 | Rollback plan reviewed | Teknik ekip onayladı | MUST |

### 9.2 Post-Migration Go Kriterleri

| # | Kriter | Hedef | Öncelik |
|---|--------|-------|---------|
| 1 | orphan_listing_count | 0 | MUST |
| 2 | tenant_mismatch_count | 0 | MUST |
| 3 | unmapped_listing_count | 0 | MUST |
| 4 | All tests green | 100% pass | MUST |
| 5 | FK constraint active | ON DELETE CASCADE | MUST |

### 9.3 No-Go Kriterleri (Durdurma)

| # | Kriter | Aksiyon |
|---|--------|--------|
| 1 | Tenant mismatch > 0 | Rollback |
| 2 | FK constraint failed | Rollback |
| 3 | Test pass rate < 95% | Investigate |
| 4 | Lock timeout > 60s | Retry with smaller batch |

---

## 10. property_workspaces.ilan_id Kararı (Ayrı Migration)

### 10.1 Mevcut Durum

```
property_workspaces
├── ilan_id (nullable, FK YOK)
└── tenant_id
```

### 10.2 Önerilen Gelecek State

```
Property
├── Listings (ilanlar)
└── Workspaces (property_workspaces)
    ├── property_id → properties.id (nullable)
    └── ilan_id → ilanlar.id (nullable)
```

### 10.3 Karar

> property_workspaces.ilan_id FK'si Sprint 12B kapsamı DIŞI.
> Ayrı bir migration proposal ile değerlendirilecek.

---

## 11. Implementation Roadmap

### 11.1 Adım Adım

```
┌─────────────────────────────────────────────────────────────┐
│  ADIM 1: Production schema preflight                         │
│    • Ölçümleri topla                                        │
│    • Risk değerlendirmesi yap                                │
│    • SAAB final approval al                                  │
├─────────────────────────────────────────────────────────────┤
│  ADIM 2: properties migration oluştur                       │
│    • 2026_07_XX_000001_create_properties_table.php          │
│    • Minimal schema (SAAB onaylı)                            │
│    • Idempotent up/down                                     │
├─────────────────────────────────────────────────────────────┤
│  ADIM 3: Legacy backfill migration                          │
│    • 2026_07_XX_000002_backfill_property_id.php             │
│    • Tenant-safe mapping                                    │
│    • Log ve audit trail                                    │
├─────────────────────────────────────────────────────────────┤
│  ADIM 4: FK constraint ekle                                 │
│    • 2026_07_XX_000003_add_property_id_fk.php               │
│    • ON DELETE CASCADE                                      │
├─────────────────────────────────────────────────────────────┤
│  ADIM 5: Test çalıştır                                     │
│    • php artisan test --filter=Property                     │
│    • Regression validation                                  │
├─────────────────────────────────────────────────────────────┤
│  ADIM 6: Evidence + certification                            │
│    • Metrics kanıtla                                        │
│    • SAAB'a sun                                            │
└─────────────────────────────────────────────────────────────┘
```

---

## 12. Özet

### 12.1 Migration Scope

| Komponent | Durum |
|-----------|-------|
| properties tablosu | Oluşturulacak |
| ilanlar.property_id FK | Eklenecek |
| property_workspaces.ilan_id | Ayrı migration |
| Domain guard (Ilan.php:1878) | Korunacak |

### 12.2 Risk Summary

| Risk | Seviye | Mitigation |
|------|--------|------------|
| Data loss | Düşük | Backfill sadece NULL kayıtları etkiler |
| Tenant mismatch | Orta | Pre/Post kontroller |
| Lock timeout | Düşük | Chunked processing |
| Test regression | Orta | Incremental testing |

### 12.3 SAAB Onayı Bekleniyor

> Bu proposal, SAAB final onayı bekliyor.
> Onay sonrası implementation başlayabilir.

---

**Proposal Durumu:** ✅ COMPLETE
**SAAB Onayı:** ⏳ BEKLENİYOR
**Sonraki Faz:** IMPLEMENTATION (onaydan sonra)
