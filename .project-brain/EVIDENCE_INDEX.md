# Evidence Index

## Evidence levels

- `REPO_VERIFIED`: observed in the current checkout.
- `DOCUMENTED`: stated in an authoritative project document but not freshly re-run.
- `PRODUCTION_VERIFIED`: observed through a live VPS/browser/HTTP action.
- `INFERRED`: reasoned from implementation; must not be presented as a fact.
- `UNKNOWN`: requires a new check.

## Canonical sources

- Product roadmap: `/Users/macbookpro/repos/yalihan-os/ROADMAP.md`
- Current ERA V roadmap: `/Users/macbookpro/repos/yalihan-os/docs/ERA_V/PHASE2-ROADMAP.md`
- System architecture: `/Users/macbookpro/repos/yalihan-os/docs/SYSTEM_ARCHITECTURE.md`
- Deployment runbook: `/Users/macbookpro/repos/yalihan-os/docs/production/DEPLOYMENT_RUNBOOK.md`
- Current Git history: `git log --oneline --decorate`

## Audit snapshot — 2026-08-26

- `PRODUCTION_VERIFIED`: `/admin/property-hub` HTTP 200 after migration `9723c2e` (Ayhan Küçük session, Property Hub dashboard fully rendered, all sections visible).

- `PRODUCTION_VERIFIED`: `/admin/property-hub` returned HTTP 200 after targeted migration `9723c2e` (Ayhan Küçük authenticated session, full dashboard rendered including Özellik Sayısı, Yayın Tipi Yönetimi, Analytics, Template Manager). Browser evidence via Kilo chrome-devtools session 2026-08-26.
- `PRODUCTION_VERIFIED`: Copilot modal buttons (İptal, Escape, backdrop click) verified closing modal correctly. `window.ilanWizard()` singleton confirmed reachable. No console errors. Deployed via `a0a52bf`.

- `REPO_VERIFIED`: current branch is `integration/era-v-phase2a-e01` at `a0a52bf`.
- `REPO_VERIFIED`: Property Hub dashboard route is defined in `routes/admin/property_hub.php` and points to `App\\Http\\Controllers\\Admin\\PropertyHub\\DashboardController`.
- `REPO_VERIFIED`: listing wizard route is defined in `routes/admin.php` and points to `IlanCrudController@create`.
- `PRODUCTION_VERIFIED`: browser reproduced Property Hub HTTP 500 and missing main stylesheet on listing creation page.
- `PRODUCTION_VERIFIED`: read-only SSH audit reached `157.180.116.63`; checkout path `/opt/yalihan2026/current` was present, branch `integration/era-v-phase2a-e01` was reported at `ea0549c`, and the three production containers were healthy. Source: Antigravity/Kilo SSH BatchMode audit, 2026-08-26.
- `REPO_VERIFIED`: local read-only project-brain gate passed after adding `scripts/tools/project-brain-gate.sh`; required brain files, `git diff --check`, and obvious secret-file checks passed.
- `PRODUCTION_VERIFIED`: browser check on 2026-08-26 navigated `/admin/property-hub`, `/admin/ilanlar/create`, and `/advisor/portfolio/doctor`; all three redirected to `/login`. Authenticated UI/E2E verification was therefore blocked.

## Session 68 — 2026-08-26 (hardening commit review)

- `REPO_VERIFIED`: commit candidate staged on branch `integration/era-v-phase2a-e01`; diff covers 2 production files.
- `REPO_VERIFIED`: `PropertyHubController.php` line 61: `active()` → `aktif()` — `KategoriYayinTipiFieldDependency::aktif()` scope exists at line 52 of the model; original `active()` scope did not exist (naming authority violation + runtime bug fix).
- `REPO_VERIFIED`: `docker/nginx/production.conf` — replaced blanket `internal` storage directive with strict MIME whitelist for raster images (jpg/jpeg/png/webp) + `deny all` fallback for all other `/storage/` paths. Blocks SVG XSS vector.
- `REPO_VERIFIED`: `docker-compose.production.yml` — replaced host `public` overlay mount with named volume `yalihan-storage:/app/storage:ro`; fixes the confirmed missing `public/build` CSS/JS delivery in production.
- `TEST_VERIFIED`: `TenantIsolationSafetyTest` — 6/6 PASS (12 assertions); tenant A cannot read/update/delete tenant B data.
- `TEST_VERIFIED`: Full suite — 2528 passed, 341 failed (pre-existing, unrelated); `PropertyHubDashboardHardeningTest` fails on SQLite concurrency lock (pre-existing, not caused by these changes).
- `DOCUMENTED`: `sab:integrity-scan` reports 131 naming authority violations; all pre-existing, none introduced by this patch. Direction of fix (`active()` → `aktif()`) is correct per naming authority rules.
- `DOCUMENTED`: `bekci:health` overall 33.4% — App Runtime Health 100%, MCP 0%, Project Health 59.25%. Pre-existing structural issues, no regression from this patch.
- `REPO_VERIFIED`: no secrets, credentials, or private identifiers in staged diff.

## Drift warnings

## Session 2026-08-29 — Sprint 14 Governance Command Center

- `REPO_VERIFIED`: `GovernanceCommandCenter` schema mismatch corrected locally: decisions use `governance_decisions.karar_tarihi`; violation telemetry uses `governance_events.occurred_at` and `is_violation`.
- `REPO_VERIFIED`: `tests/Feature/Admin/GovernanceCommandCenterTest.php` passes — 1 test, 2 assertions. PHP syntax checks and `git diff --check` pass.
- `UNKNOWN`: production deployment and live HTTP re-verification; explicitly not performed pending G-04 timing approval.

- `chief-ai/sprint-backlog.md` is older than the ERA V roadmap and may describe historical priorities.
- Chat/browser statements are evidence only when accompanied by a date, URL/command, result, and commit or environment context.
- VPS state can drift from the local checkout; record the deployed commit separately.
- The current local HEAD is `f1cef01`, while the last supplied VPS audit reported `ea0549c`; these are separate environments and must not be conflated.
