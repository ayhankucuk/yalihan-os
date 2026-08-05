# Reservation Core — Discovery Report

**Tarih:** 2026-08-05  
**Hazırlayan:** WenOX Discovery  
**SAAB Onayı:** ⏳ Bekleniyor  
**Durum:** Discovery Only — Üretim kodu yok

---

## 1. Referans Envanteri

### 1a. PropertyReservation Referansları

| Dosya | Tip | Kullanım |
|-------|-----|---------|
| `app/Models/PropertyReservation.php` | Model | Canonical aggregate |
| `app/Models/PropertyAvailability.php` | Model | `reservation_id` FK |
| `app/Models/FinancialTransaction.php` | Model | `reservation_id` FK |
| `app/Models/Ilan.php` | Model | `hasMany` (2 yerde — duplicate) |
| `app/Traits/Ilan/IlanRelationships.php` | Trait | `hasMany` (2 yerde — duplicate) |
| `app/Services/ReservationService.php` | Service | Yazma + iptal (SSOT) |
| `app/Services/FinancialLedgerService.php` | Service | **⚠️ Doğrudan ORM write** — 4 adet `::where()->update()` |
| `app/Services/RentalKpiService.php` | Service | Read-only KPI sorguları |
| `app/Services/Property/CanonicalAvailabilityService.php` | Service | Read + conflict detection |
| `app/Actions/Admin/Reservation/UpdateReservationAction.php` | Action | **⚠️ Doğrudan ORM write** — `$reservation->update()` |
| `app/Actions/Admin/Reservation/UpdateReservationStateAction.php` | Action | **⚠️ Doğrudan ORM write** — state değişimi |
| `app/Http/Controllers/Admin/PropertyEventApiController.php` | Controller | Read + route model binding |
| `app/Policies/PropertyReservationPolicy.php` | Policy | view/update/delete yetki |
| `tests/Feature/ReservationServiceTest.php` | Test | ✅ |
| `tests/Feature/ReservationConcurrencyTest.php` | Test | ✅ |
| `tests/Feature/Rental/EnterpriseMoneyTest.php` | Test | **⚠️ Doğrudan `::create()`** |
| `tests/Feature/Rental/GateCKpiTest.php` | Test | **⚠️ Doğrudan `::create()`** (5 adet) |
| `tests/Feature/Property/PropertyAvailabilityTest.php` | Test | **⚠️ Doğrudan `::create()`** |

### 1b. IlanReservation Referansları

| Dosya | Tip | Durum |
|-------|-----|-------|
| `app/Models/IlanReservation.php` | Model | ⚠️ `property_reservations` tablosunu gösteriyor — `@deprecated` işareti YOK |
| `app/Services/Calendar/IlanReservationService.php` | Service | ❌ DEPRECATED (2026-01-29) — header'da belirtilmiş ancak hâlâ kullanılıyor |
| `app/Services/Calendar/AvailabilityService.php` | Service | ❌ DEPRECATED (2026-01-29) — stub döndürüyor |
| `app/Services/Calendar/IlanCalendarIcsService.php` | Service | ⚠️ `IlanReservation` model'ini doğrudan kullanıyor |
| `app/Http/Controllers/Admin/IlanCalendarController.php` | Controller | ⚠️ `IlanReservationService` inject ediyor |
| `app/Http/Controllers/Admin/IlanPublishGateController.php` | Controller | ⚠️ `IlanReservation` import ediyor |
| `app/Services/AdminActivityEventService.php` | Service | ⚠️ `IlanReservation` parametre tipi |
| `app/Services/AdminNotificationService.php` | Service | ⚠️ `IlanReservation` 4 metod |
| `app/Services/Integrations/TelegramAIBotService.php` | Service | ⚠️ `IlanReservationService` + `IlanReservation` — 15+ kullanım |
| `tests/Feature/Calendar/IlanCalendarFeedTest.php` | Test | ⚠️ Doğrudan `IlanReservation::create()` |
| `tests/Feature/Calendar/IlanReservationIslemStatusuTest.php` | Test | ⚠️ `IlanReservationService` testi |
| `tests/Unit/Services/Calendar/AvailabilityServiceTest.php` | Test | `Deprecated\IlanReservation` — skip edilmiş |
| `tests/Feature/GuardCoverageRegressionTest.php` | Test | `IlanReservationService` guard listesinde |

