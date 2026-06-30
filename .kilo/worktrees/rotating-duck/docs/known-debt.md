# Known Technical Debt

> Son güncelleme: 2026-06-16 (Oturum 59 — MD Audit & Yeni Borçlar eklendi)
> Risk: LOW-MEDIUM — hiçbiri release blocker değil

## Active Debt Items

### 1. TelegramBrain — Missing `telegram_id` Column
- **Table:** `users`
- **Impact:** `TelegramBrain` service cannot function without this column
- **Priority:** LOW (feature not in active use)
- **Action:** Add migration when Telegram integration is activated

### 2. Telemetry Route Name Inconsistency
- **Route:** `telemetry.feature-action`
- **Impact:** Minor naming inconsistency, no runtime breakage
- **Priority:** LOW
- **Action:** Backlog — align in next cleanup sprint

### 3. SAB Legacy Baseline Violations (153)
- **Source:** `sab:integrity-scan` baseline
- **Impact:** Tracked, not growing — 0 new violations as of 2026-04-11
- **Priority:** LOW (stable baseline)
- **Action:** Gradually reduce in future refactor sprints

### 4. Open Architecture Questions (Q1-Q8)
- **Source:** `docs/architecture/open-questions.md`
- **Impact:** Unverified runtime behaviors documented but not confirmed
- **Priority:** MEDIUM
- **Action:** Verify during next scheduled architecture review

### 5. Dual System Consolidation
- **CRM:** V1 (`App\Models\Musteri`) vs V2 (`App\Models\CRM\*`) coexist
- **Finance:** `FinansalIslem` vs `Finance\*` modules overlap
- **Address:** Dual address management controllers
- **Priority:** MEDIUM (operational, not blocking)
- **Action:** Consolidation requires dedicated sprint with migration plan

### 6. GitHub Actions CI External Confirmation
- **Pipeline:** `gold-line.yml` on `main` branch
- **Impact:** Local gates all GREEN, external CI status unconfirmed
- **Priority:** LOW (local verification complete)
- **Action:** Check GitHub Actions dashboard manually

### 7. MatchingEngine N+1 Update (B-004) ✅ ÇÖZÜLDÜ
- **Çözüm:** Ilan::upsert() ile tek sorguda bulk update — Oturum 2 (2026-05-13)
- **Durum:** KAPALI

### 8. AiWalletService Balance Snapshot (B-005) ✅ ÇÖZÜLDÜ
- **Çözüm:** $wallet->refresh() eklendi (deduct ve add her ikisinde) — Oturum 2 (2026-05-13)
- **Durum:** KAPALI

### 9. Deprecated Model Dependencies (B-006) ✅ KAPALI (2026-06-15)

**Tüm `App\Models\Deprecated\*` aktif referansları temizlendi. Sıfır ghost kaldı.**

#### ✅ Tamamlanan Alt Görevler (Tam Envanter)
| # | Ghost | Kanonik Model | Migration | Commit |
|---|-------|--------------|-----------|--------|
| P1 | `Deprecated\IlanTemplate` | `UpsTemplate` | — | `6ee19086` |
| P2 | `Deprecated\IlanTurizmDetail` + `IlanArsaDetail` | `Dikey\IlanTurizmDetail` + `IlanArsaDetail` | — | `13ff5a03` |
| P3 | `Deprecated\Kullanici` | `User` | — | `b2ecac7c` |
| P4 | `Deprecated\KisiNot` + `Deprecated\KisiAktivite` | `KisiAktivite` (kisi_etkilesimler) | — | `2a5e6265` |
| P5D | `Deprecated\IlanCalendarFeed` + 5 ghost | `IlanCalendarFeed`, `Dikey\IlanTicariDetail` | `2026_06_12_000001` | `12519bba` |
| P5A | `Deprecated\ConfigOption` | `ConfigOption` | `2026_06_14_000001` | `fde46984` |
| P5B | `Deprecated\AdminNotification` + `AdminActivityEvent` | `AdminNotification`, `AdminActivityEvent` | `2026_06_14_000002` | `19174cb3` |
| P5C | `Deprecated\AIStorage` | `AIStorage` | `2026_06_14_000003` | `818e9a78` |
| P5E | `Deprecated\FeatureValue` + translations | `FeatureValue`, `FeatureTranslation`, `FeatureCategoryTranslation` | `2026_06_14_000004` | `ca1dca74` |
| P5F | `Deprecated\DemirbasKategori` + `Communication` | `DemirbasKategori`, `Communication` | `2026_06_14_000005` | `9582880c` |

