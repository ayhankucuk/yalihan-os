# ADR-002: Availability Projection Architecture

**Status:** Approved  
**Date:** 2026-08-06  
**Decision Makers:** SAAB, Development Team  
**Related:** RESERVATION_CORE Phase 2  

---

## Context

Prior to Phase 2, `PropertyAvailability` was treated as a primary data store. Reservation logic directly created, updated, and deleted availability rows. This led to:

1. **Race conditions:** Concurrent reservations could corrupt availability state.
2. **Lack of idempotency:** Replaying a confirmation event could create duplicate blocks.
3. **No replay capability:** Availability could not be rebuilt from reservation history.
4. **Weak tenant isolation:** Cross-tenant writes were possible due to implicit scoping.
5. **No observability:** Drift between reservations and availability was invisible.

The fundamental issue: **Availability was treated as source of truth instead of a derived projection.**

---

## Decision

We adopt an **Event-Sourced Projection Architecture** for `PropertyAvailability`:

### Core Principles

1. **Reservation is the source of truth.**  
   `PropertyReservation` is the canonical aggregate. All availability state must be derivable from reservation history.

2. **Availability is a projection.**  
   `PropertyAvailability` is a read-optimized materialized view, not an independent data store.

3. **Event-driven updates.**  
   Reservation state changes emit domain events (`ReservationConfirmedEvent`, `ReservationCancelledEvent`). Listeners delegate to `AvailabilityProjectionService`.

4. **Idempotent projection.**  
   Same event processed N times produces same availability state. No duplicate blocks.

5. **Replay-safe.**  
   `rebuildAvailabilityProjection()` must produce identical output to incremental runtime projection.

6. **Tenant-isolated.**  
   All projection operations scoped to `tenant_id`. Cross-tenant access blocked.

7. **Drift-aware.**  
   `AvailabilityDriftDetector` provides read-only observability into projection consistency.

---

## Architecture

### Canonical Pipeline

```
┌─────────────────────┐
│ PropertyReservation │  ← Source of Truth
│ (Aggregate Root)    │
└──────────┬──────────┘
           │
           │ State Change
           ▼
┌─────────────────────┐
│ Domain Event        │
│ • Confirmed         │
│ • Cancelled         │
└──────────┬──────────┘
           │
           │ Async (Queued)
           ▼
┌─────────────────────┐
│ Listener            │
│ • ProjectConfirmed  │
│ • ReleaseCancelled  │
└──────────┬──────────┘
           │
           │ Delegate
           ▼
┌──────────────────────────┐
│ AvailabilityProjection   │
│ Service                  │
│ • projectConfirm()       │
│ • projectCancel()        │
│ • getProjection()        │
│ • isProjectionComplete() │
└──────────┬───────────────┘
           │
           │ Write
           ▼
┌─────────────────────┐
│ PropertyAvailability│  ← Projection (Read Model)
│ (Materialized View) │
└─────────────────────┘
```

### Replay Path (Rebuild)

```
┌─────────────────────────┐
│ CanonicalAvailability   │
│ Service                 │
│ rebuildAvailability     │
│ Projection()            │
└──────────┬──────────────┘
           │
           │ Query all CONFIRMED reservations
           ▼
┌─────────────────────┐
│ PropertyReservation │
└──────────┬──────────┘
           │
           │ Generate date blocks
           ▼
┌─────────────────────┐
│ PropertyAvailability│
│ (Insert Batch)      │
└─────────────────────┘
```

### Drift Detection

```
┌──────────────────────────┐
│ AvailabilityDriftDetector│
│ • detect()               │
│ • detectForTenant()      │
└──────────┬───────────────┘
           │
           │ Compare
           ▼
┌──────────────────────────┐
│ Expected Blocks          │  ← From PropertyReservation
│ (CONFIRMED reservations) │
└──────────────────────────┘
           │
           │ vs
           ▼
┌──────────────────────────┐
│ Actual Blocks            │  ← From PropertyAvailability
│ (reservation origin)     │
└──────────────────────────┘
           │
           │ Report
           ▼
    MISSING_BLOCK / PHANTOM_BLOCK
```

---

## Consequences

### Positive

1. **Idempotency:** Event replay safe — no duplicate blocks.
2. **Replay capability:** Full rebuild from reservation history.
3. **Tenant isolation:** Enforced at service layer, cross-tenant writes impossible.
4. **Observability:** Drift detector reveals projection inconsistencies.
5. **Channel Manager readiness:** Solid foundation for multi-channel sync.
6. **Testability:** Deterministic projection → predictable test outcomes.
7. **Auditability:** Event log provides full audit trail.

### Negative

1. **Complexity:** Event-sourced architecture requires more components than direct writes.
2. **Async delay:** Projection updates happen via queued listeners (eventual consistency).
3. **Drift repair manual:** Detector is read-only — repair requires explicit `rebuildAvailabilityProjection()` call.
4. **Legacy compatibility:** Yazlik rezervasyonlar table (no `tenant_id` column) requires JOIN-based tenant scoping.

### Mitigations

- **Complexity:** Offset by comprehensive test coverage (120 tests, 389 assertions).
- **Async delay:** Acceptable for availability updates — not user-facing.
- **Drift repair:** Future: auto-remediation job triggered by drift detection.
- **Legacy compatibility:** Yazlik integration explicitly documented and tested (E03).

---

## Implementation Details

### Key Services

#### AvailabilityProjectionService

**Responsibility:** Deterministic, idempotent projection write path.

