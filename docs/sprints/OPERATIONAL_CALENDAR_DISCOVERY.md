# OPERATIONAL_CALENDAR — Discovery Charter

**Status:** 🟡 DISCOVERY  
**Charter Date:** 2026-08-06  
**Author:** WenOX (research) + SAAB onayı gerekli  
**Prerequisite:** ✅ OVERRIDE_AUTHORIZATION Phase 3C CERTIFIED  
**SAAB Authorization:** 🟢 AUTHORIZED  

---

## Mission

Provide a single, unified operational timeline view of a property's availability state by aggregating all sources from the canonical `PropertyAvailability` projection.

**Key Constraint:**  
Operational Calendar writes **no new conflict rules** and **no new availability records**. It reads from the existing canonical projection and presents a deterministic, ordered timeline.

---

## SAAB Success Question

_"Does YALIHAN produce a single, deterministic operational calendar timeline that correctly reflects reservations, owner blocks, maintenance, external channel blocks, and override decisions — all from the canonical availability projection?"_

---

## Scope

### In-Scope

1. **Read-only timeline aggregation** from `PropertyAvailability`
2. **Single timeline** covering all origin types:
   - `reservation` — confirmed guest reservations
   - `owner` — owner personal use / hold
   - `maintenance` — repair / maintenance blocks
   - `ical` / `airbnb` / `booking` — external channel blocks
   - `manual` — admin manual blocks
   - `system` — automated system blocks
3. **Date range query** — tenant-scoped, inclusive-exclusive [start, end)
4. **CalendarEntry DTO** — immutable, origin-aware, priority-aware
5. **Aggregated summary** — total blocked days, available days, source breakdown

### Out-of-Scope

- Writing new availability records (use `CanonicalAvailabilityService`)
- Conflict detection (use `ConflictDetectionService`)
- Override decisions (use `ConflictOverrideService`)
- External sync / iCal parsing (Channel Manager — Phase 3E)
- UI/Calendar visualization

---

## Architecture Decision

### Why Not Extend CanonicalAvailabilityService?

`CanonicalAvailabilityService` is the write path. The Operational Calendar is a read path. Mixing responsibilities would violate single-responsibility and make the write path heavier.

**Decision:** Separate read-only `OperationalCalendarService` that delegates to `PropertyAvailability` queries.

### Relation to Existing Services

```
PropertyAvailability (SSOT projection)
        │
        ├── Write path: CanonicalAvailabilityService.blockDateRange()
        ├── Write path: AvailabilityProjectionService.projectConfirm()
        ├── Read path: ConflictDetectionService.detect()       ← Phase 3A
        ├── Read path: AvailabilityDriftDetector.detect()     ← Phase 2 E05
        └── Read path: OperationalCalendarService.getCalendar() ← THIS
```

---

## Data Model Analysis

### PropertyAvailability — Existing Fields

| Field | Type | Usage in Calendar |
|-------|------|------------------|
| `tenant_id` | int | Tenant isolation |
| `property_id` | int | Property filter |
| `date` | date | Timeline date |
| `is_available` | bool | Available vs blocked |
| `block_reason` | string | Human-readable reason |
| `priority_tier` | int | 1=maintenance, 2=reservation, 3=owner, 4=external |
| `origin` | string | Source: reservation/owner/maintenance/ical/airbnb/booking/manual |
| `reservation_id` | int? | Links to PropertyReservation |
| `source_system` | string | internal/airbnb/booking/ical |
| `projection_source` | string | rebuild/reservation/block/external_sync |
| `projection_generated_at` | datetime | When this row was last written |

### Origin → Calendar Entry Type Mapping

| Origin | Calendar Entry Type | Priority |
|--------|---------------------|----------|
| `reservation` | CONFIRMED_RESERVATION | 2 |
| `yazlik` | LEGACY_RESERVATION | 2 |
| `owner` | OWNER_BLOCK | 3 |
| `maintenance` | MAINTENANCE | 1 |
| `airbnb` | AIRBNB_BLOCK | 4 |
| `booking` | BOOKING_BLOCK | 4 |
| `ical` | EXTERNAL_BLOCK | 4 |
| `manual` | MANUAL_BLOCK | 3 |
| `system` | SYSTEM_BLOCK | 3 |
| `null` + `is_available=true` | AVAILABLE | - |

---

## Service Contract

### OperationalCalendarContract

```php
interface OperationalCalendarContract
{
    /**
     * Get the operational calendar for a property within a date range.
     *
     * READ-ONLY. Never writes to the database.
     * Deterministic: same input → same CalendarView every time.
     * Tenant-isolated: cross-tenant data invisible.
     *
     * @param int    $tenantId
     * @param int    $propertyId
     * @param string $startDate  Inclusive (YYYY-MM-DD)
     * @param string $endDate    Exclusive (YYYY-MM-DD)
     *
     * @return CalendarView
     * @throws \InvalidArgumentException When startDate >= endDate
     */
    public function getCalendar(
        int    $tenantId,
        int    $propertyId,
        string $startDate,
        string $endDate
    ): CalendarView;
}
```

### CalendarView DTO

