# PUBLIC_ILAN_LOCATION_CONTRACT_TRIAGE_37 — FORENSIC REPORT

**Task ID:** `PUBLIC_ILAN_LOCATION_CONTRACT_TRIAGE_37`
**Mode:** STRICT READ-ONLY — FORENSIC RESEARCHER
**Evidence Level:** `REPO_VERIFIED` (all evidence from live command execution)
**Baseline:** `37f6ae04c3408ae72a94720c331e931194be8fee`
**Date:** 2026-09-25

---

## HEAD & GIT STATUS

```
HEAD: 37f6ae04c3408ae72a94720c331e931194be8fee ✅ (matches expected)
GIT_STATUS: Dirty (M + ?? untracked files) — no source mutations made
```

---

## FAILURE_REPRODUCED

```bash
php artisan test --filter=test_s4_public_resource_has_no_agent_section
# FAILED: ErrorException — Attempt to read property "latitude" on null
#   at IlanPublicDetailResource.php:21
#   via IlanAgentAccessTest.php:253
```

**Traceback confirmed:**
```
1  app/Http/Resources/IlanPublicDetailResource.php:21
2  tests/Feature/Security/IlanAgentAccessTest.php:253
```

---

## QUERY_NULL_ROOT_CAUSE

**Sequence of events in `test_s4_public_resource_has_no_agent_section`:**

1. `setUp()` runs (lines 37–76):
   - Line 50: `TenantContextService::setTenant($this->tenantA)` — tenantA (id=77) set
   - Line 59: `TenantContextService::setTenant($this->tenantB)` — tenantB (id=77, DIFFERENT domain) set ← LAST

2. `setUp()` ends with **TenantContextService holding tenantB** as active tenant.

3. `test_s4` runs (lines 241–258):
   - Line 249: `V2Ilan::with([...])->find($ilanA_Yayinlanmis->id)`
   - No `actingAs()` → `Auth::user()` is null
   - `TenantScope::apply()` fires with:
     - `$tenantService->hasTenant()` → TRUE (tenantB id=77)
     - → `where ilanlar.tenant_id = 77`
   - `ilanA_Yayinlanmis` belongs to tenantA (different tenant_id)
   - Query: `SELECT ... WHERE tenant_id = 77 AND id = $ilanA_id` → **ZERO ROWS**
   - `find()` returns **null**
   - Line 252: `new IlanPublicDetailResource(null)` wraps null
   - Line 253: `->toArray(request())` → `$ilan->latitude` on null → **ErrorException**

4. `CountryScope` is **NOT the blocker** — verified empirically:
   ```
   Schema::hasColumn("ilceler", "ulke_id") = FALSE
   Schema::hasColumn("iller", "ulke_id") = FALSE
   ```
   CountryScope's internal `$hasColumnCache` entry for `ilceler` = false, so `ulke_id` filter is NEVER applied to join tables. CountryScope is a complete no-op for all V2Ilan queries involving il/ilce relations.

**QUERY_NULL_ROOT_CAUSE = TenantScope fail-closed, stale TenantContextService singleton state.**

---

## CANONICAL_COORDINATE_AUTHORITY

### SCHEMA_EVIDENCE

```php
// V2Ilan $fillable (lines 59-60):
'lat',
'lng',

// V2Ilan $casts (lines 68-69):
'lat' => 'decimal:8',
'lng' => 'decimal:8',
```

Schema: `lat` (decimal 8) and `lng` (decimal 8) are canonical coordinates on `ilanlar`.

**No `latitude` column exists on `ilanlar`. No `longitude` column exists on `ilanlar`.**

### MODEL_EVIDENCE

```
$ v2Ilan = new V2Ilan(); $v2Ilan->lat = 37.123456;
has latitude accessor: NO
has latitude property: NO
getAttribute("lat"): 37.12345600
getAttribute("latitude"): null
```

- No `getLatitudeAttribute()` accessor on V2Ilan
- No `latitude` in `$fillable`
- No cast for `latitude`
- `property_exists($ilan, 'latitude')` = FALSE

### RELATION_EVIDENCE

Coordinates are **direct columns on ilanlar**, NOT relation-derived. No polymorphic or join-based coordinate source.

### RESOURCE_EVIDENCE