---

## 2. Split-Brain Matrisi

| Alan | IlanReservation | PropertyReservation | Karar |
|------|-----------------|---------------------|-------|
| Tablo | `property_reservations` | `property_reservations` | **Aynı tablo** — split-brain kesin |
| `tenant_id` | ❌ Yok | ✅ Var | PropertyReservation kazanır |
| `ReservationState` enum cast | ❌ Yok (string) | ✅ Var | PropertyReservation kazanır |
| `total_amount` | ✅ Var (`float`) | ❌ Yok | Semantik fark — aşağıya bak |
| `islem_tutari` | ❌ Yok | ✅ Var (`decimal:2`) | Context7 canonical adı |
| `ilan_id` FK | ✅ Var (ama `scopeForIlan` `property_id` kullanıyor) | ✅ Var | FK tutarsızlığı |
| `property_id` FK | ✅ Var (tablo FK'si) | ✅ Var | ✅ Uyumlu |
| `finansal_durum` | ✅ Var | ✅ Var | ✅ Uyumlu |
| `depozito_tutari` | ✅ Var | ✅ Var | ✅ Uyumlu |
| `locked_nightly_rate` | ✅ Var | ✅ Var | ✅ Uyumlu |
| `booking_currency` | ✅ Var | ✅ Var | ✅ Uyumlu |
| `ulke_id` | ✅ Var | ✅ Var | ✅ Uyumlu |
| `GuardsAgentWrites` | ❌ Yok | ❌ Model'de yok (Service'de var) | Service'de korunuyor |
| Observer | ❌ Yok | ❌ Yok | İkisinde de yok |
| Event | ❌ Yok | ❌ Yok | İkisinde de yok |
| `@deprecated` işareti | ❌ Yok (eksik!) | N/A | Risk — bakınız §5 |

---

## 3. Canonical Model Önerisi

**Canonical: `PropertyReservation`**

Gerekçe:
- `tenant_id` multi-tenancy için zorunlu — sadece `PropertyReservation`'da var
- `ReservationState` enum cast — tip güvenliği
- `FinancialLedgerService` + `ReservationService` + `CanonicalAvailabilityService` zaten `PropertyReservation` kullanıyor
- `PropertyAvailability.reservation_id` FK `PropertyReservation`'a bağlı

`IlanReservation` deprecate edilmeli:
- Model class'ına `@deprecated` PHPDoc eklenmeli
- Tüm caller'lar `PropertyReservation`'a migrate edilmeli
- Telegram, Calendar, Notification servisler migration listesine giriyor

---

## 4. State Transition Matrisi

### Mevcut `ReservationState` Enum (4 state)

```
PENDING ──confirm──→ CONFIRMED
   │                     │
   └──cancel──→ CANCELLED │──cancel──→ CANCELLED
                          │
                    BLOCKED (manual/channel)
```

### Eksik State'ler

| State | Neden Gerekli | Risk |
|-------|---------------|------|
| `completed` | Check-out sonrası finansal kapanış | Ledger transition tetiklemek için |
| `no_show` | Misafir gelmedi — depozito kararı | Finansal süreç ayrışması |

### Kritik Bulgu: `createReservation()` Doğrudan `confirmed` Oluşturuyor

```php
// app/Services/ReservationService.php:165
'reservation_state' => 'confirmed',  // ← pending yok!
```

Bu, `pending → confirmed` transition'ı bypass ediyor. İki senaryo var:
1. **Internal reservation** (admin): Direkt `confirmed` — intentional olabilir
2. **External/guest**: `pending` → confirm akışı eksik

SAAB kararı gerekiyor: `pending` state kullanılacak mı?

---

## 5. total_amount vs islem_tutari Semantik Analizi

