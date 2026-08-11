# 01_CONTEXT.md — Sprint 4.11

## How We Got Here

### Previous Sprint Closing
- **Wave 1 CERTIFIED** (`b70c2c2`): Auth + Property Mapping
  - `BookingAuthTransport`, `BookingCredentialManager`, `BookingTransport`
  - `BookingPropertyResolver` + tenant isolation
  - `ChannelReservationContract` interface
  - BW1-01..BW1-10: 10/10 PASS

### What Needs to Happen Next
ADR-009 invariant'ını kodlamak: `retrieve → normalize → canonical commit → ACK`.
Bu invariant bozulursa:
1. ACK before commit → double booking riski
2. ACK on failure → Booking.com retry loop'a girer
3. No idempotency → duplicate reservations

---

## Technical Context

### Architecture

```
Wave 2 Flow (NEW reservations only)
────────────────────────────────────────────────────────────────

  BookingReservationPollJob (queue dispatch)
         │
         ▼
  BookingReservationRetriever
    GET OTA_HotelResNotif
         │
         ▼
  BookingReservationPayload[] DTO[]
         │
         ▼
  BookingPropertyResolver.resolve()
         │
         ▼
  BookingReservationIngestService.ingest()
    ├── Idempotency check (external_reservation_id + channel)
    ├── ReservationService.createReservation()  ← canonical write
    └── THROW on failure  ← NO ACK
         │
         ▼ (success only)
  BookingReservationAcknowledger.acknowledge()
    POST OTA_HotelResNotif
```

### Dependencies

| Dependency | Status | Action |
|------------|--------|--------|
| Wave 1 services (Transport, Resolver) | ✅ Ready | Compose |
| `ReservationService.createReservation()` | ✅ Ready | Delegate |
| `ChannelReservationContract` | ✅ Ready | Stub implement |
| `external_reservation_id` migration | ✅ Ready | Already in schema |
| `BookingReservationIngestedEvent` | 🔲 Missing | Create in Wave 2 |
| `BookingReservationRejectedEvent` | 🔲 Missing | Create in Wave 2 |
| `BookingAckFailedEvent` | 🔲 Missing | Create in Wave 2 |
| Poll job scheduler | 🔲 Missing | Queue-based (NOT 20s cron) |

### Critical Design Decisions

**Queue-first polling (NOT 20s cron):**
- Queue job dispatched via `Bus::dispatch()` with `delay()`
- Intervals managed by retry backoff, not cron
- 20s is a target SLA, not a hard guarantee — batch processing
- Avoids `while(true) sleep(20)` anti-pattern

**Batch size configurable:**
- `config('services.booking.reservation_batch_size', 100)`
- 10–200 range supported by Booking.com API
- NOT hardcoded

**ACK endpoint per message type:**
- NEW reservation: `POST /ota/HotelResNotif` (OTA_HotelResNotif)
- Modification: `POST /ota/HotelResModifyNotif` (Wave 3)
- Cancellation: `POST /ota/HotelResNotif` with status cancelled (Wave 3)
- HTTP 400 from Booking.com = stale/out-of-order message → skip + log

---

## Sprint Boundary

**What belongs to Sprint 4.11:**
- NEW reservation retrieval + ingest + ACK
- DTO normalization
- Idempotency guard
- ACK failure handling (no rollback)
- Poll job (queue-based)
- BW2-01..BW2-12 gate tests

**What does NOT belong:**
- Modification / Cancellation retrieval
- Recovery job (~30 dakika)
- Availability push
- Rates push
- Finance side-effects
