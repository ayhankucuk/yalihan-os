# CI Failure Census — Test Stabilization Sprint

**Generated:** 2026-08-02
**CI Run:** 30733964535
**Branch:** integration/era-v-phase2a-e01
**Commit:** 7651c7f (validated)

---

## Executive Summary

| Metric | Value |
|--------|-------|
| Total Tests | 2185 |
| Errors | 218 |
| Failures | 80 |
| Skipped | 71 |
| Pass Rate | ~86.4% |

**PublishGuardTest:** ✅ PASS (commit 7651c7f validated)
**Phase 2A Merge Readiness:** ❌ HOLD

---

## Root Cause Family Table

| # | Root Cause | Errors | Failures | Total | Severity | Production Risk |
|---|------------|--------|----------|-------|---------|-----------------|
| F1 | `skipPropertyIdGuard` undeclared static property | 27 | 0 | **27** | P0 | HIGH - Test crash |
| F2 | 403 Forbidden (Auth/Authorization) | 0 | 21 | **21** | P0 | HIGH - Security tests broken |
| F3 | Governance: No tenant config for [SYSTEM] | 4 | 3 | **7** | P0 | HIGH - Governance bootstrap |
| F4 | Hardcoded local path `/Users/macbookpro/` | 1 | 2 | **3** | P0 | CRITICAL - Path injection |
| F5 | FollowUpAutomationService constructor args | 2 | 0 | **2** | P1 | MEDIUM - DI mismatch |
| F6 | Null property access (danisman/altKategori) | 4 | 0 | **4** | P1 | MEDIUM - Fixture issues |
| F7 | CategoryMismatch junction.kategori_id | 0 | 3 | **3** | P1 | MEDIUM - Schema drift |
| F8 | N+1 Query (21 vs threshold 10) | 0 | 1 | **1** | P1 | MEDIUM - Performance |
| F9 | 404 Not Found | 0 | 10 | **10** | P1 | MEDIUM - Route/endpoint |
| F10 | 500 Internal Server Error | 0 | 3 | **3** | P1 | HIGH - Unknown error |
| F11 | Completion score guard | 0 | 3 | **3** | P2 | LOW - Test expectation |
| F12 | Agent Authority write violation | 0 | 3 | **3** | P2 | LOW - Expected behavior |
| F13 | IlanDurumu Enum type assertion | 0 | 1 | **1** | P2 | LOW - Test type check |
| F14 | Redis/telemetry config | 0 | 2 | **2** | P2 | LOW - Infrastructure |
| F15 | Other/miscellaneous | 2 | 23 | **25** | P2 | LOW |

---

## P0 — Must Fix Before Any Merge

### F1: `skipPropertyIdGuard` Undeclared Static Property

**Affected Tests:** 27 errors
**Files:**
- `tests/Feature/Domain/Kisi/KisiDomainIsolationTest.php`
- `tests/Feature/Execution/M2ProductValidationTest.php`
- `tests/Feature/Admin/IlanControllerAuthorizationTest.php`
- `tests/Feature/Admin/TalepControllerAuthorizationTest.php`
- `tests/Feature/ChannelManager/AvailabilitySynchronizationServiceTest.php`
- `tests/Feature/Admin/WizardCopilotActionApiTest.php`
- `tests/Feature/Admin/KomisyonControllerTenantIsolationTest.php`
- `tests/Feature/Security/TenantIsolationSafetyTest.php`
- And ~19 more files

**Root Cause:**
```php
// Test sets: Ilan::$skipPropertyIdGuard = true;
// But Ilan model doesn't declare this static property
```

**Fix Candidate:**
1. Add `protected static bool $skipPropertyIdGuard = false;` to Ilan model
2. OR remove all references from failing tests
3. OR check property_exists before access

**Effort:** ~2 hours
**Production Risk:** LOW (test-only)

---

### F4: Hardcoded Local Path in Tests

**Affected Tests:** 3 (1 error + 2 failures)
**File:** `tests/Feature/Api/IlanQueryConsistencyTest.php`

**Root Cause:**
```php
file_put_contents('/Users/macbookpro/dev/yalihan2026/storage/logs/test_error.txt', ...);
// Hardcoded absolute path - fails on CI runner
```

**Fix Candidate:** Use `storage_path()` or `base_path()`
**Effort:** ~30 minutes
**Production Risk:** CRITICAL (path injection vector if in prod)

---

## P1 — Fix Before Phase 2A Merge

### F2: 403 Forbidden Responses

**Affected Tests:** 21 failures
**Files:**
- `tests/Feature/Admin/IlanControllerAuthorizationTest.php` (36 references)
- `tests/Feature/Admin/TalepControllerAuthorizationTest.php` (32 references)
- `tests/Feature/Wizard/WizardStep1TemplateDataTest.php` (4 failures)
- `tests/Feature/Api/Mobile/NotificationTest.php` (6 references)
- `tests/Feature/Api/Mobile/SavedSearchTest.php`
- `tests/Feature/Api/Mobile/ProfileTest.php`

**Root Cause:** Tests expect 200 but receive 403 - authentication/authorization not properly set up

**Fix Candidate:**
1. Check if tests use correct user/auth setup
2. Verify middleware configuration for test environment
3. Check if permissions are properly seeded

**Effort:** ~4-6 hours
**Production Risk:** MEDIUM (may hide real auth issues)

---

### F3: Governance Tenant Config

