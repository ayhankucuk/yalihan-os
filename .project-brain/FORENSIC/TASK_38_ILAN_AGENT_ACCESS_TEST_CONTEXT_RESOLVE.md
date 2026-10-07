# TASK_38: ILAN_AGENT_ACCESS_TEST_CONTEXT_RESOLVE

**Mode:** STRICT READ-ONLY — FORENSIC RESEARCHER  
**Expected HEAD:** `37f6ae04c3408ae72a94720c331e931194be8fee` ✅  
**Actual HEAD:** `37f6ae04c3408ae72a94720c331e931194be8fee`  
**Evidence Level:** `REPO_VERIFIED` (live command execution, tinker, class test runs)

---

## HEAD

```
37f6ae04c3408ae72a94720c331e931194be8fee
```

**GIT_STATUS:**
```
M .project-brain/EVIDENCE_INDEX.md
?? .project-brain/FORENSIC/
[... untracked files]
```

---

## S4_INDIVIDUAL_RESULT

```
FAIL — ErrorException: Attempt to read property "latitude" on null
  at IlanPublicDetailResource.php:21
  via IlanAgentAccessTest.php:253

  1 failed (0 assertions) | Duration: ~1.0s
```

---

## S4_CLASS_RESULT

```
FAIL — Same ErrorException
  1 failed, 6 passed (44 assertions) | Duration: ~4.0s

  ✓ s1 anonim→agent_name_avatar  (PASS)
  ✓ s2 cross_tenant_returns_404  (PASS)
  ✓ s3 same_tenant_full_access   (PASS)
  ⨯ s4 public_resource_no_agent  (FAIL) ← ONLY FAILURE
  ✓ s5 unpub_ilan_404           (PASS)
  ✓ s6 owner_sees_full_coords   (PASS)
  ✓ s7 anonim_sees_approx       (PASS)
```

---

## ORDER_DEPENDENCY

**NO — ORDER INDEPENDENT**

Evidence:
- S4 individual: FAIL
- S1 → S4 (consecutive): S1 PASS (1.01s), S4 FAIL (0.63s)
- Full class run: S4 fails in isolation regardless of position
- Each test runs with fresh setUp() / tearDown() — no inter-test state pollution

Confirmed: S4 fails consistently at ~0.67s whether run alone or after S1.

---

## EXPECTED_TENANT

```
tenantA.id = 100 (from diagnostic: "tenantA id: 100")
tenantA.domain = 'agent-a.local'
```

---

## EFFECTIVE_TENANT

```
TenantContextService->currentTenant = tenantB (id=101, domain='agent-b.local')
```

**CONFIRMED via diagnostic script:**
```
AFTER_SETUP:
  TenantContextService tenant_id: 101
  tenantA id: 100
  tenantB id: 101
  currentTenant === tenantB: YES
```

**Mechanism:** `setUp()` line 59 calls `app(TenantContextService::class)->setTenant($this->tenantB)`. This sets the singleton's `$currentTenant` to tenantB. No subsequent code in setUp or tearDown resets it. The singleton persists across the test method execution.

---

## ILAN_TENANT

```
ilanA tenant_id = tenantA.id = 100
```

**CONFIRMED via makeIlan()** — ilanA is created with `tenant_id => $this->tenantA->id`.

---

## EXPECTED_AUTH_USER

```
null  (S4 has no actingAs() call — confirmed by source review)
```

---

## EFFECTIVE_AUTH_USER

```
null  (no actingAs() in S4)
```

---

## USER_COUNTRY

```
ulke_id = 77  (both userA and userB have ulke_id = 77)
```

---

## ILAN_COUNTRY

```
ulke_id = 77  (ilanA created with ulke_id = 77)
```

---

## ROW_FILTERED_BY

**TenantScope** — CONFIRMED  
**CountryScope** — NO-OP (confirmed)  
**Both** — NO  
**Neither** — NO

### CountryScope Diagnosis

```php
// CountryScope::apply():
if ($user) { /* ... */ }  // $user = Auth::user() = null
// NO filtering applied
```