| Alan | Model | Tip | Anlam |
|------|-------|-----|-------|
| `total_amount` | `IlanReservation` | `float` | Toplam rezervasyon tutarı (kayıt sırasında hesaplanan) |
| `islem_tutari` | `PropertyReservation` | `decimal:2` | İşlem tutarı (Context7 canonical ad) |

**Değerlendirme:**
- Anlamsal olarak eşdeğer — ikisi de rezervasyonun toplam parasını temsil ediyor
- `islem_tutari` Context7 naming authority kurallarına uygun
- `total_amount` İngilizce — SAB ihlali potansiyeli var
- DB'de hangi kolon adının kullanıldığı migration incelemesi gerektiriyor

**Öneri:** `islem_tutari` canonical, `total_amount` deprecated alias olarak ele alınmalı

---

## 6. Reservation → Availability Yazma Zinciri

```
ReservationService::createReservation()
    ↓ DB::transaction
    ├── PropertyReservation::create()          [yazma]
    └── PropertyAvailability::update()         [her gün için is_available=false]
            block_reason = 'reservation'
            reservation_id = $reservation->id
            origin = ORIGIN_RESERVATION
            source_system = 'internal'

ReservationService::cancelReservation()
    ↓ DB::transaction
    ├── PropertyReservation::update(cancelled) [yazma]
    └── PropertyAvailability::update()         [is_available=true geri alınır]
            reservation_id = null
            block_reason = null
```

**Bulgu:** Zincir `ReservationService` içinde kapalı ve `DB::transaction` ile korunuyor. ✅

**Risk:** `UpdateReservationStateAction` ve `FinancialLedgerService` rezervasyon state'ini `ReservationService` dışından değiştiriyor — availability senkronizasyonu bu yollarda yapılmıyor.

---

## 7. ReservationService Dışı Doğrudan ORM Write'lar

| Dosya | Satır | Yazma Tipi | Risk |
|-------|-------|-----------|------|
| `FinancialLedgerService.php:210` | `PropertyReservation::where()->update(depozito_durumu)` | Finansal state | Orta — sadece finansal alan |
| `FinancialLedgerService.php:241` | `PropertyReservation::where()->update(depozito_durumu)` | Finansal state | Orta |
| `FinancialLedgerService.php:265` | `PropertyReservation::where()->update(finansal_durum)` | Finansal state | Orta |
| `FinancialLedgerService.php:277` | `PropertyReservation::where()->update(finansal_durum)` | Finansal state | Orta |
| `UpdateReservationAction.php:11` | `$reservation->update($updateData)` | Tüm alanlar | **Yüksek** — open-ended update |
| `UpdateReservationStateAction.php:11` | `$reservation->update(['reservation_state'])` | State değişimi | **Yüksek** — availability senkronizasyonu yok |
| `EnterpriseMoneyTest.php:102` | `PropertyReservation::create()` | Test fixture | Düşük — test ortamı |
| `GateCKpiTest.php:73,83,104,116,138` | `PropertyReservation::create()` | Test fixture | Düşük — test ortamı |
| `PropertyAvailabilityTest.php:216` | `PropertyReservation::create()` | Test fixture | Düşük — test ortamı |

**Kritik Risk:** `UpdateReservationStateAction` state'i `confirmed → cancelled` yapabilir ama `PropertyAvailability` bloklarını serbest bırakmaz. Bu availability leak'e yol açar.

---

## 8. Data Migration Riskleri

| Risk | Açıklama | Seviye |
|------|----------|--------|
| Tablo aynı | `IlanReservation` ve `PropertyReservation` aynı tabloyu gösteriyor | Veri migration yok — model alias problemi |
| `IlanReservation.ilan_id` | Model'de `ilan_id` FK var ama `scopeForIlan` `property_id` kullanıyor | Tutarsızlık — `ilan_id` column var mı? Kontrol gerekiyor |
| `IlanReservationService` DEPRECATED ama çalışıyor | 525 satır aktif kod, Telegram entegrasyonu kullanıyor | Yüksek bağımlılık — migration risk |
| `TelegramAIBotService` bağımlılığı | 15+ `IlanReservation` kullanımı — en büyük migration yükü | Orta-Yüksek |
| `AdminNotificationService` | `IlanReservation` type hint'li 4 metod | Orta |
| Test coverage | Mevcut testler `IlanReservation` üzerine — migrate edilmeli | Orta |

