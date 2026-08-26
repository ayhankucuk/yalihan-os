# YALIHAN OS — Project Brain State

Updated: 2026-08-26
Authority: repository + explicit production evidence

## Operating capability added

- Local project-brain gate: `scripts/tools/project-brain-gate.sh`.
- Scope: read-only validation of brain prerequisites, whitespace integrity, and obvious secret-bearing tracked diffs.
- It does not connect to VPS, deploy, restart containers, run migrations, or seed data.

## Product purpose

YALIHAN OS is an AI-assisted real-estate and property-operations operating system. Its first product promise is to turn a new portfolio/property record into a publication-ready listing with AI assistance, then extend that foundation into channel, property, task, and knowledge operations.

## Current phase

- ERA V Phase 2 — Autonomous Operations: ACTIVE.
- Sprint 13 — Channel Manager: documented as CERTIFIED.
- Sprint 14 — Property Command Center: documented as LAUNCHED / ACTIVE.
- Sprint 15 — Action Center: PLANNED.
- Sprint 16 — Knowledge Core AI: PLANNED.
- Current engineering focus: production hardening of the listing/wizard flow, authentication/session continuity, category and publication-type data, and the `/yazliklar` public page.
- Strategic decision: scope freeze and Golden Thread certification take priority over speculative feature expansion. The eight-step flow must pass code, automated, browser, and production evidence gates.

## Current Git evidence

- Branch: `integration/era-v-phase2a-e01`
- HEAD at brain creation: `ea0549c fix: Sanctum session domain configuration for cross-subdomain auth`
- Recent production-relevant changes: Sanctum session domain, nginx public-asset volume sync, Event/VillaService column alignment, migration ordering, tenant-aware feature assignments, wizard category/schema resolution.

## Production evidence captured in project history

- VPS: `157.180.116.63`
- Application path: `/opt/yalihan2026/current`
- Containers observed healthy: `yalihanai-app-v2`, `yalihanai-nginx-v2`, `yalihanai-queue-v2`.
- Migrations for settlement domain, bank accounts, and tenant-aware feature assignments were observed completing after fixes.
- Wizard API returned four publication types: Villa Gunluk, Villa Haftalik, Villa Aylik, Villa Sezonluk.
- Cross-subdomain session configuration was deployed.
- `/yazliklar` was observed returning HTTP 500 during diagnosis; this remains a production verification item unless a later fresh HTTP 200 capture is recorded.

## Known gaps

1. Fresh production HTTP test of `/yazliklar` after the latest deployed commit.
2. Exact Laravel exception for the current 500, captured immediately after a request.
3. Full authenticated wizard E2E: step 1 through draft save, including image upload.
4. Public listing data count and publication-status correctness for the Yazlık category.
5. UI/UX certification against the Premium Mediterranean design system on desktop and mobile.
6. Sprint 14 certification evidence, then Sprint 15 implementation and certification.
7. Knowledge Core AI explainability and source citation, planned in Sprint 16.
8. Embedding service availability: historical logs show `localhost:11434` connection failures.

## Fresh browser investigation — 2026-08-26

- `/admin/ilanlar/create` opened in the authenticated admin session, but the visible page was effectively unstyled. Its HTML referenced `/build/assets/css/app-F0wQNZdk.css`; the stylesheet was not loaded in the browser. This is a live asset-delivery issue, not a confirmed design decision.
- `/admin/property-hub` returned the Laravel `Server Error` page (HTTP 500) before its dashboard rendered. No client-side console error was needed to reproduce it.
- The listing page emitted a client-side `ReferenceError: L is not defined` from `leaflet-draw.js`; this may affect map functionality and is separate from the Property Hub server error.
- Local route/controller evidence maps Property Hub `/` to `PropertyHub\\DashboardController@index` and `PropertyHubOrchestrator::getDashboardStats()`; the exact production exception still needs a fresh Laravel log capture.

## Local repository audit — 2026-08-26

- Inventory: 354 controllers, 722 services, 232 models, 159 migrations, 478 test files.
- Current route topology contains both `routes/admin.php` and `routes/admin/property_hub.php` references for Property Hub; route loading order should be checked before consolidating.
- The repository contains multiple legacy/supporting route surfaces and redirects. Treat route ownership as an explicit drift-control item.

## Root-cause finding — frontend asset delivery

- `docker/Dockerfile.production` builds and copies `/src/public/build` into the image.
- `docker-compose.production.yml` mounts host `/opt/yalihan2026/current/public` over nginx `/app/public`.
- `public/build` is Git-ignored and the checkout contains only `public/build/manifest.json`; the host mount can therefore hide the image-built CSS/JS files.
- This explains the live missing `app.css` symptom. A deployment fix and fresh HTTP/browser verification are still required.

## Agent review — 2026-08-26

- Commit candidate ready on `integration/era-v-phase2a-e01` — diff reviewed, quality gates run.
- Three-file patch: `PropertyHubController` (`active()`→`aktif()`), `nginx/production.conf` (storage MIME whitelist + SVG block), `docker-compose.production.yml` (named storage volume, removes public overlay).
- All quality gates passed: TenantIsolationSafetyTest 6/6, full suite 2528/2869, no new sab violations, no secrets in diff.
- Production deploy requires explicit authorization; migration/seed/restart approval still open.

## Operating rule

Never mark a feature complete from code or an automated test alone. Require code evidence, relevant automated tests, and a real production/browser flow where applicable.
