# PILOT-002 — Reservation Operations Supervised Autonomy

**Charter Version:** 1.0
**Date:** 2026-08-13
**Status:** CHARTER — AUTHORITY DESIGN NEXT
**Model Strategy:** Opus 4.8 (charter/authority) → Sonnet 4.6 (implementation)

---

## 1. Business Goal

> Yeni rezervasyon oluşturma sürecinde manuel availability ve conflict kontrolünü otomatikleştirerek danışman iş yükünü ≥%70–80 oranında azaltmak.

**Mevcut Manuel Süreç (Baseline — İlk Pilot Operasyonunda Ölçülecek):**

| Adım | Aktivite |
|------|----------|
| 1 | Availability kontrolü — takvim/tarih çakışması araştırması |
| 2 | Conflict kontrolü — mülk bazlı overlap sorgulaması |
| 3 | Rezervasyon karar verme — uygunluk değerlendirmesi |
| 4 | Kayıt — sisteme giriş |
| 5 | Cancellation — iptal + availability açma |
| 6 | Override onayı (gerekiyorsa) — üst yönetici yetkisi |

**Hedef:** Bu süreçlerin toplam manuel süresini ≥%70–80 azaltmak.

> **Note:** Manuel süre baseline'ı uydurulmayacak. İlk pilot operasyonunda gerçek ölçüm yapılacak. KPI charter'da değil, ilk pilot sonrasında kesinleşir.

---

## 2. Scope

### In Scope

| Operasyon | Açıklama |
|-----------|----------|
| **Reservation Create** | Double-booking korumalı, readiness kontrollü, human approval'li rezervasyon oluşturma |
| **Reservation Cancel** | Idempotent iptal + availability açma + evidence |
| **Authorized Conflict Override** | STOP/LIMITED/FULL authority üzerinden yönetici override'ı |

### Out of Scope

| Operasyon | Sebep |
|-----------|-------|
| Reservation Modify | Ayrı operasyon — farklı invariant seti |
| Channel Ingest (Booking/Channex) | Kendi pipeline'ları var |
| Booking.com / Channex provider logic | Ayrı capability |
| Yeni availability engine | `PropertyAvailability` mevcut ve yeterli |
| Yeni reservation state | `PropertyReservation.reservation_state` mevcut |
| Guest Communication | Ayrı capability (sonraki pilot) |

---

## 3. Canonical Owners — REUSE, DO NOT REWRITE

```
┌─────────────────────────────────────────────────────────┐
│  Mevcut Domain Logic — YENİDEN YAZILMAZ                │
├─────────────────────────────────────────────────────────┤
│  ReservationService.php:56–65                          │
│    → lockForUpdate + overlap query (atomic, production) │
│                                                         │
│  PropertyAvailability.php                               │
│    → per-date row model (date, is_available, source)   │
│                                                         │
│  PropertyReservation.php                                │
│    → domain model (state, dates, guest, financial)      │
└─────────────────────────────────────────────────────────┘
```

---

## 4. Architecture Spine

```
ydl:context
    ↓
authority FULL / LIMITED / STOP
    ↓
ReservationReadinessService
    ↓
Canonical Conflict Check (ReservationService overlap)
    ↓
Human Approval / Override (gerekiyorsa)
    ↓
YdlReservationOrchestrator
    ↓
ReservationService  ← REUSE (lockForUpdate + atomic TX)
    ↓
PropertyAvailability  ← REUSE (date-row model)
    ↓
ReservationCreatedEvent / ReservationCancelledEvent
    ↓
ydl:session-summary
```

---

## 5. Authority Model — STOP / LIMITED / FULL

Türetilmiş from PILOT-001; reservation domain'e adapté:

| Seviye | Anlamı | Kural |
|--------|--------|-------|
| `STOP` | Mülk için tüm rezervasyon yasak | Tüm create/cancel/override talepleri DomainException ile reddedilir |
| `LIMITED` | Sınırlı — override yetkisi gerektirir | Yalnızca override authority sahibi create yapabilir |
| `FULL` | Tüm operasyonlara açık | readiness + canonical conflict check yeterli |

