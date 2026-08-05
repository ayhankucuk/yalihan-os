# RESERVATION_CORE — Phase 1 Reference Inventory

**Tarih:** 2026-08-05
**Faz:** 1 — Canonicalization
**Durum:** In Progress

---

## 1. Model Karşılaştırması

| Alan | IlanReservation | PropertyReservation | Semantik |
|------|----------------|---------------------|----------|
| Tablo | `property_reservations` | `property_reservations` | Aynı |
| `tenant_id` | ❌ Yok | ✅ Var | Tenant isolation için gerekli |
| `islem_tutari` | ❌ Yok | ✅ Var | Finansal tutar |
| `total_amount` | ✅ Var | ❌ Yok | Migration: `total_amount` → `islem_tutari` |
| `currency` | ✅ Var | ✅ Var | Aynı |
| `reservation_state` | string | enum cast | PropertyReservation canonical |
| `ilan_id` | ❌ Yok (var ama kullanılmıyor) | ✅ Var | Property FK |

### Semantik Dönüşüm
```
total_amount (float) → islem_tutari (decimal:2)
```

---

## 2. Referans Envanteri

### 2.1 Services (5 dosya)

| Dosya | Kullanım | Deprecation Action |
|-------|----------|-------------------|
| `IlanReservationService.php` | `IlanReservation::create()`, `::find()`, `::forIlan()` | Faz 2'de `PropertyReservationService`'e yönlendir |
| `AdminActivityEventService.php` | `IlanReservation` type hint | Observer/event'te `PropertyReservation`'a geçiş |
| `AdminNotificationService.php` | `IlanReservation` type hint | Notification event'te `PropertyReservation`'a geçiş |
| `TelegramAIBotService.php` | `IlanReservation::find()`, `::where()` | Faz 2 |
| `IlanCalendarIcsService.php` | `IlanReservation::forIlan()` | Faz 2 |

### 2.2 Controllers (2 dosya)

| Dosya | Kullanım | Deprecation Action |
|-------|----------|-------------------|
| `IlanCalendarController.php` | `IlanReservation` type hint, param binding | `PropertyReservation`'a güncelle |
| `IlanPublishGateController.php` | `IlanReservation` type hint | Faz 2 |

### 2.3 Models (1 dosya)

| Dosya | Kullanım | Deprecation Action |
|-------|----------|-------------------|
| `IlanReservation.php` | `$table = 'property_reservations'` | `@deprecated` annotation ekle |

### 2.4 Tests (3 dosya)

| Dosya | Test Sayısı | Action |
|-------|-------------|--------|
| `IlanReservationIslemStatusuTest.php` | 3 | Faz 2'de `PropertyReservationTest`'e taşı |
| `IlanCalendarFeedTest.php` | 2 | Faz 2'de güncelle |
| `AvailabilityServiceTest.php` | 2 (skipped) | Zaten skipped, bırak |

---

## 3. Migration Planı

### 3.1 Phase 1 (Bu Sprint)
```sql
-- total_amount → islem_tutari migration
ALTER TABLE property_reservations
    ADD COLUMN islem_tutari DECIMAL(12,2) AFTER currency;

UPDATE property_reservations
    SET islem_tutari = total_amount;
```

### 3.2 Phase 2 (Gelecek Sprint)
```sql
-- Faz 2: IlanReservation tam deprecation
-- total_amount kolonu kaldırılabilir (veri koruma için önce arşiv)
```

---

## 4. Direct ORM Write Tespiti

### 4.1 Tespit Edilen (Faz 1 kapsamı dışında - Faz 2)
```
IlanReservationService.php:112 → IlanReservation::create() — Direct ORM
IlanReservationService.php:304 → IlanReservation::create() — Direct ORM
TelegramAIBotService.php → IlanReservation::find() — Read only, Faz 2'ye kadar korunabilir
```

### 4.2 Faz 2 Action
Tüm `IlanReservation::create()` çağrıları → `ReservationService::createReservation()` üzerinden yeniden yönlendir.

---

## 5. ReservationState Lifecycle Matrix

```
┌──────────┬──────────┬────────────┬────────────┐
│ FROM     │ TO       │ Valid?     │ Trigger    │
├──────────┼──────────┼────────────┼────────────┤
│ pending  │ confirmed│ ✅ YES     │ Manual/API  │
│ pending  │ cancelled│ ✅ YES     │ User cancel │
│ confirmed│ cancelled│ ✅ YES     │ User cancel │
│ confirmed│ no_show  │ ✅ YES     │ No-show     │
│ confirmed│ completed│ ✅ YES     │ Checkout    │
│ pending  │ no_show  │ ❌ NO     │ Invalid     │
│ pending  │ completed│ ❌ NO     │ Invalid     │
│ cancelled│ *        │ ❌ NO     │ Terminal    │
│ no_show  │ *        │ ❌ NO     │ Terminal    │
│ completed │ *        │ ❌ NO     │ Terminal    │
└──────────┴──────────┴────────────┴────────────┘
```

---

## 6. Tenant/Property Invariants

| Invariant | Açıklama | Test |
|----------|----------|------|
| I1 | Her rezervasyon `tenant_id` taşımalı | `assigns_tenant_id` |
| I2 | Cross-tenant rezervasyon oluşturulamaz | `rejects_cross_tenant_property` |
| I3 | Her rezervasyon `property_id` taşımalı | Implied by I2 |
| I4 | Overlapping dates → conflict exception | (Faz 2) |

---

## 7. Faz 1 Zorunlu Testler

| Test Adı | Kapsanan Invariant | Durum |
|----------|-------------------|--------|
| `creates_pending_property_reservation` | I1 | ⏳ |
| `assigns_tenant_id` | I1 | ⏳ |
| `rejects_cross_tenant_property` | I2 | ⏳ |
| `confirms_pending_reservation` | State transition | ⏳ |
| `cancels_pending_reservation` | State transition | ⏳ |
| `cancels_confirmed_reservation` | State transition | ⏳ |
| `marks_confirmed_reservation_as_no_show` | State transition | ⏳ |
| `rejects_invalid_transition` | State transition | ⏳ |
| `uses_property_reservation_as_canonical_model` | Model canonical | ⏳ |
| `does_not_create_through_ilan_reservation` | ORM isolation | ⏳ |
| `reservation_service_is_the_only_write_path` | Write authority | ⏳ |

---

*Bu doküman Faz 1 canonicalization için referans envanteri olarak kullanılacak.*