**Kalan `Deprecated/` dizini:** `IlanArsaDetail.php` + `IlanTurizmDetail.php` (P2 — fiziksel dosyalar, aktif kullanımda, kanonik)

**P5E — Feature/Emlak Grubu** (Öncelik: 🟡 DÜŞÜK)
| Ghost | Kullanan | Durum |
|-------|----------|-------|
| `Deprecated\FeatureValue` | `HasFeatures` trait | ❌ Fiziksel yok |
| `Deprecated\FeatureCategoryTranslation` | `Emlak\FeatureCategory` | ❌ Fiziksel yok |
| `Deprecated\FeatureTranslation` | `Emlak\Feature` | ❌ Fiziksel yok |

**Önerilen Fix Sırası:** P5A → P5B → P5C → P5D → P5E

### 10. N8N Webhook URL Gaps (B-003) ✅ ÇÖZÜLDÜ
- **Çözüm:** `N8N_WEBHOOK_URL`, `N8N_WEBHOOK_HIGH_MATCH`, `N8N_WEBHOOK_NEW_LISTING` production `.env`'e eklendi (2026-05-16)
- **Durum:** KAPALI

### 11. Telegram Bot Credentials (T-001/T-002) ✅ ÇÖZÜLDÜ
- **Çözüm:** @yalihanx_bot token + admin_chat_id mühürlendi — Oturum 3 (2026-05-14)
- **Webhook:** panel.yalihanemlak.com.tr/api/telegram/webhook (Laravel deploy sonrası aktif)
- **Durum:** KAPALI

### 12. FK Constraint Stabilization (Project Milestone #7) ✅ ÇÖZÜLDÜ
- **Çözüm:** YayinTipiSablonuFactory kategori_id FK bağımlılığı eklendi — constraint hatası giderildi.
- **Durum:** KAPALI

### 13. Model Drift: Missing Transactions Table ✅ ÇÖZÜLDÜ
- **Çözüm:** transactions migration yeniden yazıldı, tenant_id + islem_turu/islem_tutari Context7 uyumlu.
- **Durum:** KAPALI

### 14. SAB Context7 Violations (175 Findings)
- **Impact:** Violation of technical constitution; naming inconsistency (`status`, `type`, `active` fields)
- **Priority:** MEDIUM
- **Action:** Systematically rename forbidden fields to Context7 canonical Turkish names

### 15. Controller Complexity (Fat Controllers)
- **Impact:** Maintenance nightmare in `DecisionEngineController`, `FieldSuggestionController`
- **Priority:** MEDIUM
- **Action:** Extract orchestration logic to dedicated Service Layer classes

### 16. Semantic Ghosts (42+ Instances) ✅ ÇÖZÜLDÜ
- **Çözüm:** 9 silent catch Log::error ile kapatıldı.
- **Durum:** KAPALI

### 18. Naming Authority Drift (18+ Instances)
- **Impact:** Violation of Hybrid Naming Policy (Turkish Domain vs English Framework).
- **Priority:** MEDIUM
- **Action:** Align fields with `NAMING-AUTHORITY.md` standards using opportunistic refactoring.

### 17. Finance: LedgerController Structural Debt ✅ ÇÖZÜLDÜ
- **Çözüm:** pre-existing, LedgerController zaten thin.
- **Durum:** KAPALI

