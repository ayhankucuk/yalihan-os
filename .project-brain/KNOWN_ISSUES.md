# Known Issues and Open Questions

## Bekçi Gate Treshold Tutarsızlığı — 2026-09-12

- **[GATE-THRESHOLD] `bekci:health` gate PASS/FAIL kararı skor eşiğiyle uyumsuz**
  - `YalihanBekciHealthCommand`: MCP offline iken genel sağlık %59 `NEEDS ATTENTION` ama wrapper `PASS` döndürüyor
  - `HealthCheckGate::passes()`: skoru 0–100 normalize edip karşılaştırmıyor; sadece `!$mcpOffline` kontrolü var
  - Bekçi gate %70 hedefi var (bkz. .clinerules §7) ama eşik kontrolü eksik
  - **Etki**: CI, %59 sağlık skoruyla PASS veriyor — yanlış negatif riski
  - **Olası çözüm**: `HealthCheckGate::passes()` → `->value('overall_score')` karşılaştırması ekle
  - **Durum**: AÇIK — ayrı görev

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
- **`[MACRO-AUDIT-2026-09] Çapraz Mimari Denetim Bulguları (Alan 1-64 — 56 Doğrulanmış P0/P1 Kusur):**
  Aşağıdaki tablo, kod tabanı çapraz denetiminde (Alan 1-64) kesin olarak tespit edilen ve giderilmesi gereken 56 sistemik mimari kusuru listeler:

| Kod | Dosya & Konum | Kusur Türü & Kök Neden | Etki & Statü |
|---|---|---|---|
| `[ROUTER-500]` | `IlanPublicController.php:430` | `show-yazlik.blade.php` view dosyası mevcut değil | 500 Fatal Error (Yazlık vitrini çöker) |
| `[LEAD-LOSS]` | `show.blade.php:605`, `contact.blade.php:268` | Dummy/Simulated JS form post; backend'e kayıt düşmüyor | Gerçek müşteri lead kaybı |
| `[EVENT-GHOST]` | `IlanObserver`, `LeadService` | `IlanYayinlandiEvent`, `IlanPriceChanged` dispatch edilmiyor | CRM & n8n otomasyonları tetiklenmiyor |
| `[SCHEDULER-MISS]` | `Kernel.php` | 11 adet artisan komutu tanımlı değilken schedule edilmiş | Cron job patlamaları |
| `[RBAC-DEADLOCK]` | `routes/admin.php` | Tüm admin rotaları `role:admin` kilitli, danışman giremiyor | Danışman izolasyon kilidi |
| `[LEDGER-LEAK]` | `FinancialLedgerService.php` | Ledger hesabı açılırken `tenant_id` verilmiyor | Kural 1 Tenant İzolasyon İhlali |
| `[AI-CRASH]` | `IlanAIController.php:109` | `YayinTipiResolverTrait` import edilmemiş | Fatal Error Class Not Found |
| `[PROJE-CONFLICT]` | `App\Models\Proje` vs `Emlak\Models\Proje` | ✅ **CLOSED / PRODUCTION_VERIFIED (3ced67c1)** — Domain split completed; `emlak_projeleri` table migrated, `projeler` preserved for Team Proje | ~~Split-brain model kaosu~~ |
| `[FORM-BLOCKER]` | `StoreIlanRequest.php` | Formda olmayan `proje_id` alanı zorunlu tutulmuş | 422 Unprocessable Entity |
| `[CHANNEL-MOCK]` | `CalendarSyncService.php` | Dış API yerine sahte mock success dönüyor | Kanal senkronizasyonu çalışmıyor |
| `[RESERVATION-SPLIT]` | `yazlik_rezervasyonlar` vs `property_reservations` | İki ayrı rezervasyon tablosu var | Rezervasyon çakışması riski |
| `[FINANS-CRASH]` | `Komisyon.php` | Tabloda olmayan `tenant_id` üzerinden `BelongsToTenant` uygulanmış | SQL Column not found |
| `[RESTORE-404]` | `IlanCrudController.php:122` | `withTrashed()` olmadan restore sorgusu | 404 Not Found (Silinen bulunamıyor) |
| `[BULK-CRASH]` | `MyListingsController.php:175` | Koşulsuz `throw new RuntimeException()` | Toplu işlem butonu kilitli |
| `[SEARCH-LEAK]` | `IlanSearchService.php` | Raw `DB::table('ilanlar')` ile tenant bypass | Cross-tenant veri sızıntısı |
| `[SLUG-SEVERANCE]` | Web route vs Controller | `yazlik` ve `yazlik-kiralama` rota tutarsızlığı | 404 Route Mismatch |
| `[PHOTO-SPLIT]` | `Photo` vs `IlanFotografi` | Aynı tabloya bakan 2 model; olmayan `incrementViews()` çağrısı | BadMethodCallException |
| `[MAIL-500]` | `BookingRequestMail.php:51` | Olmayan `emails.booking-request` view'ına referans | 500 Mail Gönderim Hatası |
| `[OWNER-COUNT]` | `OwnerDashboardController.php:34` | String enum kolonda `where('yayin_durumu', true)` boolean sorgusu | Yanlış aktif ilan sayısı |
| `[CRM-EMAIL-CRASH]`| `KisiStoreRequest.php` | Yeniden adlandırılan `eposta` yerine eski `email` unique kontrolü | Validasyon çökmesi |
| `[OBSERVER-DEAD]` | `TalepObserver.php:26` | Incompatible enum tipleri `===` ile kıyaslanıyor | Hiçbir zaman tetiklenmeyen observer |
| `[LEAD-BYPASS]` | `LeadService.php:24` | Raw `DB::table('leads')->insertGetId()` ile tenant bypass | Cross-tenant sızıntı |
| `[API-IMPORT]` | `MobileLeadController.php:12` | Yanlış `App\Models\V2\Ilan` import edilmiş | API Fatal Error |
| `[TELEGRAM-ALERT]` | `TelegramService.php` | Olmayan `$user->gorevler()` ve `ilce->name` erişimi | 500 Bildirim Çökmesi |
| `[MATCHING-MISMATCH]`| `SmartPropertyMatcherAI.php`| Para birimi dönüşümü yok; uyumsuz yayın durumu filtreleri | Yanlış müşteri-ilan eşleşmesi |
| `[ANALYTICS-VIEW]` | `AnalyticsDashboardController.php`| View `$analytics['form_analytics']` bekliyor, controller `$metrics` veriyor | Undefined array key fatal |
| `[ROUTE-DEAD-LINK]`| `MenuItemsController.php:409` | Olmayan `admin.analytics.dashboard` rota kontrolü | Kırık navigasyon menüsü |
| `[CORTEX-ADAPTER]` | Cortex Provider'lar | `AITaskType::RECOMMEND_NEXT_ACTIONS` match dalı eksik | UnhandledMatchError |
| `[SUBSCRIPTION-GATE]`| `Kernel.php` | `SubscriptionMiddleware` route middleware alias'larında kayıtlı değil | Lisans/Abonelik kapıları bypass |
| `[TRANSLATION-MOCK]`| `AITranslationService.php` | Mock prompt string return ediyor, translation tablosunu kirletiyor | Yanıltıcı çeviri verisi |
| `[READ-MODEL-DRIFT]`| `IlanObserver.php:126` | `sorumlu_danisman_id` sorgulanıyor (kolon `danisman_id`) | CQRS okuma modelinde null danışman |
| `[LOCATION-VIEW-500]`| `Admin\LocationController:22` | `admin.locations.index` blade dosyası fiziksel olarak yok | 500 ViewNotFoundException |
| `[ADDRESS-CLASS-500]`| `Admin\AddressController:13` | Olmayan `App\Models\Address` modelini import/kullanıyor | Fatal Error Class Not Found |
| `[TKGM-METHOD-500]`| `TKGMAutoFillJob:102` | `TKGMService::getParcelInfo()` metodu yok, kuyruk çöküyor | Fatal Call to Undefined Method |
| `[TKGM-SCHEMA-LEAK]`| `TKGMLearningService:80` | `tkgm_queries` tablosunda olmayan `enlem`/`boylam` ve `aktiflik_durumu` kolonlarına yazıyor | SQL General Error (Column not found) |
| `[BOSCH-FIELD-DRIFT]`| `FieldMcpController.php:81` | `ilanlar` tablosunda olmayan donanım kolonlarına (`alan_m2_verified_by_hardware` vb.) direkt update | SQL Unknown Column Error |
| `[TELESCOPE-UNPRUNED]`| `Kernel.php` | `telescope:prune` komutu schedule edilmemiş | Veritabanı disk dolması / çökme |
| `[GUEST-LISTING-BLACKOUT]`| `TenantScope.php:30` | Misafir vitrininde (`/ilanlar`, `/`) tenant context yokken fail-closed `1=0` ile tüm vitrin boş dönüyor | Vitrinde 0 İlan (Public Blackout) |
| `[CURRENCY-SPLIT-BRAIN]`| `CurrencyConversionService.php` vs `TCMBCurrencyService.php` | Vitrin hardcoded config kurunu (USD 35.20) kullanırken TCMB canlı kuru `fx_rates` tablosuna yazıyor ama vitrin bu tabloyu hiç okumuyor | Yanıltıcı / Bayat Kur Gösterimi |
| `[REFUND-DISCONNECT]`| `ReservationService::cancelReservation()` | İptal yapıldığında `CancellationPolicyService::calculateRefund()` çalıştırılmıyor, iade tutarı/cezası hesaplanmadan rezervasyon iptal ediliyor | Otomasyon / Muhasebe Kopukluğu |
| `[SMS-MOCK-BLACKHOLE]`| `NotificationService.php:256` | SMS gönderimi yorum satırında (`// SMSService::send`), `SMSService` sınıfı yok; sahte success dönüyor | SMS Blackhole (Mesajlar gitmiyor) |
| `[TELEGRAM-ADAPTER-CRASH]`| `TelegramAdapter.php:48` | `TelegramService::sendMessage()` bool dönerken adapter `$response->successful()` çağırıyor | Fatal Error Call to member function on bool |
| `[QUEUE-WORKER-MISMATCH]`| `SendNotificationJob.php:46` vs `docker-compose.production.yml` | Bildirimler `notifications` kuyruğuna atılıyor, prod worker yalnızca `default` dinliyor | Kuyruk Kilitlenmesi (Bildirimler iletilmiyor) |
| `[NOTIF-SCHEMA-CRASH]`| `NotificationService.php:189` | `notifications` tablosunda olmayan `user_id`, `priority`, `aktiflik_durumu` kolonlarına SQL insert | SQL Column Not Found / ID default value error |
| `[SITEMAP-STUB-500]` | `BlogSitemapController.php:19` | Rotalarda tanımlı `posts`, `categories`, `tags` metodları yok (HTTP 500); sitemap XML yerine mock JSON dönüyor | 500 BadMethodCallException & Geçersiz XML |
| `[NO-LISTING-SITEMAP]`| Core Architecture | Gayrimenkul portföyü (`ilanlar`) için dinamik XML sitemap jeneratörü hiç inşa edilmemiş | Sıfır İlan İndekslemesi (SEO Blackout) |
| `[REPORT-VIEW-404]`  | `ReportService.php:52` | `reports.tr.neural_analiz` view dosyası yok; mühürlü rapor üretimi `InvalidArgumentException` ile çöküyor | View [reports.tr.neural_analiz] not found |
| `[PDF-CONVERT-FAKEOUT]`| `CortexPDFReportGenerator.php:485` | PDF ürettiğini raporlayıp link veriyor ama içeriği `.html` olarak kaydediyor, PDF kütüphanesi çağrılmıyor | Sahte PDF Raporu (HTML Download) |
| `[PDF-MOCK-DOWNLOAD-FAIL]`| `PageAnalyzerController.php:486` | Dosya oluşturmadan sahte download URL dönüyor; indirme tetiklendiğinde dosya bulunamadı hatası veriyor | Kırık Export Linki (Phantom PDF) |
| `[CONTRACT-ENGINE-ABSENT]`| Core Architecture | Gayrimenkul yer gösterme formu, yetki belgesi ve kira sözleşmesi için hiçbir veri modeli/PDF motoru yok | Hukuki/Operasyonel Sözleşme Boşluğu |
| `[WEBHOOK-QUEUE-CONCIERGE]`| `ResolveWhatsAppInboundJob.php:52` vs `docker-compose.production.yml` | WhatsApp gelen mesaj işleri `concierge` kuyruğuna atılıyor, prod worker sadece `default` dinliyor | Gelen Mesajlar Kuyrukta Takılı Kalıyor |
| `[TELEGRAM-FINANCE-TENANT]`| `FinanceProcessor.php:321` | Telegram'dan eklenen `FinansalIslem` kaydına `tenant_id` atanmıyor | Kural 1 Tenant İzolasyon İhlali |
| `[DRIVE-SYNC-TIMEOUT]`| `DriveWebhookController.php:98` | Pub/Sub webhook isteği içinde senkron `processChanges()` çalıştırılıyor; Cloud Run/Nginx HTTP 504 riski | Webhook Timeout / ACK Gecikmesi |
| `[AUTH-REGISTER-MASS-ASSIGN]`| `AuthController.php:96` vs `App\Models\V2\User` | `ad_soyad` ve `sifre_hash` alanları `V2\User::$fillable` içinde yok; mutator'lar tanımlı değil | Sessiz Null Kayıt (İsimsiz & Şifresiz Kullanıcı) |
| `[AUTH-MODEL-SPLIT-BRAIN]`| `config/auth.php` vs `Api\V2\AuthController` | Sanctum `App\Models\User` üzerinden guard kurarken V2 API `App\Models\V2\User` üretiyor | Model Uyuşmazlığı & Trait/Scope Tutarsızlığı |
| `[USER-TELEFON-UNDEFINED]`| `Api\V2\AuthController:173` | `V2\User::$fillable` veya accessor'larında `telefon` alanı tanımlı değil | Undefined property `$user->telefon` / Null Dönüş |
| `[LEDGER-BALANCE-TENANT-NULL]`| `UpdateLedgerBalanceProjection:51` | Dinamik `LedgerBalance` kaydı oluşturulurken `tenant_id` atanmıyor | Yetim Kayıt / Multi-Tenant Bilanço Sızıntısı |
| `[KOMISYON-SCHEMA-MISMATCH]`| `Komisyon.php:14` vs `mysql-schema.sql` | `Komisyon` modeline `BelongsToTenant` eklenmiş fakat DB tablosunda `tenant_id` kolonu yok | SQL Column Not Found (1054) / Finans Modülü Çökmesi |
| `[KOMISYON-LEDGER-DECOUPLING]`| `KomisyonService::storeCommission()` | Danışman komisyonu hesaplanıyor ancak `FinancialLedgerService` çift taraflı deftere işlenmiyor | Muhasebe Kopukluğu (Tahakkuk eden komisyon deftere girmiyor) |
| `[AI-TELEMETRY-ARG-MISMATCH]`| `DeepSeekCortexProvider:80-84` vs `AiTelemetryService:111` | `logFailure` imzası `$errorMessage` beklerken provider HTTP integer status (`$response->status()`) geçiyor | TypeError / Telemetry Loglama Çökmesi |
| `[AI-MODEL-GUARD-DEADLOCK]` | `AIOrchestrator:312` vs `DeepSeekCortexProvider:53-57` | `AIOrchestrator` `config('ai.default_model')` (null) yolluyor, DeepSeek provider `expectedModel` ile eşleşmeyince `AIModelMismatchException` fırlatıyor | 500 AIModelMismatchException / İlan Üretim Kilitlenmesi |
| `[AI-CIRCUIT-BREAKER-SPLIT]`| `AIOrchestrator:27` vs `DeepSeekCortexProvider:27` | `AIOrchestrator` `Monetization\AiBudgetGuard` (kredi bazlı) kullanırken DeepSeek Provider `App\Services\AI\AiBudgetGuard` (token bazlı) bekliyor | Type Error / İki Ayrı Budget Guard Çakışması |
| `[USER-DELETE-RESTRICT-CRASH]`| `DeleteUserAction:11` vs `mysql-schema.sql:6347,6373,6422` | Kullanıcı silinirken `ON DELETE RESTRICT` FK ilişkileri temizlenmiyor/reassign edilmiyor | 500 QueryException (Integrity constraint violation 1451) |
| `[GDPR-RIGHT-TO-FORGET-VOID]` | Core CRM & User Architecture | KVKK/GDPR Unutulma Hakkı (Right to be Forgotten) için anonimizasyon pipeline'ı veya rıza kütüğü mevcut değil | Hukuki Risk & KVKK Madde 7/11 İhlali |
| `[WHATSAPP-TENANT-INJECTION-BYPASS]` | `VerifyWebhookTenant.php:69-71` & `routes/api.php:71` | Webhook rotasında middleware yok, payload'daki `tenant_id` parametresi doğrudan kabul edilerek kiracı enjeksiyonuna izin veriliyor | Güvenlik / Yetkisiz Kiracı İzolasyon İhlali |
| `[TELEGRAM-ADAPTER-DISCONNECT-BLACKHOLE]` | `TelegramAdvisorAdapterController.php:27-34` | Danışman yanıtı üretiliyor fakat Telegram HTTP API'sine iletilmiyor; kullanıcıya yanıt asla ulaşmıyor | Telegram İletişim Kara Deliği (Blackhole) |
| `[CHANNEX-TENANT-LOOKUP-SYNC-LEAK]` | `ChannexWebhookTenantResolver.php:20-25` | `ilan_takvim_sync` ve `ilanlar` join sorgusunda aktiflik ve tenant sahiplik doğrulaması yok; rezervasyon yanlış kiracıya yönlendirilebiliyor | Cross-Tenant Rezervasyon Sızıntısı |
| `[HERMES-QUEUE-OBJECT-GRAPH-SERIALIZATION-EXPLOSION]` | `HermesDispatcher.php:96` vs `AsyncHandlerDispatchJob.php:43` | AsyncHandlerDispatchJob constructor'ına servis nesnesi ($handler) enjekte ediliyor; tüm servis grafı Redis'e serialize edilip SerializationException riski yaratıyor | Kuyruk Şişmesi & SerializationException Çökmesi |
| `[N8N-AI-USECASES-UNQUALIFIED-MODEL-CRASH]` | `ProcessAIIlanTaslagiUseCase.php:5`, `ProcessAIMesajTaslagiUseCase.php:5`, `ProcessAIContractDraftUseCase.php:5` | Olmayan `App\Models\AIIlanTaslagi`, `AIMessage`, `AIContractDraft` sınıflarını import ediyor (gerçek konum `App\Models\AI\*`) | 500 Fatal Error (Class Not Found) |
| `[N8N-AI-TENANT-ORPHAN-INJECTION]` | `AIIlanTaslagiService.php:65-72, 130-139`, `AIIlanTaslagi.php:7-20` | `AIIlanTaslagi` modeli BaseModel'i değil Eloquent'i extend ediyor, tenant_id yok; taslak ilana çevrilirken tenant_id atanmıyor | Yetim İlan / Kiracı İzolasyon İhlali |
| `[PHOTO-SERVICE-SCHEMA-DESYNC-AND-LEAK]` | `PhotoService.php:36-40, 92-97, 180-184` vs `mysql-schema.sql:1985-2001` | Tabloda olmayan `category` kolonuna sorgu atılıyor; dosya silmede `dosya_yolu` yerine olmayan `path`/`thumbnail` okunup diskte dosya yetim kalıyor | SQL Column Not Found (1054) & Disk Sızıntısı |
| `[ADMIN-PHOTO-CROSS-TENANT-DATA-LEAK]` | `PhotoController.php:387-400`, `Photo.php:10-14` | `Photo` modelinde `BelongsToTenant` yok; admin galeri sorgusunda kiracı filtrelemesi yapılmadan tüm sistem fotoğrafları listeleniyor | Kural 1 Tenant İzolasyon İhlali / Veri Sızıntısı |
| `[SUBSCRIPTION-GATE-COMPLETE-DISCONNECT]` | `SubscriptionMiddleware.php:27-35`, `Kernel.php:77-114` | SubscriptionMiddleware alias'larda yok, hiçbir rotaya bağlı değil; bağlı olsa bile App\Models\Tenant modelinde subscription metodu yok | Lisans ve Abonelik Kapılarının Tamamen Devre Dışı Olması |
| `[SAAS-TENANT-MODEL-DUAL-SPLIT]` | `Tenant.php:13` vs `SaaS\Tenant.php:11` | İki ayrı Tenant modeli aynı tabloya bakıyor; biri BaseModel extend edip auth'ta kullanılırken diğeri izole kalıyor | Model Çatallanması & İlişki Uyuşmazlığı |
| `[PUBLIC-API-USERS-DATA-EXPOSURE]` | `v2-users.php:36-38`, `UserController.php:32-49` | ✅ **CLOSED / PRODUCTION_VERIFIED (a1f2d168)** — Sanctum auth enforced, unauthenticated calls return 401 | ~~Kritik Veri Güvenliği Sızıntısı (KVKK/GDPR İhlali)~~ |
| `[V2-USERS-ROUTE-MODEL-BINDING-MISMATCH]` | `v2-users.php:38, 43, 44`, `UserController.php:77, 88, 109` | ✅ **CLOSED / PRODUCTION_VERIFIED (a1f2d168)** — Route parameters corrected to `{user}`, implicit binding functional | ~~Boş Veri Dönüşü / Yanlış Kayıt Silme/Güncelleme~~ |
| `[V2-DRAFTS-TABLE-SCHEMA-IMAGINARY-COLLISION]` | `DraftController.php:40-45`, `StoreDraftAction.php:11-19`, `AiIlanTaslagi.php:18-32` | Controller ve Action ilan_taslaklar tablosunda olmayan kullanici_id, ai_response kolonlarını sorgulayıp kaydediyor | SQL Column Not Found (1054) / Taslak Modülü Çökmesi |
| `[OPENCLAW-CONFIG-KEY-DEADLOCK]` | `EnsureAgentScope.php:53-54` vs `config/openclaw.php:112-115` | Middleware var olmayan config('services.openclaw.agent_token') değerini okuyor; token her zaman boş kabul edilip 403 ile kilitleniyor | Tüm OpenClaw Otonom Ajan İsteklerinin Kilitlenmesi |
| `[SCHEMA-DUMP-MIGRATION-DRIFT]` | `mysql-schema.sql:1985-2001` vs `database/migrations/` | mysql-schema.sql aylardır dump edilmemiş; core tablolardaki tenant_id ve proj_listings gibi 40+ yeni migration eksik | Test & Canlı Ortam Şema Uçurumu (Schema Drift) |
| `[MIGRATION-SQLITE-SYNTAX-INCOMPATIBILITY]` | `2026_08_26_000002_fix_bina_yasi_column_type.php:74, 134` | Migration içinde SQLite'ın desteklemediği ALTER TABLE MODIFY ve UNSIGNED cast raw SQL kullanılmış | SQLite Test Bootstrap Çökmesi (Syntax Error) |
| `[CRON-GHOST-COMMAND-ORPHANS]` | `Console/Kernel.php:28, 38, 70` | testsprite:auto-learn ve context7:phase-scan komutları yok; quality:gate'te --with-context7 opsiyonu yok | Zamanlanmış Görevlerin Sessizce Çökmesi |
| `[CRON-DAILY-SNAPSHOT-TYPE-COERCION-VOID]` | `Console/Kernel.php:233` vs `mysql-schema.sql:6665` | Tenant::where('aktiflik_durumu', 1) sorgusu atılıyor; DB'de kolon varchar 'active' olduğundan MySQL'de daima boş dönüyor | Günlük Kiracı Snapshot İşlerinin Asla Çalışmaması |
| `[SOCIAL-WEBHOOK-LEAD-ORPHAN-VOID]` | `InstagramWebhookController:292`, `LeadAuthorityService:120`, `routes/api.php:74` | Meta webhook'larında tenant çözümlemesi yok; oluşturulan lead'ler tenant_id=null kalarak CRM ekranlarında görünmez oluyor | Yetim Lead / CRM Vitrin Görünmezliği |
| `[BACKUP-DESTINATION-LOCAL-ONLY-DISASTER-RISK]` | `config/backup.php:153-156`, `app/Console/Kernel.php` | Yedekler yalnızca local diskte tutuluyor (off-site yok); ayrıca backup komutları scheduler'a eklenmemiş | Felaket Kurtarma Sıfır Toleransı / Veri Kaybı Riski |
| `[S3-MEDIA-DIRECT-URL-SIGNING-VOID]` | `Photo.php:89-92`, `config/filesystems.php:50-61` | İlan fotoğrafları ve belgeler Storage::url() ile salt public çağrılıyor; S3 diskinde hassas dokümanlara geçici imzalı URL (signed URL) mekanizması yok | Hassas Belge Güvenlik Açığı / 403 S3 Hatası |
| `[PHOTO-THUMBNAIL-STORAGE-SYNC-DRIFT]` | `StorePhotoAction.php:44-58`, `PhotoService.php:260-280`, `Photo.php:97-116` | Action ve Service disk üzerine thumbnails/ klasörüne fiziksel thumbnail üretip kaydediyor ancak Photo modelinde thumbnail kolonu yok (accessor null dönüyor); diskte yetim dosya birikiyor | Disk Sızıntısı & Kırık Thumbnail İlişkisi |
| `[SMS-CHANNEL-DISPATCH-DISCONNECT-AND-VOID]` ✅ | `NotificationDispatcher.php:141-148`, `NotificationService.php:257` | ✅ **CLOSED / PRODUCTION_VERIFIED** (2026-09-20 · Commit `4074a27b`) — Fake success path removed; `NotificationService` returns `success => false` (fail-closed). Real SMS capability: `NOT_IMPLEMENTED`. | ~~SMS Bildirimlerinin Asla Gönderilmemesi (SMS Blackhole)~~ |
| `[PROD-QUEUE-WORKER-NOTIFICATIONS-BLACKOUT]` ✅ | `SendNotificationJob.php:46`, `docker-compose.production.yml:146` | SendNotificationJob 'notifications' kuyruğuna atılıyor; canlı Docker worker ise sadece '--queue=default' dinliyor | ✅ **ÇÖZÜLDÜ** (2026-09-20) `docker-compose`: `--queue=default,notifications,concierge` eklendi · Commit `3fbd937d` · `QueueRoutingRegressionTest` ile koruma altına alındı |
| `[OPENCLAW-RATE-LIMITER-BYPASS]` | `EnforceOpenClawBoundary.php:111`, `EnsureOpenClawScope.php:23`, `config/openclaw.php:97` | Yeni 3 katmanlı OpenClaw middleware'inde rate limiter uygulanmıyor; config('openclaw.rate_limits') tanımlı ancak EnforceOpenClawBoundary içinde RateLimiter çağrısı yok | Ajan Gateway Rate Limit Korumasızlığı / DoS Riski |
| `[AUTH-LOGIN-CSRF-BYPASS]` | `VerifyCsrfToken.php:19`, `routes/auth.php:10` | POST /login uç noktası $except listesine alınarak CSRF korumasından tamamen muaf tutulmuş | Kritik Giriş Güvenlik Açığı (Login CSRF Vulnerability) |
| `[LARAVEL-CORS-CONFIG-ABSENT]` | `Kernel.php:18`, `config/cors.php` | Global middleware'de HandleCors aktif ancak config/cors.php dosyası yok; varsayılan framework fallback kurallarıyla çalışıyor | Öngörülemeyen CORS Politikası & Çapraz İstek Sızıntısı |
| `[REF-SEQUENCE-CONCURRENT-INSERT-COLLISION]` | `RefSequence.php:65-81`, `IlanNoGenerator.php:86-96` | Sequence tablosunda kayıt yokken lockForUpdate() null dönüyor; eşzamanlı iki işlem aynı anda create/insert deneyerek Duplicate Key (1062) ile çöküyor | Eşzamanlı İlan No Üretim Kilitlenmesi / Duplicate Key |
| `[HERMES-CORRELATION-CAUSATION-CHAIN-VOID]` | `HermesEventContract.php:11-32`, `HermesEventLog.php:24-35` | Hermes kontratında ve log tablosunda correlation_id ve causation_id kolonları yok; asenkron ajan zincirlerinde kök neden izleme (distributed tracing) yapılamıyor | Olay İzsizliği & Kök Neden Analiz Körlüğü |
| `[ACTION-CENTER-IDEMPOTENCY-RACE-CONDITION]` | `ActionCenterService.php:591-629`, `2026_09_06_000001_add_action_center_fields_to_gorevler.php:97-118` | İdempotency kontrolü exists() + create() ile yapılıyor fakat DB seviyesinde benzersiz (unique) indeks yok; eşzamanlı iki event geldiğinde çift görev oluşuyor | Çift Görev Oluşumu & İş Yükü Çoğalması |
| `[ACTION-ASSIGNMENT-STATUS-COLUMN-DRIFT]` | `ActionAssignmentService.php:184-186`, `User.php:27` | Kullanıcı aktiflik kontrolünde Schema::hasColumn('is_active') aranıyor; User tablosunda Context7 standardı 'aktiflik_durumu' olduğundan bu kontrol daima atlanıyor ve pasif danışmanlara görev atanabiliyor | Pasif/Ayrılmış Danışmana Görev Atanması (SLA İhlali) |
| `[ADVISOR-COMMAND-CENTER-STATELESS-DRIFT]` | `AdvisorCommandCenterService.php:116-178`, `ActionCenterService.php:33` | AI Komuta Merkezi öncelikli aksiyonları her istekte RAM'de hesaplıyor ancak Action Center / Gorevler tablosuna kaydetmiyor; üretilen aksiyonlar görev havuzunda izlenemiyor ve atanamıyor | Eylemsiz AI Önerileri & Görev Takip Kopukluğu |
| `[OPPORTUNITY-ENGINE-GHOST-PROJECTION-VOID]` | `OpportunityEngineService.php:32-35`, `ListingSearchProjection.php:14-16` | OpportunityEngineService listing_search_projection tablosunu okuyor; ancak bu projection tablosu hiçbir sistem tarafından doldurulmuyor (@deprecated), sonuçlar boş dönüyor | Fırsat Havuzu Sıfır Veri / Boş Ekran (Ghost Projection) |
| `[COMMAND_CENTER_TENANT_PROPAGATION]` | `CommandGateway.php:91-124`, `tests/Feature/CommandCenter/CommandGatewayTenantPropagationTest.php` | Telegram kullanıcısı bulunuyor ancak TenantContextService::setTenant() çağrılmıyordu — IlanSearchService fail-closed davranışı nedeniyle Tenant A listing görünmüyordu | Tenant A €1M aramasında sıfır sonuç / Tenant İzolasyonu İhlali Riski |


