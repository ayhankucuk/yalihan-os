# 03_DECISIONS.md — Sprint 4.11

## Decisions Made

| # | Decision | Rationale |
|---|----------|-----------|
| D1 | ACK in success path only, not in `finally` block | ADR-009 invariant: ACK only after commit success |
| D2 | Queue-first polling, not 20s cron | Avoids `sleep()` anti-pattern; retry is Laravel-native |
| D3 | ACK failure → no rollback | Booking.com idempotency protection; duplicate ACK on retry |
| D4 | Separate Acknowledger class (not in IngestService) | Single responsibility; cleaner test surface |
| D5 | Batch size configurable via `config()` | API supports 10–200; no hardcoded limit |
| D6 | `external_reservation_id` idempotency — existing field | Already in schema from Wave 2 migration |
| D7 | NEW reservation uses `OTA_HotelResNotif` endpoint | Modification/cancellation → Wave 3 (different endpoint) |
| D8 | HTTP 400 from Booking ACK → skip + log | Stale/out-of-order message; not retryable |
