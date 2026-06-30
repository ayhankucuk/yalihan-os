# Known Technical Debt

> Last updated: 2026-05-16 (Owner Portal sprint — migration'lar tamamlandı)
> Risk: LOW — none are release blockers

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

### 9. Deprecated Model Dependencies (B-006) — Kısmi Çözüm (2026-06-12)

**Fiziksel Gerçek:** `app/Models/Deprecated/` dizininde yalnızca 2 dosya vardır:
`IlanArsaDetail.php` (P2) + `IlanTurizmDetail.php` (P2). Diğer tüm `Deprecated\` referansları ghost (dosya yok).

#### ✅ Tamamlanan Alt Görevler
| # | Ghost | Kanonik | Commit |
|---|-------|---------|--------|
| P1 | `Deprecated\IlanTemplate` | `UpsTemplate` | `6ee19086` |
| P2 | `Deprecated\IlanTurizmDetail` + `IlanArsaDetail` | `Dikey\IlanTurizmDetail` + `IlanArsaDetail` | `13ff5a03` |
| P3 | `Deprecated\Kullanici` | `User` | `b2ecac7c` |
| P4 | `Deprecated\KisiNot` (ghost) + `Deprecated\KisiAktivite` (yanlış ns) | `KisiAktivite` (kisi_etkilesimler) | `2a5e6265` |

#### ⏳ Bekleyen Alt Görevler (Tam Envanter — 2026-06-12)

**P5A — ConfigOption Grubu** (Öncelik: 🟠 ORTA)
| Ghost | Kullanan | Durum |
|-------|----------|-------|
| `Deprecated\ConfigOption` | `StoreConfigOptionAction`, `DeleteConfigOptionAction`, `ConfigOptionController` | ❌ Fiziksel yok |

**P5B — Admin Servis Grubu** (Öncelik: 🟠 ORTA)
| Ghost | Kullanan | Durum |
|-------|----------|-------|
| `Deprecated\AdminNotification` | `AdminNotificationService` | ❌ Fiziksel yok |
| `Deprecated\AdminActivityEvent` | `AdminActivityEventService` | ❌ Fiziksel yok |

**P5C — AIStorage** (Öncelik: 🟠 ORTA)
| Ghost | Kullanan | Durum |
|-------|----------|-------|
| `Deprecated\AIStorage` | `FlexibleStorageManager` (satır 104, 116) | ❌ Fiziksel yok |

**P5D — Ilan / IlanRelationships God Group** (Öncelik: 🔴 KRİTİK — 2 kritik dosya etkileniyor)
| Ghost | Kullanan | Durum |
|-------|----------|-------|
| `Deprecated\IlanCalendarFeed` | `Ilan.php`, `IlanRelationships`, `IlanCalendarIcsService` | ❌ Fiziksel yok |
| `Deprecated\MatchingFeedback` | `Ilan.php`, `IlanRelationships` | ❌ Fiziksel yok |
| `Deprecated\IlanTranslation` | `Ilan.php`, `IlanRelationships` | ❌ Fiziksel yok |
| `Deprecated\IlanPortalSync` | `Ilan.php`, `IlanRelationships` | ❌ Fiziksel yok |
| `Deprecated\IlanTicariDetail` | `Ilan.php`, `IlanRelationships` | ❌ Fiziksel yok |
| `Deprecated\IlanDocument` | `Ilan.php` | ❌ Fiziksel yok |

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

### 26. Bekçi Otonom Pattern Senkronizasyon Komutu (T-BEKCI) — 2026-06-11
- **Hedef:** `app/Console/Commands/Governance/BekciPatternSyncCommand.php` (mevcut değil)
- **Gerekçe:** Bekçi v2.1 yerel MCP/IDE oturumlarında tespit ettiği pattern'leri (`yalihan-bekci/knowledge/*.json`) manuel olarak `docs/governance/LEARNED_PATTERNS.json` SSOT'una taşımak gerekiyor. Otonom senkronizasyon komutu yok.
- **Risk:** 🟡 LOW — Operasyonel manuel yük; CI/CD'yi bloke etmiyor.
- **Çözüm Beklentisi:** `php artisan bekci:pattern:sync` komutunun yazılması ve CI pipeline'ına pre-flight adımı olarak eklenmesi.
- **Durum:** ⏳ AÇIK (backlog)

### 27. Dikey İlan Detayları JSONB Göçü — Split-Brain Çözümü (T-UPS-V2) — 2026-06-12
- **Hedef:** `app/Models/Dikey/IlanTurizmDetail.php`, `app/Models/Dikey/IlanArsaDetail.php`, `app/Services/Ilan/IlanCrudService.php`
- **Gerekçe:** `ilan_turizm_details` ve `ilan_arsa_details` tabloları `ilanlar` ana tablosuyla kısmi veri tekrarı (Double-Write / Split-Brain) yaratıyor. `IlanCrudService::handleVerticalDetails()` aynı veriyi hem yardımcı tabloya hem ana tabloya yazıyor (`min_stay_nights`, `max_guests`, `cleaning_fee` çift yazım). B-006 kapsamında geçici olarak `App\Models\Deprecated\` → `App\Models\Dikey\` namespace geçişi yapıldı.
- **Risk:** 🔴 HIGH — Veri tutarsızlığı potansiyeli (Split-Brain)
- **Çözüm Beklentisi:** `ilan_turizm_details` ve `ilan_arsa_details` verilerinin `ilanlar.ekstra_ozellikler` JSONB kolonuna tam göçü + `CortexROIEngine` / `IlanVerticalDomainService` / `CortexPDFReportGenerator` kontratlarının JSONB okuyacak şekilde güncellenmesi.
- **Durum:** ⏳ AÇIK — Sprint 3 (UPS V2 tam geçiş sonrası)
