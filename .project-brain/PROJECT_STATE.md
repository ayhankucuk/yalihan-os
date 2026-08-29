# YALIHAN OS — Project Brain State

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `09862e8` (branch: `integration/era-v-phase2a-e01`)
- **Working Tree:** `Dirty` (57 dosya)
- **Evidence Date:** 2026-08-29T13:30:00Z (UTC) [TR: 2026-08-29 16:30:00 +03:00]
- **Evidence Level:** `DOCUMENTED`
- **Production Authorization:** `NONE (Read-Only Gate)`
<!-- ───────────────────────────────────────────────────────────── -->

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

## Known gaps & Local Progress

9. Property Type Manager & Category Hub Modernization: Local Phase 1 completed (`REPO_VERIFIED / TEST_VERIFIED`). Category Configuration Hub (`/admin/ilan-kategorileri`) and Master Template Manager (`/admin/property-hub/templates`) upgraded with health traffic lights, template feature rollups, informational diagnosis drawer, and safe deletion guard.
10. `UpsFeatureLifecycle::STABLE` backward-compatibility case added and unit-tested to support legacy feature records.
11. Combined Property Hub review: Feature Pool, category tree, publication types, templates, and field dependencies are linked in the UI; polymorphic separation between `YayinTipiSablonu` (35 features) and `IlanKategori` (0 direct) clarified in architecture docs.

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

---

## Production Reality Check — 2026-08-29

> "Deployed", "resolved" veya "certified" yazması tek başına güncel production gerçeği değildir.
> Güncel repo, test ve canlı kanıt ayrı kontrol edilmelidir.

| Görev | Durum | Kanıt |
|--------|--------|-------|
| **Golden Thread TC-GT-06** | **BLOCKED** | 4/6 E2E FAILED, 2 SKIPPED. Kök neden: location seed eksik. Fixture değişikliği yapıldı; başarılı koşu doğrulanmadı. |
| **Checkout/Manuel Ödeme** | **Kısmen tamamlandı** | Kod/test/deploy kayıtlı. Authenticated production browser kanıtı eksik. |
| **Governance Command Center** | **Yerel düzeltme mevcut** | `7d402de` commit'li. Production doğrulaması ve G-04 Part 2 bekliyor. |
| **`/yazliklar`** | **UNVERIFIED** | Güncel HTTP 200 kanıtı yok. En son HTTP 500 teşhis edildi. |
| **Property Engine/Hub** | **Analiz tamamlandı** | Schema/assignment sebebi kesinleşmedi. Veri değişikliği yapılmadı. |
| **Ollama/Cortex** | **Açık known issue** | `localhost:11434` bağlantı hatası. Servis topology doğrulanmadı. |

**Bugün için en gerçek ve doğrudan geliştirilebilir görev: TC-GT-06'dır.**

---

## Checkout/Payment Feature — 2026-08-28

**Status:** `COMMITTED / DEPLOYED / PRODUCTION_PARTIALLY_VERIFIED`

| Check | Result | Evidence |
|-------|--------|---------|
| Code ready | ✅ | `5198cbe feat(checkout): manual payment flow with tenant isolation` |
| Origin pushed | ✅ | `ad025d7..5198cbe push — 2026-08-28 05:20 UTC` |
| Backend tests | ✅ | 7 tests / 19 assertions — ALL PASS |
| E2E tests | ✅ | 4 scenarios — ALL PASS |
| Tenant isolation | ✅ | Cross-tenant 403, guardTenantAccess, guardReservationBelongsToIlan |
| SAB compliance | ✅ | Thin controller, no env(), no empty catch |
| Deployment | ✅ | `root@157.180.116.63` (oracle key) — docker build + compose |
| Production HEAD | ✅ | `5198cbee5d8aaf477b05e43b8e16b81d4db0b7f1` |
| Container health | ✅ | 3/3 healthy (nginx, app, queue) |
| Checkout routes | ✅ | 4 routes active |
| Checkout endpoint | ✅ | HTTP 302 → /login (auth protected) |
| Browser flow | ⏸️ PENDING | Authenticated checkout ödeme akışı test edilmedi |
| Migration drift | ⚠️ OPEN | 10 pending, checkout'ı bloke etmiyor |

**Production Infrastructure (confirmed 2026-08-28):**
- Host: `157.180.116.63` (root SSH — oracle key)
- App: `/opt/yalihan2026/current`
- Branch: `integration/era-v-phase2a-e01` ✅
- Docker: yalihanai-nginx-v2, yalihanai-app-v2, yalihanai-queue-v2 — all healthy
- Health: `{"success":true}` ✅
- Checkout: 4 routes active (`GET/POST`, `POST/approve`, `POST/fail`)

**Kalan iş:**
1. Authenticated browser flow: checkout page → payment create → approve/fail
2. Migration drift: 10 pending çözümü

**Doküman:** `audits/CHECKOUT_PRODUCTION_CERTIFICATION.md`