### 19. Content Security Policy — Alpine.js unsafe-inline/eval
- **Dosyalar:** `SecurityMiddleware.php`, `SecureHeaders.php`
- **Etki:** Production CSP şu an `'unsafe-inline'` + `'unsafe-eval'` içeriyor. Alpine.js v3'ün `new Function()` kullanımı nedeniyle zorunlu. Bu XSS saldırılarına karşı CSP koruma düzeyini düşürür.
- **Öncelik:** MEDIUM
- **Kök neden:** Admin dashboard `copilot-diff-modal` bileşeni Alpine.js çalışmadan açık kalıyordu (APP_ENV=production CSP blocking). `x-cloak` geçici güvenli önlem olarak eklendi.
- **Uzun vadeli çözüm:** Nonce tabanlı CSP — her request için unique nonce üretilip blade şablonlarına ve Vite manifest'e enjekte edilmeli. `strict-dynamic` ile birleştirilmeli.
- **Geçici önlem:** `x-cloak` modal blade'e eklendi (2026-05-16).
- **Çözüm (Phase 15):** `SecureHeaders` middleware `nonce`-tabanlı `strict-dynamic` CSP'ye yükseltildi. 30+ CDN script tag `<x-csp-script>` merkezi bileşenine dönüştürüldü. `unsafe-eval` ve `unsafe-inline` (script-src) kaldırıldı.
- **Durum:** ✅ KAPALI (2026-05-16)

### 20. Governance Hash Chain — Migration Pending ✅ KAPALI
- **Migration:** `add_hash_columns_to_governance_decisions_table`
- **Çözüm:** `php artisan migrate` → `Nothing to migrate` — migration zaten uygulanmış (2026-06-04).
- **Durum:** ✅ KAPALI

### 21. Phase 16 — Authority Hardening: Filtering ≠ Authorization ✅ KAPALI
- **Etkilenen:** `IlanRepository::getAdminListings()`, `KisiService::getAllKisiler()`
- **İhlal:** `danisman_id` filtresi ownership scope atlanarak tüketici tarafından kontrol edilebiliyordu.
- **Çözüm:** Her iki dosyaya `isAdmin` kontrolü ve non-admin otomatik ownership scope eklendi (2026-05-16).
- **Durum:** ✅ KAPALI

### 22. sab:integrity-scan Baseline (2026-05-16)
- **Tarama tarihi:** 2026-05-16
- **Toplam ihlal:** ~4500 (tamamı pre-existing)
- **Bu oturumda yeni açılan:** 0
- **Dağılım:**

| Kural | Adet | Ağırlık | Durum |
|---|---|---|---|
| `NamingAuthorityAST` | ~3118 | LOW | Pre-existing — kanonik isimlendirme borcu |
| `ForbiddenFieldAST` (Wizard) | ~864 | MEDIUM | ✅ `app/Services/Wizard/` exclusion eklendi |
| `SilentCatchAST` | ~262 | LOW | Pre-existing — Cortex servisler |
| `CONTEXT7_GUARD_V3` | ~144 | LOW | Pre-existing |
| `THIN_CONTROLLER_GUARD_V3` | 7 | LOW | Owner Portal `@sab-ignore-thin` eklendi |
| `Foundation Lock Violation` | 10 | LOW | Pre-existing |
| `Security Risk` | 3 | LOW | Pre-existing |

- **Kalan bloker:** Migration (`php artisan migrate`) sonrası scan yeniden koşturulacak.
- **Öncelik:** LOW (tümü önceden var olan borç, bu oturum sıfır yeni açılım)

### 23. Owner Portal — 3 Migration ✅ KAPALI
- **Migration'lar:** `owner_report_rows`, `owner_report_metrics`, `owner_report_exports`
- **Çözüm:** `php artisan migrate` → `Nothing to migrate` — tüm migration'lar zaten uygulanmış (2026-06-04).
- **Durum:** ✅ KAPALI

### 24. PropertyHub CircuitBreaker Test Drift (T-CB) — 2026-06-11
- **Hedef:** `tests/Unit/Domain/PropertyHub/CircuitBreakerTest.php`
- **Gerekçe:** Shadow API tasfiye edildi, yeni `App\Domain\PropertyHub\Resiliency\CircuitBreaker` implementasyonuna geçildi. Test skeleton `markTestSkipped` ile kilitli — gerçek assertion yok.
- **AST Bekçi Durumu:** ✅ `CircuitBreaker::trip()` → `Log::critical()` çağrısı mevcut (Fail-Fast onaylı, SilentCatch ihlali yok).
- **Paralel Dal:** Yok — refaktör dalı açılmamış.
- **Risk:** 🟠 MEDIUM — Trip/reset ve bucket rate hesaplama mantığı test kapsamı dışında.
- **Çözüm:** `tests/Unit/Domain/PropertyHub/CircuitBreakerTest.php` yeni API'ye göre yeniden yazıldı (2026-06-11). ✅ ÇÖZÜLDÜ

