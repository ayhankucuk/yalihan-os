# Task: Authenticated Browser E2E Flow Verification

- **Task ID:** `TASK-E2E-AUTH-001`
- **Created Date:** 2026-08-26
- **Status:** `BLOCKED_AWAITING_AUTH_SESSION`
- **Target Surfaces:**
  1. `/admin/property-hub` (Property Hub Dashboard)
  2. `/admin/ilanlar/create` (Listing Wizard Step 1–5)
  3. `/advisor/portfolio/doctor` (Portfolio Doctor AI)
  4. `/admin/integrations` (Automation Hub)

---

## 1. Context & Objective

During browser subagent / E2E test runs without an active authenticated cookie session, all protected admin and advisor routes redirect to `/login`.

The objective of this task is to perform an authenticated, end-to-end browser inspection once a valid test session / authenticated context is provided, without storing or exposing raw credentials.

---

## 2. Verification Checklist (When Unblocked)

- [ ] **Session Continuity:** Authenticated cookie persistence across `/admin/*` and `/advisor/*`.
- [ ] **Asset Delivery:** Confirm `/build/assets/app-*.css` and `/build/assets/app-*.js` load with HTTP 200 (no 404/shadowing).
- [ ] **Property Hub Dashboard:** Verify HTTP 200 and visual rendering of 22 features / 42 field dependencies.
- [ ] **Listing Wizard Flow:**
  - Step 1: Category & publication type selection.
  - Step 2: Dynamic fields rendered by Property Engine.
  - Step 3: Location map picker (Confirm zero `ReferenceError: L is not defined`).
  - Step 4: Photo upload raster validation.
  - Step 5: Draft save execution (`IlanCrudService`).
- [ ] **Portfolio Doctor AI:** Confirm 9 health signals and AI treatment recommendations render visually.
- [ ] **Browser Console:** Zero uncaught JavaScript errors or network 500s.

---

## 3. Evidence Status

- `VPS_CONTAINERS`: `PRODUCTION_VERIFIED`
- `LOCAL_PHPUNIT_HARDENING`: `TEST_VERIFIED` (6 tests, 22 assertions OK)
- `ADMIN_BROWSER_E2E`: `BLOCKED` (Redirects to `/login`)
- `WIZARD_E2E`: `UNKNOWN` (Pending authenticated session)
