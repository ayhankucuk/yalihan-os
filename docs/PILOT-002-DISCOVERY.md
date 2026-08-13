# PILOT-002 — Reservation Operations Discovery Report
**Oturum:** 120 | **Tarih:** 2026-08-13 | **Model:** Opus 4.8 (Discovery)
**Status:** DISCOVERY COMPLETE

---

## Executive Summary

PILOT-002 aims to automate the manual calendar/conflict checking in the reservation creation process using supervised autonomy — mirroring the PILOT-001 pattern: `Context → Authority → Readiness → Approval Token → Orchestrator → Evidence → Certification`.

**Foundation is strong.** `ReservationService` has production-grade atomic double-booking prevention (`lockForUpdate` + overlap query). `PropertyAvailability` date-row model is complete. The orchestration layer above it is entirely absent.

---

## Q1 — ReservationService

| File | Class | Public Methods | Status |
|------|-------|---------------|--------|
| `app/Services/ReservationService.php` | `ReservationService` | `createReservation()`, `cancelReservation()`, `modifyReservation()` | ACTIVE — primary authority |
| `app/Services/Calendar/IlanReservationService.php` | `IlanReservationService` | `create()`, `cancel()`, `confirm()`, `cancelById()`, `checkConflict()`, `availabilityMatrix()`, `closeCalendar()`, `listForIlan()` | DEPRECATED 2026-01-29 |
| `app/Services/YazlikKiralamaService.php` | `YazlikKiralamaService` | `createListing()`, `updateListing()`, `deleteListing()`, `getMonthlyRevenue()`, `updateBookingStatus()` | ACTIVE — listing CRUD only |
| `app/Services/ChannelManager/ChannexReservationIngestService.php` | `ChannexReservationIngestService` | `ingest()` | ACTIVE — Channex channel |
| `app/Services/ChannelManager/BookingReservationIngestService.php` | `BookingReservationIngestService` | `ingest()` | ACTIVE — Booking.com channel |
| `app/Http/Controllers/Api/BookingRequestController.php` | `BookingRequestController` | `store()`, `checkAvailability()`, `getPrice()`, `suggestAlternatives()` | ACTIVE — public API |

Key: `createReservation()` uses atomic TX with `lockForUpdate()` overlap check + `PropertyAvailability` block.

---

## Q2 — Double-Booking / Conflict Detection

| File | Line | Mechanism |
|------|------|-----------|
| `app/Services/ReservationService.php` | 56–65 | `PropertyReservation` overlap: `start_date < $end AND end_date > $start` + `lockForUpdate()` |
| `app/Services/ReservationService.php` | 91–106 | `PropertyAvailability` double-check: iterate locked rows, throw if `!is_available` |
| `app/Services/ReservationService.php` | 202–212 | `modifyReservation()` overlap check (excludes self via `id != $reservationId`) |
| `app/Services/Calendar/IlanReservationService.php` | 97–109 | `checkConflict()` — overlap scope on `property_reservations` |
| `app/Services/Calendar/IlanReservationService.php` | 111–127 | `scopeCakisan()` scope on `YazlikRezervasyon` — 3-way overlap detection |
| `app/Http/Controllers/Api/BookingRequestController.php` | 168–178 | `checkAvailability()` — overlap via events table |

**ConflictDetectionService contract** registered in `AppServiceProvider.php:143` → **CONCRETE IMPLEMENTATION NOT FOUND.**

---

## Q3 — Override Authorization

| File | Line | Mechanism | Status |
|------|------|-----------|--------|
| `app/Providers/AppServiceProvider.php` | 147–150 | `ConflictOverrideContract` → `ConflictOverrideService` | **INTERFACE REGISTERED, CLASS MISSING** |
| `app/Services/Ydl/YdlPublishOrchestrator.php` | 74, 81 | `$ydlAuthorityOverride` param | PILOT-001 pattern only |
| `app/Services/ReservationService.php` | — | No override/force-book | **ABSENT** |

**No `canOverride`, `forceBook`, or admin reservation rule bypass exists.**

---

## Q4 — Availability Tracking

| Model | Table | Key Fields | Mechanism |
|-------|-------|-----------|-----------|
| `app/Models/PropertyAvailability.php` | `property_availability` | `property_id`, `date`, `is_available`, `block_reason`, `source_system`, `reservation_id`, `external_ref` | Per-date row: `is_available=true/false`, `source_system` = `internal`/`airbnb_ical`/etc. |
| `app/Models/YazlikRezervasyon.php` | `yazlik_rezervasyonlar` | `ilan_id`, `check_in/check_out`, `musteri_*`, `rezervasyon_durumu` | Separate summer rental table; scopes: `active()`, `gelecek()`, `mevcut()`, `gecmis()`, `cakisan()` |