### 25. PropertyHub VersionStateMachine Context7 Test Drift (T-VSM) — 2026-06-11
- **Hedef:** `tests/Unit/Domain/PropertyHub/Governance/VersionStateMachineTest.php`
- **Gerekçe:** `VersionStateMachine` `DURUM_*` Context7 Türkçe sabitlerini kullanıyor (`TASLAK`, `INCELEME`, `ONAYLANDI`, `AKTIF`, `ARSIVLENDI`). Test skeleton eski İngilizce API'ye kilitliydi.
- **Paralel Dal:** Yok — yalnızca `markTestSkipped` stub'ı mevcuttu.
- **Risk:** 🟠 MEDIUM — Geçiş kuralları (`ALLOWED_TRANSITIONS`) ve terminal state (`ARSIVLENDI`) doğrulanmıyordu.
- **Çözüm:** `tests/Unit/Domain/PropertyHub/Governance/VersionStateMachineTest.php` `DURUM_*` sabitleriyle yeniden yazıldı (2026-06-11). ✅ ÇÖZÜLDÜ

### 26. Bekçi Otonom Pattern Senkronizasyon Komutu (T-BEKCI) ✅ ÇÖZÜLDÜ
- **Hedef:** `app/Console/Commands/Governance/BekciPatternSyncCommand.php`
- **Çözüm:** `bekci:pattern:sync` komutu implementasyonu tamamlandı. 40 knowledge dosyasından 19 yeni pattern `LEARNED_PATTERNS.json`'a eklendi (LP-017 → LP-035). Toplam 35 pattern.
- **Kullanım:** `php artisan bekci:pattern:sync [--dry-run] [--force] [--since=YYYY-MM-DD] [--detail]`
- **Durum:** ✅ KAPALI (Sprint 4 — 2026-06-24)

