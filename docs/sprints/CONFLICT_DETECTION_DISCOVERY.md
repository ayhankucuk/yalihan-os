# CONFLICT_DETECTION — Discovery Charter

**Status:** 🟡 DISCOVERY  
**Charter Date:** 2026-08-06  
**Author:** WenOX (ön araştırma) + SAAB onayı gerekli  
**Type:** Capability Research & Design  
**Prerequisite:** ✅ RESERVATION_CORE Phase 2 CERTIFIED  

---

## Mission

Determine the canonical conflict detection rules for YALIHAN OS reservation system. Establish deterministic, transaction-safe, tenant-scoped conflict detection that prevents double-booking across all reservation sources.

**SAAB Success Question:**  
_"Can YALIHAN detect reservation conflicts on the same property in a transaction-safe, tenant-safe, and deterministic manner, and reject the second reservation?"_

---

## Scope

### In-Scope

1. **Date overlap semantics:** What constitutes a conflict?
2. **Reservation state priority:** Which states block availability?
3. **Availability source priority:** Reservation, Owner Block, Maintenance, External Channel, Operational.
4. **Override policy:** Who can override a conflict?
5. **Deterministic result:** Same input → same conflict result.
6. **Service contract:** `ConflictDetectionService` interface design.
7. **Event design:** `ConflictDetectedEvent`, `ReservationRejectedEvent`.

### Out-of-Scope (Phase 3B+)

- UI/Calendar visualization
- Channel Manager integration (Airbnb, Booking.com)
- Auto-remediation
- Pricing Engine
- Operational Calendar UI

---

## Critical Decision Points

### 1. Date Overlap Rule (CRITICAL)

**Question:**  
What is the canonical overlap rule for check-in/check-out?

**Example Scenario:**
```
Reservation A: check_in = 2026-08-10, check_out = 2026-08-15
Reservation B: check_in = 2026-08-15, check_out = 2026-08-20
```

**Is this a conflict?**

#### Option A: Inclusive-Exclusive (Hotel Standard)
- `[start_date, end_date)` — end_date is exclusive
- Reservation A blocks: Aug 10, 11, 12, 13, 14
- Reservation B blocks: Aug 15, 16, 17, 18, 19
- **Result:** NO CONFLICT (Aug 15 check-in allowed)

#### Option B: Inclusive-Inclusive (Conservative)
- `[start_date, end_date]` — both inclusive
- Reservation A blocks: Aug 10, 11, 12, 13, 14, 15
- Reservation B blocks: Aug 15, 16, 17, 18, 19, 20
- **Result:** CONFLICT (Aug 15 blocked)

**Repository Evidence Needed:**
- Check `PropertyAvailability` projection logic in `AvailabilityProjectionService`
- Check `ReservationService.createReservation()` validation
- Check `CanonicalAvailabilityService.checkAvailability()` logic

**Current Implementation Hypothesis:**
Based on `AvailabilityProjectionService.generateDateRange()`:
```php
while ($current->lt($end)) {  // lt = less than (exclusive end)
    $dates[] = $current->format('Y-m-d');
    $current->addDay();
}
```
**Hypothesis:** Inclusive-Exclusive (Option A) is already implemented.

**Decision Required:** ✅ Confirm and document as canonical rule.

---

### 2. Reservation State Priority Matrix

**Question:**  
Which reservation states produce conflicts?

| State | Conflicts with New Reservation? | Rationale |
|-------|----------------------------------|-----------|
| `PENDING` | ❓ **DECISION NEEDED** | Should pending reservations block availability? |
| `CONFIRMED` | ✅ YES | Canonical blocking state |
| `CANCELLED` | ❌ NO | Terminal, released |
| `COMPLETED` | ❌ NO | Terminal, stay finished |
| `NO_SHOW` | ❓ **DECISION NEEDED** | Does no-show keep the block? |

**Repository Evidence Needed:**
- Check `ReservationState.isTerminal()` enum method
- Check `CanonicalAvailabilityService.checkAvailability()` state filtering
- Check `rebuildAvailabilityProjection()` state exclusions

