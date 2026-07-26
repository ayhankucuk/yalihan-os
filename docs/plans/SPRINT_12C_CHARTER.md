# Sprint 12C — Legacy Migration

**Charter ID:** BR-20260726-SPRINT12C
**Status:** ACTIVE
**Start Date:** 2026-07-26
**Sprint Goal:** IlanCrudService'i emekliye ayırıp ListingCrudService'i tek canonical write path haline getirmek.

---

## Sprint 12C Authorization

**Board Question:** YALIHAN, IlanCrudService write operasyonlarını ListingCrudService üzerinden tek bir write path ile çalıştırabiliyor mu?

**Precondition:** Sprint 12B tamamlanmış olmalı ✅

---

## Sprint 12C Context

### Sprint 12B ile Elde Edilen

- Workspace Isolation (`validateWorkspaceOwnership()`)
- TenantContextService workspace context
- Immutable State Transition
- 40 test / 83 assertion

### Sprint 12C Hedefi

Sprint 12B güvenlik katmanlarını koruyarak legacy `IlanCrudService` yazma yolunu kaldırmak.

---

## Phase 1 — Discovery

### Görev

Repository genelinde `IlanCrudService` kullanımlarının tam envanterini çıkarmak.

### Sınıflandırma

| Kategori | Örnek |
|----------|-------|
| Controller | Admin/Api/Owner controller'lar |
| Action | SubmitIlanAction, PublishIlanAction |
| Job | Background processing |
| Command | CLI commands |
| Event | Event listener'lar |
| Test | Feature/Unit test'ler |
| Seeder | Database seeder'lar |

---

## Phase 2 — Feature Flag

### Görev

Kontrollü geçiş için feature flag mekanizması kurmak.

### İmplementasyon

**Config:** `config/feature-flags.php`
```php
'listing_crud_v2_enabled' => env('LISTING_CRUD_V2_ENABLED', false),
'listing_crud_v2_shadow' => env('LISTING_CRUD_V2_SHADOW', false),
```

**Bridge:** `app/Services/Listing/ListingCrudBridge.php`
```
Controller → Bridge → [Shadow: Both] → Compare
                └→ [Normal: Single] → Return
```

### Flow

```
Feature Flag OFF (default):
  Controller → IlanCrudService (legacy)

Feature Flag ON:
  Controller → ListingCrudService (new)

Shadow Mode ON:
  Controller → Both Services
             → Compare results
             → Log differences
             → Return legacy result
```

### Amaç

Eski servis ile yeni servis arasında geçiş yapılabilir olmalı.

---

## Phase 3 — Migration

### Görev

Tüm write operasyonlarını `ListingCrudService` üzerine taşımak.

### Operasyonlar

| Operasyon | ListingCrudService Metod |
|-----------|-------------------------|
| Create | `createFromProperty()` |
| Update | `update()` |
| Archive | `archive()` |
| Submit | `submitForReview()` |
| Publish | `publish()` |
| Unpublish | `unpublish()` |
| Delete | `delete()` → `archive()` |

---

## Phase 4 — Parity Validation

### Görev

Yeni write path'in legacy ile aynı davranışı sergilediğini kanıtlamak.

### Validasyon Noktaları

| # | Doğrulama | Açıklama |
|---|-----------|----------|
| 1 | API Response | Aynı response formatı |
| 2 | Business Rule | Aynı iş kuralları |
| 3 | Authorization | Aynı yetkilendirme |
| 4 | Tenant Isolation | Korundu |
| 5 | Event Dispatch | Aynı event'ler tetikleniyor |
| 6 | Audit Log | Aynı audit kayıtları |

---

## Phase 5 — Legacy Cleanup

### Görev

`IlanCrudService` deprecated etmek veya kaldırmak.

### Ön Koşullar

- [ ] Aktif referans kalmadı (0 usage)
- [ ] Feature flag kapatıldı
- [ ] Tüm testler geçti

---

## Sprint 12C Definition of Done

| Gate | Beklenen |
|------|----------|
| Discovery | Repository envanteri tamamlandı |
| Migration | Tüm write path'ler ListingCrudService kullanıyor |
| Feature Flag | Kontrollü geçiş mümkün |
| Tenant Isolation | Korundu |
| Replay | Bozulmadı |
| Event/Audit | Aynı davranış |
| Legacy Usage | 0 aktif kullanım |
| CI | PASS |
| bekci | PASS |
| sab | PASS |

---

## Mimari Sonuç

Sprint 12C tamamlandığında:

```
API
  │
  ▼
Actions
  │
  ▼
ListingCrudService
  │
  ▼
TenantContextService
  │
  ▼
State Machine
  │
  ▼
Immutable Audit Log
```

**Tek canonical write path sağlanır:**
- İş kuralları tek noktada uygulanır
- Tenant isolation merkezi olarak korunur
- Event ve audit davranışları tutarlı kalır
- Legacy kod güvenle kaldırılır

---

## Çıktılar

| Çıktı | Hedef |
|--------|-------|
| Discovery Report | Phase 1 |
| Feature Flag Implementation | Phase 2 |
| Migration Complete | Phase 3 |
| Parity Test Suite | Phase 4 |
| Legacy Cleanup | Phase 5 |
| Certification Package | Sprint end |

---

**Board Decision:** Sprint 12C Legacy Migration başlatılmıştır.
