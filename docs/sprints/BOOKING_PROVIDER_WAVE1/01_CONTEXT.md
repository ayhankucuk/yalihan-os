# 01_CONTEXT.md — Sprint 4.10

## How We Got Here

### Previous Sprint Closing
- **DEBT-001**: `ChannelSyncContract.php` missing — 4/4 FAIL → 4/4 PASS ✅
- **ADR-009**: Booking.com Reservation Provider Architecture — ACCEPTED ✅
- Channel Manager Provider: Channex Wave 1–3 CERTIFIED (`94e85bf`, `9ede781`, `377cc75`)

### What Needs to Happen Next
Booking.com production entegrasyonu Wave 1–5 planlı. Wave 1'in görevi: authentication + property mapping foundation'ı kurmak. Tüm üst katmanlar (retrieval, ACK, persistence) bu iki sütuna dayanıyor. Yanlış foundation = tüm üst katmanlar yanlış olur.

---

## Technical Context

### Architecture

```
ADR-009 Baseline Architecture
──────────────────────────────────────────────────────────────

  BookingConnectivityTransport  ← NEW (Wave 1 foundation)
         │
         ├── BookingAuthTransport     ← NEW (Wave 1)
         │      ├── token acquisition (two-legged, Client ID + Secret)
         │      ├── token cache + expiry
         │      └── 401 → token refresh
         │
         ├── BookingPropertyResolver  ← NEW (Wave 1)
         │      └── HotelCode → ilan_id + tenant isolation
         │
         └── BookingConnectivityAdapter  ← NEW (Wave 2)
                ├── GET /reservations
                └── POST /reservations/{id}/ack

  ChannelReservationContract     ← NEW interface (Wave 1)
  ChannelSyncContract           ← existing
```

### Dependencies

| Dependency | Status | Action |
|------------|--------|--------|
| ADR-009 ACCEPTED | ✅ Ready | Reference |
| `IlanTakvimSync` model | ✅ Ready | Add token fields via migration |
| `ChannelTransportContract` | ✅ Ready | Reference for structure |
| Booking.com credentials | ⏳ Pending | Will NOT be in repo |
| `ChannelReservationContract` | 🔲 Missing | Create in Wave 1 (stub) |
| `BookingAuthTransport` | 🔲 Missing | Create in Wave 1 |
| `BookingPropertyResolver` | 🔲 Missing | Create in Wave 1 |
| `BookingTransport` | 🔲 Missing | Create in Wave 1 |

### Known Pre-Existing Issues

| Issue | Count | Action |
|-------|-------|--------|
| `api_key`/`api_secret` in `IlanTakvimSync` | 1 | Deprecate, add token fields |
| BookingChannelAdapter DISABLED stub | 1 | Wave 1'de aktif etmiyoruz |
| T1 Wave 1 test artifact (wrong tenant) | 1 | Out of scope |

---

## Sprint Boundary

**What belongs to Sprint 4.10:**
- Token auth transport + lifecycle
- Secure credential resolution (secret masking)
- HotelCode → canonical Ilan mapping
- Tenant isolation in property resolver
- Container bindings for new services
- ChannelReservationContract interface
- BW1-01..BW1-10 gate tests

**What does NOT belong:**
- Reservation retrieval API calls
- Acknowledgement
- Canonical persistence
- Recovery job
- Availability/rates push
- Finance side-effects