**Current Implementation:**
Phase 2 uses:
```php
->whereNotIn('reservation_state', array_map(
    fn(ReservationState $s) => $s->value,
    array_filter(ReservationState::cases(), fn(ReservationState $s) => $s->isTerminal())
))
->whereNotIn('reservation_state', [ReservationState::PENDING->value])
```

**Hypothesis:**  
- CONFIRMED → blocks
- PENDING → does NOT block (guests can hold multiple pending, only one confirms)
- Terminal states (CANCELLED, COMPLETED, NO_SHOW) → do NOT block

**Decision Required:** ✅ Confirm PENDING + NO_SHOW semantics.

---

### 3. Availability Source Priority

**Question:**  
What sources can create conflicts, and what is their priority?

| Source | Origin Field | Blocks New Reservation? | Can Be Overridden By |
|--------|-------------|-------------------------|----------------------|
| Reservation (CONFIRMED) | `reservation` | ✅ YES | Admin |
| Owner Block | `owner` | ❓ **DECISION NEEDED** | Owner, Admin |
| Maintenance | `maintenance` | ❓ **DECISION NEEDED** | Admin |
| External Channel (Airbnb) | `external` | ❓ **DECISION NEEDED** | Admin (manual sync) |
| Operational | `operational` | ❓ **DECISION NEEDED** | Admin |

**Repository Evidence Needed:**
- Check `PropertyAvailabilityContract` origin constants
- Check `CanonicalAvailabilityService.checkAvailability()` — does it respect origin?
- Check `priority_tier` field usage

**Current Implementation:**
Phase 2 has `origin` field:
```php
const ORIGIN_RESERVATION  = 'reservation';
const ORIGIN_OWNER        = 'owner';
const ORIGIN_MAINTENANCE  = 'maintenance';
const ORIGIN_EXTERNAL     = 'external';
const ORIGIN_OPERATIONAL  = 'operational';
const ORIGIN_YAZLIK       = 'yazlik';
```

**Decision Required:**  
Should conflict detection respect **all** origins, or only `reservation` origin?

**Hypothesis:**  
Owner blocks, maintenance, and external channel blocks **should** produce conflicts (prevent double-booking). Conflict detection must check ALL blocked dates regardless of origin.

---

### 4. Override Policy

**Question:**  
Who can override a conflict and force a reservation?

| Actor | Can Override Conflict? | Audit Required? |
|-------|------------------------|-----------------|
| Admin | ✅ YES | ✅ YES |
| Owner | ❓ **DECISION NEEDED** | ✅ YES |
| Agent | ❌ NO | — |
| System (auto) | ❌ NO | — |

**Service Design Implication:**
```php
public function detectConflict(
    int $tenantId,
    int $propertyId,
    string $startDate,
    string $endDate,
    ?int $actorId = null,
    bool $allowOverride = false
): ConflictResult;
```

**Decision Required:** Define override rules and audit trail design.

---

### 5. Deterministic Result Guarantee

**Question:**  
How do we ensure same input always produces same conflict result?

**Requirements:**
1. **Idempotency:** Calling `detectConflict()` N times → same result.
2. **No race conditions:** Two concurrent conflict checks → consistent result.
3. **Read-only:** `detectConflict()` writes nothing.

**Repository Evidence:**
Phase 2 `AvailabilityDriftDetector` is read-only (proven in E05). Conflict detection should follow same pattern.

**Decision Required:**  
Should conflict detection use:
- **Option A:** `PropertyAvailability` read-only query (fast, projection-based)
- **Option B:** `PropertyReservation` source-of-truth query (slower, canonical)
- **Option C:** Both (check projection first, validate against source)

**Hypothesis:** Option A (projection-based) is sufficient, since Phase 2 established projection correctness via drift detection.

---

## Proposed Service Contract

### ConflictDetectionService

