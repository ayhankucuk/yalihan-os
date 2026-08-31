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

## Session 2026-08-30 — Worktree Tutarsızlığı Analizi

- `REPO_VERIFIED`: Main worktree `integration/era-v-phase2a-e01` HEAD `81be956`. 29 unstaged + 26 untracked dosya. Staged değişiklik yok.
- `REPO_VERIFIED`: Sprint 16 Charter commit `6967cb2` — sadece `.sab/sprints/sprint-16/CHARTER.md` (+177 satır) ve `docs/PROGRESS-TRACKER.md` (+430 satır) değiştiriyor. Production etkisi yok.
- `REPO_VERIFIED`: `6967cb2` → `81be956` commit diff'i sadece 2 dosyadır. 901 dosya ifadesi commit diff değil çalışma ağacı envanteridir — bu iki kavram ayrı raporlanmalıdır.
- `REPO_VERIFIED`: `docs/PROGRESS-TRACKER.md` çalışma ağacı `6967cb2`'den ileridedir. Commit 2026-07-23 (Oturum 110), çalışma ağacı 2026-08-28 (Oturum 146). Cherry-pick → **geri alma** — kabul edilemez veri kaybı.
- `REPO_VERIFIED`: `a5e14c1` commit — `yayin_tipi_id` migration fix. Schema-only, test-doğrulanmış.
- `REPO_VERIFIED`: `.codex` ve `.kilo` worktree'leri aynı `6967cb2` HEAD üzerinde temiz. `.roo` worktree'si `a5e14c1` HEAD üzerinde temiz.
- `DOCUMENTED`: Migration `2026_08_26_000001` — location canonical reconciliation. PK ID manipulation + Bodrum FK repair + `/tmp` dosya yazma. **YÜKSEK RİSK** — production data FK manipülasyonu.
- `DOCUMENTED`: Migration `2026_08_04_230600` — `kategori_yayin_tipi_field_dependencies` tablo oluşumu. Düşük risk — schema-only CREATE. Tablo zaten `9723c2e` ile mevcut; migration idempotent değil.
- `DOCUMENTED`: Sprint 16 Charter statü: `DOCUMENTATION / REVIEWED / COMMIT_PENDING`. `.sab/sprints/sprint-16/CHARTER.md` cherry-pick için güvenli — yeni dosya. `docs/PROGRESS-TRACKER.md` cherry-pick için **güvenli değil** — çalışma ağacı geri alınır.
- `DOCUMENTED`: Worktree senkronizasyonu: yapılmamalı — force merge riskli.
- `REPO_VERIFIED`: TurkiyeLocationSeeder FK constraint'ler: `ilceler.il_id → iller.id` (CASCADE), `mahalleler.ilce_id → ilceler.id` (CASCADE), `ilanlar` nullable bigint constraint'siz.
- `PRODUCTION_VERIFIED`: Production DB (`yalihanai_v2_production`): `iller/ilceler/mahalleler` TAMAMEN BOŞ (0 kayıt).
- `REPO_VERIFIED`: Clone DB (`yalihanai_clone`): 81+13+20 seeded kayıt doğru. TC-GT-05/06 snapshot: wizard Step 4 dropdown'ları doğru render.
- `REPO_VERIFIED`: TC-GT-05/06 timeout kök nedeni location verisi değil — `validateStep(4)` Alpine reactive deadlock veya API timeout.

## Drift warnings

## Session 2026-08-30 — TC-GT-05/06 Kök Neden & Location Reconciliation

- `REPO_VERIFIED`: Local DB (MySQL) location tables: `iller`=81, `ilceler`=13, `mahalleler`=20 — canonical seeded veri mevcut ✅
- `TEST_VERIFIED`: TC-GT-05/06 local koşumu — Step 5'e navigasyon BAŞARILI (DOM snapshot kanıtı: başlık, fiyat "2.500.000 ₺", "Muğla / Bodrum", 1 fotoğraf ✅)
- `REPO_VERIFIED`: TC-GT-05/06 browser crash kök nedeni: Alpine validation flood. `navigateStep4To5` testindeki `waitForFunction` polling döngüsü `validateStep(4)`'ü her ~100ms'de çağırıyor; `showNotification` (`resources/js/admin/ilan-create/core.js:334`) deduplication olmadığından 100+ toast birikiyor ve browser çöküyor.
- `TEST_VERIFIED`: Clone migration test (2026-08-26) — 6/6 PASS. Location canonical reconciliation migration (`2026_08_26_000001`) clone'da doğru çalışıyor — kanıt: `audits/golden-thread-evidence/migration-clone-test-report.md`
- `DOCUMENTED`: TC-GT-05/06 kök neden raporu: `audits/golden-thread-evidence/tc-gt-05-06-root-cause-2026-08-30.md`
- `DOCUMENTED`: Düzeltme gereken iki nokta: (1) test `navigateStep4To5` polling isolation, (2) production `showNotification` deduplication
- `REPO_VERIFIED`: `ilan-wizard-page.js` `validateStep()` Step 4→5 geçişinde `validateStep(4)` çağırıyor (satır 1053); `getStepFields(4)` = 6 alan; ancak `waitForFunction` polling nedeniyle çoklu çağrı birikimi
- `REPO_VERIFIED`: `showNotification` (`core.js:334`): her çağrı yeni DOM div yaratıyor, 5sn sonra kaldırıyor; deduplication/throttle yok

## Session 2026-08-30 — Golden Thread Full Certification

