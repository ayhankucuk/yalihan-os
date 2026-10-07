# CANDIDATE FINDING: CF-2026-10-07-SERVICE-FIELD-DRIFT

## Metadata
- **CANDIDATE_ID:** CF-2026-10-07-SERVICE-FIELD-DRIFT
- **Status:** ROOT_CAUSE_PROVEN
- **Evidence Level:** REPO_VERIFIED + SCHEMA_VERIFIED
- **Classification:** SCHEMA_MIGRATION_DEBT
- **Resolution:** REPO_AUTHORITY — bounded fix authorized

## Problem

Multiple services use legacy field names (`starts_at`, `ends_at`) while the schema canonical contract is (`start_date`, `end_date`).

## Schema Canonical (REPO_VERIFIED + SCHEMA_VERIFIED)
```
property_reservations table:
  start_date: YES ✅
  end_date: YES ✅
  starts_at: NO ❌
  ends_at: NO ❌
```

## Field Drift Locations

### Fixed ✅
| Service | Method | Line | Status |
|---------|--------|------|--------|
| IlanReservationService | create() | 97-100 | FIXED (22cbb36a) |

### Unfixed ❌
| Service | Method | Line | Field |
|---------|--------|------|-------|
| IlanReservationService | checkConflict() | 357-358 | starts_at/ends_at |
| IlanReservationService | closeCalendar() | 488 | starts_at/ends_at |
| AdminNotificationService | notifyReservationCreated() | 63,72 | starts_at |
| AdminNotificationService | notifyReservationCancelled() | 118,131 | starts_at |
| AdminNotificationService | notifyCalendarClosed() | 181 | starts_at |
| AdminActivityEventService | multiple | 53,54,86,87,111,112 | starts_at/ends_at |

## Root Cause (PROVEN)

Schema migration replaced `starts_at/ends_at` with `start_date/end_date`.
Services were not fully updated.

## Fix Scope

Replace in all affected services:
- `starts_at` → `start_date`
- `ends_at` → `end_date`

## Impact

- Runtime null errors when accessing `$reservation->starts_at`
- Broken notifications and activity logging
- Broken conflict detection in checkConflict()

## Priority

HIGH — Multiple runtime crashes

## Related Findings

- CF-2026-10-07-REZ-SCHEMA-MISMATCH (same domain)
- CDA-REZ-01 (related remediation)
