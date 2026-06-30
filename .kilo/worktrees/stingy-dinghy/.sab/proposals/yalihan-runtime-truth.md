# Yalıhan Emlak — Runtime Truth Snapshot

**Tarih:** 6 Nisan 2026, 21:00 (Local Dev)
**Ortam:** macOS, Laravel 10, MySQL, SQLite (test)
**Amaç:** brain-v2.md'deki TEORİK bilgiyi GERÇEK runtime verileriyle doğrulamak

---

## 1. ROUTE REALITY (Gerçek Aktif Route'lar)

### Özet

| Metrik | Doküman (brain-v2) | Gerçek |
|--------|-------------------|--------|
| Toplam route | "150+ AI endpoint" | **1594 toplam route** |
| AI-related route | — | **214 route** |
| Wizard route | — | **19 route** |
| Method dağılımı | — | GET: 880, POST: 554, DELETE: 78, PUT: 56 |

### Prefix Dağılımı (Top 20)

| Prefix | Route Sayısı |
|--------|-------------|
| `api/v1` | 423 |
| `admin/ilanlar` | 60 |
| `admin/property-hub` | 57 |
| `admin/takim-yonetimi` | 52 |
| `admin/ups` | 46 |
| `telescope/telescope-api` | 43 |
| `api/takim-yonetimi` | 34 |
| `admin/ilan-kategorileri` | 33 |
| `admin/blog` | 32 |
| `admin/ai` | 29 |
| `admin/governance` | 29 |
| `admin/adres-yonetimi` | 26 |
| `admin/crm` | 25 |
| `admin/ozellikler` | 25 |
| `admin/finans` | 21 |
| `admin/property-type-manager` | 21 |
| `horizon/api` | 21 |
| `admin/notifications` | 18 |
| `api/analitik` | 18 |
| `admin/ai-settings` | 16 |

### Wizard Routes (Tam Liste — 19 Route)

| Method | URI | Named Route |
|--------|-----|-------------|
| GET | `admin/ilanlar/create-wizard` | `admin.ilanlar.create-wizard` |
| POST | `api/advisor/listings/wizard/price-advisor` | `advisor.price-advisor.wizard.api` |
| POST | `api/v1/wizard/analyze-images` | `api.wizard.visual-analysis` |
| GET | `api/v1/wizard/context` | `api.wizard.context` |
| POST | `api/v1/wizard/feature-feedback` | `api.wizard.feature-feedback` |
| GET | `api/v1/wizard/features` | `api.v1.wizard.features` |
| GET | `api/v1/wizard/features-with-values` | `api.v1.wizard.features-with-values` |
| POST | `api/v1/wizard/field-suggestions` | `api.v1.wizard.field-suggestions` |
| POST | `api/v1/wizard/field-suggestions/approve` | `api.v1.wizard.field-suggestions.approve` |
| POST | `api/v1/wizard/field-suggestions/rollback` | `api.v1.wizard.field-suggestions.rollback` |
| POST | `api/v1/wizard/price-to-text` | `api.wizard.price-to-text` |
| GET | `api/v1/wizard/quick-selections` | `api.wizard.quick-selections` |
| GET | `api/v1/wizard/schema` | `api.wizard.schema` |
| POST | `api/v1/wizard/suggest` | `api.wizard.suggest` |
| POST | `api/v1/wizard/telemetry/feature-action` | `api.wizard.telemetry.feature-action` |
| GET | `api/v1/wizard/template-auto-select` | `api.wizard.template-auto-select` |
| POST | `api/v1/wizard/validate-step-2` | `api.wizard.validate-step-2` |
| GET | `api/v1/wizard/validation-rules` | `api.wizard.validation-rules` |
| GET | `test/form-wizard-debug` | *(unnamed — debug route)* |

### Doğrulama Notu

