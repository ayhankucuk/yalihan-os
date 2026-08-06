# ADR-003: Canonical Conflict Detection Architecture

**Status:** Approved (SAAB Phase 3A)  
**Date:** 2026-08-06  
**Decision Makers:** SAAB, Development Team  
**Related:** CONFLICT_DETECTION Phase 3A  
**Prerequisite:** ADR-002 (Availability Projection Architecture)  

---

## Context

RESERVATION_CORE Phase 2 established a deterministic, event-sourced availability projection. The next capability required is **conflict detection**: the ability to determine whether a requested date range is available for a new reservation.

Prior to this ADR, conflict detection logic was scattered:
1. `ReservationService.createReservation()` — DB-level overlap guard via `lockForUpdate()`
2. `CanonicalAvailabilityService.checkAvailability()` — projection-based availability check
3. No unified `ConflictDetectionService` with a canonical interface

This creates a risk of inconsistency: two code paths that check availability may produce different results.

---

## Decision

We adopt a **canonical, read-only, projection-based Conflict Detection Service** as the single authority on reservation conflicts.

### Core Principles

1. **Projection-based conflict detection.**  
   `ConflictDetectionService` reads `PropertyAvailability` projection. It does not query `PropertyReservation` directly for conflict checks.

2. **Read-only.**  
   `ConflictDetectionService` never writes to the database. No INSERT, UPDATE, or DELETE.

3. **Deterministic.**  
   Same input (`tenantId`, `propertyId`, `startDate`, `endDate`) → same `ConflictResult` every time.

4. **Inclusive-Exclusive date semantics.**  
   `[start_date, end_date)` — end_date is exclusive. A reservation checking in on the same day another checks out is NOT a conflict.

5. **All availability origins respected.**  
   Conflicts are detected regardless of `origin` field (`reservation`, `owner`, `maintenance`, `external`, etc.). Any blocked date is a conflict.

6. **Tenant-isolated.**  
   All queries scoped to `tenant_id`. Cross-tenant data is invisible.

7. **Separation of detection and rejection.**  
   `ConflictDetectionService` reports what it finds. The decision to reject a reservation is an application-layer responsibility (`ReservationService`).

8. **Two-layer protection for PENDING reservations.**  
   See PENDING Semantics section below.

---

## PENDING Reservation Semantics

**This is the critical resolved question from Discovery.**

### Two-Layer Architecture

| Layer | Component | PENDING Behavior |
|-------|-----------|-----------------|
| Layer 1 — Write Guard | `ReservationService.createReservation()` | PENDING **blocks creation** of overlapping reservations (via `lockForUpdate()` on `PropertyReservation`) |
| Layer 2 — Projection | `PropertyAvailability` + `ConflictDetectionService` | PENDING **does NOT appear** as a conflict (not projected to availability) |

### Evidence

**Layer 1 (createReservation):**
```php
// Overlap check — confirmed/pending reservations block dates.
$overlapQuery = PropertyReservation::where('property_id', $propertyId)
    ->whereNotIn('reservation_state', [
        ReservationState::CANCELLED->value,
        ReservationState::COMPLETED->value,
        ReservationState::NO_SHOW->value,
    ]);
// PENDING is NOT in the exclusion list → blocks new reservation creation
```

**Layer 2 (checkAvailability / projection):**
```php
->whereNotIn('reservation_state', $terminalValues)
->where('reservation_state', '!=', ReservationState::PENDING->value)
// PENDING IS excluded → not shown as blocked in availability projection
```

### Canonical Rule

> **PENDING = intent, not commitment.**
>
> A PENDING reservation prevents a second reservation from being created for the same dates (Layer 1 DB guard). However, PENDING does not block availability in the projection and does not appear as a conflict in `ConflictDetectionService` (Layer 2).
>
> This is intentional: PENDING represents an unconfirmed intent. Only CONFIRMED reservations are visible to the availability calendar.

### Conflict State Matrix

| Reservation State | ConflictDetectionService | createReservation guard |
|------------------|--------------------------|------------------------|
| `PENDING` | ❌ Not a conflict (not projected) | ❌ Blocks creation |
| `CONFIRMED` | ✅ Is a conflict | ❌ Blocks creation |
| `CANCELLED` | ❌ Not a conflict | ✅ Allowed |
| `COMPLETED` | ❌ Not a conflict | ✅ Allowed |
| `NO_SHOW` | ❌ Not a conflict | ✅ Allowed |

