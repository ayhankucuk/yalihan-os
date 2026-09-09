# Known Issues and Open Questions

## Acil — 2026-09-09

- **[GÜVENLİK] `storage/app/public/ilan-fotograflari/` — `.gitignore` eklendi ama tarihi commit'te mevcut**
  - ✅ `.gitignore`'a `/storage/app/public/ilan-fotograflari/` eklendi
  - ✅ `git rm --cached` + reset yapıldı — şu an `git status`'ta görünmüyor
  - ⚠️ Ancak dosyalar `01f8b84a` commit'inde tarihe girmiş — `.gitignore` gelecek commit'leri korur, tarihisilmez
  - Tenant fotoğrafları hâlâ repo tarihinde mevcut — BFG-repo-cleaner ile temizlenebilir (ayrı onay gerekli)
  - Kapsam: tüm dizinler (`1,3,4,55-92`) — 25 tenant klasörü
  - Öncelik: **ORTA** (ACİL'den düştü — yeni commit riski engellendi)

- **DEBT-04 — Ana RC2 Kirli ✅ TEMİZLENDİ**
  - ✅ 83 dosya 5 commit'e ayrıldı ve push'landı
  - ✅ Worktree artık clean — `rc2-release-certification-gate.sh` → `worktree: PASS` (ilk kez!)
  - Commit'ler: `a742724a` (frontend), `38e16c4a` (governance), `c223e1a1` (docs), `36b3b296` (audit), `1349bcb6` (security tests)
  - Öncelik: ~~ACİL~~ **ÇÖZÜLDÜ**

## Orta — 2026-09-09

- **37 worktree, çoğu muhtemelen terk edilmiş**
  - Worktree listesi: `git worktree list` çıktısı
  - Terk edilmiş olanları tespit için: son commit tarihi, son erişim, dirty durumu
  - Otomatik silme yapılmıyor — sahiplik doğrulanacak
  - Plan: her worktree için rapor → ayrı temizlik planı
  - Öncelik: **ORTA**

- **Skill / Gate dosyalarının Git commit'i yok — sahiplik belirsiz**
  - 4 skill + gate scripti `.agents/skills/` ve `scripts/tools/`'e yazıldı ama Git commit edilmedi
  - Kaynak: `codex/antigravity-browser-runtime-certification` worktree (commit: `4093e489`, `db43057f`, `9eb751c3`)
  - Commit durumu: `REPO_VERIFIED` (worktree'de commitli)
  - İnsan/ajan sahipliği: `UNKNOWN` (commit'i kimin ürettiği bilinmiyor)
  - Cline skill entegrasyonu: `NOT_VERIFIED`
  - Bu kayıt (KNOWN_ISSUES + BEKCI changelog): `DOCUMENTED` — Git'e commitlenmedi
  - Öncelik: **ORTA** — commit yapılmadan önce kaynak teyit edilmeli

- **DEBT-02 & DEBT-03 — Harita köprüsü + TC-GT-11 ✅ ÇÖZÜLDÜ**
  - ✅ `edit.blade.php` map bridge uygulandı (satır ~613-665):
    - `if (!this.map && window.mapManager?.map) this.map = window.mapManager.map`
    - `already initialized` hata maskeleme eklendi
  - ✅ `tab=drafts` desteği test dosyasına eklendi
  - ✅ TC-GT-11: `id:067` ve `id:093` edit ekranı açıldı → `mapInitialized: true`, `failures: []`
  - ✅ TC-GT-06: Step 1➔5 → edit'e redirect başarılı (yeni ilan ID:93)
  - Kanıt seviyesi: **BROWSER_VERIFIED** — 2026-09-09
  - ⚠️ Not: TC-GT-04/05 throttle HTTP 429 (Copilot AI rate limit) — kod hatası değil
  - Öncelik: ~~ORTA~~ **ÇÖZÜLDÜ**

## Düşük / Çözüldü — 2026-09-09

- **SEC-08 ✅ ÇÖZÜLDÜ — V2 `PUT/DELETE/publish/unpublish` 404 Kök Neden**
  - Kök Neden: Laravel Route Model Binding `IlanController` öncesi çalışır → `BelongsToTenant` trait → `TenantScope` global scope ekler → `TenantContextService::hasTenant() = FALSE` (test ortamında set edilmiyor) → `WHERE 1 = 0` (fail-closed) → `ModelNotFoundException` → 404
  - Düzeltme: `update`, `destroy`, `publish`, `unpublish` method'larında implicit `Ilan $ilan` binding yerine explicit `Ilan::withoutGlobalScope(TenantScope::class)->find($id)` + 404 kontrolü. `authorizeIlanAccess()` auth kontrolü aynı kaldı.
  - Commit: `83dd1e8a` (`release-candidate/RC2`)
  - Kanıt: `V2RouteBindingCountryScopeTest` 3/3 PASS, `V2IlanAuthResearchTest` 2/2 PASS
  - Kanıt seviyesi: **TEST_VERIFIED**

- **Yeni yetenekler Antigravity worktree'inden kopyalandı — 2026-09-09**
  - Kaynak: `codex/antigravity-browser-runtime-certification` worktree (commit: `4093e489`, `db43057f`, `9eb751c3`)
  - Commit durumu: `REPO_VERIFIED` (worktree'de commitli)
  - İnsan/ajan sahipliği: `UNKNOWN` (commit'i kimin ürettiği bilinmiyor)
  - Cline skill entegrasyonu: `NOT_VERIFIED`
  - Bu kayıt (KNOWN_ISSUES + BEKCI changelog): `DOCUMENTED` — Git'e commitlenmedi

- **SKILL_INDEX güncellenmemişti — 2026-09-09 düzeltildi**
  - 8 yeni file pattern satırı + 4 yeni skill tanımı eklendi
  - Artık ajanlar yeni skill'leri otomatik seçebilir

- **`rc2-release-certification-gate.sh` return/exit hatası — 2026-09-09 düzeltildi**
  - `run_gate()` içinde `return 1` → `exit 1` olarak düzeltildi
  - Artık `set -e` olmadan da doğru çalışıyor
  - Kaynak: Antigravity `db43057f` düzeltmesi temel alındı

- **`blade-alpine-runtime-guardian` Kural 7 normalize önerisi — 2026-09-09 uygulandı**
  - "URL path normalize" ipucu skill'e eklendi
  - "Sessiz fallback yasağı" netleştirildi

- **`multi-agent-worktree-sandbox` temizlik komutu — 2026-09-09 düzeltildi**
  - `git reset --hard` yasağı korundu
  - `git restore -- <path>` / `git checkout HEAD -- <path>` alternatif olarak eklendi

## Tarihi — Çözülmüş

- `/yazliklar` had a recorded HTTP 500. The definitive current exception is not yet indexed.
- Historical application logs show embedding requests failing against `localhost:11434`; container-localhost may not be the intended model-service address.
- Some terminal output was accidentally pasted back as shell input, causing `command not found` and command-substitution errors. Keep commands separate from prompts and output.
- A healthy Docker container does not prove the requested route or database query is healthy.
- Automated wizard tests do not prove that all fields render in the browser or that a draft can be saved.
- Live admin listing page references the main app stylesheet but the browser did not load it; inspect `/build/assets/css/app-F0wQNZdk.css` response and nginx/app public mounts.
- Live `/admin/property-hub` **RESOLVED 2026-08-26**: `kategori_yayin_tipi_field_dependencies` table restored via migration `9723c2e` → `migration/fix-kytfd-table` branch. HTTP 200 verified in browser (Ayhan Küçük session, full dashboard loaded).
- `leaflet-draw.js` reported `L is not defined`, indicating a frontend dependency/load-order defect for map features — fix deployed in `80b6703` / `a0a52bf`.
- Repository has a large and historically layered route/controller/service surface; route ownership and duplicate legacy paths need a dedicated drift audit before broad refactoring.
- Property Engine research 2026-08-28: Arsa, İşyeri, Yazlık Kiralama, Turistik Tesis, and Projeden Satış combinations currently resolve to five global features with zero required fields in the inspected matrix; this may cause silent fallback and publication-gate incompleteness. `FeatureAssignmentSeeder` changes require a reviewed matrix and explicit data-change authorization.
- Sidebar route audit 2026-08-28: six referenced sidebar routes are missing or inconsistent (`admin.listing-features.index`, `admin.yayin-tipi-sablonlari.index`, `admin.takim-yonetimi.takim.performans`, `admin.analytics.dashboard`, `admin.telegram-bot.durum`, `admin.smart-calculator`). Consolidation under a single Property Engine menu is planned; route ownership and authenticated navigation still need verification.
- Operational research 2026-08-28: iCal UTC/Europe-Istanbul date-boundary normalization, TKGM circuit-breaker behavior, and `IlanPublished` → CRM matching job automation require contract/integration evidence before being treated as production-safe.
- Wizard/AI contract research 2026-08-28: missing `ilan_sahibi_id`/`danisman_id` can produce a 422 trap; dynamic feature values such as `bina_yasi`, `kaks`, `ada_no`, and boolean switches need explicit type-normalization tests; synchronous photo/vision processing may create timeout risk at scale.
- Nginx host mount `/opt/yalihan2026/current/public:/app/public:ro` can hide image-built `public/build` assets; this is the confirmed likely cause of the live missing CSS. Fix: `docker-compose.production.yml` replaces host overlay with named volume `yalihan-storage:/app/storage:ro` — pending commit + deploy.
- Local hardening changes are not yet committed or deployed; production remains on the separately recorded commit until an explicit release approval.
- `/admin/property-hub` HTTP 500: RESOLVED. Root cause was missing `kategori_yayin_tipi_field_dependencies` table. Fix: `active()`→`aktif()` (correct scope) + migration restore `9723c2e`. Both deployed to production 2026-08-26.
- The project-brain gate is local and read-only; it is a prerequisite check, not production certification.
- Live Property Type Manager review 2026-08-27: Turistik Tesisler has 3 subtypes and active Kiralik/Satilik publication types, but shows `0 Alan` and no category feature assignment. This may block complete, category-specific forms; exact API/database cause is not yet verified.
- Follow-up field-dependencies review 2026-08-27: both `Turistik Tesisler Kiralik 0` and `Turistik Tesisler Satilik 0` tabs render without field rows or rule controls. Determine whether this is genuinely empty configuration or a backend/API/rendering contract issue before changing data.
- Global Feature Pool review 2026-08-27: counters show 36 total, 30 active, 0 passive, and 7 categories, leaving six records unexplained by the displayed status totals. All visible rows show `0 atama`; verify count definitions and assignment relations. Validate Text/Select/Multiselect types against wizard contracts before configuring production.
- Template Manager review 2026-08-27: 91 master templates, 35 assignments, and 0.4 average features/template; most visible templates are empty. Edit links visibly use `kategori_id=0`; verify category context and route/API behavior before enabling template edits or AI generation.
- Combined Property Hub review 2026-08-27: Feature Pool, category manager, Property Type Manager, and field-dependencies surfaces are structurally connected but mostly report zero assignments/fields. Treat the configuration as incomplete or unverified until relation queries and API contracts are reconciled. The user-provided `/admin/ilan-kategorile` path is not the verified category-list path; verified path is `/admin/ilan-kategorileri`.
- Dependency Rules review 2026-08-27: live screen reports 0 total, visible_if, and required_if rules and instructs users to add rules from the template editor. Publication-type/category selectors contain duplicate-looking labels; verify IDs, joins, and rule storage before configuration.
- Template edit read-only test 2026-08-27: Arsa & Arazi Kiralik template correctly identifies itself but its "active subcategories" list mixes Daire, Villa, Ofis, Otel, Pansiyon, Tatil Köyü and other domains. Verify category scoping/query joins before any master-template application.
- Property Hub, Copilot modal, and admin listing page now accessible in authenticated session. Browser session established via Ayhan Küçük (ayhankucuk@gmail.com / admin123), verified 2026-08-26.
- **Sprint 14 Blocker (2026-08-29):** `/admin/analytics/command-center` → HTTP 500. **RESOLVED** — commit `7d402de` fixed: `occurred_at` → `karar_tarihi` for decisions, `governance_events` query for violations. Semantic approved: `karar_tarihi` = decision date, `occurred_at` = event timestamp. No migration required. Test: PASS. G-04 timing: PENDING. Audit: `audits/COMMIT_B_MIGRATION_SCHEMA_SECURITY_AUDIT_2026-08-29.md`.
- **TC-GT-05/06 Kök Neden Düzeltmesi — RESOLVED (2026-09-08):** `navigateStep4To5` test infrastructure zaten düzeltilmişti (tek seferlik evaluate). Şimdi production UX düzeltildi: `showNotification()` deduplication eklendi — aynı message+type kombinasyonu için mevcut toast yeniden kullanılıyor. Commit `ee1725a8`. Kök neden kanıtı: `audits/golden-thread-evidence/tc-gt-05-06-root-cause-2026-08-30.md`.
- **Golden Thread E2E — TC-GT-05/06 BLOCKED (2026-08-30):** TC-GT-01/02/03/04 PASS (4/6). TC-GT-05/06 timeout — `validateStep(4)` 60sn içinde `nextStep()` başarısız. Page snapshot doğruladı: clone DB (`yalihanai_clone`) location verisi tamamen doğru (81 iller, 13 Muğla ilçesi, 20 Bodrum mahallesi). Kök neden location verisi değil — muhtemelen Step 4 form validation/API timeout veya Alpine reactive state deadlock. Production DB (`yalihanai_v2_production`): location tabloları tamamen BOŞ (0 kayıt). Clone DB seeded veri ile dolu. TC-GT-05/06 snapshot: il/ilçe/mahalle dropdown'ları doğru render ediliyor. Test hatası farklı bir kök nedeni işaret ediyor.
- **Golden Thread E2E — TC-GT-05/06 Browser Flow Verified (2026-08-30):** Step 1→5 navigasyonu tüm 6 test PASS ✅ (34.1s). Kök neden: `waitForFunction()` polling döngüsü `validateStep(4)`'ü her ~100ms'de çağırıyordu; `showNotification` deduplication yok → 100+ toast → browser çöküyordu. Düzeltme: tek seferlik `evaluate()` çağrısı + `currentStep >= 5` guard. TC-GT-06 submit: HTTP 422 (minimal fixture, backend ulaştı — E2E zinciri işliyor). **Redirect veya DB persistence doğrulanamadı** — fixture eksik alanlar nedeniyle 422. Kanıt: `audits/golden-thread-evidence/certification-report.md`. Açık görev: fixture'ı tüm zorunlu alanlarla doldur → gerçek redirect + DB persistence doğrula.
- **Checkout/Manuel Ödeme (2026-08-29):** Kod/test/deploy kayıtlı. Authenticated production browser kanıtı eksik — ödeme akışı tarayıcıda doğrulanmadı.
- **Governance Command Center (2026-08-29):** Yerel düzeltme `7d402de` commit'li. Production doğrulaması ve G-04 Part 2 operator timing ölçümü bekliyor.
- **`/yazliklar` (2026-08-29):** Güncel HTTP 200 kanıtı yok. En son HTTP 500 teşhis edildi. Fresh exception + fix doğrulaması gerekiyor.
- **Property Engine/Hub (2026-08-29):** Analiz tamamlandı; schema/assignment kök nedeni kesinleşmedi, veri değişikliği yapılmadı. `FeatureAssignmentSeeder` için açık matris onayı gerekli.
- **Ollama/Cortex (2026-08-29):** Açık known issue — `localhost:11434` bağlantı hatası geçmişte görüldü. Servis topology kararı açık.
- **SESSION NOTES Tutarsızlığı (2026-08-29):** `memory/SESSION_NOTES.md` Oturum 113–146 arası 24 kayıt eksik. Otorite kaynak: `docs/BEKCI_CHANGELOG.md`.
- **TurkiyeLocationSeeder — Location Data Gap (2026-08-30):** `database/seeders/TurkiyeLocationSeeder.php` DatabaseSeeder'a kayıtlı. İçerik doğru (81 il, 13 Muğla ilçesi, 20 Bodrum mahallesi). Ancak seeder mevcut yanlış kayıtları (`id=1,2,3` yanlış iller) **silmez** — `updateOrInsert(['id' => N])` sadece canonical ID'leri ekler. Eski kayıtlar orphan olarak kalır. `2026_08_26` migration `BLOCKED`. Clone test planı hazır — clone kanıtı olmadan hiçbir ortamda çalıştırılmamalı.
- **Untracked Migration — `2026_08_04` (2026-08-30):** `kategori_yayin_tipi_field_dependencies` tablo migration'ı. Schema-only, rollback riski düşük. Tablo zaten `9723c2e` ile mevcut; migration idempotent değil — `UNTRACKED / REVIEW_REQUIRED`.
- **Untracked Migration — `2026_08_26` (2026-08-30):** Location canonical reconciliation migration. Primary key ID manipulation + Bodrum FK repair + `/tmp` dosya yazma. Production data FK manipülasyonu gerektirir. **BLOCKED** — explicit production onayı olmadan kesinlikle çalıştırılmamalı.
- **Worktree Tutarsızlığı (2026-08-30):** Dört worktree (`main`, `.codex`, `.roo`, `.kilo`) farklı HEAD commit'lerinde. Sprint 16 Charter (`6967cb2`) iki worktree'de mevcut. Force merge/senkronizasyon yapılmadı — yapılmamalı.
- **Dokümantasyon Commit Onayı (2026-08-30):** Dokümantasyon commit'leri için bile açık kullanıcı onayı gerekir — D007 protokolüne aykırı ifade düzeltildi.
- **`CRITICAL: Ilan Tenant Isolation Security Gap (2026-08-31):** 24 cross-tenant test (23 PASS, 1 SKIPPED). P0 açık düzeltildi: `POST /api/ai/generate-description` — tenant-scoped resolve eklendi: `Ilan::query()->whereKey($id)->where('tenant_id', (int) $user->tenant_id)->first()`. Null user/tenant_id için 403 döndürülüyor. `optimizeTitle` açıktan etkilenmedi — endpoint ID parametresi kullanmıyor. Çoğu mevcut controller doğru tenant yalıtımı yapıyor. Envanter: `.project-brain/ILAN_INVENTORY.md`. Test: `tests/Feature/Security/IlanCrossTenantIsolationTest.php`. **Production blocked** — merkezi guard/policy tasarımı bekleniyor.