**Public API:**
```php
interface AvailabilityProjectionContract
{
    public function projectConfirm(
        int $reservationId,
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): array;

    public function projectCancel(
        int $reservationId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): array;

    public function getProjection(int $reservationId, int $tenantId): array;
    public function isProjectionComplete(...): bool;
    public function validateTenantPropertyMatch(int $tenantId, int $propertyId): bool;
}
```

**Idempotency Mechanism:**
```php
// Load all existing rows for date range with lockForUpdate
$existingRows = PropertyAvailability::where('property_id', $propertyId)
    ->whereIn('date', $dates)
    ->lockForUpdate()
    ->get();

// Skip dates already blocked by THIS reservation
$alreadyProjectedDates = $existingRows
    ->filter(fn($r) => !$r->is_available && $r->reservation_id === $reservationId)
    ->keys()->all();

// Early return if full projection exists
if (count($alreadyProjectedDates) === count($dates)) {
    return ['success' => true, 'idempotent' => true];
}
```

#### CanonicalAvailabilityService

**Responsibility:** Rebuild projection from canonical reservation history.

**Key Method:**
```php
public function rebuildAvailabilityProjection(
    int $tenantId,
    int $propertyId,
    string $startDate,
    string $endDate
): int;
```

**Rebuild Algorithm:**
1. Delete origin-scoped rows (`reservation`, `yazlik`) for date range.
2. Query CONFIRMED `PropertyReservation` records (tenant-scoped).
3. Query active `yazlik_rezervasyonlar` records (JOIN on `ilanlar.tenant_id`).
4. Generate date blocks for each reservation.
5. Batch insert with `projection_source = 'rebuild'`.

#### AvailabilityDriftDetector

**Responsibility:** Read-only projection consistency verification.

**Key Methods:**
```php
public function detect(
    int $tenantId,
    int $propertyId,
    string $startDate,
    string $endDate
): array;

public function detectForTenant(
    int $tenantId,
    string $startDate,
    string $endDate
): array;
```

**Drift Types:**
- `MISSING_BLOCK`: Confirmed reservation exists but no availability block.
- `PHANTOM_BLOCK`: Availability block exists but no matching confirmed reservation.

---

## Domain Events

### ReservationConfirmedEvent

```php
class ReservationConfirmedEvent
{
    public readonly int $reservationId;
    public readonly int $tenantId;
    public readonly int $propertyId;
    public readonly string $startDate;
    public readonly string $endDate;
    public readonly int $nights;
    public readonly string $guestName;
    public readonly ReservationState $previousState;
    public readonly ?int $confirmedBy;
}
```

### ReservationCancelledEvent

```php
class ReservationCancelledEvent
{
    public readonly int $reservationId;
    public readonly int $tenantId;
    public readonly string $startDate;
    public readonly string $endDate;
    public readonly ?string $cancellationReason;
    public readonly ?int $cancelledBy;
}
```

---

## Testing Strategy

### Test Coverage Matrix

| Capability | Suite | Tests |
|-----------|-------|-------|
| Projection Foundation | AvailabilityProjectionFoundationTest | 5 |
| Idempotency | AvailabilityProjectionIdempotencyTest | 5 |
| Replay Safety | AvailabilityProjectionReplayTest | 6 |
| Tenant Isolation | AvailabilityProjectionTenantIsolationTest | 7 |
| Drift Detection | DriftDetectionE05Test | 7 |
| Tenant Aggregation | AvailabilityDriftDetectorTenantTest | 4 |

### Test Principles

1. **Isolation:** Each test creates its own tenant/property/reservation.
2. **Determinism:** No flaky tests — all date ranges far in future.
3. **Idempotency verification:** Call projection methods 2-3 times, assert same result.
4. **Tenant scope verification:** Create cross-tenant scenarios, assert isolation.
5. **Read-only verification:** Capture before/after counts, assert no change.

---

## Alternatives Considered

### Alternative 1: Direct Availability Writes

**Rejected:** No idempotency, no replay, no drift detection.

### Alternative 2: Synchronous Projection

**Rejected:** Couples reservation write to availability write. Performance impact.

### Alternative 3: Auto-remediation on Drift

**Deferred:** Phase 2 establishes read-only detection. Auto-fix deferred to Phase 3.

---

## Migration Path

### Phase 1 → Phase 2 Migration

1. ✅ Introduce `AvailabilityProjectionService` (E01).
2. ✅ Wire domain events and listeners (E01).
3. ✅ Add idempotency guarantees (E02).
4. ✅ Implement `rebuildAvailabilityProjection()` (E03).
5. ✅ Enforce tenant isolation (E04).
6. ✅ Add drift detection (E05).
7. ✅ Regression testing: 120/120 PASS.

### Backward Compatibility

- Legacy `yazlik_rezervasyonlar` supported via JOIN on `ilanlar.tenant_id`.
- Direct `PropertyAvailability` writes **deprecated** but not yet removed (Phase 3).

---

## Monitoring & Observability

### Metrics (Future)

- `availability_projection_lag_seconds`: Time between event dispatch and projection completion.
- `availability_drift_count`: Number of properties with drift.
- `availability_drift_severity`: MISSING_BLOCK vs PHANTOM_BLOCK ratio.

### Alerting (Future)

- Alert if drift count > threshold.
- Alert if projection lag > 60 seconds.

---

## References

- [RESERVATION_CORE Phase 2 Certification](../sprints/RESERVATION_CORE_PHASE2_CERTIFICATION.md)
- [RESERVATION_CORE Charter](../sprints/RESERVATION_CORE_CHARTER.md)
- Commit Chain: `4e640660` (E01), `2ef65cf` (E02), `98903dc` (E03), `4265a13` (E04), `f53ea12` (E05)
- Test Evidence: 120 tests, 389 assertions, 100% PASS
