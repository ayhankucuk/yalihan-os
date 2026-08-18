# ADR-007: Channel Manager Webhook Ingest Architecture

**Status:** ACCEPTED
**Date:** 2026-08-10
**SAAB Authorization:** CHANNEL_MANAGER_PROVIDER Wave 2 Discovery APPROVED

---

## Decision

1. Webhook endpoint: `POST /api/v1/webhook/channex`
2. Auth: HMAC-SHA256 (`X-Channex-Signature`), Channex webhook secret
3. Tenant resolution: `property_id` → `IlanTakvimSync` → `ilan_id` → `tenant_id` (DB join, no global scope)
4. Idempotency: `external_reservation_id` + `external_channel` columns on `property_reservations`
5. Async ingest: `ChannexReservationIngestJob` — webhook controller hemen 200 döner
6. Conflict davranışı: 200 OK + PENDING state + admin alert (4xx vermez)
7. `ReservationService.createReservation()` değişmez — canonical chain korunur
8. `ChannexReservationIngestService` thin wrapper (normalize + delegate)

## Canonical Chain

```
ChannexWebhookController (thin)
    └─ ChannexReservationIngestJob (queue)
            └─ ChannexReservationIngestService
                    └─ ReservationService.createReservation() ← unchanged
```

## Referanslar

- `docs/sprints/CHANNEL_MANAGER_PROVIDER_WAVE2_DISCOVERY.md`
- `docs/adrs/ADR-006-Channel-Manager-Provider-Architecture.md`