---

## Architecture

### Canonical Flow

```
New Reservation Request
        │
        ▼
ConflictDetectionService.detect()  ← READ-ONLY
        │
        ▼
ConflictResult
{
  has_conflict: bool,
  conflict_dates: [],
  blocking_sources: [{date, origin, reservation_id}],
  tenant_id, property_id, start_date, end_date
}
        │
        ├── has_conflict = false → ReservationService creates reservation
        │
        └── has_conflict = true → ReservationService rejects
                │
                ▼
        ReservationRejectedForConflict event
        (application-layer decision, not detection-layer)
```

### Detection Flow

```
ConflictDetectionService.detect()
        │
        ▼ Query PropertyAvailability
        (WHERE tenant_id = X AND property_id = Y AND date IN dates AND is_available = false)
        │
        ▼
        Return all blocked dates → ConflictResult
```

### Override Flow (Authorized)

```
Authorized Actor (Admin)
        │
        ▼
Override Request + Reason
        │
        ▼
ConflictOverrideService.override()
        │
        ├── Validate actor authorization
        ├── Record audit entry
        └── Proceed with ReservationService.createReservation()
```

---

## Service Contract

### ConflictDetectionContract

```php
interface ConflictDetectionContract
{
    /**
     * Detect availability conflicts for a date range.
     *
     * READ-ONLY. Never writes to the database.
     * Deterministic: same input → same result.
     * Tenant-isolated: cross-tenant data is invisible.
     *
     * @return ConflictResult
     */
    public function detect(
        int $tenantId,
        int $propertyId,
        string $startDate,  // inclusive
        string $endDate,    // exclusive [start, end)
        ?int $excludeReservationId = null  // exclude this reservation's own blocks
    ): ConflictResult;
}
```

### ConflictResult

```php
final class ConflictResult
{
    public function __construct(
        public readonly bool $hasConflict,
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly array $conflictDates,     // ['2026-08-10', '2026-08-11']
        public readonly array $blockingSources,   // [{date, origin, reservation_id, block_reason}]
        public readonly string $summary,
    ) {}
}
```

---

## Event Architecture

### ConflictDetected (Domain Fact)

```php
class ConflictDetectedEvent
{
    public readonly int $tenantId;
    public readonly int $propertyId;
    public readonly string $requestedStart;
    public readonly string $requestedEnd;
    public readonly array $conflictDates;
    public readonly string $conflictingSource;  // origin of the blocking record
    public readonly ?int $conflictingRecordId;  // reservation_id if applicable
    public readonly string $conflictType;       // 'reservation', 'owner_block', 'maintenance', etc.
    public readonly \DateTimeImmutable $detectedAt;
    public readonly string $correlationId;      // for audit tracing
    // NO personal data, NO financial data
}
```

**Purpose:** Observable fact — conflict existed. Used for audit, alerting, analytics.

### ReservationRejectedForConflict (Application Decision)

```php
class ReservationRejectedForConflictEvent
{
    public readonly int $tenantId;
    public readonly int $propertyId;
    public readonly string $requestedStart;
    public readonly string $requestedEnd;
    public readonly string $rejectionReason;  // 'conflict'
    public readonly int $conflictCount;       // number of blocked dates
    public readonly string $correlationId;
    // NO personal data, NO financial data
}
```

**Purpose:** Application rejected a reservation due to conflict. Separate from detection — detection is a domain fact, rejection is an application decision.

**Important:** `ConflictDetectionService` fires `ConflictDetectedEvent`. `ReservationService` fires `ReservationRejectedForConflictEvent`.

---

## Priority Tier Semantics

Existing `priority_tier` field is preserved. It defines precedence when multiple blocks exist on the same date:

| Tier | Name | Value |
|------|------|-------|
| TIER_MAINTENANCE | Highest (non-overridable) | 1 |
| TIER_RESERVATION | Confirmed reservation | 2 |
| TIER_OWNER_BLOCK | Owner manual block | 3 |
| TIER_EXTERNAL_SYNC | Airbnb/Booking.com | 4 |
| TIER_HOLD_PENDING | Available (sentinel) | 5 |

**Phase 3A constraint:** Detection reports conflicts but does NOT automatically select a winner. Override is explicit, authorized, and audited.

---

## Banned Patterns

