# Sprint 4.2 — Task 1: CRUD Domain Assessment
**Tarih:** 2026-07-03
**Mode:** RESEARCH ONLY — Evidence Only
**Durum:** ✅ TAMAMLANDI

---

## CRUD Readiness Matrix

```
═══════════════════════════════════════════════════════════════════════
DOMAIN: ILAN
═══════════════════════════════════════════════════════════════════════

CREATE
  Route:        ✅  POST /admin/ilanlar → IlanCrudController@store
  Controller:   ✅  Thin — $this->ilanService->storeListing($input)
  Validation:   ✅  StoreIlanRequest + UpdateIlanRequest (form request classes)
  Service:      ✅  IlanService → IlanCrudService (write authority)
  Repository:   ✅  IlanRepository (read authority)
  Transaction:  ✅  DB transaction in service (event AFTER commit)
  Audit:        ✅  IlanCreated event dispatched
  Tenant:       ✅  BelongsToTenant + HasCountryScope global scopes
  Policy:       ✅  IlanPolicy (viewAny, create, view, update, delete)
  Test:         ✅  IlanControllerAuthorizationTest + IlanControllerTest
  Playwright:   ❓  IlanWizardPageTest (partial)

READ
  Route:        ✅  GET /admin/ilanlar (index) + /admin/ilanlar/{id} (show)
  Controller:   ✅  Thin — delegated to IlanService + IlanRepository
  Service:      ✅  getAdminListings, getDetailedListingAnalysis
  Repository:   ✅  findOrFail with tenant scope
  Policy:       ✅  IlanPolicy (viewAny, view)
  Tenant:       ✅  Global scope via IlanRepository
  Test:         ✅  Multiple authorization + CRUD tests
  Playwright:   ❓  Partial (wizard page)

UPDATE
  Route:        ✅  PUT /admin/ilanlar/{id} → IlanCrudController@update
  Controller:   ✅  Thin — $this->ilanService->updateListing($ilan, $input)
  Validation:   ✅  UpdateIlanRequest
  Service:      ✅  IlanCrudService
  Transaction:  ✅  Event AFTER commit
  Audit:        ✅  IlanUpdated event
  Tenant:       ✅  findOrFail before authorize (ownership check)
  Policy:       ✅  IlanPolicy update
  Test:         ✅  Authorization tests
  Playwright:   ❓  Partial

ARCHIVE (Soft Delete)
  Route:        ⚠️  No dedicated archive route. destroy() uses softDeletes.
  Controller:   ✅  destroy() → $this->ilanService->deleteListing($ilan)
  Model:        ✅  use SoftDeletes (deleted_at column)
  Service:      ✅  deleteListing() — no explicit archive method
  Audit:        ✅  IlanDeleted event dispatched
  Restore:      ❌  MISSING — No restore route/method

UPDATE (Portal)
  Route:        ✅  POST /admin/ilanlar/{ilan}/portal-ids
  Controller:   ✅  ownerPrivate + updatePortalIds (policy-gated)

DELETE (Soft)
  Route:        ✅  DELETE /admin/ilanlar/{id} → destroy()
  Controller:   ✅  Thin — $this->ilanService->deleteListing($ilan)
  Model:       ✅  SoftDeletes trait
  Service:      ✅  Event dispatched after delete
  Audit:        ✅  IlanDeleted event

───────────────────────────────────────────────────────────────────────
BLOCKING ISSUES — ILAN
───────────────────────────────────────────────────────────────────────
[P0] No restore route/method — archived ilanlar cannot be recovered
[P1] Playwright coverage incomplete — wizard page only, no full CRUD E2E
```

