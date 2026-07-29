# G-01 — Capability Evidence

**Sprint 13: Channel Manager — Internal Automation Architecture**
**Date:** 2026-07-29
**Gate:** G-01 — Çalışan Capability

---

## Referenced Commits

| Commit | E | Description |
|--------|---|-------------|
| `4b44ed80` | E01 | Channel Manager Domain Foundation |
| `57d9495a` | E02 | Availability Synchronization Engine |
| `5a4ad2c` | E02 | Synchronization Tests (25 tests) |
| `cbaf1abf` | E03 | Airbnb Adapter Architecture |

---

## Capability Chain — End-to-End Path

```
Canonical Reservation / Block Created
        ↓
[1] AvailabilitySynchronizationService::synchronize()
    ├── Tenant isolation verified
    ├── Idempotency check (existing sync?)
    ├── Canonical PropertyAvailability write (DB)
    └── ChannelSyncExecution record created (immutable)
        ↓
[2] afterCommit → SynchronizeAvailabilityJob dispatched
        ↓
[3] SynchronizeAvailabilityJob::handle()
    ├── ChannelSyncExecution loaded
    ├── Registered channels resolved (IlanTakvimSync)
    └── AirbnbChannelAdapter::pushAvailability() invoked
        ↓
[4] AirbnbChannelAdapter::pushAvailability()
    ├── Tenant-scoped listing mapping resolved
    ├── external_listing_id used (NOT internal property_id)
    ├── AirbnbAvailabilityMapper::mapBatch()
    └── AirbnbAvailabilityRequest built
        ↓
[5] AirbnbClient::updateAvailability()
    └── HMAC-SHA256 signed request sent
        ↓
[6] ChannelApiResponse / SyncResult returned
    ├── Success → SyncResult::success()
    ├── Auth error → non-retryable
    ├── Rate limit → retryable
    └── Rejection → non-retryable
```

---

## Domain Invariants Verified

| Invariant | Enforced By | Evidence |
|-----------|-------------|----------|
| Canonical availability FIRST | `AvailabilitySynchronizationService` | DB write before job dispatch |
| Queue-first execution | `SynchronizeAvailabilityJob::dispatch()->afterCommit()` | Job dispatched after DB commit |
| Idempotent operations | `idempotency_key` = `tenant:prop:res:op:start:end` | Duplicate call returns existing sync |
| Conflict detection | `AvailabilitySyncAggregate::receiveAvailability()` | Different states → conflict event |
| Tenant isolation | `enforceTenantIsolation()` + `BelongsToTenant` | Cross-tenant → RuntimeException |
| Replay safety | `ChannelSyncExecution` immutable + `markProcessed()` | Replay creates new execution |
| Secret sanitization | Exception `toLogContext()` | No credentials in logs |

---

## Capability Summary

**Internal automation chain:** VERIFIED
- Canonical reservation → canonical availability → job dispatch → adapter → response
- Each step produces verifiable artifacts (DB records, events, log entries)

**External connectivity:** BLOCKED (see G-03)

---

## Gate Result

| Gate | Result |
|------|--------|
| **G-01** | ✅ **PASS — Architecture/runtime path demonstrated** |
| External production path | ❌ BLOCKED — No Airbnb API credentials |

---