```php
// ❌ NEVER: Direct conflict check in controller
class ReservationController {
    public function store() {
        $blocked = PropertyAvailability::where(...)->where('is_available', false)->exists();
    }
}

// ❌ NEVER: ConflictDetectionService writes anything
class ConflictDetectionService {
    public function detect() {
        PropertyAvailability::create(...);  // FORBIDDEN
    }
}

// ❌ NEVER: Auto-override without authorization and audit
class ConflictDetectionService {
    public function detect() {
        if ($hasConflict && $priorityTier > $existingTier) {
            $this->override();  // FORBIDDEN in detection layer
        }
    }
}

// ✅ CORRECT: Read-only detection, application-layer rejection
$result = $this->conflictDetection->detect($tenantId, $propertyId, $start, $end);
if ($result->hasConflict) {
    event(new ReservationRejectedForConflictEvent(...));
    throw new ConflictException($result->summary);
}
```

---

## Required Test Suite (SAAB Mandated)

| Test | Requirement |
|------|-------------|
| `overlapping_confirmed_reservation_is_conflict` | CONFIRMED blocks = conflict |
| `back_to_back_ranges_do_not_conflict` | Aug 10-15 then 15-20 = NO conflict |
| `maintenance_block_is_conflict` | TIER_MAINTENANCE = conflict |
| `owner_block_is_conflict` | TIER_OWNER_BLOCK = conflict |
| `external_block_is_conflict` | TIER_EXTERNAL_SYNC = conflict |
| `cancelled_reservation_is_ignored` | CANCELLED = no conflict |
| `completed_reservation_is_ignored` | COMPLETED = no conflict |
| `no_show_reservation_is_ignored` | NO_SHOW = no conflict |
| `pending_behavior_matches_saab_decision` | PENDING = no conflict in detection |
| `same_input_returns_same_ordered_result` | Deterministic |
| `cross_tenant_records_are_invisible` | Tenant isolation |
| `excluded_reservation_does_not_conflict_with_itself` | excludeReservationId works |
| `conflict_detection_is_read_only` | No DB writes |
| `conflict_event_contains_correlation_data` | correlationId present |
| `reservation_rejection_is_application_layer_decision` | Service separation |
| `override_requires_authorization` | Actor check |
| `override_requires_reason` | Reason mandatory |
| `override_creates_audit_record` | Audit trail |

---

## Alternatives Considered

### Alternative 1: Query PropertyReservation directly

**Rejected:** Slower, bypasses projection layer. Phase 2 established projection as the canonical read model. Drift detector ensures projection accuracy.

### Alternative 2: Merge conflict detection into ReservationService

**Rejected:** Violates single-responsibility. Detection is a domain query; rejection is an application decision.

### Alternative 3: Auto-resolve conflicts via priority_tier

**Deferred:** Phase 3A reports conflicts only. Auto-resolution requires additional business rules and is deferred to Phase 3B+.

---

## Consequences

### Positive

1. **Single authority:** One service owns conflict detection rules.
2. **Deterministic:** Same input → same result (testable, debuggable).
3. **Read-only:** Safe to call multiple times without side effects.
4. **Separation of concerns:** Detection ≠ rejection ≠ override.
5. **Auditable:** Events carry `correlationId` for full audit trail.
6. **Tenant-safe:** Projection queries scoped to `tenant_id`.

### Negative

1. **Two-layer complexity:** PENDING behavior differs between Layer 1 and Layer 2.
2. **Projection dependency:** If drift occurs, conflict detection may report false-negatives until rebuilt.

### Mitigations

- **PENDING complexity:** Documented in this ADR, tested in `pending_behavior_matches_saab_decision`.
- **Projection drift:** Phase 2 `AvailabilityDriftDetector` monitors and alerts. Drift repair via `rebuildAvailabilityProjection()`.

---

## References

- [ADR-002: Availability Projection Architecture](./ADR-002-Availability-Projection-Architecture.md)
- [CONFLICT_DETECTION_DISCOVERY.md](../sprints/CONFLICT_DETECTION_DISCOVERY.md)
- [RESERVATION_CORE Phase 2 Certification](../sprints/RESERVATION_CORE_PHASE2_CERTIFICATION.md)
- Evidence: `ReservationService.createReservation()` line 78-86
- Evidence: `CanonicalAvailabilityService.checkAvailability()` line 61-64, 78-88, 94-105
