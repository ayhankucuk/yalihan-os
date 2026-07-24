# 🏛️ Yalıhan OS — Sprint 19 Charter: Unified Calendar Core & Channel Integration Foundation

**Ratified By:** Strategic Architecture & Automation Board (SAAB)  
**Governance Standard:** Current SAAB Governance Standard (SAB Rule 1 Enforced)  
**Date:** 2026-07-24  
**Status:** AUTHORIZED FOR CHARTER  

---

## 🎯 Primary Business Operation Goal

> **Board Assessment Question:**  
> *"YALIHAN OS, dış bir kanal takvimindeki rezervasyon veya kapalı tarih bilgisini insan müdahalesi olmadan alıp doğrulayabiliyor, çakışmayı engelliyor ve Workspace Unified Calendar'a yansıtabiliyor mu?"*

---

## 📋 Preconditions & Quality Gates

### Certification Debt Prerequisites (`CD-001` ... `CD-005`)
- [ ] **`CD-005` (MUST RESOLVE BEFORE PHASE 2):** Add `UNIQUE (tenant_id, source_event_id, projection_type)` database constraint to Timeline / Projection tables to guarantee replay safety.
- [ ] **`CD-001` Reviewed:** True multi-process DB concurrency barrier test suite.
- [ ] **`CD-002` Reviewed:** Fresh vs Incremental schema parity audit report.
- [ ] **`CD-003` Reviewed:** Database-level UUID constraint verification.
- [ ] **`CD-004` Reviewed:** Migration rollback step-by-step verification logs.

---

## 🚦 SAAB Quality Gates Matrix

| Gate | Success Criteria | Verification Method |
|---|---|---|
| **1. Architecture** | 100% compliant with ADR-038 taxonomy & layers | Preflight AST / Structural audit |
| **2. Tenant Isolation** | Zero cross-tenant data leakage (`tenant_id` scope) | Automated PHPUnit feature tests |
| **3. Replay Safety** | Projection 100% rebuildable from write-side source DB | Replay projection unit tests |
| **4. Idempotency** | Re-importing exact same external event produces 0 duplicate records | Idempotency integration tests |
| **5. Concurrency** | Double booking & range overlap prevented under row lock | Parallel concurrency tests |
| **6. Integration** | Empirical iCal feed import proof creating availability blocks | Automated iCal integration test |
| **7. Documentation** | ADR-038 + Progress Tracker + Bekçi Changelog fully updated | Governance preflight check |
| **8. Certification Debt** | `CD-001` through `CD-005` preconditions reviewed and recorded | Governance Gate |

---

## 🏗️ Architectural Topology & Source of Truth Hierarchy

```
Property (Physical Aggregate Root)
    │
    ▼
Commercial Offering (Terms & Price Aggregate)
    │
    ▼
Reservation Lifecycle (Booking Aggregate Root)
    │
    ▼
Availability Engine (Write-Side Conflict & Range Locks: property_availability_blocks)
    │
    ▼
Unified Calendar Core (Canonical Calendar Projection & Rebuildable Read Model)
    ├─────────────────────────────────┐
    ▼                                 ▼
iCal Import Adapter             Channel Adapter Foundation
    │                                 │
    └────────────────┬────────────────┘
                     ▼
        Raw External Channel Webhook
                     │
                     ▼
          Signature Verification
                     │
                     ▼
           Webhook Inbox Receipt (Raw payload stays here)
                     │
                     ▼
             Deduplication & Normalization
                     │
                     ▼
             Canonical Command
                     │
                     ▼
     Reservation / Availability Transaction
                     │
                     ▼
        Unified Calendar Projection
                     │
                     ▼
     Workspace Timeline (Business-level events only)
```

---

## 🚀 Phased Implementation Plan

### Phase 1 — Reservation Lifecycle State Machine
- **Lifecycle States:** `PENDING` ➔ `CONFIRMED` ➔ `CHECKED_IN` ➔ `CHECKED_OUT` ➔ `CLOSED`.
- **Side Transitions:** `PENDING ➔ EXPIRED`, `PENDING ➔ CANCELLED`, `CONFIRMED ➔ CANCELLED`.
- **Immutable Modification Events:** Reservations modifications will be modelled as immutable domain events (`ReservationDatesChanged`, `GuestCountChanged`, `ReservationTermsChanged`) rather than un-audited state mutations.

### Phase 2 — Unified Calendar Projection Core
- **Projection Engine:** Derived from `PropertyReservation` and `PropertyAvailabilityBlock`.
- **Features:** Property calendar view, multi-property workspace calendar view, projection rebuild/replay endpoint, property timezone support, projection freshness & health indicators.
- **Resilience:** Projection failure MUST NOT roll back canonical reservation/availability DB transactions.

### Phase 3 — iCal Import Adapter Proof
- **Operational Scenario:**
  1. Fetch external iCal feed URL for registered property.
  2. Parse and normalize iCal events into canonical DTOs.
  3. Deduplicate via external event UID within `(tenant_id, channel_code)` scope.
  4. Create or update `property_availability_blocks` via `AvailabilityEngine`.
  5. Release blocks removed from external feed (`status = RELEASED`).
  6. Rebuild `UnifiedCalendar` projection and write business event to `WorkspaceTimeline`.

### Phase 4 — Channel Adapter Foundation (Interface Segregation)
Implement segregated, capability-based interface contracts:
- `ChannelReservationImporter`: For OTAs supporting full reservation payload imports.
- `ChannelAvailabilityImporter`: For channels supporting calendar block imports (iCal, XML).
- `ChannelAvailabilityPublisher`: For pushing rate and availability updates outward.
- `ChannelAdapter`: Metadata provider (`channelCode`, `capabilities`).

---

## 🛡️ Mandatory System Invariants

1. **UID Uniqueness:** External event UID MUST be unique within tenant and channel scope (`tenant_id`, `channel_code`, `external_uid`).
2. **Import Idempotency:** Re-importing the exact same iCal feed item MUST NOT produce duplicate availability blocks.
3. **Traceable Modifications:** Date modifications MUST NOT mutate existing availability blocks silently without emitting an audit trail event.
4. **Feed Removal / Cancellation:** Removal of an event from an external feed MUST trigger `cancelReservation()` / availability block release (`status = RELEASED`, `released_at = now()`).
5. **Tenant Isolation:** Cross-tenant channel connection reading is strictly forbidden (`BelongsToTenant` scope mandatory).
6. **Transaction Isolation:** Projection compilation failures MUST NOT roll back canonical DB transactions.
7. **Replay Integrity:** Projection replays MUST NEVER alter original domain history.
