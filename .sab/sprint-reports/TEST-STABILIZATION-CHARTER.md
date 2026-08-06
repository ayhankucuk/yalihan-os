# Test Stabilization Sprint Charter

**Sprint:** CI Stabilization — Tenant, Authorization and Test Baseline
**Start:** 2026-08-02
**Target End:** 2026-08-06 (5 days)
**Status:** PENDING APPROVAL
**Parent Phase:** Phase 2A (HOLD)

---

## Mission

Establish a reliable CI baseline where:
- 0 errors
- 0 failures
- All skips/risky tests are documented
- GitHub CI PASS
- New regressions are detectable

**Why:** The current 298 failures (218 errors + 80 failures) make it impossible to distinguish new regressions from pre-existing debt.

---

## Scope

### IN SCOPE
- Root cause families F1-F15 identified in CI Failure Census
- P0 fixes: `skipPropertyIdGuard`, hardcoded path
- P1 fixes: authorization, governance bootstrap, null handling
- P2 fixes: CategoryMismatch, N+1, completion scores

### NOT IN SCOPE
- Phase 2A feature code changes
- New feature development
- Architecture modifications
- Scope expansion beyond census findings

---

## Root Cause Summary

| Severity | Families | Impact | Tests |
|----------|----------|--------|-------|
| P0 | F1, F4 | 30 | ~30 |
| P1 | F2, F3, F5, F6, F7, F8, F9, F10 | ~50 | ~60 |
| P2 | F11-F15 | ~25 | ~25 |

---

## Execution Plan

### Day 1: P0 Fixes

**F1: `skipPropertyIdGuard` (27 errors)**
```
Action: Add protected static property to Ilan model
File: app/Models/Ilan.php
Pattern: protected static bool $skipPropertyIdGuard = false;
Tests Fixed: ~27
```

**F4: Hardcoded Local Path (1 error + 2 failures)**
```
Action: Replace with storage_path()
File: tests/Feature/Api/IlanQueryConsistencyTest.php
Pattern: storage_path('logs/test_error.txt')
Tests Fixed: 3
```

**Verification:**
```bash
php artisan test --parallel 2>&1 | grep -E "Errors:|FAIL"
```

**Expected Day 1 Result:** ~190 errors → ~160 errors

---

### Day 2: Authorization Fixes

**F2: 403 Forbidden (21 failures)**
```
Focus Files:
- tests/Feature/Admin/IlanControllerAuthorizationTest.php
- tests/Feature/Admin/TalepControllerAuthorizationTest.php
- tests/Feature/Wizard/WizardStep1TemplateDataTest.php
- tests/Feature/Api/Mobile/NotificationTest.php

Root Cause: Auth setup missing in tests
Fix: Ensure actingAs() or proper user seeding
```

**F3: Governance Tenant Config (7 failures)**
```
Root Cause: No [SYSTEM] tenant governance config
Fix: Seed GovernanceTenantConfig in TestCase::setUp()
```

**F9: 404 Not Found (10 failures)**
```
Root Cause: Missing routes or middleware blocks
Fix: Verify route registration and test setup
```

**Expected Day 2 Result:** ~160 errors → ~120 errors, ~60 failures → ~40 failures

---

### Day 3: Quality Fixes

**F5: FollowUpAutomationService (2 errors)**
```
File: tests/Feature/TaskAuthorityTest.php
Fix: Add required constructor argument
```

**F6: Null Property Access (4 errors)**
```
Files: Multiple test files
Fix: Ensure fixtures properly load relations
```

**F10: 500 Internal Errors (3 failures)**
```
Files: ListingLifecycleFinalSealTest, IlanCrudTest
Fix: Investigate exception stack traces
```

**Expected Day 3 Result:** ~120 errors → ~110 errors, ~40 failures → ~25 failures

---

### Day 4: P2 Cleanup

**F7: CategoryMismatch (3 failures)**
```
Fix: Align YayinTipiSablonu junction setup
```

**F8: N+1 Query (1 failure)**
```
Fix: Add eager loading or optimize query
```

**F11-F15: Remaining Issues (~25 failures)**
```
Individual investigation per test
```

**Expected Day 4 Result:** ~110 errors → ~85 errors, ~25 failures → ~5 failures

---

### Day 5: Final Polish

**Final verification:**
```bash
php artisan test --parallel
```

**Any remaining failures:**
- Skip with `@group known-issue` + ticket reference
- Or fix if quick

**Expected Day 5 Result:**
- Errors: 0
- Failures: 0
- Skips: Documented with reasons
- CI: PASS

---

## Success Criteria

| Criterion | Target | Measurement |
|-----------|--------|-------------|
| PHP Errors | 0 | `php artisan test` exit code |
| Test Failures | 0 | Assertion count |
| CI Status | PASS | GitHub Actions |
| Documentation | Complete | Census updated |

---

## Not Allowed

- ❌ Adding `@skip` without documented reason
- ❌ Weakening assertions
- ❌ Removing CI gates
- ❌ Debt waiver as default solution
- ❌ Scope expansion
- ❌ New feature development

---

## Only Allowed

- ✅ Fixing root causes
- ✅ Adding documented `@group known-issue` skips
- ✅ Fixing test fixtures
- ✅ Aligning test expectations with code behavior
- ✅ Adding proper auth setup to tests

---

## Handoff Protocol

After each day:
1. Run focused test suite for changed files
2. Run full suite: `php artisan test --parallel`
3. Update census with progress
4. Report to stakeholders

After completion:
1. Run full suite 3x to confirm stability
2. Push to branch
3. Verify CI passes
4. Phase 2A merge readiness confirmed

---

## Key Risks

| Risk | Mitigation |
|------|-----------|
| Authorization fix reveals real security issue | Escalate to security team |
| Governance fix complex | May need architecture change |
| Test interdependencies | Run focused suites first |
| 5 days not enough | Report at Day 3 for re-evaluation |

---

## Approval Required

This charter requires SAAB approval before execution.

**Recommended approvers:**
- Chief AI Architect
- Technical Lead
- QA Lead

---

**Charter prepared by:** Kilo Agent
**Model:** Claude Opus 4.8
**Date:** 2026-08-02
**Status:** PENDING APPROVAL