`Schema::hasColumn("ilceler","ulke_id") = FALSE` — CountryScope never adds any WHERE clause for ilanlar join tables. CountryScope is completely inert for all V2Ilan queries in this test context.

### TenantScope Diagnosis

```php
// TenantScope::apply():
$tenantService = app(TenantContextService::class);

if ($tenantService->hasTenant()) {           // TRUE — tenantB active
    $builder->where($table . '.tenant_id', $tenantService->getTenant()->id);
    // → WHERE tenant_id = 101
} elseif (auth()->check() && !empty(auth()->user()->tenant_id)) {
    // NOT REACHED — hasTenant()=true, first branch fires
} else {
    $builder->whereRaw('1 = 0');  // NOT REACHED
}

// Query: WHERE tenant_id = 101 AND id = {ilanA.id}
// Expected rows: 0 (ilanA has tenant_id = 100)
```

**Result: `V2Ilan::find(ilanA.id)` returns `null`**

---

## EXACT_ROOT_CAUSE

```
TenantContextService singleton holds stale tenantB state at S4 execution time.

Sequence:
  1. setUp() line 50: app(TenantContextService)->setTenant(tenantA)  [for userA creation]
  2. setUp() line 59: app(TenantContextService)->setTenant(tenantB)  [for userB creation — LAST]
  3. setUp() ends → singleton now holds tenantB permanently
  4. S4 line 249: V2Ilan::with([...])->find(ilanA.id)
  5. TenantScope::apply(): hasTenant()=true (tenantB), auth=null
  6. → WHERE tenant_id = 101 AND id = {ilanA.id}  → 0 rows
  7. find() returns null
  8. new IlanPublicDetailResource(null)
  9. toArray() → $this->latitude on null → ErrorException

PRIMARY: TenantScope fail-closed triggered by stale singleton state.
SECONDARY: Test queries model directly without bypassing TenantScope.
TERTIARY: No tearDown() resets TenantContextService singleton.
```

**Not CountryScope. Not Sanctum/Auth state. Not fixture inconsistency. Not test ordering.**

---

## WITHOUT_GLOBAL_SCOPES_FIX

**REJECTED**

### Evidence for Rejection

The instruction "withoutGlobalScopes() is NOT an approved remediation" is **correct and justified** for this specific test context:

1. **IlanController::show() (line 97)** uses `withoutGlobalScope(TenantScope::class)` — this is the **production path**. S4 tests a **different code path** (direct resource instantiation).

2. S4 directly instantiates `IlanPublicDetailResource($ilan)` **without going through the controller**. Its purpose is to verify the resource's output structure, not the controller's query behavior.

3. Using `withoutGlobalScopes()` in S4 would create a **structural bypass**: a direct model query that ignores tenant isolation — exactly the security boundary this test suite is supposed to verify.

4. **Legitimate bypass**: The production controller bypasses TenantScope by design (to handle anonymous cross-tenant reads). S4 is NOT the controller.

5. S1 (HTTP path) already tests the controller + resource integration correctly and passes. S4 tests the resource in isolation.

### Justification

`withoutGlobalScopes()` bypasses a **security-critical global scope** without evidence that the test requires it. The correct fix is to set the proper tenant context, not to disable the scope.

---

## CANONICAL_TEST_REMEDIATION

### Option A: Reset TenantContextService in tearDown() [PREFERRED — SHARED LIFECYCLE FIX]

**File:** `tests/Feature/Security/IlanAgentAccessTest.php`  
**Location:** `tearDown()` method, line 79

```php
protected function tearDown(): void
{
    foreach (['ilanA_Yayinlanmis', 'ilanB_Yayinlanmis'] as $key) {
        if (isset($this->$key)) {
            V2Ilan::withoutGlobalScopes()->forceDelete($this->$key->id);
        }
    }

    // ADD THIS — reset singleton state to prevent stale tenant context leakage
    app(TenantContextService::class)->clearTenant();

    parent::tearDown();
}
```

**Why:** `clearTenant()` is the explicit lifecycle cleanup method defined in `TenantContextService.php:53-56`. It is called `clearTenant()` (not `reset()`), designed exactly for this purpose: "request lifecycle cleanup to prevent tenant context leakage."