---

## 9. Availability Projection Yönü

Mevcut akış:
```
ReservationService → PropertyAvailability (senkron, transaction içinde)
```

Önerilen akış (SAAB kararı bekleniyor):

**Seçenek A — Observer (Event-Driven):**
```
PropertyReservation::saved() →
    ReservationObserver::confirmed() → availability blok
    ReservationObserver::cancelled() → availability serbest
```

**Seçenek B — Service Layer (Mevcut — Korunur):**
```
ReservationService::createReservation() → availability yazma (mevcut)
ReservationService::cancelReservation() → availability temizleme (mevcut)
```

**Öneri:** Seçenek B korunmalı — mevcut `DB::transaction` güvencesi var. Observer eklemek çift yazma riskine yol açar.

---

## 10. P0/P1 Implementasyon Planı

### P0 — Güvenlik (Önce yapılmalı)

| # | Görev | Neden P0 |
|---|-------|---------|
| P0.1 | `UpdateReservationStateAction` → `ReservationService::cancelReservation()` kullanmalı | Availability leak riski |
| P0.2 | `IlanReservation` model'ine `@deprecated` PHPDoc eklenmeli | Yanlış kullanımı önlemek |
| P0.3 | `ReservationState` enum'a `COMPLETED` + `NO_SHOW` state ekle | Ledger kapanış için |

### P1 — Canonical Konsolidasyon

| # | Görev | Bağımlılık |
|---|-------|-----------|
| P1.1 | `IlanCalendarIcsService` → `PropertyReservation` migrate | P0.2 sonrası |
| P1.2 | `AdminNotificationService` → `PropertyReservation` type hint | P1.1 sonrası |
| P1.3 | `AdminActivityEventService` → `PropertyReservation` type hint | P1.1 sonrası |
| P1.4 | `TelegramAIBotService` → `ReservationService` + `PropertyReservation` | En büyük iş — son |
| P1.5 | `IlanCalendarController` → `ReservationService` delegate | P1.2 sonrası |
| P1.6 | Calendar feed testleri → `PropertyReservation` fixture | P1.1 sonrası |

### P2 — Cleanup

| # | Görev |
|---|-------|
| P2.1 | `IlanReservationService` silinmesi (tüm caller'lar migrate sonrası) |
| P2.2 | `IlanReservation` model silinmesi |
| P2.3 | `AvailabilityService` stub'ı silinmesi |
| P2.4 | `IlanReservation.ilan_id` orphan kolonu incelenmesi |

---

## 11. Test Planı

| Test | Kapsam | Hedef |
|------|--------|-------|
| `PropertyReservationLifecycleTest` | pending→confirmed→completed, pending→cancelled, no_show | 8+ test |
| `ReservationServiceTest` (genişletme) | createReservation, cancelReservation, conflict detection, tenant isolation | 10+ test |
| `ReservationAvailabilityProjectionTest` | create→availability blocked, cancel→availability freed | 5+ test |
| `ReservationStateActionTest` | UpdateReservationStateAction → availability senkronizasyonu | 3+ test |
| `ReservationFinancialStateTest` | finansal_durum, depozito_durumu transitions | 4+ test |

---

## Özet Karar Noktaları (SAAB için)

| Soru | Seçenekler | Öneri |
|------|-----------|-------|
| `pending` state kullanılacak mı? | A) Direkt `confirmed` (mevcut) B) `pending → confirmed` flow | SAAB kararı |
| Availability projection yöntemi | A) Observer B) Service (mevcut) | Seçenek B — mevcut korunur |
| `total_amount` alanı | A) Silinir B) `islem_tutari` alias olur | A — silinir, `islem_tutari` canonical |
| Telegram migration önceliği | A) P1 B) P2 (sonraya bırak) | B — en büyük risk, isolated sprint |
| `IlanReservationService` ne zaman silinir | A) P1 sonunda B) Ayrı sprint | B — güvenli |