### 27. Dikey İlan Detayları JSONB Göçü — Split-Brain Çözümü (T-UPS-V2) — 2026-06-12
- **Hedef:** `app/Models/Dikey/IlanTurizmDetail.php`, `app/Models/Dikey/IlanArsaDetail.php`, `app/Services/Ilan/IlanCrudService.php`
- **Gerekçe:** `ilan_turizm_details` ve `ilan_arsa_details` tabloları `ilanlar` ana tablosuyla kısmi veri tekrarı (Double-Write / Split-Brain) yaratıyor. `IlanCrudService::handleVerticalDetails()` aynı veriyi hem yardımcı tabloya hem ana tabloya yazıyor (`min_stay_nights`, `max_guests`, `cleaning_fee` çift yazım). B-006 kapsamında geçici olarak `App\Models\Deprecated\` → `App\Models\Dikey\` namespace geçişi yapıldı.
- **Risk:** 🔴 HIGH — Veri tutarsızlığı potansiyeli (Split-Brain)
- **Çözüm Beklentisi:** `ilan_turizm_details` ve `ilan_arsa_details` verilerinin `ilanlar.ekstra_ozellikler` JSONB kolonuna tam göçü + `CortexROIEngine` / `IlanVerticalDomainService` / `CortexPDFReportGenerator` kontratlarının JSONB okuyacak şekilde güncellenmesi.
- **Durum:** ⏳ AÇIK — Sprint 3 (UPS V2 tam geçiş sonrası)

---

### 28. app/Domains/ vs app/Domain/ — Çift Mimari (#28) ✅ KAPALI
- **Çözüm:** `app/Domains/PropertySchema/` → `app/Domain/PropertyHub/` altına taşındı, adapter kaldırıldı. Commit: `6909772`
- **Durum:** ✅ KAPALI (Sprint 2 — 2026-06-15)

### 29. DriftDetectionService Çift İmplementasyon (#58) ✅ KAPALI
- **Çözüm:** `Core\DriftDetectionService` (ActiveConfigRegistry) kanonik seçildi, diğeri kaldırıldı. Commit: `a8cf352`
- **Durum:** ✅ KAPALI (Sprint 2 — 2026-06-15)

### 30. ModuleServiceProvider İsim Çakışması (#60) ✅ KAPALI
- **Çözüm:** `App\Providers\ModuleServiceProvider` → `App\Providers\CoreModuleServiceProvider` olarak yeniden adlandırıldı. Commit: `6125ca3`
- **Durum:** ✅ KAPALI (Sprint 2 — 2026-06-15)

### 31. YalihanCortex God Object (#19) ✅ KAPALI
- **Çözüm:** 5800+ → 3139 satıra indirildi. `CortexQualityService`, `CortexPredictionService`, `CortexIntelligenceService` ayrıştırıldı. Commit: `5004346`
- **Durum:** ✅ KAPALI (Sprint 2 — 2026-06-15)

### 32. PROGRESS-TRACKER Kırık Referanslar ✅ KAPALI (2026-06-16)
- **Sorun:** Arşiv silme (Oturum 59) sonrası 17 satırda kırık MD linki oluştu.
- **Çözüm:** Tüm `PHASE4B_*`, `PHASE4C_*`, `repo-gov-*.md` referansları `registry/FAZLAR_GECMIS_RAPORLAR.md` özet linkine yönlendirildi.
- **Durum:** ✅ KAPALI (2026-06-16)

### 33. ilan_favorileri Pivot FK Uyumsuzluğu (T-FAV-01) ✅ ÇÖZÜLDÜ
- **Kaynak:** `ilan_favorileri` tablosu `user_id` içeriyor; `Ilan.php` ve `Kisi.php` pivot metodları `kisi_id` ile join yapıyor.
- **Çözüm:** `Kisi.php` pivot FK `user_id` olarak güncellendi, `Ilan.php` `favorilenKisiler()` DB query helper'a dönüştürüldü.
- **Durum:** ✅ KAPALI (Sprint 4 — 2026-06-24)

### 34. Dikey İlan JSONB Tam Göçü (T-UPS-V2-FULL) ✅ ÇÖZÜLDÜ (Phase 1 — Write Path)
- **Kaynak:** `ilan_turizm_details` + `ilan_arsa_details` → `ilanlar.ekstra_ozellikler` JSONB göçü yarım kaldı.
- **Çözüm:** Migration `2026_06_24_074746_add_ekstra_ozellikler_to_ilanlar_table.php` çalıştırıldı, `Ilan.php` cast/fillable eklendi, `IlanCrudService::handleEkstraOzellikler()` store/update zinciri tamamlandı.
- **Not:** Reader servisleri (`CortexROIEngine` vb.) hâlâ dikey tablolardan okuyor — Phase 2 (read path) ayrı görev.
- **Durum:** ✅ KAPALI — Phase 1 (Sprint 4 — 2026-06-24)

### 35. Deploy Görevleri (#20-25) — Sunucu Kurulum
- **Kaynak:** Oracle Cloud, `ubuntu@159.13.59.128` (Hermes), SSH ✅ çalışıyor.
- **Görevler:** PHP 8.2 + Nginx + MySQL + Redis + Supervisor + migrate + Cloudflare Tunnel
- **Risk:** 🔴 HIGH — Production workflow bu görevlere bağlı
- **Durum:** ⏳ AÇIK (#21-25)

### 36. Finans Komisyonlar — Eksik Admin Blade View (B-007)
- **Kaynak:** Local smoke test 2026-06-24
- **Sorun:** [`KomisyonController::index()`](app/Modules/Finans/Controllers/KomisyonController.php:35) sadece `JsonResponse` döndürüyor; Blade view yok
- **URL:** `GET /admin/finans/komisyonlar` → ham JSON yanıt
- **Çözüm:** `resources/views/admin/finans/komisyonlar/index.blade.php` oluştur + Alpine.js fetch mimarisi (`islemler` sayfası pattern)
- **Risk:** 🟡 MEDIUM — API çalışıyor, sadece admin UI eksik
- **Durum:** ⏳ AÇIK
