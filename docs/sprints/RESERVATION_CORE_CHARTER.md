# Sprint Charter — Reservation Core

**Charter Tarihi:** 2026-08-05  
**Hazırlayan:** WenOX (ön araştırma) + SAAB onayı bekleniyor  
**Sprint Tipi:** Canonical Domain Consolidation  
**Önkoşul:** ✅ CERT-DEBT-001 CLOSED (Oturum 93), ✅ ADR-001 CLOSED, ✅ LP-008 CLOSED

---

## Misyon

YALIHAN'da her rezervasyonun tek ve kanonik bir temsilcisi vardır.  
Tüm availability ve kanal senkronizasyonu bu kaynaktan türetilir.

---

## Mevcut Durum (Split-Brain Tespiti)

| Model | Tablo | Sorun |
|-------|-------|-------|
| `IlanReservation` | `property_reservations` | `ReservationState` enum yok, `total_amount` adı farklı, `tenant_id` yok — **DEPRECATED edilecek** |
| `PropertyReservation` | `property_reservations` | `ReservationState` enum cast var, `tenant_id` var — **CANONICAL** |
| `PropertyAvailability` | `property_availabilities` | LP-008'de doğrulandı — `reservation_id` FK `PropertyReservation`'a bağlı ✅ |
| `ReservationService` | — | `GuardsAgentWrites` var, Sprint 22 E01'de `tenant_id` auto-resolve eklendi — **korunacak** |
| `IlanReservationService` | — | Calendar feed bağlamında kullanılıyor — bağımlılık incelenmeli |

---

## Reservation Aggregate — Canonical Tanım

```
PropertyReservation (Aggregate Root)
    ├── identity:    id, tenant_id, property_id (→ Ilan)
    ├── period:      start_date, end_date, nights
    ├── guest:       guest_name, guest_phone, guest_email, guest_count
    ├── state:       reservation_state (ReservationState enum)
    ├── finance:     islem_tutari, currency, depozito_tutari, depozito_durumu
    │                locked_nightly_rate, booking_currency, booking_fx_rate
    │                booking_country_code, finansal_durum
    ├── lifecycle:   confirmed_at, cancelled_at, created_by_user_id
    └── projection:  → PropertyAvailability (günlük blok projeksiyon)
```

---

## Reservation State Machine

```
PENDING ──confirm──→ CONFIRMED ──complete──→ COMPLETED
   │                     │
   └──cancel──→ CANCELLED │──cancel──→ CANCELLED
                          │
                          └──no_show──→ NO_SHOW
```

| State | Açıklama |
|-------|----------|
| `pending` | Oluşturuldu, henüz onaylanmadı |
| `confirmed` | Onaylandı, takvimi bloklar |
| `completed` | Check-out yapıldı |
| `cancelled` | İptal edildi — `cancelled_at` set edilir |
| `no_show` | Misafir gelmedi |

---

## Sprint Kapsamı (Fazlar)

### Faz 1 — Canonical Aggregate Tescili
- [ ] `IlanReservation` → `@deprecated` işaretlenmeli, tüm kullanımlar `PropertyReservation`'a yönlendirilmeli
- [ ] `ReservationState` enum'u eksik state'lerle (`completed`, `no_show`) tamamlanmalı
- [ ] `PropertyReservation` model'ine eksik helper metodlar eklenmeli (`confirm()`, `cancel()`, `complete()`, `isActive()`)

### Faz 2 — Lifecycle & Invariants
- [ ] `ReservationService::createReservation()` → state `pending` olarak başlamalı (mevcut: `confirmed`)
- [ ] `ReservationService::confirmReservation()` ayrı metod olarak eklenmeli
- [ ] Çakışma kontrolü: aynı `property_id` + overlapping `start_date/end_date` → throw exception
- [ ] Ownership invariant: her rezervasyon `tenant_id` taşımalı (auto-resolve korunacak)

### Faz 3 — Availability Projection Zinciri
- [ ] `PropertyReservation` `confirmed` state'e geçince → `PropertyAvailability` günlük bloklar otomatik oluşturulmalı
- [ ] `PropertyReservation` `cancelled` state'e geçince → `PropertyAvailability` blokları kaldırılmalı
- [ ] Projeksiyon job veya observer olarak implement edilecek (SAAB kararı bekleniyor)

### Faz 4 — Test Coverage
- [ ] `PropertyReservationTest` — 15+ test, lifecycle state machine coverage
- [ ] `ReservationServiceTest` — create, confirm, cancel, conflict detection
- [ ] `ReservationAvailabilityProjectionTest` — projection zinciri doğrulama

---

## Kapsam Dışı (Bu Sprint)

| Konu | Neden Dışarıda |
|------|----------------|
| Airbnb iCal sync | Kanal katmanı — Availability Engine sonrası |
| Booking.com entegrasyonu | Kanal katmanı |
| Fiyat takvimi (`PropertyPricingCalendar`) | Ayrı sprint |
| Finansal muhasebe (ledger kayıtları) | Ayrı sprint |
| Misafir self-service portal | UI katmanı |

---

## Başarı Kriteri

Sprint sonunda şunu söyleyebilmeliyiz:

> YALIHAN'da her rezervasyonun tek ve kanonik bir temsilcisi vardır;  
> tüm availability ve kanal senkronizasyonu bu kaynaktan türetilir.

### Ölçülebilir Hedefler

| Kriter | Hedef |
|--------|-------|
| `PropertyReservationTest` | ✅ 15+ PASS |
| `ReservationServiceTest` | ✅ 10+ PASS |
| `ReservationAvailabilityProjectionTest` | ✅ 5+ PASS |
| `IlanReservation` referansları | ✅ 0 (deprecated) |
| `ReservationState` enum coverage | ✅ 5 state |
| Conflict detection | ✅ overlapping rezervasyon exception |

---

## Bağımlılıklar

| Bağımlılık | Durum |
|------------|-------|
| `property_reservations` tablosu | ✅ Mevcut |
| `property_availabilities` tablosu | ✅ LP-008'de doğrulandı |
| `ReservationState` enum | ✅ Mevcut, genişletilecek |
| `PropertyAvailabilityContract` | ✅ `ReservationService` inject ediyor |
| `GuardsAgentWrites` | ✅ `ReservationService`'de var |

---

## SAAB Onay Kapıları

| Kapı | Durum |
|------|-------|
| Charter hazırlandı | ✅ Bu doküman |
| SAAB mimari onayı | ⏳ Bekleniyor |
| Faz 1 implementasyon | ⏳ |
| Faz 2 implementasyon | ⏳ |
| Faz 3 implementasyon | ⏳ |
| Test coverage | ⏳ |
| Kapanış sertifikasyonu | ⏳ |

---

*Bu Charter SAAB onayından sonra implementasyona açılacaktır.*