```
═══════════════════════════════════════════════════════════════════════
DOMAIN: KISI
═══════════════════════════════════════════════════════════════════════

CREATE
  Route:        ✅  POST /admin/kisiler → KisiController@store
  Controller:   ⚠️  @sab-ignore-thin + @sab-ignore-catch — violates SAB
  Validation:   ✅  KisiStoreRequest
  Service:      ✅  KisiService.register() (Central CRM Registration)
  Transaction:  ⚠️  Unknown — need deeper inspection
  Audit:        ✅  ActivityLog via KisiService
  Tenant:       ✅  HasCountryScope (NO BelongsToTenant)
  Policy:       ✅  KisiPolicy

READ
  Route:        ✅  GET /admin/kisiler (index) + /admin/kisiler/{id} (show)
  Controller:   ⚠️  @sab-ignore-thin
  Service:      ✅  kisiRepository->paginate() with user scoping
  Repository:    ✅  KisiRepository (tenant-aware via HasCountryScope)
  Policy:        ✅  KisiPolicy

UPDATE
  Route:        ✅  PUT /admin/kisiler/{id} → KisiController@update
  Controller:   ⚠️  @sab-ignore-thin + @sab-ignore-catch
  Validation:   ✅  KisiUpdateRequest
  Service:      ✅  KisiService
  Audit:        ✅  ActivityLog

ARCHIVE (Soft Delete)
  Route:        ✅  DELETE /admin/kisiler/{id} → destroy()
  Controller:   ⚠️  @sab-ignore-thin
  Model:        ✅  use SoftDeletes
  Service:      ✅  deleteKisi() — $kisi->delete()
  Audit:        ✅  Via KisiService

DELETE
  Route:        ✅  Same as archive (soft delete)
  Model:        ✅  SoftDeletes

RESTORE
  Route:        ❌  MISSING — No restore route
  Controller:   ❌  MISSING
  Model:       ✅  SoftDeletes (deleted_at exists, restore possible)

───────────────────────────────────────────────────────────────────────
BLOCKING ISSUES — KISI
───────────────────────────────────────────────────────────────────────
[P0] KisiController has @sab-ignore-thin + @sab-ignore-catch — FAT controller
     → SAB violations: catch blocks not logged, controller has business logic
[P0] No restore route/method
[P1] BelongsToTenant missing — only HasCountryScope (multi-country OK, but
     multi-tenant within same country NOT enforced)
[P1] Playwright: ZERO E2E tests for Kisi CRUD
```

```
═══════════════════════════════════════════════════════════════════════
DOMAIN: TALEP
═══════════════════════════════════════════════════════════════════════

CREATE
  Route:        ✅  POST /admin/talepler → TalepController@store
  Controller:   ✅  Thin — StoreTalepAction + TalepOrchestrator
  Validation:   ⚠️  Inline $request->validate() (not Form Request class)
  Service:      ✅  TalepOrchestrator + StoreTalepAction
  Transaction:  ✅  Via TalepAuthorityService
  Audit:        ✅  logActivity() in TalepAuthorityService
  Tenant:       ✅  HasCountryScope
  Policy:       ✅  TalepPolicy

READ
  Route:        ✅  GET /admin/talepler (index) + /admin/talepler/{id} (show)
  Controller:   ✅  Thin — TalepOrchestrator
  Repository:   ✅  TalepRepository
  Policy:       ✅  TalepPolicy

UPDATE
  Route:        ✅  PUT /admin/talepler/{id} → TalepController@update
  Controller:   ✅  Thin
  Validation:   ⚠️  Inline validate (not Form Request)
  Service:      ✅  TalepAuthorityService
  Audit:        ✅  logActivity()

ARCHIVE (Soft Delete)
  Route:        ✅  DELETE /admin/talepler/{id}
  Controller:   ✅  DeleteTalepAction
  Model:        ✅  use SoftDeletes
  Service:      ✅  TalepAuthorityService.deleteTalep()
  Audit:        ✅  logActivity('deleted', ...)

RESTORE
  Route:        ❌  MISSING
  Controller:   ❌  MISSING
  Model:        ✅  SoftDeletes (can restore)

DELETE
  Route:        ✅  Same as archive (soft delete)
  Model:        ✅  SoftDeletes

───────────────────────────────────────────────────────────────────────
BLOCKING ISSUES — TALEP
───────────────────────────────────────────────────────────────────────
[P0] No restore route/method
[P1] Inline validation instead of Form Request class (not reusable)
[P1] No TalepService CRUD methods — uses Actions + AuthorityService
     (Not inherently bad, but inconsistent with Ilan pattern)
[P1] Playwright: ZERO E2E tests for Talep CRUD
```

