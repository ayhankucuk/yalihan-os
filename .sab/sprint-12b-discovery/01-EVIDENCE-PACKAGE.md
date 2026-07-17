# Sprint 12B Discovery Evidence Package

**Oturum:** 114
**Tarih:** 2026-07-17
**Agent:** Kilo (Claude Opus 4.8)
**Durum:** DISCOVERY COMPLETE — SAAB Review'a sunulacak

---

## 1. FK Dependency Map

```
┌─────────────────────────────────────────────────────────────┐
│                    FK DEPENDENCY MAP                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ilanlar                                                    │
│  ├── property_id (nullable) ──X──> properties (EKSİK!)     │
│  │                                  Batch: YOK              │
│  │                                  Migration: YOK          │
│  │                                  Table: YOK               │
│  ├── workspace_id (nullable) ──X──> property_workspaces     │
│  │                                       Batch: 46          │
│  │                                       FK: YOK              │
│  └── 11 other FKs (kategori, users, kisiler, vs.)          │
│                                                              │
│  property_workspaces                                         │
│  └── tenant_id ──> tenants                                  │
│      ilan_id ──X──> ilanlar (FK YOK)                       │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

**FK Durumu:**
| Kaynak Tablo | Kolon | Hedef Tablo | FK Var mı? | Migration Var mı? | Tablo Var mı? |
|--------------|-------|-------------|------------|-------------------|---------------|
| ilanlar | property_id | properties | ❌ HAYIR | ❌ HAYIR | ❌ HAYIR |
| ilanlar | workspace_id | property_workspaces | ❌ HAYIR | Evet (Batch 46) | ✅ Evet |
| property_workspaces | ilan_id | ilanlar | ❌ HAYIR | Hayır | ✅ Evet |

---

## 2. Migration Order Report

| Dosya | Batch | Tarih | Durum |
|-------|-------|-------|-------|
| 2026_07_06_000001_create_property_workspaces_table | 46 | 2026-07-06 | ✅ Ran |
| 2026_07_15_000001_add_workspace_and_idempotency_to_properties | 46 | 2026-07-15 | ✅ Ran |
| 2026_07_15_000002_add_property_and_workspace_to_ilanlar | 46 | 2026-07-15 | ✅ Ran |
| 2026_07_16_000001_add_property_foreign_key_cascade | 46 | 2026-07-16 | ✅ Ran (FK EKLENEMEDİ) |

**Kritik Sorun:** FK migration (Batch 46) çalıştı AMA `fkExists()` kontrolü nedeniyle FK eklemedi çünkü `properties` tablosu yok.

---

## 3. Orphan / Invalid Data Risk Report

### Mevcut Veri Durumu
| Tablo | Toplam Kayıt | property_id Dolu | property_id Boş | Orphan |
|-------|-------------|-----------------|-----------------|--------|
| ilanlar | 2 | 0 | 2 | N/A (properties yok) |

### Risk Analizi
| Risk | Seviye | Açıklama |
|------|--------|----------|
| FK constraint çalışmıyor | 🔴 KRİTİK | Cascade delete tetiklenemez |
| Domain invariant çalışıyor ama... | 🟡 YÜKSEK | 3 test "Listing must be created from a Property" hatası veriyor |
| Veri bütünlüğü | 🟡 YÜKSEK | property_id silinirse FK yok, phantom reference oluşur |

### Test Failures (Publish Regression Evidence)
```
FAILED Tests\Feature\SyncPropertyCalendarFeedTest
DomainException: Listing must be created from a Property.
at app/Models/Ilan.php:1878

Tests: 3 failed, 6 incomplete, 149 passed (360 assertions)
```

**Failures Nedeni:** Sprint 11 M2 domain invariant'ı aktif ama backing infrastructure eksik.

---

## 4. Publish Regression Evidence

### State Machine
```
PropertyWorkspaceAggregate states:
  workspace_created → draft → ready_for_review → published → archived
```

### Publish Workflow
1. `PropertyWorkspaceService::transitionToPublished($workspaceId)`
2. Aggregate state transition → `STATE_PUBLISHED`
3. Model save → `property_workspaces.state = 'published'`

### Regresyon
| Senaryo | Expected | Actual | Neden |
|---------|----------|--------|-------|
| Test: SyncPropertyCalendarFeedTest | Pass | FAIL | Ilan::creating guard "Listing must be created from a Property" |
| FK cascade delete | Property silinince listing silinir | Çalışmaz | properties tablosu yok |
| Workspace → Ilan ilişkisi | FK ile garantili | Zayıf | property_workspaces.ilan_id FK yok |

---

## 5. Çözüm Seçenekleri

### Option A: Create Properties Table + FK (Önerilen)
**Approach:** Tam Property aggregate tablosu oluştur, FK ekle

| Adım | Action | Risk |
|------|--------|------|
| 1 | Create `properties` migration | Orta — veri kaybı yok |
| 2 | Backfill: Tüm ilanlar için Property oluştur | Yüksek — veri transformasyonu |
| 3 | FK constraint ekle | Düşük — once backfill tamamlanmalı |
| 4 | Test regression gider | Düşük |

**Avantaj:** Tam domain model
**Dezavantaj:** Backfill karmaşık, Production migration riski

### Option B: Sofıt FK — Domain İnvariant'ı Kaldır
**Approach:** Ilan::creating guard'ı kaldır, publish workflow'u test edilebilir kıl

| Adım | Action | Risk |
|------|--------|------|
| 1 | Guard'ı conditional yap (test ortamında skip) | Düşük |
| 2 | Publish workflow test et | Düşük |
| 3 | properties tablosu + FK sonra ekle | Orta |

**Avantaj:** Hızlı, regression gider
**Dezavantaj:** Domain invariant zayıflatılır

### Option C: Soft Reference — No FK, Domain Validation Only
**Approach:** FK yerine service-layer validation

| Adım | Action | Risk |
|------|--------|------|
| 1 | property_id nullable bırak | N/A |
| 2 | PropertyWorkspaceService::validatePropertyExists() ekle | Düşük |
| 3 | Unit test ile validation kapsa | Düşük |

**Avantaj:** Minimal değişiklik
**Dezavantaj:** Veri bütünlüğü DB seviyesinde garantilenmez

---

## 6. Geri Dönüş Planı

| Risk | Tetikleyici | Aksiyon |
|------|------------|---------|
| Backfill data loss | Insert/Update hatası | Rollback migration |
| FK ekleme başarısız | Constraint violation | `down()` ile revert |
| Test regression | Yeni invariant | `skipPropertyIdGuard` toggle |

---

## 7. Öneri

**SAAB Review'a Sunulacak Karar:**
> Sprint 12B için Option A (Create Properties Table + FK) önerilir.
> Ancak ön koşul olarak:
> 1. Production'da kaç ilan var? (veri riski değerlendirmesi)
> 2. Property aggregate'ın tam schema'sı nedir? (Property.php modelden çıkarılacak)
> 3. Backfill stratejisi: Tüm ilanlar için Property oluşturulacak mı yoksa sadece yeni oluşturulanlar için mi?

---

## Ek Kanıtlar

- `app/Models/Property.php` — Property model (118 satır, Value Objects, domain invariants)
- `app/Domain/PropertyWorkspace/PropertyWorkspaceAggregate.php` — State machine
- `app/Models/Ilan.php:1878` — Domain guard (source of test failures)
- `tests/Feature/SyncPropertyCalendarFeedTest.php` — 3 failing tests

---

**Discovery Durumu:** ✅ COMPLETE
**Sonraki Faz:** RISK ANALYSIS → SAAB APPROVAL