Pattern: date-level row-per-night with `source_system` discrimination.

---

## Q5 — Event Chain

| Event | Dispatched From |
|-------|----------------|
| `ChannexReservationIngestedEvent` | `ChannexReservationIngestService` |
| `ChannexReservationCancelledViaChanEvent` | `ChannexReservationIngestService.php:198` |
| `ChannexReservationModifiedEvent` | `ChannexReservationIngestService.php:150` |
| `BookingReservationIngestedEvent` | Booking.com ingest |
| `BookingReservationCancelledEvent` | `BookingCancellationProcessor.php:96` |
| `BookingReservationModifiedEvent` | `BookingModificationProcessor.php:99` |

**Gap:** No generic `ReservationCreatedEvent`, `AvailabilityChangedEvent`, or `ReservationDoubleBookedEvent` for internal `ReservationService` path.

---

## Q6 — IlanCrudService Relationship

`ReservationService` does **NOT** interact with `IlanCrudService`. Read-only `Ilan` lookup for `rental_enabled` / `min_stay_nights` validation only. Completely decoupled flows.

---

## Q7 — Domain Model

**`PropertyReservation`** (table: `property_reservations`)
- `tenant_id`, `property_id`/`ilan_id`, `start_date`/`end_date`, `nights`
- `guest_name`/`phone`/`email`/`count`, `notes`
- `reservation_state` (PENDING/CONFIRMED/BLOCKED/CANCELLED)
- `cancelled_at`/`confirmed_at`, `finansal_durum`, `depozita_*`, `locked_nightly_rate`
- `external_reservation_id`/`external_channel`, `created_by_user_id`, `ulke_id`

**`PropertyAvailability`** (table: `property_availability`)
- `property_id`, `date`, `is_available`, `block_reason`, `source_system`, `reservation_id`, `external_ref`

---

## Build vs Reuse Matrix

| Component | Exists | File/Class | Action |
|-----------|--------|-----------|--------|
| Double-booking prevention (atomic TX + lockForUpdate) | YES ✅ | `ReservationService.php:56–65` | REUSE — production-grade |
| Availability tracking (PropertyAvailability) | YES ✅ | `PropertyAvailability.php` | REUSE |
| Overlap detection query | YES ✅ | 3 implementations | REUSE |
| ConflictDetectionContract | PARTIAL ⚠️ | `AppServiceProvider.php:143` | BUILD — concrete missing |
| ConflictOverrideContract | PARTIAL ⚠️ | `AppServiceProvider.php:147` | BUILD — concrete missing |
| YDL-style authority context | NO ❌ | — | BUILD — `ReservationContextReader` |
| Readiness Evaluator | NO ❌ | — | BUILD — `ReservationReadinessService` |
| Human Approval Token | NO ❌ | — | BUILD — `reservation_approval_tokens` table + service |
| Orchestrator | NO ❌ | — | BUILD — `ReservationOrchestrator` |
| Generic `ReservationCreatedEvent` | NO ❌ | — | BUILD |
| Generic `AvailabilityChangedEvent` | NO ❌ | — | BUILD |
| `ReservationDoubleBookedEvent` | NO ❌ | — | BUILD |

**Net assessment:** Foundation (ReservationService, PropertyAvailability) is solid and reusable. Entire orchestration layer must be built from scratch.

---

## PILOT-001 Pattern Adaptation for PILOT-002

```
ydl:context  →  authority (STOP/LIMITED/FULL for reservations)
     ↓
ReservationReadinessService
  ├─ availability clean?
  ├─ no pending cancellations?
  ├─ ilan.rental_enabled?
  ├─ min_stay_nights met?
  └─ tenant in good standing?
     ↓
ReservationRecommendation → agent'e conflicting dates / missing data bildirir
     ↓
Human Approval Token (24s TTL)
     ↓
ReservationOrchestrator::createReservation()
  ├─ Token validation
  ├─ STOP authority → DomainException
  ├─ ConflictOverride authority → override check
  ├─ Idempotency guard (event_id)
  └─ lockForUpdate + overlap check
     ↓
ReservationService::createReservation()  ← REUSE
     ↓
ReservationCreatedEvent → evidence
     ↓
ydl:session-summary CERTIFIED
```

---

## Next Steps

1. **Charter Document** — Write `docs/PILOT-002-CHARTER.md` with: business goal, KPI (manual conflict check time), scope (create + cancel + override), out-of-scope (modify, channel ingest)
2. **Wave 1** — `ReservationContextReader` + `ReservationReadinessService` + `ReservationApprovalToken`
3. **Wave 2** — `ReservationOrchestrator` + `ReservationDoubleBookedEvent` + evidence chain
4. **Wave 3** — `ConflictOverrideService` + SAAB certification
