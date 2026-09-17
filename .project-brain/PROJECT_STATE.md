---
document_id: BRAIN-STATE-001
document_owner: engineering-lead
decision_owner: product-owner
status: active
canonical: true
  evidence_level: TEST_VERIFIED
  as_of_commit: 911e4e3c
last_reviewed: 2026-09-14
review_after: 2026-09-28
supersedes: null
---

# YALIHAN OS — Project Brain State

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `e8a90cda` (HEAD) — RC2 dirty tree fully cleaned, 3 hygiene skills added
- **Branch:** `release-candidate/RC2`
- **Working Tree:** DIRTY — 2 dosya değişti (`app/Repositories/IlanRepository.php`, `docs/BEKCI_CHANGELOG.md`)
- **Evidence Date:** 2026-09-14T14:00:00+03:00
- **Evidence Level:** `TEST_VERIFIED` — `admin/ilanlar` zero-results root cause: `backedEnum + groupBy` + `TenantScope whereRaw('1=0')` — both layers fixed
- **admin/ilanlar Fix:** `IlanService` stat sorguları → `DB::table()` facade + `CAST(yayin_durumu AS CHAR)`, `IlanRepository` → `withoutGlobalScopes()` + explicit `tenant_id`
- **Production Authorization:** BACKFILL + FAIL_CLOSED AUTHORIZED (OPERATOR/Saab 2026-09-08)
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

## Active Architectural Gate — Proje Domain Status (ADR #006 — 2026-09-17)

- **ADR #006:** Accepted (`.project-brain/DECISION_LOG.md`).
- **Canonical Commit:** `3ced67c16f228f50ba1b375a0b15fcd9acf00718` — "fix(emlak): complete ADR #006 project context separation"
- **Takım Projesi Authority:** `PRODUCTION_VERIFIED` (`projeler` table, `App\Models\Proje` isolated).
- **Emlak Projesi Production Persistence:** `PRODUCTION_VERIFIED` (`emlak_projeleri`, `emlak_proje_translations`, `emlak_proje_gorselleri` tables created via path-constrained migration `2026_09_17_000001`).
- **Ilan Relationship Column:** `PRODUCTION_VERIFIED` (`ilanlar.proje_id` added via path-constrained migration `2026_09_17_000002`).
- **Production Status:** `ADR006_PRODUCTION_VERIFIED` (Deployed and empirically verified on live production server `ubuntu-8gb-hel1-1`).
- **Production Verification Detail:**
  - Migrations 000001 and 000002 executed cleanly via container path-constrained migration (`php artisan migrate --path=...`).
  - Bounded application models (`App\Modules\Emlak\Models\Proje`, updated `App\Models\Ilan`), controllers (`ProjeController`), and web routes deployed to `/app`.
  - Production database schema & model empirical probes verified: `emlak_projeleri` (1), `emlak_proje_translations` (1), `emlak_proje_gorselleri` (1), `ilanlar.proje_id` (1), `Ilan::proje()` contract (resolves to `App\Modules\Emlak\Models\Proje`).
  - Pre-deploy Quality Gate & Local Regression Suite: `6/6 PASS` (`EmlakProjeBoundedContextTest.php`).

## Active Governance Gate — G2.3 Agent–Skill Router Status (2026-09-17)