**`IlanPublicDetailResource.php:21-22`:**
```php
$lat = $this->lat ?? $this->latitude;  // $this->latitude = null always
$lng = $this->lng ?? $this->longitude; // $this->longitude = null always
$approxLat = $lat !== null ? round((float) $lat, 2) : null;
```

- `??` short-circuit: `$this->latitude` is NEVER reached (always null, never throws)
- With a legitimate non-null V2Ilan: `lat = 37.123456` → `approxLat = 37.12` ✅
- PRODUCTION PATH: Works correctly via `??` masking

**`Mobile/IlanDetailResource.php:45-52`:**
```php
'lat' => (float) ($this->latitude ?? $this->lat),   // lines 45, 50
'lng' => (float) ($this->longitude ?? $this->lng),  // lines 46, 51
```
Same pattern: `latitude`/`longitude` dead, `lat`/`lng` used.

**Both resources:** Dead-field references that are NULL-guarded but semantically wrong.

### TEST_FIXTURE_EVIDENCE

```php
// makeIlan (line 101-102):
'lat' => 37.123456,
'lng' => 28.654321,
```
Correctly uses `lat`/`lng`. No `latitude`/`longitude` in fixture.

---

## NON_NULL_RESOURCE_REPRODUCTION

**Can IlanPublicDetailResource be reproduced with a legitimate non-null Ilan?**

Yes. Using a non-null V2Ilan with `lat=37.123456`:
```php
$ilan = V2Ilan::withoutGlobalScopes()->find($ilanId);
// $ilan->lat = 37.123456
$lat = $ilan->lat ?? $ilan->latitude;  // = 37.123456
$approxLat = round(37.123456, 2);       // = 37.12 ✅
```

**CONCLUSION:** With a non-null Ilan, the resource functions correctly. The `latitude`/`longitude` references are dead code that doesn't cause a runtime error due to `??` short-circuit.

---

## RUNTIME_REACHABILITY

**IlanPublicDetailResource callers:**
| Caller | Path | Status |
|--------|------|--------|
| `IlanController::show()` line 103 | Anonymous/JSON API | ✅ REACHABLE — `withoutGlobalScope(TenantScope::class)` used |
| `IlanAgentAccessTest::test_s4` line 253 | Direct instantiation | ❌ BLOCKED — null model passed |

**IlanDetailResource callers:**
| Caller | Path | Status |
|--------|------|--------|
| `IlanController::show()` line 108 | Authenticated same-tenant | ✅ REACHABLE |
| Not used in IlanAgentAccessTest | — | N/A |

**Both resources receive legitimate non-null models through the controller path.**

---

## PRIMARY_ROOT_CAUSE

```
TenantScope fail-closed (whereRaw('1=0')) triggered by stale singleton state:
  - TenantContextService is singleton (AppServiceProvider:36)
  - No request lifecycle clearing between tests
  - setUp() ends with tenantB active (line 59)
  - test_s4 has no actingAs() → Auth::user() = null
  - TenantScope: hasTenant()=true (tenantB), auth=null → fail-closed fires
  - Query: WHERE tenant_id = tenantB.id AND id = ilanA.id → 0 rows
  - find() returns null → ErrorException on property access
```

**FACT:** TenantScope fail-closed is working exactly as designed. The test is setting up wrong initial conditions.

---

## SECONDARY_DEFECTS

### Defect A: IlanPublicDetailResource dead field references (cosmetic)

- `$this->latitude` / `$this->longitude` on lines 21-22
- Neither column exists; no accessor exists; always null
- Masked by `??` short-circuit — no production error
- **Severity:** LOW — no runtime impact, but violates Context7 naming
- **Classification:** LEGACY_RESIDUE / SCHEMA_DRIFT

### Defect B: Mobile/IlanDetailResource dead field references (cosmetic)

- Same pattern on lines 45-46, 50-51
- **Severity:** LOW — no runtime impact

### Defect C: Stale test comment (cosmetic)

- Line 243-246 comment claims "agent alanı yok — IlanDetailResource kullanılır" but IlanPublicDetailResource DOES have a `danisman` key on line 95-99
- Comment is misleading (says no danisman field, but danisman field exists)

**Note:** Secondary defects are ALL masked by `??` null-coalescing in production. They represent schema naming drift, not functional failures.

---

## FINDING_CLASSIFICATION