```
═══════════════════════════════════════════════════════════════════════
DOMAIN:KOMISYON
═══════════════════════════════════════════════════════════════════════

CREATE
  Route:        ✅  POST /api/v1/admin/komisyonlar
  Controller:   ⚠️  Fat — $this->komisyonService directly in controller
  Validation:   ⚠️  Inline Validator (not Form Request)
  Service:      ✅  KomisyonService
  Transaction:  ❓  Unknown — need inspection
  Audit:        ❓  Unknown — no explicit audit found
  Tenant:       ❌  MISSING — NO BelongsToTenant, NO HasCountryScope
  Policy:       ⚠️  Middleware 'can:manage-ilanlar' (not KomisyonPolicy)
  Test:         ❌  ZERO tests

READ
  Route:        ✅  GET /api/v1/admin/komisyonlar + /{id}
  Controller:   ⚠️  Fat controller — queries in controller
  Service:      ⚠️  Some logic in controller, some in service
  Policy:       ⚠️  Global middleware (not resource-based policy)

UPDATE
  Route:        ✅  PUT /api/v1/admin/komisyonlar/{id}
  Controller:   ⚠️  Fat
  Validation:   ⚠️  Inline
  Audit:        ❌  MISSING

ARCHIVE (Soft Delete)
  Route:        ✅  DELETE /api/v1/admin/komisyonlar/{id}
  Model:        ✅  use SoftDeletes
  Service:      ✅  destroy()
  Audit:        ❌  MISSING

RESTORE
  Route:        ❌  MISSING
  Controller:   ❌  MISSING
  Model:        ✅  SoftDeletes (can restore)

DELETE
  Route:        ✅  Same as archive (soft delete)
  Model:        ✅  SoftDeletes

───────────────────────────────────────────────────────────────────────
BLOCKING ISSUES — KOMISYON
───────────────────────────────────────────────────────────────────────
[P0] NO TENANT ISOLATION — Komisyon model has neither BelongsToTenant
     nor HasCountryScope. ALL komisyonlar visible to ALL tenants.
     → CRITICAL SAB VIOLATION (Tenant Isolation Rule 1)
[P0] No restore route/method
[P0] ZERO tests
[P1] Fat controller (business logic in controller)
[P1] Inline validation instead of Form Request
[P1] No audit logging
[P1] No KomisyonPolicy
```

---

## Missing Components Summary

### P0 — Blocking (Must Fix Before Certification)

| # | Domain | Op | Component | Location | Issue |
|---|--------|----|-----------|----------|-------|
| 1 | KOMISYON | ALL | Tenant Isolation | `app/Modules/Finans/Models/Komisyon.php` | NO tenant/country scope — ALL DATA VISIBLE TO ALL TENANTS |
| 2 | ILAN | RESTORE | Route + Method | routes/admin.php | No restore endpoint |
| 3 | KISI | RESTORE | Route + Method | routes/admin.php | No restore endpoint |
| 4 | TALEP | RESTORE | Route + Method | routes/admin.php | No restore endpoint |
| 5 | KOMISYON | RESTORE | Route + Method | routes/api/v1/admin.php | No restore endpoint |
| 6 | KISI | CONTROLLER | Fat controller violations | `KisiController.php:6-11` | @sab-ignore-thin + @sab-ignore-catch |

### P1 — High Priority (Should Fix During Sprint)