**Effect on S4:** After tearDown() from previous test clears the singleton, S4's setUp() sets the context correctly before each test.

**Secondary benefit:** Prevents cross-test singleton pollution for ALL tests in this suite.

**Risk:** `clearTenant()` sets `$currentTenant = null`. In subsequent tests that call `withoutGlobalScope()`, `hasTenant()` would return false and TenantScope would fall back to `auth()->user()->tenant_id`. This fallback is correct for authenticated tests (S3, S6) and correct for the fail-closed path (S2, S5). No regression expected.

### Option B: S4 sets TenantContextService to tenantA before querying [TEST-SPECIFIC]

**File:** `tests/Feature/Security/IlanAgentAccessTest.php`  
**Location:** `test_s4()` method, before line 249

```php
public function test_s4_public_resource_has_no_agent_section(): void
{
    // Reset TenantContextService to tenantA so TenantScope resolves via
    // auth()->user()->tenant_id path (S4 has no actingAs — null user).
    // Fallback: auth null → hasTenant() must be FALSE for correct fallback.
    app(TenantContextService::class)->setTenant($this->tenantA);

    $ilan = V2Ilan::with([...])->find($this->ilanA_Yayinlanmis->id);
    // ...
}
```

**BUT:** Setting `hasTenant()=true` with tenantA changes the TenantScope branch:
- `hasTenant()=true` → `where tenant_id = tenantA.id` → query finds ilanA ✅
- This makes TenantScope work correctly — no bypass.

**Effect:** S4 passes. But this also means TenantScope IS active and correctly filtering. The test now validates resource structure with proper tenant isolation in place.

**Assessment:** This is semantically correct — the test gets ilanA through proper tenant isolation. The resource structure test still works. This is equivalent to "S4 uses tenantA's context" which is the intended test setup.

**Concern:** Requires understanding that TenantScope will now query for `tenant_id = tenantA`. The test still validates the right ilan (ilanA, which belongs to tenantA).

### Option C: S4 uses actingAs + controller HTTP path [STRUCTURAL RESTRUCTURE]

Similar to how S1 tests via `getJson("/api/v1/ilanlar/{$id}")`, S4 could:
```php
public function test_s4_public_resource_has_no_agent_section(): void
{
    $response = $this->getJson("/api/v1/ilanlar/{$this->ilanA_Yayinlanmis->id}");
    // This goes through IlanController::show() which bypasses TenantScope
    // ...
}
```

**Concern:** S4's current purpose is to test the **resource in isolation** (direct instantiation). Changing to HTTP path changes what's being tested.

### Decision

**Option A (tearDown reset) is the correct remediation** because:
1. It fixes the ROOT CAUSE (stale singleton state) rather than working around it
2. `clearTenant()` is the explicitly designed cleanup method in TenantContextService
3. It benefits ALL tests in the suite, not just S4
4. It makes the test suite's state management match production expectations
5. No bypass of any security scope — all tests still run with full scope enforcement

**Option B is acceptable as a fallback** if Option A is deemed too broad (affects other tests).

---

## DECLARED_WRITE_SCOPE

**File:** `tests/Feature/Security/IlanAgentAccessTest.php`  
**Location:** `tearDown()` method  
**Change:** Add `app(TenantContextService::class)->clearTenant();`

**Alternative scope (Option B):**
**File:** `tests/Feature/Security/IlanAgentAccessTest.php`  
**Location:** `test_s4()` method, before line 249  
**Change:** Add `app(TenantContextService::class)->setTenant($this->tenantA);`

---

## REQUIRED_REGRESSION_TESTS

1. All 7 IlanAgentAccessTest scenarios must pass (S1–S7)
2. Anonymous API path (`GET /api/v1/ilanlar/{id}`) still returns 200 + public fields
3. Cross-tenant isolation still returns 404 (S2 — tests fail-closed with null auth)
4. S1, S3, S6 (authenticated paths) still work with correct tenant context
5. TenantScope fail-closed behavior still works for other test suites

---