- **ADR #007:** Accepted (`.project-brain/DECISION_LOG.md`).
- **Specification:** [`docs/architecture/AGENT_SKILL_ROUTER.md`](file:///Users/macbookpro/repos/yalihan-os/docs/architecture/AGENT_SKILL_ROUTER.md) (v2.0.0).
- **Status:** `CANONICAL OPERATIONAL ROUTING CONTRACT` (Accepted by Human Decision Owner Ayhan).
- **Authority Source:** `NO` (`AGENTS.md` and `.sab/authority.json` remain Authority SSOT).
- **Evidence Level & Type:** `TEST_VERIFIED / TOOL_RUNTIME` (Empirically verified via G2.3 pilot, zero working tree mutations).
- **Hierarchy Standard:**
  - `Human Decision Owner`: Ayhan
  - `Session Owner`: Antigravity Parent Agent
  - `Router`: Engine (`CLASSIFY → ROUTE → CONSTRAIN → HANDOFF`)
- **Actor-Aware Human Gate:** 6 critical AI side-effects guarded; human operator manual actions free.
- **Model-Agnostic Roles:** Research, Forensic Research, Architect, Implementer, Verifier, Integrator.

## Active Security Gate — V2 Users API Tenant Isolation (2026-09-17)

- **Commit:** `a1f2d1681c27961e641cb18604ad6cbe4904d813` — "fix(security): secure V2 users API tenant boundaries"
- **Status:** `PRODUCTION_VERIFIED / COMMITTED`
- **Production Status:** `V2_USERS_SECURITY_PRODUCTION_VERIFIED` (Deployed and empirically verified on live production server `ubuntu-8gb-hel1-1`)
- **Production Runtime Evidence:**
  - Unauthenticated `GET /api/v1/users` → **HTTP 401 Unauthorized** (`{"success":false,"data":null,"error":{"code":"AUTH_REQUIRED"}}`)
  - Unauthenticated `GET /api/v1/users/{user}` → **HTTP 401 Unauthorized**
  - Zero PII / user data exposed to unauthenticated callers.
- **Defect Resolved:** Tenant A POST `/api/v1/users` correctly assigns `tenant_id`; cross-tenant isolation enforced.
- **Security Guarantees:**
  - Normal tenant users: created users inherit authenticated `tenant_id`
  - Client-supplied `tenant_id` injection: **BLOCKED** (server overwrites with auth tenant)
  - Cross-tenant isolation: `GET/PUT/DELETE` operations remain blocked (404 for cross-tenant)
  - Database integrity: Cross-tenant update/delete attempts leave target records unchanged
- **Test Coverage:**
  - `V2UsersApiSecurityTest.php`: 12 tests / 29 assertions — **100% PASS**
  - `DanismanSeedTenantIntegrityTest.php`: 8 tests / 45 assertions — **100% PASS**
- **Files Modified:**
  - `app/Http/Controllers/Api/V2/UserController.php` (tenant ownership enforcement in `store()`)
  - `app/Actions/Api/V2/User/StoreUserAction.php` (`tenant_id` parameter acceptance)
  - `tests/Feature/Api/V2UsersApiSecurityTest.php` (comprehensive security contract tests)
  - `routes/api/v1/v2-users.php` (route corrections)
- **Known Issues Resolved:**
  - `[PUBLIC-API-USERS-DATA-EXPOSURE]`: `CLOSED / PRODUCTION_VERIFIED`
  - `[V2-USERS-ROUTE-MODEL-BINDING-MISMATCH]`: `CLOSED / PRODUCTION_VERIFIED`

## Effective Production Baseline State (2026-09-18)

- **Base Git Commit:** `0f2489402d5b7882340bd4b485fba92b3a74f06b`
- **Effective Production Runtime State:** `base 0f248940 + bounded deployed artifacts from a1f2d168 + 3ced67c1`
- **Unrelated RC2 Commits Deployed:** `0` (Zero unrelated RC2 commits deployed)
- **Production Local Backups:** Preserved (`LOCKED,`, `backups/`, `mysql-schema.sql.bak`)

## Sprint 14 Certification — Hermes Hardening Findings (Oturum 184 — 2026-09-14)

**Sprint 14:** `CONDITIONAL_CERTIFIED` (board-approved 2026-09-11) — G-01..G-04 Part 1 PASS
- G-04 Part 2 (operator timing): BLOCKED — API yokluğu, production'da operator ölçümü bekleniyor

**3 Critical Hermes Runtime Findings — STATUS:**
| ID | Bulgu | Status | Kanıt |
|----|-------|--------|-------|
| H-01 | PropertyScoreAgent namespace/directory mismatch | ✅ RESOLVED (2026-08-28) | namespace Workforce→Workflow |
| H-02 | DriveAgent constructor eksik DriveWebhookService | ✅ RESOLVED (2026-08-28) | ServiceProvider'a eklendi |
| H-03 | NotificationAgent subscribesTo event mismatch | ✅ RESOLVED (2026-08-28) | subscribesTo: workforce.publishing.decision_ready |

**Non-blocking / LOW:**
| ID | Bulgu | Status |
|----|-------|--------|
| H-05 | PropertyScoreAgent in-memory buffer | ✅ RESOLVED — Cache 24h TTL + chain_id propagation |
| H-07 | DriveAgent sync execution | ⏳ AÇIK — Non-blocking, queue'ya geçiş planlanacak |
| H-10 | TelegramNotificationHandler stub | ⏳ AÇIK — Non-blocking, dış servis bağlantısı bekleniyor |

**Sprint 14 Workforce Chain Tests:**
- `WorkforceAgentsTest.php`: 22 PASS / 108 assertions ✅
- E2E zincir: `workforce.workspace.created` → tüm ajanlar → notification ✅

**Sprint 15 (Action Center):** LAUNCHED — 53 PASS (c44dc8ad) — Herme'siz bağımsız çalışıyor

## RC2 Hygiene Skills (2026-09-14)

Oturum 183'te öğrenilen 3 yeni agent skill:
- `git-worktree-hygiene` — Dirty tree'yi temiz state'e getirir, kararları loglar
- `conflict-guard-preflight` — Commit öncesi hot-spot taraması + lock kontrolü
- `dirty-inventory-generator` — Dirty dosyaları otomatik sınıflandırma + önceliklendirme

## Bekçi MCP — Stdio Transport (2026-09-14)

MCP server `yalihan-bekci-mcp.js` **stdio subprocess** olarak çalışır (HTTP server değil).
Health check artık `pgrep` ile process check yapıyor — eski HTTP probe yanlıştı.
Overall score: **79%** (önceki 59% — MCP artık doğru algılanıyor).
## Workspace Execution Tenant Isolation (2026-09-17)

**Session 20:** `WorkspaceExecutionTenantIsolationTest.php` — 17 PASS / 17 ✅
- `WorkspaceExecutionController::cancel()`, `retry()`, `replay()` → policy tabanlı tenant kontrolü
- `TenantScope` global scope + explicit `tenant_id` korunuyor
- Super-admin cross-tenant erişim test edildi
- Regression: `tests/Feature/Workspace/` → 28/28 PASS ✅

---

## Bekçi MCP — Stdio Transport (2026-09-14)

## BEKCI-IMMUNE-V1 Kararı (2026-09-14)

KABUL EDİLDİ. Uygulama ayrı worktree'de yapılacak:
`worktree-bekci-immune/feature/BEKCI-IMMUNE-V1`

5'li öğrenme döngüsü: Incident → Bug Class → Rule Proposal → Approval Gate → Permanent Guard

Kaynak: `.project-brain/DECISION_LOG.md` — "BEKCI-IMMUNE-V1" section.

## Architect ↔ Bekçi MCP Entegrasyonu (2026-09-14)

`yalihan-os-architect` skill'i artık Yalihan Bekçi MCP tool'larını çağırıyor:
- `check_violation`, `get_authority`, `get_canonical`, `validate_file`, `get_project_health`, `record_learning`, `get_audit_report`, `get_learning_history`

Entegrasyon noktası: `.agents/skills/yalihan-os-architect/SKILL.md` — "MCP Entegrasyon Noktaları" section.

Agent pipeline artık: Architect skill → MCP tools → PHP Artisan → sonuç.
- Sprint 13 — Channel Manager: documented as CERTIFIED.
- Sprint 14 — Property Command Center: documented as LAUNCHED / ACTIVE.
- Sprint 15 — Action Center: PLANNED.
- Sprint 16 — Knowledge Core AI: **IN_PROGRESS** (2026-09-14). Migration `2026_09_06_000001` çalıştırıldı. 4 yeni API endpoint + 3 service sınıfı eklendi.
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
| `/repos/yalihan-os` | `release-candidate/RC2` | `3638a978` | 53 dirty | Bu oturum | RC2 geliştirme (governance/dokümantasyon) | `ACTIVE_DIRTY` |
| `.codex/worktrees/0584/...` | `cleanup/playwright-mcp-screenshots-2026-08-29` | `6967cb2` | 17 untracked | Codex | Playwright MCP screenshot kanıtları | `CLEAN_UNTRACKED` |
| `.roo/worktrees/yalihan-os-9yphh` | `worktree/roo-9yphh` | `a5e14c1` | Temiz | Roo | Tamamlanmış/kullanım dışı | `CLEAN_INACTIVE` |
| `.kilo/worktrees/confirmed-nigella` | `confirmed-nigella` | `6967cb2` | Temiz | Kilo | Tamamlanmış/kullanım dışı | `CLEAN_INACTIVE` |
| `.worktrees/codex-security-wizard-01` | `codex/security-wizard-feature-suggestions-01` | `3638a978` | 1 untracked | Bu oturum | SECURITY-WIZARD-FEATURE-SUGGESTIONS-01 | `ACTIVE_DIRTY` |
| `.worktrees/codex-bekci-agent-preflight` | `codex/bekci-agent-preflight` | `3638a978` | Temiz | Codex | Bekçi agent preflight | `CLEAN_INACTIVE` |
| `.worktrees/codex-constitution-review-workflow` | `codex/constitution-review-workflow` | `67a07e43` | Temiz | Codex | Constitution review | `CLEAN_INACTIVE` |

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
| **Golden Thread TC-GT-06** | **✅ FULL PASS — 6/6 Browser Verified** | 2026-09-09: `tests/e2e/golden-thread-wizard.spec.ts:425` — `'cephe': 'guney'` → `'cephe': 'cadde-cepheli'` (schema whitelist fix). HTTP 422 → HTTP 200, redirect `/admin/ilanlar/75/edit`. 6/6 PASS — 39.8s. Rapor: `audits/golden-thread-evidence/RC2-CERTIFICATION-2026-09-08.md` |
| **Checkout/Manuel Ödeme** | **Kısmen tamamlandı** | Kod/test/deploy kayıtlı. Authenticated production browser kanıtı eksik. |
| **Governance Command Center** | **Yerel düzeltme mevcut** | `7d402de` commit'li. Production doğrulaması ve G-04 Part 2 bekliyor. |
| **`/yazliklar`** | **UNVERIFIED** | Güncel HTTP 200 kanıtı yok. En son HTTP 500 teşhis edildi. |
| **Property Engine/Hub** | **Analiz tamamlandı** | Schema/assignment sebebi kesinleşmedi. Veri değişikliği yapılmadı. |
| **Ollama/Cortex** | **Açık known issue** | `localhost:11434` bağlantı hatası. Servis topology doğrulanmadı. |

**Bugün için en gerçek ve doğrudan geliştirilebilir görev: `/yazliklar` HTTP 500 doğrulama + çözüm.**

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
  - HOTSPOT_LOCK:config/canonical_tables.php:Kilo:2026-09-05T12:35:00+03:00:7200

---

## TenantScope Fail-Closed & Backfill — 2026-09-08

**Commit:** `fe17dd5c` (cherry-pick from `kilo/tenant-backfill:098affe9`)
**Evidence:** TEST_VERIFIED — 30/30 PASS

### Fail-Closed TenantScope (Kural 1)

`app/Scopes/TenantScope.php` — `else { $builder->whereRaw('1 = 0'); }`:

```php
if ($tenantService->hasTenant()) {
    $builder->where($model->getTable() . '.tenant_id', $tenantService->getTenant()->id);
} else {
    // Fail-closed koruması: Tenant context yoksa hiçbir veriyi döndürme (Kural 1)
    $builder->whereRaw('1 = 0');
}
```

### Backfill Migration

`database/migrations/2026_09_08_000002_backfill_tenant_id_null_records.php`:
- ilanlar: `9 kayıt` backfill (kisiler.tenant_id üzerinden)
- users: `6 kayıt` backfill (kisiler.tenant_id üzerinden)
- Orphan kayıtlar (ilanlar: 8, users: 40): NULL kalır — fail-closed zaten korur
- **BLOCKED_PENDING_PRODUCTION_AUTH** — Operator 2026-09-08 onayladı

### Operator/SAAB Onayı (2026-09-08)

- Backfill migration production: **AUTHORIZED**
- TenantScope fail-closed: **AUTHORIZED**
- Kilo backfill worktree: `kilo/tenant-backfill` (c669bcad, fe17dd5c)

HOTSPOT_LOCK:database/migrations/2026_08_30_120000_align_yazlik_rezervasyonlar_canonical_columns.php:antigravity:2026-09-08T18:06:21Z:3600
HOTSPOT_LOCK:database/migrations/2026_09_08_000001_add_tenant_id_to_cqrs_projection_tables.php:antigravity:2026-09-08T18:06:21Z:3600

---

## 2026-09-08 RC2+1 — Sprint 15 Phase 2 İcra (Kilo)

**Commit:** `114802bd` — Action Center Phase 2 auto-assignment + API
**Production:** HTTP 200 ✅ (Hetzner VPS `157.180.116.63`)
**Quality Gates:** 4/4 PASS

### Action Center Phase 2 — Tamamlanan

| Komponent | Dosya | Açıklama |
|-----------|-------|-----------|
| `ActionAssignmentService` | `app/Services/ActionCenter/` | 3 strategy: owner, round-robin, workload-balanced |
| `ActionCenterController` | `app/Http/Controllers/Api/V1/` | 7 REST endpoint |
| API Routes | `routes/api/v1/action-center.php` | `auth:sanctum`, tenant-isolated |

### Sprint 15 Durumu

| Phase | Durum | Kanıt |
|-------|-------|-------|
| Phase 1 (Event→Gorev listeners) | ✅ Tamam | 11 listener wired in EventServiceProvider |
| Phase 2 (Auto-assignment + API) | ✅ Tamam | `114802bd` |
| Phase 3 (action_evidence + trackEvidence + lifecycle) | ✅ Tamam | `d121b3da` |

---

## 2026-09-12 — Oturum 176: Wizard ERA V FAZ 4B-3 + FAZ 5

**Commit:** `d592f404`
**Evidence Level:** REPO_VERIFIED
**Quality Gates:** 6/6 PASS (antigravity-full-gate)

### Tamamlanan
| Faz | Görev | Durum |
|-----|-------|-------|
| 4B-3 | Legacy submitWizard → WizardStepExecutor delegation | ⏸️ NO-OP — WizardStepExecutor prodüksiyonda kullanılmıyor |
| 5 | LocationValidationCapability boundary migration | ✅ DONE — IlanWizardController coordinate validation → domain service |

### Mimari Karar
- `IlanWizardController::validateAsama3()` içindeki `validateCoordinates()` (Turkey-wide: 36.1-42.1 / 26.1-44.8) kaldırıldı.
- `LocationValidationCapability` (Muğla-specific: 36.12-37.35 / 26.25-29.75) enjekte edildi. Artık tek yetkili domain validator.
- `RealityCheckException` ile standardize error handling.

### Değişen Dosyalar
- `app/Http/Controllers/Api/IlanWizardController.php`

---

## 2026-09-08 — Sprint 15/RC2 Mühürleme & Açık Madre Durumu

**Commit:** `911e4e3c` (P2-DS-01) + `331fd10a` (BACKLOG-02) + `8b1ca956` (E2E)
**Lead Architect Review:** Antigravity — 2026-09-08T18:43
**Production:** HTTP 200 ✅ (Hetzner VPS `157.180.116.63`)

### Madde Durumları — REPO_VERIFIED

| # | Madde | Gerçek Durum | Kanıt |
|---|-------|-------------|-------|
| A | `category_field_schema` tablosu drop migration | ❌ **YOK — TABLO HİÇ OLUŞMADI** | `kategori_yayin_tipi_field_dependencies` gerçek tablo; 4 Admin controller aktif kullanıyor. Drop = Admin çökmesi. |
| B | TC-GT-09/10 (Arsa Kiralık + İşyeri Devren E2E) | ✅ **TAMAMLANDI** | `8b1ca956` — Playwright 4/4 PASS (14.3s) |
| C | FeatureTemplateResolver birleştirme planı | 📋 **Planlandı (Sonraki Sprint)** | `docs/architecture/RESOLVER_CONSOLIDATION_PLAN.md` — 218 satır, `911e4e3c` mühürlü |
| D | `config/arsa-dictionaries.php` deprecation | ✅ **ZATEN DEPRECATED** | `@deprecated` docblock mevcut; kod tabanında 0 aktif tüketici; SSOT: `config/yali_options.php` |

### RC2 Kalite Güvenceleri

- Quality Gates: **4/4 PASS** — Conflict Guard, Preflight Guard, Layout Validator, Route Duplication
- Golden Thread E2E: **8/8 PASS** — TC-GT-01..10
- TenantScope: **30/30 PASS** — fail-closed + V2 isolation
- Secrets: **0 tespit** — Secret Scan tüm staged dosyalarda temiz

### Teknik Borç Durumu — Net

Mevcut sprint'te icra edilen tüm teknik borç maddeleri ya tamamlanmış ya da doğru şekilde konumlandırılmıştır. Önümüzdeki sprint öncelikleri:
1. VPS'e son 2 commit sync'i (`git pull`) — rutin operasyonel
2. `kategori_yayin_tipi_field_dependencies` → aktif admin kullanım analizi (Sistem B miras)
3. FeatureTemplateResolver Faz 1 (shared trait)
HOTSPOT_LOCK:database/migrations/2026_09_06_000001_add_ilceler_il_id_foreign_key.php:kilo-ilce-fk-fix:2026-09-10T21:07:36Z:7200

HOTSPOT_LOCK:database/migrations/2026_09_06_000001_add_ilceler_il_id_foreign_key.php:kilo-ilce-fk-fix:2026-09-10T21:07:36Z:7200

---

## 2026-09-12 — Talep Domain Strangler Fig (Adım 2.1)

**Branch:** `release-candidate/RC2` — unstaged artifacts

### Açık P0 Güvenlik Görevleri
| ID | Açıklama | Durum | Öncelik |
|---|---|---|---|
| `SECURITY-WIZARD-FEATURE-SUGGESTIONS-01` | Wizard approve/rollback: auth:sanctum + tenant.context + role:admin|super_admin + 2 savunma hattı | DESIGN_APPROVED | P0 |

### Blokeli Ürün Kararları
| ID | Karar | Blokeli | Öncelik |
|---|---|---|---|
| `TENANT-FEATURE-ASSIGNMENT-01A` | A/B ürün kararı | SECURITY-WIZARD-FEATURE-SUGGESTIONS-01 | P0 |

### Önceki Kararlar (Düzeltilmiş)
- `TENANT-FEATURE-ASSIGNMENT-01A` → `BLOCKED_PENDING_SECURITY` (önceki: `A — GLOBAL_TEMPLATE_ONLY`, hatalı)

### Eklenen Artifact'lar — REPO_VERIFIED

| Dosya | Tür | Açıklama |
|-------|-----|----------|
| `app/Domain/CRM/Contracts/TalepRepositoryInterface.php` | Driven Port | TalepRepository kontratı |
| `app/Domain/CRM/DTOs/TalepCreateCommand.php` | DTO | Immutable creation input (spillover-aware) |
| `app/Domain/CRM/DTOs/TalepListCriteria.php` | DTO | Immutable query criteria |
| `app/Domain/CRM/Services/ListTaleplerUseCase.php` | Application Service | Listeleme + stats + form data |
| `app/Domain/CRM/Services/CreateTalepUseCase.php` | Application Service | Oluşturma + Kişi spillover |
| `app/Infrastructure/CRM/EloquentTalepRepositoryAdapter.php` | Adapter | Legacy repo → domain interface |
| `tests/Unit/Domain/PropertyHub/CRM/TalepDomainCharacterizationTest.php` | Karakterizasyon | 21 PASS / 1 SKIP (KisiScoringService bug) |

### Mimari Kararlar
- `EloquentTalepRepositoryAdapter`: `search` → `q`, `status` → `talep_durumu`, `il_id` post-filter (legacy repo eksik)
- `CreateTalepUseCase`: `kisi_tipi = 'lead'` → `KisiTipi::LEAD` (NOT 'Potansiyel')
- `TalepCreateCommand::kisiId`: nullable (`?int`) — Kişi spillover gerekli durumlar için
- Admin mock: `Mockery::mock` + `isAdmin()` + `hasRole()` → Spatie DB check bypass

### Bilinen Gap'ler
- `KisiScoringService::segmentSkoru` → `strtolower(KisiTipi)` pre-existing bug (line 101) — ayrı tracked
- `talepler.tip` migration eksik → `TalepOrchestrationParityTest` SKIP

### Strangler Fig Sonraki Adım
Controller adapter → `config('crm.use_domain_talep', false)` feature flag ile aktif edilecek
HOTSPOT_LOCK:config/feature-flags.php:cline:2026-09-14T07:22:09Z:3600
HOTSPOT_LOCK:.sab/sab-baseline.json:cline:2026-09-14T07:23:04Z:3600
HOTSPOT_LOCK:.sab/authority.json:cline:2026-09-14T07:23:04Z:3600
HOTSPOT_LOCK:config/exchange.php:cline:2026-09-14T07:24:04Z:3600
HOTSPOT_LOCK:config/location.php:cline:2026-09-14T07:24:04Z:3600
HOTSPOT_LOCK:database/migrations/2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php:cline:2026-09-14T07:24:04Z:3600
HOTSPOT_LOCK:database/migrations/2026_08_23_000002_create_c51_settlement_domain_tables.php:cline:2026-09-14T07:24:05Z:3600
HOTSPOT_LOCK:database/migrations/2026_09_04_173133_add_unique_composite_index_to_ilan_fotograflari.php:cline:2026-09-14T07:24:05Z:3600
HOTSPOT_LOCK:database/migrations/2026_08_23_000004_create_bank_accounts_table.php:cline:2026-09-14T07:24:12Z:3600
HOTSPOT_LOCK:database/migrations/2026_08_24_000001_create_workforce_executions_table.php:cline:2026-09-14T07:24:12Z:3600

---

## 2026-09-15 — İlan Edit Sayfası: Tabless Redesign

**Branch:** `release-candidate/RC2` — commit `77518bb2`

### Yapılan Değişiklikler
- İlan Edit səhifəsi (admin/ilanlar/edit.blade.php) — 6 tab'dan 0 tab'a
- Bütün sections eyni anda görünür:
  - SECTION 1: Temel Bilgiler & Fiyat
  - SECTION 2: Konum & Harita
  - SECTION 3: İlan Özellikleri
  - SECTION 4: Medya & Fotoğraflar
  - SECTION 5: CRM, Portallar & Yayın
  - SECTION 6: Kiralama & Rezervasyon (opsiyonel)
- Tab Navigation Bar silindi
- activeTab Alpine state çıxarıldı
- x-show conditional wrappers silindi
- Floating footer text: "Tüm sekmelerdeki" → "Tüm alanlardaki"

### User Request
"İlan ekleme, düzenleme, detay tüm verileri göstermeli, tab vs olmamalı"

### Durum
✅ REPO_VERIFIED — Antigravity Quality Gates 6/6 PASS

---

## [2026-09-15] GOVERNANCE SISTEMI — YAPI VE CALISMA PRENSIBI

**Son Güncelleme:** 2026-09-15
**Kaynak:** `docs/SAB.md` (deprecated), `app/Http/Controllers/Admin/DecisionEngineController.php`, codebase analizi

### SAB Nedir?
- **SAB = Standart Uygulama Bloğu** — projenin bağlayıcı teknik anayasası
- Version: 24.2.0 (Phase 12: Monetization & Financial Seal)
- **Deprecated (2026-09-12):** `docs/SAB.md` arşivlendi
  - Güncel SSOT: `.sab/authority.json` (v6.1.1)
  - Mimari Anayasa: `docs/ysos/SAAB_V7.md`

### SAB Governorluk ile Iliskili Bilesenler

| Kısaltma | Acikım | Fonksiyon | Ilgili Controller/Service |
|---|---|---|---|
| SAB2 | Cortex Decision Engine | AI kararlarinin olusturulmasi ve onay/red | DecisionEngineController::reviewQueue |
| SAB3 | Decision Safety Layer | Rollback, suppression, override | RollbackService, SuppressionService |
| SAB4 | Multi-Agent Intelligence Center | Agent öneri yönetimi | CortexFindingService |
| SAB5 | Operator Intelligence | AI davranis kurallarini dinamik güncelleme | DecisionEngineController::toggleSafeMode |
| SAB6 | Controlled Autonomy | AI özerklik seviyesi | AutonomyService, DecisionEngineController::autonomyPanel |
| SAB8 | Decision → Action → Feedback Loop | Aksiyon sonuç feedback'i | ActionFeedbackService |

### Mimari Dosya Yapisi
```
app/Services/Governance/
├── GovernanceDashboardService.php
├── GovernanceMetricsService.php
├── GovernanceObservabilityService.php
├── EloquentGovernanceAuditLogger.php
└── Telemetry/

app/Services/Intelligence/
├── CortexFindingService.php     (SAB4)
├── AutonomyService.php         (SAB6)
├── ActionFeedbackService.php    (SAB8)
├── RollbackService.php          (SAB3)
└── SuppressionService.php       (SAB3)

app/Http/Controllers/Admin/
├── DecisionEngineController.php  ← SAB2/SAB3/SAB5/SAB6/SAB8
├── GovernanceController.php       ← Ana dashboard
└── UpsGovernanceController.php    ← Feature health matrix
```

### Admin Panel Route-Lari
- `/admin/governance` → dashboard
- `/admin/governance/telemetry` → Livewire real-time izleme
- `/admin/governance/review-queue` → SAB2 karar onay kuyruğu
- `/admin/governance/decision-history` → Gecmis kararlar
- `/admin/governance/intelligence-center` → SAB4 agent önerileri
- `/admin/governance/feature-health` → Feature saglik matrisi
- `/admin/governance/autonomy` → SAB6 özerklik kontrolü
- `/admin/governance/action-dashboard` → SAB8 feedback döngüsü
- `/admin/yalihan-bekci` → Sistem saglik kontrolü
- `/admin/audit-log` → Tüm operasyonlarin kaydi

### Calısma Akısı
```
AI Agent karar üretir
  → Review Queue (SAB2)
    → Admin: Onayla / Reddet / Rollback
      → Onay: Aksiyon hayata gecer
      → Red: Loglanir
      → Rollback: Önceki state'e döner (SAB3)
    → Feedback kaydi (SAB8)
    → Sistem öğrenir
```

### Güvenlik Kurallari
- Core'a dogrudan write yasak → sadece Service katmani
- Silent catch yasak → hata log + rethrow zorunlu
- Governance bypass yasak → bypass eden kod merge edilemez
- Multi-tenant tenant_id zorunlu
- AI Circuit Breaker: her AI operasyonu AiBudgetGuard'a tabi

### Etkilenen Dosyalar
- `resources/views/admin/ilanlar/edit.blade.php` (-78 satır, +18 satır)
HOTSPOT_LOCK:database/migrations/2026_09_17_000001_create_emlak_projeleri_tables.php:antigravity:2026-09-17T13:53:56Z:3600
HOTSPOT_LOCK:database/migrations/2026_09_17_000002_add_proje_id_to_ilanlar_table.php:antigravity:2026-09-17T13:53:56Z:3600
HOTSPOT_LOCK:database/schema/mysql-schema.sql:antigravity:2026-09-17T13:54:36Z:3600
HOTSPOT_LOCK:.sab/schema-checksum.sha256:antigravity:2026-09-17T13:54:36Z:3600
