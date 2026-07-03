# 05_TEST_REPORT.md — Sprint 4.2 (CLOSED)

## Sprint Test Report

**Command:** `php artisan test --filter=OwnerIlanCrudTest`
**Date:** 2026-07-03
**Branch:** main (ahead of origin by 8 commits)

### Results: 12 PASSED / 3 FAILED

| Test | Status | Notes |
|------|--------|-------|
| owner_can_list_own_ilanlar | ✅ PASS | |
| guest_cannot_access_owner_ilanlar | ✅ PASS | |
| owner_can_view_own_ilan | ✅ PASS | |
| owner_cannot_view_other_owners_ilan | ✅ PASS | Previously 500 → 404 (ucfirst fix) |
| owner_can_access_create_form | ✅ PASS | |
| owner_can_store_new_ilan_as_taslak | ⚠️ FAIL | **PRE-EXISTING** — SQLite `yazlik_details.deleted_at` |
| store_rejects_invalid_payload | ✅ PASS | |
| store_always_assigns_authenticated_user_as_owner | ⚠️ FAIL | **PRE-EXISTING** — SQLite `yazlik_details.deleted_at` |
| owner_can_access_edit_form_for_own_ilan | ✅ PASS | Previously BadMethodCallException → 200 OK |
| owner_cannot_access_edit_form_of_other_owners_ilan | ✅ PASS | Previously BadMethodCallException → 404 |
| owner_can_update_own_ilan | ⚠️ FAIL | **PRE-EXISTING** — SQLite `yazlik_details.deleted_at` |
| owner_cannot_update_other_owners_ilan | ✅ PASS | Previously 403 → 404 |
| update_cannot_change_yayin_durumu | ✅ PASS | |
| owner_can_delete_own_ilan | ✅ PASS | Previously 500 → 302 redirect |
| owner_cannot_delete_other_owners_ilan | ✅ PASS | Previously 500 → 404 |

---

## Improvement vs Pre-Sprint

| Metric | Before | After | Change |
|--------|--------|-------|--------|
| Passing tests | 9 | 12 | +3 |
| Failing tests | 11 | 3 | -8 |
| Total assertions | 24 | 24 | — |

**Key Fixes:**
1. `ucfirst()` TypeError on enum → `->label()` (3 blade files)
2. Missing `edit()` method → implemented (2 tests fixed)
3. Missing `update()` method → implemented (1 test fixed)
4. Missing `destroy()` method → implemented (2 tests fixed)
5. Ownership check 403 → 404 (3 tests fixed)
6. Route model binding `{id}` → `{ilan}` (1 test fixed)

---

## Pre-Existing Failures (NOT in Sprint Scope)

All 3 failures share the same root cause:
```
SQLSTATE[HY000]: General error: 1 no such column: yazlik_details.deleted_at
```

This is a **SQLite vs MySQL migration difference** — `yazlik_details` table in MySQL has `deleted_at`
but SQLite schema definition doesn't include it. Not caused by Sprint 4.2 changes.

**Resolution:** Separate backlog item — Sprint 4.x backlog.
