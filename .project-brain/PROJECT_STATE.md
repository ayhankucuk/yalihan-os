# YALIHAN OS — Project Brain State

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `81be956` (branch: `integration/era-v-phase2a-e01`)
- **Working Tree:** `Dirty` (unstaged + 2 staged: ilan-wizard-page.js + YazlikKiralamaController.php)
- **Evidence Date:** 2026-08-30T10:07:00Z (UTC) [TR: 2026-08-30 13:07:00 +03:00]
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

## CRITICAL SECURITY — Ilan Tenant Isolation Gap (2026-08-31)

**Durum:** `CRITICAL_SECURITY / GENERATE_DESCRIPTION_FIX_VERIFIED / OPTIMIZE_TITLE_SCOPE_CONFIRMED / 23_PASS / 1_SKIPPED / 0_FAIL / UNSTAGED / PRODUCTION_BLOCKED / MERKEZI_GUARD_DESIGN_PENDING`

### Test Sonuçları (2026-08-31)

| Kategori | Sonuç |
|----------|--------|
| Toplam test | 24 |
| Geçen | 23 (96%) |
| Atlanan | 1 (V2 update positive — danisman_id fixture) |
| Başarısız | 0 |
| Düzeltilen P0 açık | 1 (`generateDescription`) |

### Açık Durumu

| # | Endpoint | Analiz | Durum |
|---|----------|--------|--------|
| 1 | `POST /api/ai/generate-description` | Tenant-scoped resolve eklendi — `Ilan::query()->whereKey()->where('tenant_id')` | ✅ DÜZELTİLDİ |
| 2 | `POST /api/ai/optimize-title` | Endpoint ID parametresi kullanmıyor — sadece validated data alıyor. Tenant açığı yok. | ✅ DOĞRULANDI — ETKİLENMEDİ |

### Negatif Testler Durumu

- `BookingRequestController` — 4/4 geçti ✅ (tenant yalıtımı çalışıyor)
- `YazlikKiralamaController` — 4/4 geçti ✅ (tenant yalıtımı çalışıyor)
- `CortexSmartAPIController` AI — 1/4 geçti ❌ (generateDescription açık)
- `ReferenceController` — 1/1 geçti ✅
- `QRCodeController` — 2/2 geçti ✅
- `NavigationController` — 1/1 geçti ✅
- `SloganController` — 1/1 geçti ✅
- `V2 IlanController` — 4/4 geçti ✅ (explicit tenant_id kontrolü)

**Sonuç:** TenantScope global scope olmasa bile, **mevcut controller'ların çoğu doğru şekilde tenant yalıtımı yapıyor** — 404/403 döndürüyor. Açıklar spesifik endpoint'lerde.

### withoutGlobalScopes() Ayrımı

| Kullanım Tipi | Örnek | Durum |
|---------------|-------|-------|
| Sadece `visibility` scope kaldırma | `Ilan::withoutGlobalScopes()->where(...)` | MEŞRU ✅ |
| Tenant kontrolü ile birlikte | `TenantResolver::resolveIlan()` | MEŞRU ✅ |
| Tenant kontrolü olmadan | `ReservationService::findOrFail()` | RİSKLİ ❌ |

**Aksiyon:** Düzeltme kodu yazılmadı. Test sonuçlarına göre tasarım kararı bekleniyor.
**Envanter:** `.project-brain/ILAN_INVENTORY.md`
**Test:** `tests/Feature/Security/IlanCrossTenantIsolationTest.php`

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
- `/yazliklar` HTTP 500 — root cause: `YazlikKiralamaController` methods `calculateMonthlyRevenue`, `getBookingStats`, `getRevenueStats` used non-existent column `aktiflik_durumu` instead of `durum`. Fixed (staged: `YazlikKiralamaController.php`). Production `yazlik_rezervasyonlar.durum` confirmed via baseline migration. HTTP 200 after deploy: `UNVERIFIED` (pending production auth).

## Staged fix — 2026-08-30

### Wizard validation (`resources/js/admin/ilan-wizard-page.js`) — `TEST_VERIFIED`
- `submitForm()` now validates all 5 steps sequentially (was: only step 3)
- `nextStep()` Step 2 duplicate inline validation removed → delegates to `validateStep(2)`
- `nextStep()` Step 3 photo check removed → `validateStep(3)` is single authority
- Dead JSDoc `Legacy matrix fallback` removed
- E2E: TC-GT-01–06 all 6/6 passed (41.9s, `http://127.0.0.1:8000`)