| # | Domain | Op | Component | Location | Issue |
|---|--------|----|-----------|----------|-------|
| 7 | ILAN | CRUD | Playwright E2E | tests/e2e/ | No full CRUD E2E tests |
| 8 | KISI | CRUD | Playwright E2E | tests/e2e/ | Zero E2E tests |
| 9 | TALEP | CRUD | Playwright E2E | tests/e2e/ | Zero E2E tests |
| 10 | KOMISYON | CRUD | Playwright E2E | tests/e2e/ | Zero E2E tests |
| 11 | TALEP | VALIDATION | Form Request | `TalepController.php:66` | Inline validation — not reusable |
| 12 | KOMISYON | VALIDATION | Form Request | `KomisyonController.php` | Inline validation |
| 13 | KOMISYON | CONTROLLER | Thin Controller | `KomisyonController.php` | Business logic in controller |
| 14 | KOMISYON | AUDIT | Audit Logging | `KomisyonService.php` | No audit log calls |
| 15 | KISI | TENANT | BelongsToTenant | `Kisi.php` | Only HasCountryScope — no multi-tenant isolation |
| 16 | KOMISYON | POLICY | Policy Class | `app/Policies/` | No KomisyonPolicy |

### P2 — Medium Priority (Can Fix After Sprint)

| # | Domain | Op | Component | Issue |
|---|--------|----|-----------|-------|
| 17 | ILAN | ARCHIVE | SoftDelete scope | Check if archived ilanlar excluded from listings |
| 18 | KISI | STORE | KisiStoreRequest | Verify validation completeness |
| 19 | TALEP | UPDATE | Inline validation | Convert to FormRequest |
| 20 | KOMISYON | STORE | Inline validation | Convert to FormRequest |

---

## Blocking Issues (P0 Only)

### P0-1: KOMISYON — NO TENANT ISOLATION (CRITICAL)
```
File: app/Modules/Finans/Models/Komisyon.php
Issue: Model has no BelongsToTenant trait and no HasCountryScope.
       Komisyon table has no tenant_id column.
       ALL komisyon records visible to ALL tenants.

Evidence:
  grep "BelongsToTenant\|HasCountryScope\|tenant_id" Komisyon.php → NO OUTPUT

Fix Required:
  1. Add tenant_id column to komisyonlar table
  2. Add BelongsToTenant trait to Komisyon model
  3. Add HasCountryScope trait to Komisyon model
  4. Add tenant_id to all queries in KomisyonService
  5. Update KomisyonController middleware to check tenant ownership

Estimated: M (medium) — 1-2h
```

### P0-2: ALL DOMAINS — NO RESTORE ENDPOINT
```
Issue: SoftDeletes used everywhere, but NO restore/unarchive mechanism.
       Once deleted, records can only be recovered via direct DB access.

Fix Required:
  1. Add route: POST /admin/{domain}/{id}/restore
  2. Add controller method: restore($id)
  3. Add service method: restoreListing($ilan)
  4. Dispatch IlanRestored event

Estimated: S (small) per domain — 30min each
```

### P0-3: KISI — FAT CONTROLLER
```
File: app/Http/Controllers/Admin/KisiController.php
Issue: @sab-ignore-thin + @sab-ignore-catch at class level
       Business logic in controller instead of service

Fix Required:
  1. Move remaining business logic to KisiService
  2. Remove @sab-ignore-thin / @sab-ignore-catch
  3. Add logging to all catch blocks

Estimated: M — 2-3h
```

---

## Recommended Implementation Order

### ✅ Phase 0: Emergency Fixes (Before Any Testing)

```
ORDER: 0
WHAT:   Fix P0 blocking issues
WHO:    Team Hermes

Tasks:
  [P0-1] KOMISYON: Add tenant isolation (BelongsToTenant + HasCountryScope)
  [P0-2] ALL: Add restore endpoints for all 4 domains
  [P0-3] KISI: Fix fat controller (remove @sab-ignore-*)
```

### Phase 1: Ilan Certification

```
ORDER: 1 — START HERE (Recommended)
RATIONALE: Ilan is the most mature domain — already has:
  • Thin controller ✅
  • Form Requests ✅
  • Service + Repository pattern ✅
  • Events (Created, Updated, Deleted) ✅
  • SoftDeletes ✅
  • Tenant scope ✅
  • Policy ✅
  • Feature tests (partial) ✅

WORK:
  1. Add restore endpoint + service method
  2. Write Playwright E2E tests for full CRUD
  3. Verify archived ilanlar excluded from listings
  4. Run IlanControllerAuthorizationTest
  5. Run IlanControllerTest

ESTIMATED: S — Half day
```