**Override scope:** `LIMITED` seviyesinde, `ConflictOverrideService` tarafından onaylanan kullanıcılar rezervasyon oluşturabilir. `STOP` seviyesinde override mümkün değildir.

---

## 6. Critical Invariant

```
┌──────────────────────────────────────────────────────────────┐
│  INVARIANT #1 — Tek Source of Truth for Conflict Detection   │
│                                                              │
│  ReservationOrchestrator asla kendi overlap/conflict          │
│  algoritmasini YAZMAZ.                                       │
│                                                              │
│  lockForUpdate + canonical overlap check hangi servis         │
│  tarafindan bugun guvenli sekilde uygulaniyorsa,            │
│  orchestrator dogrudan onu cagirir.                          │
│                                                              │
│  Result: Iki farkli double-booking dogrulugu OLUSMAZ.        │
└──────────────────────────────────────────────────────────────┘
```

**Bugün güvenli uygulama:** `ReservationService::createReservation()` — `lockForUpdate` + `start_date < $end AND end_date > $start` overlap sorgusu. Bu kesinlikle korunur.

---

## 7. Implementation Waves

### Wave 1 — Create + Double-Booking Prevention

- `YdlReservationContextReader` — mevcut rezervasyon state + availability snapshot okur
- `ReservationReadinessService` — `rental_enabled`, `min_stay_nights`, availability, tenant durumu
- `YdlReservationOrchestrator` — authority → readiness → human approval → canonical create
- `ReservationCreatedEvent` — evidence event
- 12+ test senaryosu

### Wave 2 — Cancellation

- Idempotent cancel operasyonu
- Availability açma (internal source_system filtreli)
- `ReservationCancelledEvent` — evidence event
- 6+ test senaryosu

### Wave 3 — Authorized Override

- `ConflictOverrideService` concrete implementation
- `LIMITED` authority → override scope intersection
- `STOP` authority → override reddi
- Override log → evidence
- 8+ test senaryosu

### Guest Communication — Ayrı Capability (Sonraki Pilot)

> Reservation correctness + guest communication otomasyonunu aynı certification sınırına sokmak gereksiz risk yaratır. Ayrı pilot olarak planlanır.

---

## 8. State Machine

```
RESERVATION LIFECYCLE
─────────────────────
PENDING → CONFIRMED → BLOCKED
    ↓
CANCELLED (terminal)
```

```
AUTHORITY TRANSITIONS (YdlReservationContext)
────────────────────────────────────────────
(null)     → STOP    → LIMITED → FULL
uninitialized → fully_governed
```

---

## 9. Evidence Chain

Her operasyon sonrası:

```
YdlReservationOrchestrator
    ↓
ReservationCreatedEvent | ReservationCancelledEvent
    ↓
YdlEventLog::append()
    ↓
ydl:session-summary CERTIFIED | BLOCKED | OVERRIDE_LOGGED
```

---

## 10. Kapanış Kriteri

| Kriter | Hedef |
|--------|-------|
| Wave 1 — 3 test | 12/12 PASS |
| Wave 2 test | 6+/PASS |
| Wave 3 test | 8+/PASS |
| Business KPI (pilot sonrası) | ≥%70–80 manual time reduction |
| Authority invariant | Tek overlap source — ReservationService |
| Mimari | PILOT-001 pattern adaptation |
| Certification | **BUSINESS AUTOMATION CERTIFIED** |

---

## 11. Next Steps

```
CURRENT:  CHARTER ▶
          ↓ Opus 4.8
NEXT:     Authority + Invariant Design (Sonnet 4.6 ile)
          ↓
AFTER:    Wave 1 Implementation
```

**PILOT-002 Status:** `DISCOVERY ✅ → CHARTER ▶ → IMPLEMENTATION ⏳`