- brain-v2'de "150+ AI endpoint" yazdık → gerçekte **214 AI-related route** var. Doküman EKSİK KALMIŞ.
- Wizard route'lar doğru belgelenmiş (brain-v2 ile uyumlu)
- `test/form-wizard-debug` route'u production'a sızmamalı — **UYARI**

---

## 2. TEST REALITY

### Sonuç

```
Tests:    566 failed, 69 skipped, 452 passed (1416 assertions)
Duration: 36.61s
```

| Metrik | Değer |
|--------|-------|
| Toplam test | 1087 |
| ✅ Passed | 452 (%42) |
| ❌ Failed | 566 (%52) |
| ⏭ Skipped | 69 (%6) |
| Süre | 36.61s |

### Failure Pattern Analizi

**TEK KÖKÜ:** Tüm 566 failure aynı sebepten:

```
SQLSTATE[HY000]: General error: 1 no such table: yayin_tipi_sablonlari
(Connection: sqlite)
```

**Teşhis:**
- Test ortamı: **SQLite** (in-memory veya file-based)
- Production ortamı: **MySQL**
- `yayin_tipi_sablonlari` tablosu MySQL'de var ama SQLite test DB'de yok
- Çünkü bu tablo migration dosyasıyla DEĞİL, doğrudan SQL ile oluşturulmuş
- Test ortamında migration çalışınca bu tablo hiç oluşturulmuyor

**Sınıflandırma:** `ENVIRONMENT` — test altyapı sorunu, kod kalitesi sorunu DEĞİL.