### `/yazliklar` controller fix (`YazlikKiralamaController.php`) — `REPO_VERIFIED`
- `aktiflik_durumu` → `durum` in 3 methods; `'onaylandi'` → `'Onaylandı'`
- Production column confirmed via baseline migration `2024_01_01_000000_create_core_baseline_tables.php:931`
- `VillaService` + `Event` model: already use production column names — no change needed
- `Event.php` + `VillaService.php`: NOT staged (already correct)
- `database/migrations/2026_08_30_120000_align_yazlik_rezervasyonlar_canonical_columns.php`: BLOCKED (production auth required)

### Evidence labels
| Artifact | Status |
|---|---|
| Wizard JS fix | `TEST_VERIFIED` |
| YazlikKiralamaController fix | `REPO_VERIFIED` |
| Migration | `BLOCKED_PENDING_PRODUCTION_AUTH` |
| `/yazliklar` HTTP 200 after deploy | `UNVERIFIED` |

## Known gaps & Local Progress

9. Property Type Manager & Category Hub Modernization: Local Phase 1 completed (`REPO_VERIFIED / TEST_VERIFIED`). Category Configuration Hub (`/admin/ilan-kategorileri`) and Master Template Manager (`/admin/property-hub/templates`) upgraded with health traffic lights, template feature rollups, informational diagnosis drawer, and safe deletion guard.
10. `UpsFeatureLifecycle::STABLE` backward-compatibility case added and unit-tested to support legacy feature records.
11. Combined Property Hub review: Feature Pool, category tree, publication types, templates, and field dependencies are linked in the UI; polymorphic separation between `YayinTipiSablonu` (35 features) and `IlanKategori` (0 direct) clarified in architecture docs.

1. Fresh production HTTP test of `/yazliklar` after the latest deployed commit. Root cause identified and fix staged (see above).
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

## Worktree Durumu — 2026-08-30

**Görev:** Worktree tutarsızlığı ve staged değişiklik analizi.

### Worktree Matrisi

| Worktree | Branch | HEAD | Dirty | Sahip | Görev Amacı | Durum |
|----------|--------|------|-------|-------|-------------|-------|
| `/repos/yalihan-os` (main) | `integration/era-v-phase2a-e01` | `81be956` | 29 unstaged + 26 untracked | Kullanıcı + Kilo | Ana geliştirme | `ACTIVE_DIRTY` |
| `.codex/worktrees/0584/...` | `cleanup/playwright-mcp-screenshots-2026-08-29` | `6967cb2` | 17 untracked | Codex | Playwright MCP screenshot kanıtları | `CLEAN_UNTRACKED` |
| `.roo/worktrees/yalihan-os-9yphh` | `worktree/roo-9yphh` | `a5e14c1` | Temiz | Roo | Tamamlanmış/kullanım dışı | `CLEAN_INACTIVE` |
| `.kilo/worktrees/confirmed-nigella` | `confirmed-nigella` | `6967cb2` | Temiz | Kilo | Tamamlanmış/kullanım dışı | `CLEAN_INACTIVE` |

### Commit Karşılaştırması

| Commit | Yazar | Tarih | Mesaj | Scope |
|--------|-------|-------|-------|-------|
| `81be956` | ayhankucuk | ~Ağu 2026 | `docs(sprint-14): record property command center certification` | Sprint 14 certified |
| `6967cb2` | ayhankucuk | 23 Tem 2026 | `docs: Sprint 16 Charter + M2 milestone + PROGRESS-TRACKER update` | Sprint 16 Charter |
| `a5e14c1` | ayhankucuk | 4 Ağu 2026 | `fix(schema): add yayin_tipi_id to yayin_tipi_sablonlari` | Migration fix |

**`6967cb2` → `81be956` commit diff (kesin):** Sadece **2 dosya** değişiyor:
- `.sab/sprints/sprint-16/CHARTER.md` — 177 satır yeni dosya
- `docs/PROGRESS-TRACKER.md` — 430 satır güncelleme

**Not — kavram ayrımı:** "901 artifact" ifadesi bir önceki raporda hatalıydı. O 901 dosya **commit diff'inin** değil, **çalışma ağacının (working tree) envanterinin** bir parçasıdır. Git diff komutu çalışma ağacındaki tracked+modified dosyaları ayrı gösterir; commit diff'i ise sadece iki commit arasındaki gerçek değişiklikleri gösterir. Bu iki kavram karıştırılmamalıdır.

### Sprint 16 Charter Kararı

**Statü:** `DOCUMENTATION / REVIEWED / COMMIT_PENDING`

- Roadmap uyumu: ✅ `docs/ERA_V/PHASE2-ROADMAP.md` ile uyumlu
- Production etkisi: ✅ Yok — sadece dokümantasyon
- `.codex` ve `.kilo` aynı commit (`6967cb2`) üzerinde
- Branch senkronizasyonu gerekli değil — farklı görev alanları, force merge riskli
- main branch'e taşınabilir; **ancak commit için açık kullanıcı onayı gerekir** — dokümantasyon commit'i için bile

