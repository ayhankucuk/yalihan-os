# CANDIDATE FINDING: CF-2026-10-07-REZ-SCHEMA-MISMATCH

## Metadata
- **CANDIDATE_ID:** CF-2026-10-07-REZ-SCHEMA
- **Status:** ROOT_CAUSE_PROVEN — bounded fix authorized
- **Evidence Level:** REPO_VERIFIED + SCHEMA_VERIFIED
- **Classification:** SCHEMA_MISMATCH
- **Resolution:** REPO_AUTHORITY — no Human Gate required

## Revalidation Result (2026-10-07)

### Schema (REPO_VERIFIED + SCHEMA_VERIFIED)
```
property_reservations:
  property_id: YES (NOT NULL) ← AUTHORITATIVE
  ilan_id: NO
  tenant_id: YES
  start_date: YES
  end_date: YES
```

### IlanReservation Model
```
Table: property_reservations
Fillable: property_id, tenant_id, start_date, end_date, ...
NOT fillable: ilan_id
scopeForIlan($ilanId): WHERE property_id = $ilanId ✓ CORRECT
```

### IlanReservationService Contract
```
create(int $ilanId, array $data, ?int $userId):
  'ilan_id' => $ilanId  ❌ WRONG — no such column!
  'property_id' => ???  ❌ MISSING
  'tenant_id' => $ilan->tenant_id  ✓
  'start_date' => ...  ✓
  'end_date' => ...  ✓
```

### Runtime Status
```
ACTIVE_LEGACY_RUNTIME
Used by:
  - IlanCalendarController (constructor injection)
  - TelegramAIBotService (constructor injection)
```

## Root Cause (PROVEN)

Service::create() writes using `ilan_id` persistence key.
Schema + Model contract requires `property_id`.
`scopeForIlan` already correctly maps to `property_id`.
Only `create()` persistence key is wrong.

Historical cause: INFERRED / UNKNOWN
- Likely model scopeForIlan was fixed, create() was not
- No test coverage to catch divergence

## Fix Scope

```
File: app/Services/Calendar/IlanReservationService.php
Line: ~113
Change: 'ilan_id' => $ilanId → 'property_id' => $ilanId
```

## Task Reference

**TASK_ID:** CDA_REZ_01B_PROPERTY_ID_CONVERGENCE
**Status:** ROUTED_TO_KODLAYICI
**Routed to:** YALIHAN Kodlayıcı

## Ideas for Future (Not Implemented)

NEW_IDEA:
- Contract Drift Detector: Automated Model ↔ Migration ↔ Service validation
- Deduplicate against: Model ↔ Migration ↔ Relation Contract Guard

NEW_IDEA:
- Tenant Boundary Scanner: Static candidate scanner + runtime verification
- Deduplicate against: API Auth/Tenant Scope Scanner