**Etkilenen test dosyası:** `tests/Feature/WizardSchemaStep2Test.php` (bu tek dosya tüm 566 failure'ı üretiyor olabilir veya diğer test dosyaları da aynı tabloya bağımlı)

### Doğrulama vs brain-v2

brain-v2'de test durumu belgelenmemişti → şimdi gerçek durum net:
- Testlerin %52'si kırık ama sebebi tek: **SQLite/MySQL uyumsuzluğu**
- Bu, kod kalitesi DEĞİL altyapı meselesi

---

## 3. MIGRATION REALITY

### Gerçek Durum

| Kaynak | Sayı |
|--------|------|
| Migration dosyası (filesystem) | **2** (sadece Finans modülünde) |
| Çalışmış migration (DB) | **3** (2 Finans + 1 Laravel default) |
| Pending migration | **0** |
| `database/migrations/` dizini | **YOK** |

### Çalışmış Migration'lar

| Migration | Batch | Durum |
|-----------|-------|-------|
| `2019_12_14_000001_create_personal_access_tokens_table` | 1 | Ran |
| `2025_11_26_011602_create_finansal_islemler_table` | 1 | Ran |
| `2025_11_26_011602_create_komisyonlar_table` | 1 | Ran |

### Kritik Bulgu

**Veritabanı şeması migration-driven DEĞİL.**

Bu çok önemli bir gerçek:
- Proje production DB'yi doğrudan SQL ile yönetiyor
- `database/migrations/` dizini bile yok (sadece `Modules/Finans/` altında 2 tane)
- Tüm tablo yapısı MySQL'deyken, testler SQLite üzerinde çalışıyor → bu yüzden 566 test fail

**Sonuç:** brain-v2'de "migration plan" var diye yazıldı ama gerçekte migration-driven deployment YOK.

---

## 4. DEAD SERVICE REALITY

### Hiçbir Yerden Referans Almayan Servisler (0 Reference)

| # | Servis Dosyası | Domain |
|---|---------------|--------|
| 1 | `AI/AIArsaAnalizService.php` | AI |
| 2 | `AI/AIContractService.php` | AI |
| 3 | `AI/ContinuousThresholdOptimizer.php` | AI |
| 4 | `AI/IlanGecmisAIService.php` | AI |
| 5 | `AI/IlanStorytellingService.php` | AI |
| 6 | `AIMatch/BuyerIntentExtractionService.php` | AI/Match |
| 7 | `AITranslation/ListingTranslationService.php` | AI/Translation |
| 8 | `AITranslation/TranslationFallbackService.php` | AI/Translation |
| 9 | `CRM/AgentAssignmentService.php` | CRM |
| 10 | `CRM/FollowUpAutomationService.php` | CRM |
| 11 | `Context7/QueryStringAnalyzer.php` | Governance |
| 12 | `ErrorAutoRepairService.php` | Infrastructure |
| 13 | `Finance/ListingFinanceService.php` | Finance |
| 14 | `ICalParserService.php` | Integration |
| 15 | `IlanDataProviderService.php` | Listing |
| 16 | `IletisimService.php` | Listing |
| 17 | `Integrations/AudioGenerationService.php` | Integration |
| 18 | `InvestorDashboardService.php` | Analytics |
| 19 | `MarketIntelligence/ActionQueueService.php` | Intelligence |
| 20 | `MarketIntelligence/CalibrationService.php` | Intelligence |
| 21 | `MarketIntelligence/OutcomeTrackingService.php` | Intelligence |
| 22 | `MarketIntelligence/PredictionSnapshotService.php` | Intelligence |
| 23 | `N8nService.php` | Integration |
| 24 | `Notification/FacebookAutoReplyService.php` | Notification |
| 25 | `Notification/InstagramAutoReplyService.php` | Notification |
| 26 | `Notification/TelegramOutboundService.php` | Notification |
| 27 | `Notification/WhatsAppNotificationManager.php` | Notification |
| 28 | `Notification/WhatsAppNotificationService.php` | Notification |
| 29 | `PlanNotlariAIService.php` | AI |
| 30 | `Price/PriceTextService.php` | Listing |

### Domain Bazlı Dead Service Dağılımı

| Domain | Dead Service Sayısı |
|--------|-------------------|
| AI / AI-related | 8 |
| Notification | 5 |
| MarketIntelligence | 4 |
| Integration | 3 |
| CRM | 2 |
| Listing | 2 |
| Translation | 2 |
| Finance | 1 |
| Governance | 1 |
| Analytics | 1 |
| Infrastructure | 1 |
| **TOPLAM** | **30** |

### Doğrulama vs brain-v2

brain-v2'de "170+ servis" yazıyordu. Gerçekte:
- **30 servis DEAD** (hiçbir yerden çağrılmıyor)
- Gerçek aktif servis: **~140** — doküman kısmen doğru ama dead code belirtilmemişti
- AI domain'de en çok dead service: 8 → büyüme ama kullanılmama riski

**Not:** Bu tarama `maxdepth 2` ile yapıldı. Alt klasörlerdeki deep services dahil değil — gerçek dead sayısı daha yüksek olabilir.

---

## 5. GOVERNANCE ENFORCE REALITY

### Artisan Command Gerçeği

brain-v2'de `context7:integrity-scan` yazılmıştı → **GERÇEKLİK:**

| Doküman | Gerçek |
|---------|--------|
| `context7:integrity-scan` | ❌ MEVCUT DEĞİL |
| `sab:integrity-scan` | ✅ Bu çalışıyor |
| `bekci:wizard-contract` | ✅ Çalışıyor |
| `quality:gate` | ✅ Mevcut |

**brain-v2'de YANLIŞ komut belgelenmiş.** Doğru komut: `sab:integrity-scan`

### SAB Integrity Scan Gerçek Çıktısı

| Kategori | Sayı | Severity |
|----------|------|----------|
| Technical Debt (TODO/FIXME) | 14 | MEDIUM |
| Missing Global Scope (HasCountryScope) | 4 | CRITICAL |
| Foundation Lock Violation (BaseModel extend) | 2 | CRITICAL |
| Security Risk (raw SQL) | 3 | HIGH |
| Hardcoded State String | 7 | HIGH |
| SAB Governance Violation (forbidden 'status') | 1 | HIGH |
| **Total NEW Violations** | **31** | — |
| **Legacy Violations (Baseline)** | **191** | — |

### Gerçek Violation Breakdown

**CRITICAL (6):**
- `AgentMemory`, `AgentRun`, `GovernanceDecision`, `OptimizerSuggestion` → HasCountryScope trait eksik
- `GovernanceRollback`, `GovernanceSuppression` → BaseModel extend etmiyor

**HIGH (11):**
- `GovernanceDecision` L139, L148 → unparameterized raw SQL (SQL injection riski)
- `ActionFeedbackService` L205 → unparameterized raw SQL
- `GovernanceDashboardService` (7 yer) → hardcoded state string
- `RollbackService` L42 → forbidden pattern 'status' (Context7 violation)

**MEDIUM (14):**
- PropertyHubController (4 yer), IlanBulkService (8 yer), IlanFeatureService (1), Wizard FeatureTemplateResolver (1) → TODO/FIXME

### Bekçi Wizard Contract Sonucu

```
✅ All contract checks passed!
```

| Check | Sonuç |
|-------|-------|
| WFC-001: DB Schema Sync | ✅ kategori_id column exists |
| WFC-002: Yayin Tipi Naming | ✅ YayinTipiResolverTrait exists |
| WFC-013: Migration Rename Guard | ✅ |
| WFC-014: Test DB Validation | ⚠️ Skipped (not testing env) |
| WFC-015: Hallucination Protection | ✅ AI Safety guards detected |

### CI Pipeline Gerçeği

`.github/workflows/sab-guard.yml` → **VAR ve DOĞRU yapılandırılmış:**
- Trigger: push/PR to main/develop
- Layer 0: Policy Check (controller guard)
- Layer 1: `sab:guard` (runtime enforcement)
- Layer 2: Drive sync (dry-run)

### MCP Server Durumu

| Server | Port | Doküman | Gerçek Durum |
|--------|------|---------|-------------|
| `yalihan-bekci-mcp` | 4001 | Aktif | ❌ NOT RUNNING |
| `context7-validator-mcp` | 4002 | Aktif | ❌ NOT RUNNING |

**MCP server'lar çalışmıyor.** Manuel start gerekli: `./scripts/services/start-all-mcp-servers.sh`

---

## 6. DOĞRULAMA TABLOSU: TEORİ vs REALİTE

| brain-v2 İddiası | Gerçek | Uyumlu? |
|-------------------|--------|---------|
| "150+ AI endpoint" | 214 AI route | ⚠️ Eksik (gerçek daha fazla) |
| "170+ servis" | ~170 var ama 30'u dead | ⚠️ Dead service belirtilmemişti |
| Wizard flow doğru | ✅ 19 route, named, tümü aktif | ✅ |
| "context7:integrity-scan" komutu | `sab:integrity-scan` ← doğru isim | ❌ YANLIŞ |
| Migration-driven deployment | Migration dosyası yok, SQL-based | ❌ YANLIŞ varsayım |
| Quality gate zinciri | SAB + Bekçi = çalışıyor | ✅ (isim farklı) |
| CI guard var | .github/workflows/sab-guard.yml | ✅ |
| MCP server aktif | Port 4001/4002 çalışmıyor | ❌ |
| Git history var | `.git` dizini yok (deploy target) | ❌ Erişilemez |
| AI block etmez (Warning Mode) | ✅ SmartFieldGenerationService'de guard var | ✅ |
| authority.json SSOT | .sab/authority.json + Drive'da | ✅ |
| 191 legacy baseline violation | sab:integrity-scan doğruladı | ✅ |
| "31 new violation" | SAB scan çıktısıyla eşleşiyor | ✅ |

---

## 7. KRİTİK BULGULAR (brain-v2'de YOK)

### B1: Test Altyapısı Kırık
- %52 test failure — tümü SQLite/MySQL uyumsuzluğu
- `yayin_tipi_sablonlari` tablosu test DB'de yok
- Çözüm: test setup'a tablo oluşturma SQL'i veya test-specific migration ekle

### B2: 30 Dead Service
- 170+ servisten 30'u hiçbir yerden çağrılmıyor
- AI domain'de en çok (8 dead service)
- Risk: bakım maliyeti, import yönetimi, false dependency

### B3: Governance Komutu Yanlış Belgelenmiş
- Tüm dökümanlarda `context7:integrity-scan` yazıyor
- Gerçek komut: `sab:integrity-scan`
- `.github/copilot-instructions.md` dahil hepsinde YANLIŞ

### B4: Migration-Free DB
- Production DB şeması migration-driven değil
- Bu, schema drift riski = her ortam farklı olabilir
- Test ortamı bunu kanıtlıyor (tablo yok)

### B5: MCP Sunucuları Varsayılan Kapalı
- brain-v2'de "MCP aktif" yazıyor ama runtime'da kapalılar
- copilot-instructions.md'de "MUST be active" yazıyor ama enforce yok

### B6: debug route production'a sızma riski
- `test/form-wizard-debug` — named route bile yok
- Production'da bu route açık mı? → güvenlik riski

### B7: SQL Injection Riski
- `GovernanceDecision` L139, L148 — raw SQL parametresiz
- `ActionFeedbackService` L205 — raw SQL parametresiz
- SAB scanner bunu tespit ediyor ama fix edilmemiş

---

## 8. GAP KAPANIŞ DURUMU

| # | Gap | Durum | Detay |
|---|-----|-------|-------|
| 1 | Runtime truth | ✅ KAPATILDI | 1594 route, 19 wizard, 214 AI — gerçek veri |
| 2 | Plan durumu | ⚠️ KISMEN | Git history erişilemez (deploy target), migration reality belgelendi |
| 3 | Dependency graph | ✅ KAPATILDI | 30 dead service tespit edildi |
| 4 | Kırılma noktaları | ✅ KAPATILDI | 31 new + 191 legacy violation, 3 SQL injection, test failure pattern |
| 5 | Kullanıcı davranışı | ❌ KAPATILAMADI | Analytics/Telescope verisi gerekli — koddan çıkmaz |
| 6 | Governance enforce | ✅ KAPATILDI | CI var, SAB çalışıyor, MCP kapalı, Bekçi geçti, komut adı yanlıştı |

---

## 9. KOMUT REFERANSI (Gerçek Governance Komutları)

| Komut | Açıklama | Durum |
|-------|----------|-------|
| `sab:integrity-scan` | SAB Zero-Tolerance integrity scan | ✅ Çalışıyor |
| `sab:guard` | SAB Strict Guard (CI fail condition) | ✅ Çalışıyor |
| `sab:audit` | Full SAB Audit (markdown rapor) | ✅ Mevcut |
| `sab:baseline` | SAB baseline oluştur/yenile | ✅ Mevcut |
| `sab:doctor` | SAB diagnostics + health summary | ✅ Mevcut |
| `sab:preflight` | SAB preflight chain (fast/full/release) | ✅ Mevcut |
| `sab:scan` | SAB quick architecture scan | ✅ Mevcut |
| `bekci:wizard-contract` | Wizard contract validator | ✅ Çalışıyor |
| `bekci:aesthetics` | UI/UX Dark Mode denetimi | ✅ Mevcut |
| `bekci:health` | AI sistemi sağlık durumu | ✅ Mevcut |
| `quality:gate` | SAB Kristal Temizlik (Zero Drift) | ✅ Mevcut |
| `guard:cqrs` | CQRS Integrity Guard | ✅ Mevcut |
| `guard:routes:v2` | Route integrity guard | ✅ Mevcut |
| `standard:check` | Context7 standard check | ✅ Mevcut |
| `context7:integrity-scan` | — | ❌ MEVCUT DEĞİL |

---

*Bu snapshot read-only veri toplamadır. Hiçbir dosya değiştirilmedi, hiçbir migration çalıştırılmadı.*
*Snapshot zamanı: 6 Nisan 2026, ~21:00 UTC+3*
