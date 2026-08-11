# ADR-008: Channex Reservation Lifecycle Ingest — Modification & Cancellation

**Status:** ACCEPTED
**Date:** 2026-08-10
**SAAB Authorization:** CHANNEL_MANAGER_PROVIDER Wave 3 Discovery APPROVED

---

## Decision

1. `action='new'` → Wave 2 path unchanged
2. `action='modified'` → `ChannexReservationModifyJob` → `ReservationService.modifyReservation()`
3. `action='cancelled'` → `ChannexReservationCancelJob` → `ReservationService.cancelReservation()`
4. `ReservationService.modifyReservation()` canonical — conflict detection runs inside
5. Channel Manager does NOT own reservation state — it normalizes external payload only
6. Out-of-order: modification on cancelled reservation → ignore + log (no exception thrown)
7. Unknown `external_reservation_id` → 200 OK + warning log (no 4xx to Channex)
8. Idempotency for modification: `(external_reservation_id, channel)` lookup + date comparison

## Canonical Chain

```
ChannexWebhookController
    ├─ action='new'       → ChannexReservationIngestJob (Wave 2, unchanged)
    ├─ action='modified'  → ChannexReservationModifyJob
    │       └─ ChannexReservationIngestService.ingestModification()
    │               └─ ReservationService.modifyReservation()  ← NEW canonical method
    └─ action='cancelled' → ChannexReservationCancelJob
            └─ ChannexReservationIngestService.ingestCancellation()
                    └─ ReservationService.cancelReservation()  ← existing
```

## Invariants

- `ReservationService.cancelReservation()` değişmez (already idempotent)
- `modifyReservation()` conflict detection içerir (date change → availability re-check)
- Terminal state reservation (cancelled) → modification → silently ignored
- No 4xx to Channex on business logic failures (retry storm prevention)

## Referanslar

- `docs/sprints/CHANNEL_MANAGER_PROVIDER_WAVE3_DISCOVERY.md`
- `docs/adrs/ADR-007-Channel-Manager-Webhook-Ingest.md`
- `app/Services/ReservationService.php`