```php
interface ConflictDetectionContract
{
    /**
     * Detect conflicts for a date range on a property.
     * 
     * @return ConflictResult {
     *   has_conflict: bool,
     *   conflict_dates: array,
     *   blocking_sources: array [
     *     {date, source_origin, reservation_id, block_reason}
     *   ],
     *   can_override: bool,
     *   message: string
     * }
     */
    public function detectConflict(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $actorId = null,
        bool $checkOverridePermission = false
    ): ConflictResult;

    /**
     * Validate that a reservation can be created (conflict + business rules).
     */
    public function validateReservation(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        array $reservationData
    ): ValidationResult;
}
```

### ConflictResult DTO

```php
class ConflictResult
{
    public bool $hasConflict;
    public array $conflictDates;      // ['2026-08-10', '2026-08-11']
    public array $blockingSources;    // [{date, origin, reservation_id, block_reason}]
    public bool $canOverride;
    public string $message;           // Human-readable conflict explanation
    public int $tenantId;
    public int $propertyId;
    public string $startDate;
    public string $endDate;
}
```

---

## Event Design

### ConflictDetectedEvent

```php
class ConflictDetectedEvent
{
    public readonly int $tenantId;
    public readonly int $propertyId;
    public readonly string $startDate;
    public readonly string $endDate;
    public readonly array $conflictDates;
    public readonly array $blockingSources;
    public readonly ?int $attemptedBy;
}
```

**Purpose:** Audit trail, alerting, analytics.

### ReservationRejectedEvent

```php
class ReservationRejectedEvent
{
    public readonly int $tenantId;
    public readonly int $propertyId;
    public readonly string $startDate;
    public readonly string $endDate;
    public readonly string $rejectionReason; // 'conflict', 'min_stay', 'blackout', etc.
    public readonly array $conflictDetails;
    public readonly ?int $rejectedBy;
}
```

**Purpose:** Track why reservations fail (for UX improvement).

---

## Integration Points

### With ReservationService

`ReservationService.createReservation()` should call `ConflictDetectionService.validateReservation()` **before** creating `PropertyReservation`.

### With PropertyAvailability

Conflict detection reads `PropertyAvailability` projection (established in Phase 2).

### With AvailabilityDriftDetector

If conflict detection and drift detection disagree, drift detector reveals projection inconsistency (observability).

---

## Findings (Repository Evidence)

### Finding 1: Date Overlap Rule — INCLUSIVE-EXCLUSIVE ✅ CONFIRMED

**Evidence from `CanonicalAvailabilityService.checkAvailability()` line 61-64:**
```php
while ($cursor->lt($end)) {  // lt = less than → end_date EXCLUSIVE
    $dates[] = $cursor->format('Y-m-d');
    $cursor->addDay();
}
```

**Evidence from `rebuildAvailabilityProjection()` line 471:**
```php
if ($d->gte($rStart) && $d->lt($rEnd) && !isset($blockedIndex[$dateStr])) {
```

**Confirmed:** YALIHAN uses **Inclusive-Exclusive** `[start_date, end_date)`.
- Reservation Aug 10–15 → blocks Aug 10, 11, 12, 13, 14 (5 nights)
- New reservation check_in = Aug 15 → **NO CONFLICT** ✅

**Decision:** Canonical rule already implemented. No change needed.

---

### Finding 2: Reservation State Priority — ONLY CONFIRMED ✅ CONFIRMED

**Evidence from `ReservationState.isTerminal()` (ReservationState.php line 34-41):**
```php
return in_array($this, [self::CANCELLED, self::COMPLETED, self::NO_SHOW]);
```

**Evidence from `checkAvailability()` line 82-88:**
```php
$conflictingReservations = PropertyReservation::where('tenant_id', $tenantId)
    ->whereNotIn('reservation_state', $terminalValues)   // excludes CANCELLED, COMPLETED, NO_SHOW
    ->where('reservation_state', '!=', ReservationState::PENDING->value)  // excludes PENDING
    ->whereNull('cancelled_at')
    ->get();
```

**Confirmed State Matrix:**

| State | Blocks Availability | Rationale |
|-------|---------------------|-----------|
| `PENDING` | ❌ NO | Not confirmed, does not hold availability |
| `CONFIRMED` | ✅ YES | Canonical blocking state |
| `CANCELLED` | ❌ NO | Terminal — released via `projectCancel()` |
| `COMPLETED` | ❌ NO | Terminal — stay finished |
| `NO_SHOW` | ❌ NO | Terminal — availability already resolved |