```php
final class CalendarView
{
    public readonly int    $tenantId;
    public readonly int    $propertyId;
    public readonly string $startDate;
    public readonly string $endDate;
    public readonly int    $totalNights;
    public readonly int    $availableNights;
    public readonly int    $blockedNights;
    public readonly array  $entries;    // CalendarEntry[]
    public readonly array  $summary;    // ['reservation'=>N, 'owner'=>N, ...]
}
```

### CalendarEntry DTO

```php
final class CalendarEntry
{
    public readonly string  $date;
    public readonly bool    $isAvailable;
    public readonly string  $entryType;   // CONFIRMED_RESERVATION, OWNER_BLOCK, etc.
    public readonly string  $origin;      // reservation/owner/maintenance/airbnb...
    public readonly int     $priorityTier;
    public readonly ?string $blockReason;
    public readonly ?int    $reservationId;
    public readonly string  $sourceSystem; // internal/airbnb/booking/ical
}
```

---

## Required Test Suite (SAAB Mandated)

| Test | Requirement |
|------|-------------|
| `calendar_returns_all_dates_in_range` | Every day in [start, end) has an entry |
| `confirmed_reservation_appears_as_blocked` | Origin=reservation → CONFIRMED_RESERVATION |
| `owner_block_appears_in_calendar` | Origin=owner → OWNER_BLOCK |
| `maintenance_block_appears_in_calendar` | Origin=maintenance → MAINTENANCE |
| `external_block_appears_in_calendar` | Origin=airbnb → AIRBNB_BLOCK |
| `available_dates_are_correctly_reported` | is_available=true → AVAILABLE entry |
| `calendar_is_tenant_scoped` | Cross-tenant blocks invisible |
| `calendar_is_read_only` | No DB writes on getCalendar() |
| `calendar_is_deterministic` | Same input → same output N times |
| `summary_counts_are_accurate` | Summary totals match entry counts |
| `priority_tier_is_correctly_mapped` | TIER_MAINTENANCE=1 → priorityTier=1 |
| `calendar_service_is_bound_in_container` | AppServiceProvider binding |

---

## Banned Patterns

```php
// ❌ NEVER: Calendar writes availability records
class OperationalCalendarService {
    public function getCalendar() {
        PropertyAvailability::create(...); // FORBIDDEN
    }
}

// ❌ NEVER: Calendar makes conflict decisions
class OperationalCalendarService {
    public function getCalendar() {
        if ($entry->priority_tier > $other->priority_tier) {
            // This is ConflictDetectionService's job
        }
    }
}

// ✅ CORRECT: Read-only aggregation
$rows = PropertyAvailability::where('tenant_id', $tenantId)
    ->where('property_id', $propertyId)
    ->whereIn('date', $dates)
    ->orderBy('date')
    ->get();
```

---

## Findings (Repository Evidence)

### Finding 1: PropertyAvailability SSOT ✅

`PropertyAvailability` already contains all data needed:
- `origin` field covers all sources
- `priority_tier` defines ordering
- `is_available` defines blocked state
- `reservation_id` links to reservations
- `tenant_id` ensures isolation

**No new tables or columns needed.**

### Finding 2: AvailabilityService DEPRECATED ✅

`app/Services/Calendar/AvailabilityService` is a stub (always returns `false`). Operational Calendar does not extend or replace it — it's a separate read path on `PropertyAvailability`.

### Finding 3: No Existing Unified Timeline

No existing service aggregates all origins into a single calendar view. `checkAvailability()` returns conflicts, not a full timeline. Operational Calendar fills this gap.

### Finding 4: Date Semantics Confirmed ✅

`PropertyAvailability.scopeForDateRange()`:
```php
return $query->where('date', '>=', $startDate)
             ->where('date', '<', $endDate);
```
Inclusive-Exclusive confirmed. Calendar must use same semantics.

---

## Success Criteria (Discovery Phase)

- [x] All origin types mapped to CalendarEntry types
- [x] Data model analysis complete (no new schema needed)
- [x] Service contract designed
- [x] Test suite mandated
- [x] ADR-004 scope defined
- [ ] `OperationalCalendarContract` interface approved by SAAB
- [ ] Implementation sprint authorized

---

## Next Steps

1. SAAB review and approve `OperationalCalendarContract`
2. Implementation Sprint:
   - `OperationalCalendarContract`
   - `CalendarView` + `CalendarEntry` DTOs
   - `OperationalCalendarService`
   - AppServiceProvider binding
   - Test suite (12 tests)
3. ADR-004: Operational Calendar Architecture
4. SAAB certification

---

## References

- [ADR-002: Availability Projection Architecture](../adrs/ADR-002-Availability-Projection-Architecture.md)
- [ADR-003: Canonical Conflict Detection Architecture](../adrs/ADR-003-Canonical-Conflict-Detection-Architecture.md)
- [CONFLICT_DETECTION_DISCOVERY.md](./CONFLICT_DETECTION_DISCOVERY.md)
- Evidence: `PropertyAvailabilityContract` origin/tier constants
- Evidence: `PropertyAvailability.scopeForDateRange()` — inclusive-exclusive