**Affected Tests:** 7 (4 errors + 3 failures)
**Files:**
- `tests/Feature/Admin/TalepControllerAuthorizationTest.php`
- `tests/Feature/Chaos/ChaosPerformanceTest.php`
- `tests/Feature/Security/TenantIsolationSafetyTest.php`

**Root Cause:**
```
Yalıhan Governance Error: No active configuration found for tenant [SYSTEM].
```

**Fix Candidate:** Seed governance config in test database
**Effort:** ~2 hours
**Production Risk:** MEDIUM (governance not bootstrapped)

---

### F9: 404 Not Found

**Affected Tests:** 10 failures
**Root Cause:** Route/endpoint doesn't exist or middleware blocks

**Fix Candidate:** Verify routes exist and are properly registered
**Effort:** ~2 hours
**Production Risk:** LOW (test endpoint issue)

---

### F10: 500 Internal Server Error

**Affected Tests:** 3 failures
**Files:**
- `tests/Feature/ListingLifecycle/ListingLifecycleFinalSealTest.php`
- `tests/Feature/Crud/IlanCrudTest.php`

**Fix Candidate:** Check exception logs, fix null handling
**Effort:** ~3 hours
**Production Risk:** HIGH (unknown error in critical path)

---

## P2 — Fix After P0/P1

### F7: CategoryMismatch

**Affected Tests:** 3 failures
**Error:** `CategoryMismatch: junction_id=1 junction.kategori_id=null`

**Fix Candidate:** Fix YayinTipiSablonu junction setup
**Effort:** ~2 hours

### F8: N+1 Query

**Affected Tests:** 1 failure
**Error:** 21 queries vs threshold 10

**Fix Candidate:** Optimize relation loading
**Effort:** ~2 hours

### F11: Completion Score Guard

**Affected Tests:** 3 failures
**Error:** completion_score recalculated to lower value

**Fix Candidate:** Align test fixtures with score computation
**Effort:** ~1 hour

---

## Test Files by Failure Count

| File | Count | Family |
|------|-------|--------|
| TestFixtureHelper.php | 194 | F6 |
| AvailabilitySynchronizationServiceTest.php | 36 | F1 |
| IlanControllerAuthorizationTest.php | 36 | F1/F2 |
| TalepControllerAuthorizationTest.php | 32 | F1/F3 |
| WizardCopilotActionApiTest.php | 30 | F1 |
| KomisyonControllerTenantIsolationTest.php | 30 | F1 |
| IlanQueryConsistencyTest.php | 23 | F4 |
| RecoveryEngineServiceTest.php | 18 | F1 |
| AdvisorPhotoUploadTest.php | 16 | F1 |
| KisiTest.php | 12 | F1 |
| TenantIsolationSafetyTest.php | 12 | F1/F3 |

---

## Remediation Waves

### Wave 1: P0 Fixes (Day 1)
- [ ] F1: Add `skipPropertyIdGuard` property to Ilan model
- [ ] F4: Fix hardcoded local path

### Wave 2: P1 Authorization (Day 2-3)
- [ ] F2: Fix 403 auth issues in admin/api tests
- [ ] F3: Seed governance tenant config
- [ ] F9: Fix 404 route issues

### Wave 3: P1 Quality (Day 3-4)
- [ ] F5: Fix FollowUpAutomationService DI
- [ ] F6: Fix null property access
- [ ] F10: Investigate 500 errors

### Wave 4: P2 Cleanup (Day 4-5)
- [ ] F7: Fix CategoryMismatch
- [ ] F8: Fix N+1 query
- [ ] F11-F15: Remaining issues

---

## Path to Green CI

| Milestone | Target | Status |
|-----------|--------|--------|
| P0 fixes | Day 1 | ~50 errors resolved |
| P1 fixes | Day 3 | ~40 errors + 30 failures |
| P2 fixes | Day 5 | Full green |
| CI PASS | Day 5 | Target |
| Phase 2A Merge | Day 5-6 | After CI green |

---

## Recommendations

1. **Do NOT merge Phase 2A** until CI is green
2. **Start with F1** (skipPropertyIdGuard) - fixes 27 errors instantly
3. **F4 (hardcoded path)** is CRITICAL - remove immediately
4. **Authorization tests** (F2) may reveal real security issues - investigate
5. **TestFixtureHelper.php** has 194 references - investigate for systemic fix
6. **Governance config** (F3) indicates test DB seeding issue - check TestCase setup

---

## Files Requiring Modification

### Add Property to Ilan Model
```
app/Models/Ilan.php
```

### Fix Hardcoded Path
```
tests/Feature/Api/IlanQueryConsistencyTest.php
```

### Fix Authorization Tests
```
tests/Feature/Admin/IlanControllerAuthorizationTest.php
tests/Feature/Admin/TalepControllerAuthorizationTest.php
tests/Feature/Wizard/WizardStep1TemplateDataTest.php
tests/Feature/Api/Mobile/NotificationTest.php
```

### Fix Governance Bootstrap
```
tests/Feature/Admin/TalepControllerAuthorizationTest.php
tests/Feature/Chaos/ChaosPerformanceTest.php
tests/TestCase.php (seed governance config)
```

### Fix FollowUpAutomationService
```
tests/Feature/TaskAuthorityTest.php
tests/Feature/Repositories/IlanRepositoryWriteHardeningTest.php
```

---

**Census compiled by:** Kilo Agent
**Model:** Claude Opus 4.8
**Date:** 2026-08-02T08:34 UTC+3