### Phase 2: Kisi Certification

```
ORDER: 2
RATIONALE: Second most mature — but needs controller cleanup first.
  • SoftDeletes ✅
  • HasCountryScope ✅ (but NO BelongsToTenant)
  • Form Requests ✅
  • KisiService (mostly clean) ✅
  • Policy ✅
  • Feature tests ✅

WORK:
  1. Fix fat controller (Phase 0)
  2. Add restore endpoint
  3. Add BelongsToTenant (optional — depends on multi-tenant requirement)
  4. Write Playwright E2E tests
  5. Run KisiControllerAuthorizationTest

ESTIMATED: M — 1 day
```

### Phase 3: Talep Certification

```
ORDER: 3
RATIONALE: Good foundation — Actions pattern is correct.
  • SoftDeletes ✅
  • HasCountryScope ✅
  • Actions (StoreTalepAction, DeleteTalepAction) ✅
  • TalepAuthorityService ✅
  • TalepPolicy ✅
  • Feature tests (authorization) ✅

WORK:
  1. Add restore endpoint
  2. Convert inline validation to StoreTalepRequest/UpdateTalepRequest
  3. Write Playwright E2E tests
  4. Run TalepControllerAuthorizationTest

ESTIMATED: S — Half day
```

### Phase 4: Komisyon Certification

```
ORDER: 4 — LAST (Most work)
RATIONALE: Greenfield — needs tenant isolation first (Phase 0).
  • SoftDeletes ✅
  • KomisyonService ✅
  • API routes (basic) ✅
  • ZERO tests ⚠️
  • NO tenant isolation ⚠️⚠️
  • Fat controller ⚠️
  • Inline validation ⚠️
  • No audit ⚠️

WORK:
  1. Phase 0: Add tenant isolation
  2. Convert fat controller to thin (KomisyonService)
  3. Create KomisyonStoreRequest + UpdateKomisyonRequest
  4. Add audit logging
  5. Create KomisyonPolicy
  6. Add restore endpoint
  7. Write Playwright E2E tests
  8. Write Feature tests

ESTIMATED: L — 2 days
```

---

## Recommended First Domain to Certify: ILAN

**Why Ilan first:**
1. Most mature codebase — closest to certification-ready
2. Thin controller ✅, Form Requests ✅, Events ✅, Tenant scope ✅
3. Establishes pattern for other domains to follow
4. Risk: LOW — most likely to pass quickly
5. AI Workforce (Sprint 4.3) depends on Ilan CRUD working correctly
6. If Ilan passes, Kisi/Talep follow the same pattern
7. If Ilan reveals issues, those lessons apply to all domains

**Certification sequence:**
```
Ilan (0.5d) → Kisi (1d) → Talep (0.5d) → Komisyon (2d) = 4 days
```

---

## Test Coverage Summary

| Domain | Feature Tests | Playwright E2E | Status |
|--------|-------------|----------------|--------|
| Ilan | ✅ Partial | ❌ Partial | 🟡 |
| Kisi | ✅ Partial | ❌ NONE | 🟡 |
| Talep | ✅ Authorization | ❌ NONE | 🟡 |
| Komisyon | ❌ NONE | ❌ NONE | 🔴 |

---

## Summary

```
BLOCKING ISSUES:    6 P0 (including 1 CRITICAL tenant isolation)
HIGH PRIORITY:    10 P1
MEDIUM PRIORITY:   4 P2

RECOMMENDED FIRST: Ilan
ESTIMATED TOTAL:   4 days (all domains)

CRITICAL RISK: Komisyon — has zero tenant isolation
               All other domains at least have HasCountryScope
```

---

*Assessment: 2026-07-03*
*Evidence: Routes, Controllers, Services, Repositories, Models, Policies*
*No code modified.*