## LAT_LNG_NULLABILITY

**nullable** — both `lat` and `lng` are nullable in the schema (confirmed via V2Ilan fillable/casts and diagnostic tinker run).

**Evidence:**
- V2Ilan `fillable` array includes `'lat'` and `'lng'` with no null guard
- V2Ilan `$casts` maps them to `'decimal:8'` (not non-nullable decimal)
- `makeIlan()` fixture provides explicit values (37.123456, 28.654321) but schema allows null
- `IlanPublicDetailResource:21-22` uses `??` null coalescing for both fields

**LAT_LNG_NULLABILITY = nullable**

---

## FINAL DETERMINATION

```
TEST_CONTEXT_DEFECT_READY_FOR_REMEDIATION

BLOCKED_ROOT_CAUSE_UNKNOWN = NO

EXACT_ROOT_CAUSE = TenantContextService singleton stale state (tenantB)
                   causes TenantScope fail-closed on S4's direct model query
                   for ilanA (tenantA). No tearDown() cleanup. No actingAs().

EXCLUDED CAUSES:
  A. TenantContextService stale state         → INCLUDED (PRIMARY)
  B. Sanctum/Auth user state leakage          → EXCLUDED (null in both S4 and S1)
  C. CountryScope mismatch                   → EXCLUDED (CountryScope is NO-OP)
  D. TenantScope mismatch                    → INCLUDED (secondary mechanism)
  E. Fixture tenant/country inconsistency     → EXCLUDED (all use ulke_id=77)
  F. Test ordering/shared application state → EXCLUDED (order-independent)

SHARED_LIFECYCLE_DEFECT_REQUIRES_SEPARATE_TASK = YES
  TenantContextService::clearTenant() is never called in the test suite.
  This is a shared infrastructure gap affecting all tests that use TenantContextService.
  Proper remediation: tearDown() lifecycle cleanup.
  OUT OF SCOPE for this task (STRICT READ-ONLY).

BAD_FIXTURE_READY_FOR_REMEDIATION = NO
  Fixtures are correct (ulke_id=77 for all, correct tenant assignments).

WITHOUT_GLOBAL_SCOPES_FIX = REJECTED (justified above)

PRODUCTION_IMPACT = UNKNOWN
  Production code uses withoutGlobalScope(TenantScope::class) in controller
  (IlanController::show line 97). Production path is not affected.

Evidence Level: REPO_VERIFIED + LIVE_TEST_EXECUTION
  - S4 individual: FAIL confirmed
  - S4 class: FAIL confirmed (only S4 fails)
  - S1→S4: S1 PASS, S4 FAIL confirmed
  - Order-independence: confirmed
  - TenantContextService singleton state: confirmed via diagnostic
  - CountryScope NO-OP: confirmed via Schema::hasColumn
  - TenantScope fail-closed: confirmed via diagnostic trace
```

---

## EVIDENCE CHAIN

| Evidence | Source | Value |
|----------|--------|-------|
| HEAD | `git rev-parse HEAD` | `37f6ae04...` ✅ |
| S4 individual FAIL | `php artisan test --filter=test_s4` | ErrorException confirmed |
| S4 class FAIL (only S4) | Full class run | 1 failed, 6 passed |
| Order-independence | S1→S4 run | S1 PASS, S4 FAIL |
| currentTenant=tenantB | Diagnostic script | `currentTenant === tenantB: YES` |
| ilanA tenant_id | makeIlan() source | `tenantA->id` |
| CountryScope NO-OP | `Schema::hasColumn("ilceler","ulke_id")=false` | confirmed |
| TenantScope fail-closed | Diagnostic trace + TenantScope.php:28-31 | confirmed |
| IlanController bypass | IlanController.php:97 | `withoutGlobalScope(TenantScope::class)` |
| clearTenant() exists | TenantContextService.php:53-56 | confirmed |
| S4 no actingAs() | Source review line 244 | confirmed |
| S1 has no actingAs() | Source review line 117 | confirmed |
| S1 PASSES | Live test execution | HTTP path bypasses TenantScope |

---

*S4 forensic research complete. No source files modified.*
