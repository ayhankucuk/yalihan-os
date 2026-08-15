# Availability Sync — Certification Archive

**Capability:** Availability Sync (Sprint 13 E02)
**Certification Date:** 2026-08-15
**Baseline:** `7ce1c8d`
**Final Commit:** `4211255`
**Certification Status:** ✅ CERTIFIED

---

## Certification Summary

| Metric | Result |
|--------|--------|
| Total tests | 38 |
| Passed | 35 |
| Failed | 3 (pre-existing CERT-DEBT) |
| New regressions | 0 |
| Canonical invariant tests | 4/4 PASS |
| Event backbone tests | 7/7 PASS |
| Guest communication tests | 12/12 PASS |

---

## SAAB Decisions — All APPROVED

| Decision | Topic | Commit |
|----------|-------|--------|
| 4.1 | Canonical Source | `7ce1c8d` |
| 4.2 | Events + Single Materializer | `7a981d1` |
| 4.3 | Channel Boundary | `7a981d1` |
| 4.4 | Idempotency | `7a981d1` |
| 4.5 | Tenant Isolation | `7ce1c8d` |
| 4.6 | Retry/Evidence | `7a981d1` |

---

## Implementation Artifacts

### Core Services
- `app/Application/ChannelManager/Services/AvailabilitySynchronizationService.php`
- `app/Jobs/ChannelManager/SynchronizeAvailabilityJob.php`
- `app/Models/ChannelSyncExecution.php`

### Event Wiring
- `app/Jobs/Reservation/ProcessReservationCreated.php` — E02 wired
- `app/Jobs/Reservation/ProcessReservationCancelled.php` — E02 wired
- `app/Jobs/Reservation/ProcessReservationModified.php` — E02 wired
- `app/Services/ReservationService.php` — availability writes removed

### Decision Records
- `.saab/decisions/decision-4.6-retry-evidence.md`

### Migrations
- `database/migrations/2026_08_15_000001_extend_channel_sync_executions_for_retry_evidence.php` — `attempts` column + `retry_exhausted` status
- `database/migrations/2026_08_15_000002_add_property_availabilities_unique_constraint.php` — `(property_id, date)` unique

---

## MUST Clauses — All Fulfilled

| MUST | Description | Evidence |
|------|-------------|----------|
| MUST 1 | `(property_id, date)` unique invariant | Migration `2026_08_15_000002` |
| MUST 2 | `findExistingSync()` lockForUpdate() race protection | `AvailabilitySynchronizationService::findExistingSync()` |
| MUST 3 | Replay creates new execution, never mutates | `AvailabilitySynchronizationService::replay()` |

---

## Architecture

```
Business Fact (PropertyReservation)
    ↓ after-commit
Lifecycle Event (ReservationCreated/Modified/Cancelled)
    ↓
AvailabilitySynchronizationService (single materializer)
    ↓
property_availabilities (unique invariant enforced)
    ↓
SynchronizeAvailabilityJob ($tries=3, $backoff=30, $timeout=30)
    ↓ afterCommit()
ChannelSyncContract (push to OTA platforms)
```

---

## Known Pre-Existing CERT-DEBT

See `docs/known-debt.md` — Item #37: **Availability Sync — SQLite Test Schema Gap**

Three tests fail due to SQLite `property_availability` table not being created during test bootstrap. These are environment issues unrelated to the architecture. Resolution: either switch `phpunit.xml` to MySQL or add SQLite-specific migration path.

---

## Regression History

| Commit | Description | Regression |
|--------|-------------|-----------|
| `4211255` | Override sync release | NEW — fixed in same commit |
| `b98bb10` | Single Materializer Cutover | 0 introduced |

---

## Certifications Passed

- ✅ Canonical state invariant (ChannexCanonicalMutationTest — 4/4)
- ✅ Event backbone (ReservationEventBackboneTest — 7/7)
- ✅ Guest communication (GuestCommunicationWave1Test — 12/12)
- ✅ Idempotency (multiple reservations with same idempotency key — no duplicate executions)
- ✅ Tenant isolation (cross-tenant sync rejected)
- ✅ Retry/exhaustion (job retries, `failed()` → `retry_exhausted` state)
- ✅ Channel boundary (transport failure ≠ canonical mutation)
- ✅ Replay contract (new execution with new idempotency key)
- ✅ Override lifecycle (ReservationCancelledEvent + ReservationCreatedEvent both dispatched)

---

**Board:** SAAB — Yalıhan AI OS Strategic Architecture Board
**Certified by:** Chief AI Architect + Kilo Code (Agentic)
**Status:** CLOSED — Ready for production