**Decision:** Existing logic is correct. Only CONFIRMED creates conflicts.

---

### Finding 3: Availability Source Priority — ALL ORIGINS RESPECTED ✅ CONFIRMED

**Evidence from `checkAvailability()` line 94-105:**
```php
if ($rec && !$rec->is_available) {
    $conflicts[] = [
        'date'           => $dateStr,
        'origin'         => $rec->origin,  // ← ALL origins included
        'reservation_id' => $rec->reservation_id,
    ];
}
```

**Confirmed:** `checkAvailability()` returns conflicts for **ANY** blocked row (`!is_available`), regardless of origin. Owner blocks, maintenance, external channel, and reservation blocks all produce conflicts.

**Decision:** Conflict detection must respect ALL blocked dates, regardless of origin.

---

### Finding 4: Override Policy — PRIORITY_TIER EXISTS, ROLE-BASED MISSING ⚠️

**Evidence from `blockDateRange()` priority comparison:**
```php
const TIER_MAINTENANCE   = 1;  // Highest priority
const TIER_RESERVATION   = 2;
const TIER_OWNER_BLOCK   = 3;
const TIER_EXTERNAL_SYNC = 4;
const TIER_HOLD_PENDING  = 5;  // Lowest (available)
```

Lower `priority_tier` value = higher priority.

**Gap:** No explicit role-based override (Admin vs Owner vs Agent).

**Decision:** Phase 3A adds `allowOverride` flag + actor role check, without changing existing priority_tier system.

---

### Finding 5: Deterministic Result — READ-ONLY, PROJECTION-BASED ✅ CONFIRMED

**Evidence:** `checkAvailability()` is read-only — no INSERT/UPDATE/DELETE.
**Evidence:** Phase 2 E03 proves `PropertyAvailability` projection is deterministic.

**Decision:** Conflict detection uses `PropertyAvailability` projection (Option A) — fast and sufficient.

---

## Research Tasks Status

| Task | Status | Evidence |
|------|--------|---------|
| 1. Date Overlap Semantics | ✅ COMPLETE | `checkAvailability()` line 61-64 |
| 2. Reservation State Priority | ✅ COMPLETE | `ReservationState.isTerminal()`, line 82-88 |
| 3. Availability Source Priority | ✅ COMPLETE | `checkAvailability()` line 94-105 |
| 4. Override Policy | ⚠️ PARTIAL | priority_tier exists, role-based extension needed |
| 5. Service Contract Design | 🟡 IN PROGRESS | Interface designed, needs SAAB review |

---

## Success Criteria (Discovery Phase)

- [x] Date overlap rule documented and confirmed via code evidence
- [x] Reservation state priority matrix finalized
- [x] Availability source priority matrix finalized
- [x] Override policy documented (priority_tier system + role-based extension needed)
- [ ] `ConflictDetectionContract` interface approved by SAAB
- [ ] Event designs approved
- [ ] ADR-003 draft: Conflict Detection Architecture

---

## Out-of-Scope (Reminder)

**NOT in this discovery:**
- Implementation code
- Test suites
- UI/Calendar changes
- Channel Manager integration
- Pricing rules

**Those belong in Phase 3A Implementation Sprint** (after SAAB charter approval).

---

## References

- [RESERVATION_CORE Phase 2 Certification](./RESERVATION_CORE_PHASE2_CERTIFICATION.md)
- [ADR-002: Availability Projection Architecture](../adrs/ADR-002-Availability-Projection-Architecture.md)
- [RESERVATION_CORE Charter](./RESERVATION_CORE_CHARTER.md)

---

## Next Steps

1. **WenOX:** Complete research tasks (evidence gathering from codebase).
2. **WenOX:** Submit findings to SAAB for review.
3. **SAAB:** Review and approve decision points.
4. **WenOX:** Write ADR-003: Conflict Detection Architecture (draft).
5. **SAAB:** Approve charter → Phase 3A Implementation Sprint can begin.