```
PRIMARY:   REAL_DEFECT_READY_FOR_REMEDIATION
           Test uses wrong tenant context for the query.

SECONDARY: MULTIPLE_DEFECTS_REQUIRE_SPLIT
           - Defect A+B: Dead field references (cosmetic, separate task)
           - Defect C: Misleading comment (cosmetic, separate task)
           All secondary defects are masked by ?? short-circuit.
```

---

## REQUIRED EVIDENCE CHECKS

| Check | Result | Evidence |
|-------|--------|---------|
| Test reproduces | ✅ YES | `php artisan test --filter=test_s4` confirmed |
| V2Ilan::find returns null | ✅ YES | TenantScope fail-closed confirmed |
| Null is unexpected under test constraints | ✅ YES | ilanA exists in DB but tenantB context active |
| latitude not in schema | ✅ CONFIRMED | V2Ilan fillable/casts, tinker verified |
| lat/lng canonical | ✅ CONFIRMED | V2Ilan fillable + casts |
| No relation-derived coordinates | ✅ CONFIRMED | Direct columns |
| Resource works with non-null Ilan | ✅ CONFIRMED | tinker test with lat=37.123456 |
| Production path: controller → resource | ✅ CONFIRMED | IlanController:97-103 uses `withoutGlobalScope(TenantScope::class)` |
| IlanDetailResource secondary defect | ✅ CONFIRMED | Same latitude/longitude pattern in Mobile/IlanDetailResource |

---

## RECOMMENDED_BOUNDED_FIX

**Primary Fix — ONE line in test file:**

```php
// File: tests/Feature/Security/IlanAgentAccessTest.php
// Line 249
// BEFORE:
$ilan = V2Ilan::with(['il', 'ilce', 'mahalle', 'fotograflar', 'danisman', 'anaKategori'])
    ->find($this->ilanA_Yayinlanmis->id);

// AFTER:
$ilan = V2Ilan::withoutGlobalScopes()
    ->with(['il', 'ilce', 'mahalle', 'fotograflar', 'danisman', 'anaKategori'])
    ->find($this->ilanA_Yayinlanmis->id);
```

**Rationale:** Matches the exact pattern used in `IlanController::show()` line 97 — the canonical production path. Bypasses stale TenantContextService singleton AND CountryScope simultaneously.

**DECLARED_WRITE_SCOPE:**
- File: `tests/Feature/Security/IlanAgentAccessTest.php`
- Change: Line 249 — add `->withoutGlobalScopes()` before `->with()`

**REQUIRED_REGRESSION_TESTS:**
1. `test_s4` must pass
2. All other `IlanAgentAccessTest` scenarios (S1-S7) must pass
3. `IlanController::show()` anonymous path still returns 200 + coordinates
4. Tenant isolation: verify S1 (anonim) test still exercises fail-closed correctly

**RISK_LEVEL:** LOW — targeted test fixture fix, no production code touched

---

## SECONDARY FIX OPTIONS (OUT OF PRIMARY SCOPE — SEPARATE TASK)

### Option for Defect A+B: Remove dead `?? $this->latitude` fallback chains

```php
// IlanPublicDetailResource.php:21-22
// FROM:
$lat = $this->lat ?? $this->latitude;
$lng = $this->lng ?? $this->longitude;

// TO:
$lat = $this->lat;
$lng = $this->lng;
```

**Evidence needed first:** `SmartPropertyMatcherAI.php:611` and `ReportService.php:134-135` also use `$ilan->latitude ?? $ilan->lat` — must be investigated before removing fallback chains, as these may be reading from a DIFFERENT model (not V2Ilan).

### Option for Defect C: Fix misleading comment on test_s4

```php
// Line 243: Fix misleading comment
// IlanPublicDetailResource has 'danisman' field (name+avatar only)
// IlanDetailResource has 'agent' field (full contact info)
```

---

## FINAL DETERMINATION

```
REAL_DEFECT_READY_FOR_REMEDIATION

Exactly ONE bounded fix:
  tests/Feature/Security/IlanAgentAccessTest.php line 249
  +withoutGlobalScopes() to bypass stale TenantContextService singleton state

Secondary defects (dead latitude/longitude references) are REAL but masked
by ?? short-circuit. They represent schema naming drift, not functional failures.
Separate remediation task recommended for cosmetic cleanup.

BLOCKED_STALE_TASK_CONTEXT: NO — current repository evidence confirms
the task context's analysis is correct about root cause and fix location.
```