**KRİTİK BULGU — `6967cb2` → çalışma ağacı ilerlemesi:**

Çalışma ağacındaki `docs/PROGRESS-TRACKER.md`, `6967cb2` commit'inden **ilerdedir.** Commit 2026-07-23 (Oturum 110) tarihli; çalışma ağacı 2026-08-28 (Oturum 146) tarihli. Bu, Oturum 111–146 arasında 35 oturumluk local geliştirme içeriği demektir.

Cherry-pick veya restore ile `6967cb2` versiyonu uygulanırsa çalışma ağacındaki 632 satır ilerleme **geri alınır** — bu kabul edilemez veri kaybıdır.

**Güncellenmiş karar:**

| Dosya | Statü | Gerekçe |
|-------|-------|---------|
| `.sab/sprints/sprint-16/CHARTER.md` | `COMMIT_PENDING` | Yeni dosya, çalışma ağacında mevcut, cherry-pick güvenli |
| `docs/PROGRESS-TRACKER.md` | ❌ **YAPILMAMALI** | Çalışma ağacı `6967cb2`'den ileride — cherry-pick geri alma yapar |
| `TurkiyeLocationSeeder` | `UNTRACKED / CLONE_TEST_REQUIRED` | Seeder mevcut bozuk kayıtları silmez; sadece yeni kayıt ekler. Clone test planı hazır. |

### Untracked Migration Değerlendirmesi

| Dosya | Tablo | Risk | Durum |
|-------|-------|------|-------|
| `2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php` | `kategori_yayin_tipi_field_dependencies` | Düşük | `UNTRACKED / REVIEW_REQUIRED` |
| `2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php` | iller/ilceler/mahalleler canonical reconciliation | **YÜKSEK** | `BLOCKED` |

**2026_08_04 migration notu:** Tablo zaten `9723c2e` ile mevcut. Migration idempotent değil — tekrar çalıştırılırsa `CREATE TABLE` hatası verir.

**2026_08_26 migration:** Doğrudan primary key ID manipulation yapıyor. `down()` rollback son derece karmaşık. Production veri FK manipülasyonu gerektirir. `file_put_contents` ile `/tmp` dosya yazma — production ortamında yan etki. **GATE_BLOCKED.**

### Kararlar

- `.sab/sprints/sprint-16/CHARTER.md`: `DOCUMENTATION / REVIEWED / COMMIT_PENDING`
- `docs/PROGRESS-TRACKER.md`: `DOCUMENTATION / REVIEWED / COMMIT_PENDING`
- Migration `2026_08_04`: `UNTRACKED / REVIEW_REQUIRED`
- Migration `2026_08_26`: `BLOCKED`
- Worktree senkronizasyonu: yapılmamalı
- Production Authorization: `NONE`
- **Tüm commit'ler için açık kullanıcı onayı gerekir** — dokümantasyon commit'i için bile

---

## Production Reality Check — 2026-08-30 (Güncellendi)

> "Deployed", "resolved" veya "certified" yazması tek başına güncel production gerçeği değildir.
> Güncel repo, test ve canlı kanıt ayrı kontrol edilmelidir.

| Görev | Durum | Kanıt |
|--------|--------|-------|
| **Golden Thread TC-GT-06** | **⏳ Alpine Validation Flood — Düzeltme Gerekiyor** | TC-GT-01/02/03/04 PASS. TC-GT-05/06 browser crash — location veri doğru (81/13/20). Kök neden: `navigateStep4To5` polling döngüsü `validateStep(4)` çağırıyor; `showNotification` deduplication yok → 100+ toast → crash. Rapor: `audits/golden-thread-evidence/tc-gt-05-06-root-cause-2026-08-30.md` |
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

## Active Protocol Locks

<!--
Format: HOTSPOT_LOCK:<file_pattern>:<agent>:<timestamp_iso>:<ttl_seconds>
Managed by: ./scripts/tools/conflict-guard.sh (--acquire / --release / --list-locks)
Protected Hot-spots:
  - database/schema/mysql-schema.sql
  - database/migrations/*
  - routes/web.php, routes/api.php, routes/admin.php
  - .sab/authority.json
  - config/*.php
  - app/Services/IlanCrudService.php

Active Locks:
  - HOTSPOT_LOCK:database/migrations/2026_09_04_*:Kilo:2026-09-04T22:00:00+03:00:7200
-->
HOTSPOT_LOCK:database/migrations/2026_09_01_000000_add_ulke_tenant_to_ilanlar_for_v2_api.php:wenox-rc2:2026-09-04T19:39:36Z:3600