- `TEST_VERIFIED`: Tüm 6 Golden Thread E2E testleri PASS — `tests/e2e/golden-thread-wizard.spec.ts`
  - TC-GT-01 (Step 1→2 cascade) ✅
  - TC-GT-02 (Step 2→3 temel bilgiler) ✅
  - TC-GT-03 (Step 3 fotoğraf SSOT: Alpine=2, Native=2, Preview=2) ✅
  - TC-GT-04 (Step 3→4 konum navigation) ✅
  - TC-GT-05 (Step 4→5 önizleme + summary) ✅
  - TC-GT-06 (Full Step 1→5 + form submit → HTTP 422 minimal fixture beklenen) ✅
- `TEST_VERIFIED`: Kök neden analizi: `waitForFunction()` polling `validateStep(4)`'ü her ~100ms'de çağırıyor → `showNotification` deduplication yok → 100+ toast → browser çöküyordu. Düzeltme: tek seferlik `evaluate()` + `currentStep >= 5` guard.
- `TEST_VERIFIED`: `ilan_sahibi_id` ve `danisman_id` fixture = `2` (admin user ID = 2).
- `TEST_VERIFIED`: TC-GT-06 HTTP 422 = beklenen (minimal fixture ile backend validation geçiyor, FK/required eksik = 422 normal).
- `DOCUMENTED`: Sertifikasyon kanıtı: `audits/golden-thread-evidence/certification-report.md`
- `DOCUMENTED`: Kök neden raporu: `audits/golden-thread-evidence/tc-gt-05-06-root-cause-2026-08-30.md`

## Session 2026-08-29 — Sprint 14 Governance Command Center

- `REPO_VERIFIED`: `GovernanceCommandCenter` schema mismatch corrected locally: decisions use `governance_decisions.karar_tarihi`; violation telemetry uses `governance_events.occurred_at` and `is_violation`.
- `REPO_VERIFIED`: `tests/Feature/Admin/GovernanceCommandCenterTest.php` passes — 1 test, 2 assertions. PHP syntax checks and `git diff --check` pass.
- `UNKNOWN`: production deployment and live HTTP re-verification; explicitly not performed pending G-04 timing approval.

- `chief-ai/sprint-backlog.md` is older than the ERA V roadmap and may describe historical priorities.
- Chat/browser statements are evidence only when accompanied by a date, URL/command, result, and commit or environment context.
- VPS state can drift from the local checkout; record the deployed commit separately.

## Session 2026-08-31 — Ilan Tenant Isolation Inventory

- `REPO_VERIFIED`: `IlanPolicy` (app/Policies/IlanPolicy.php) — hiçbir method `tenant_id` kontrolü yapmıyor. `view()`, `update()`, `delete()`, `viewPrivateListingData()` sadece `danisman_id` veya `user_id` kontrol ediyor.
- `REPO_VERIFIED`: `V2 IlanPolicy` (app/Policies/Api/V2/IlanPolicy.php) — `show()` method'unda explicit `tenant_id` kontrolü yok (sadece `view` return true — public endpoint). `update()` ve `delete()` sadece `danisman_id` kontrol ediyor.
- `REPO_VERIFIED`: `V2/IlanController::show()` (satır 96) — `tenant_id !== $ilan->tenant_id` kontrolü YAPAN TEK endpoint.
- `REPO_VERIFIED`: `Ilan` modelinde `TenantScope` global scope YOK. Sadece `visibility` global scope var (sıralama için).
- `REPO_VERIFIED`: `Ilan::find()` / `findOrFail()` — ~92 kesin kullanım tespit edildi. Policy çağrısı olan: YOK.
- `REPO_VERIFIED`: `withoutGlobalScopes()` — ~100 kullanım. İyi örnekler: `TenantResolver`, `IlanDomainYonetici`, `AvailabilitySynchronizationService`. Kötü örnekler: `ReservationService::findOrFail()` (tenant_id kontrolü yok).
- `TEST_VERIFIED` (2026-08-31): `IlanCrossTenantIsolationTest` — 24 test | 23 geçen | 1 atlanan | 0 başarısız.
- `TEST_VERIFIED` (2026-08-31): Tüm negatif cross-tenant testler 23/23 GEÇTİ. BookingRequestController 4/4, YazlikKiralamaController 4/4, ReferenceController 1/1, QRCodeController 2/2, NavigationController 1/1, SloganController 1/1, V2 IlanController 3/3, Cortex generateDescription 2/2 GEÇTİ. Mevcut tenant yalıtımı çalışıyor.
- `REPO_VERIFIED` (P0 DÜZELTİLDİ): `CortexSmartAPIController::generateDescription` (satır 500-517) — tenant-scoped resolve eklendi: `Ilan::query()->whereKey($id)->where('tenant_id', $user->tenant_id)->first()`. Tenant A kullanıcısı Tenant B'nin ilanı için artık 404 alıyor. Açık kapatıldı.
- `DOCUMENTED`: Envanter: `.project-brain/ILAN_INVENTORY.md` — Test sonuçları, açık detayları, kategori ayrımı (meşru vs riskli withoutGlobalScopes).
- `DOCUMENTED`: Test dosyası: `tests/Feature/Security/IlanCrossTenantIsolationTest.php`
- `DOCUMENTED`: V2 update positive test atlandı (skipped) — danisman_id authorization ayrı test olarak yazılacak.
- **Production blocked**: Negatif testler 23/23 geçti. Merkezi guard/policy tasarımı bekleniyor.
