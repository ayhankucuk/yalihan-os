# Yalıhan Governance Karmaşa Analizi & Sadeleştirme Planı

**Tarih:** 15 Şubat 2026
**Hazırlayan:** GitHub Copilot (Context7 v2 Scanner Upgrade sonrası)
**Durum:** Analiz Raporu — Hiçbir dosya değiştirilmemiştir

---

## Özet

Projede 3 governance sistemi paralel çalışıyor: **Context7** (63 dosya), **Yalıhan Bekçi** (20 dosya), **Governance/QG** (10 dosya). Toplamda **93 dosya**, **7 CI workflow**, **16 artisan command**, **8 service** ve **6 script** var.

> **Ana Bulgu:** Forbidden field kontrolü **4 farklı yerde** yapılıyor. CI'da **3 workflow aynı trigger**'da çalışıyor. 2 artisan komut (`context7:integrity-scan` vs `Context7IntegrityScanner`) **aynı signature'ı paylaşıyor** ve çakışıyor.

---

## 1) Envanter (Dosya Listesi + Amaç)

### Context7 Sistemi — 63 dosya

| Dosya                                 | Amaç                                                                       |
| ------------------------------------- | -------------------------------------------------------------------------- |
| `config/context7.php`                 | Context7 ana konfigürasyonu, field mapping                                 |
| `config/context7_guard.php`           | Guard kuralları ve threshold'lar                                           |
| `config/context7_pricing.php`         | Fiyatlandırma modülü config                                                |
| `config/context7.json`                | JSON formatında field authority                                            |
| `config/context7-super-analyzer.json` | Super analyzer pattern config                                              |
| `config/authority.json`               | SSOT field naming authority                                                |
| `.context7ignore`                     | Scanner'ın atlayacağı dosyalar                                             |
| **Artisan Commands (13)**             |                                                                            |
| `Context7IntegrityScan.php` (v2)      | **ANA SCANNER** — PHP+JS+Blade tarama, telemetry MVP doğrulama             |
| `Context7IntegrityScanner.php`        | **İKİNCİ SCANNER (ÇAKIŞMA!)** — DB schema + kod uyumu, 1123 satır          |
| `Context7SmartDetectCommand.php`      | AI-driven deprecated pattern detection (basit regex)                       |
| `Context7SmartValidator.php`          | Smart validation (detayı bilinmiyor)                                       |
| `Context7ComplianceScoreCommand.php`  | Compliance score dashboard (hardcoded değerlerle)                          |
| `Context7CodeSmellCommand.php`        | Code smell detection (büyük dosya, karmaşık metod)                         |
| `Context7PhaseScanCommand.php`        | Phase-based scan (1-6 phase, basit str_contains)                           |
| `Context7TrendAnalysisCommand.php`    | Trend analizi                                                              |
| `Context7SampleData.php`              | Sample data generation                                                     |
| `Context7CrmTest.php`                 | CRM integration test                                                       |
| `Context7AdvisorTest.php`             | Advisor test                                                               |
| `Context7TeamTest.php`                | Team test                                                                  |
| `Context7TelegramAutomation.php`      | Telegram automation                                                        |
| **Services (8)**                      |                                                                            |
| `Context7ComplianceChecker.php`       | **HAYALET SERVİS** — hardcoded `return ['compliance' => 98.9]` (15 satır!) |
| `Context7CrmService.php`              | CRM entegrasyonu                                                           |
| `Context7AdvisorService.php`          | Advisor AI servisi                                                         |
| `Context7ProjeService.php`            | Proje yönetimi                                                             |
| `Context7AuthService.php`             | Auth entegrasyonu                                                          |
| `Context7DashboardService.php`        | Dashboard veri servisi                                                     |
| `Context7CacheService.php` (×2)       | Cache servisi (2 KOPYA!)                                                   |
| **Tests (2)**                         |                                                                            |
| `Context7ComplianceTest.php`          | Compliance test                                                            |
| `Context7GuardTest.php`               | Guard test                                                                 |
| **Public JS (5)**                     | Frontend JS dosyaları (live search, location, features)                    |
| **CSS (1)**                           | Live search CSS                                                            |
| **CI Workflows (6)**                  |                                                                            |
| `context7-check.yml`                  | **ÇAKIŞMA** — `context7-full-scan.sh` çalıştırır                           |
| `context7-compliance.yml`             | **ÇAKIŞMA** — artisan integrity scan çalıştırır                            |
| `context7-ci-cd.yml`                  | Context7 CI/CD pipeline                                                    |
| `context7-schema-sync.yml`            | Migration/model değişikliklerinde schema sync                              |
| `context7-drift-detection.yml`        | Drift tespit                                                               |
| `context7-mcp-latest.yml`             | MCP server version check                                                   |
| **Scripts (3)**                       |                                                                            |
| `context7-advisor.php`                | Advisor script                                                             |
| `context7-v2-only-scan.sh`            | v2 scanner wrapper                                                         |
| `archive/batch_context7_refactor.php` | Arşiv — eski refactor script                                               |
| **IDE Extension (5)**                 | VS Code extension files                                                    |
| **MCP (1)**                           | MCP log dosyası                                                            |

### Yalıhan Bekçi Sistemi — 20 dosya

| Dosya                                  | Amaç                                                                |
| -------------------------------------- | ------------------------------------------------------------------- |
| `.bekciignore`                         | Bekçi'nin atlayacağı dosyalar                                       |
| `.bekci-rules.md`                      | Bekçi kuralları dokümanı                                            |
| **Artisan Commands (7)**               |                                                                     |
| `BekciWizardContractCommand.php`       | Wizard sözleşme doğrulama (DB schema, naming, migration guard)      |
| `BekciAuditCommand.php`                | **ÇAKIŞMA** — Telescope + kod taraması (Context7 ihlallerini arar!) |
| `BekciAestheticsCommand.php`           | UI/UX estetik + dark mode denetimi                                  |
| `BekciFeatureEmptyDetectorCommand.php` | Feature boş döndüğünde diagnostik                                   |
| `YalihanBekciHealthCommand.php`        | MCP server + knowledge base health check                            |
| `YalihanBekciWatchCommand.php`         | File watcher — değişimleri izle, AI'a öğret                         |
| `YalihanBekciLearnCommand.php`         | AI'a aksiyon öğret (MCP üzerinden)                                  |
| **Services (1)**                       |                                                                     |
| `BekciNotificationService.php`         | Bildirim servisi                                                    |
| **Controller (1)**                     |                                                                     |
| `YalihanBekciController.php`           | Admin dashboard controller                                          |
| **Views (2)**                          | Bekçi dashboard blade view'ları                                     |
| **Knowledge (2)**                      | Anti-loop protocol, vision logic markdown'ları                      |
| **MCP Server (3)**                     | Bekçi MCP server (cjs + js + shell)                                 |
| **CI (1)**                             |                                                                     |
| `bekci-enforcement.yml`                | **ÇAKIŞMA** — Context7 ihlallerini de tarar + 6 saatte 1 schedule   |
| **Agent Workflow (1)**                 | `.agent/workflows/bekci-fix.md`                                     |
| **Scripts (1)**                        | `bekci-prune.cjs`                                                   |

### Governance & Quality Gate — 10 dosya

| Dosya                              | Amaç                                         |
| ---------------------------------- | -------------------------------------------- |
| `scripts/quality-gate.sh`          | **ANA ORKESTRATÖR** — 7 adımlı kalite kapısı |
| `scripts/core-integrity-check.cjs` | SHA-256 hash ile immutable dosya koruması    |
| `scripts/doc-integrity-guard.sh`   | Dokümantasyon bütünlüğü                      |
| `scripts/compile-authority.cjs`    | Authority JSON derleme                       |
| `config/ai-governance.php`         | AI governance kuralları                      |
| `config/telemetry-events.php`      | Telemetry event allowlist + MVP schema       |
| `dap-governance.yml`               | DAP governance CI workflow                   |
| `scripts/dap-docs-governance.cjs`  | DAP doküman governance                       |
| `.github/copilot-instructions.md`  | Copilot kuralları (governance'ın SSOT'u)     |
| `ide-extensions/INSTALLATION.md`   | IDE extension kurulum                        |

---

## 2) RACI Tablosu (Sorumluluk Sınırları)

> **R** = Responsible (İşi yapan) | **A** = Accountable (Son karar) | **C** = Consulted (Danışılan) | **I** = Informed (Bilgilendirilen)

| Alan                            |             Context7             |                 Bekçi                 |            Governance/QG             |
| ------------------------------- | :------------------------------: | :-----------------------------------: | :----------------------------------: |
| **Forbidden field naming**      |    **R/A** (IntegrityScan v2)    |     R ⚠️ (BekciAudit de tarıyor!)     |     I (quality-gate.sh çağırır)      |
| **DB schema ↔ kod uyumu**      | R (IntegrityScanner 1123 satır)  | **R ⚠️** (WizardContract de tarıyor!) |                  I                   |
| **Telemetry schema validation** | **R/A** (IntegrityScan v2, yeni) |                   —                   |   C (telemetry-events.php config)    |
| **Telemetry field naming**      |    **R/A** (IntegrityScan v2)    |                   —                   |    A (telemetry-events.php SSOT)     |
| **Dark mode compliance**        |      C (PhaseScan phase 3)       |      **R/A** (AestheticsCommand)      |                  I                   |
| **Wizard runtime guard**        |                —                 |       **R/A** (WizardContract)        |       I (quality-gate step 4)        |
| **CI merge gate**               |         R (6 workflow!)          |            R (1 workflow)             | **A** (quality-gate.sh orchestrator) |
| **API contract freeze**         |                —                 |                   C                   |  **R/A** (compile-api-contract.cjs)  |
| **Code smell detection**        |       R (CodeSmellCommand)       |                   —                   |      C (code-quality-checks.sh)      |
| **File integrity (hash)**       |                —                 |                   —                   |  **R/A** (core-integrity-check.cjs)  |
| **AI cost/hallucination**       |                —                 |              R (WFC-015)              |     C (ai-governance.php config)     |

### ⚠️ RACI İhlalleri (Çift R = Çakışma)

1. **Forbidden field naming**: Hem Context7 hem Bekçi "Responsible" — hangisi otorite?
2. **DB schema uyumu**: Hem Context7IntegrityScanner hem BekciWizardContract kontrol ediyor
3. **CI merge gate**: 7 workflow, hepsi aynı trigger'da (`push: main, PR: main`)

---

## 3) Çakışma & Tekrar Analizi

### 🔴 P0 — Prod'da Bug Çıkarır

| #        | Çakışma                               | Detay                                                                                                                                                                                                                                                                                            | Risk                                   |
| -------- | ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------- |
| **P0-1** | **İki scanner aynı signature**        | `Context7IntegrityScan.php` ve `Context7IntegrityScanner.php` ikisi de `context7:integrity-scan` komutu. Laravel hangisini çalıştıracağını belirleyemez — sonuncusu kazanır. **Artisan Registry çakışması!**                                                                                     | **Kritik** — Scanner sonucu güvenilmez |
| **P0-2** | **Forbidden field listesi 4 yerde**   | (1) `Context7IntegrityScan` — base64 patterns (2) `Context7IntegrityScanner` — `$forbiddenCoreFields` array (3) `Context7SmartDetectCommand` — `$deprecatedPatterns` array (4) `BekciAuditCommand` → `AuditMcpServer` — kendi forbidden listesi. **Bir yerde güncelleme diğerlerini etkilemez!** | **Kritik** — False negative riski      |
| **P0-3** | **CI workflow PHP version çakışması** | `bekci-enforcement.yml` → `php-version: '11.0'` (VAR OLMAYAN VERSİYON!), `context7-check.yml` → `8.2`, `context7-compliance.yml` → `8.3`. Production 8.1+. CI sonuçları güvenilmez.                                                                                                              | **Kritik** — CI silent fail            |

### 🟠 P1 — Mental Yük / Maintenance Maliyeti

| #        | Çakışma                                          | Detay                                                                                                                                                                                                  | Maliyet                                    |
| -------- | ------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------ |
| **P1-1** | **3 ayrı CI workflow aynı işi yapıyor**          | `context7-check.yml` (full-scan.sh), `context7-compliance.yml` (artisan scan), `bekci-enforcement.yml` (bekci reality check + context7). Hepsi `push: main, PR: main` trigger. Her PR'da 3× aynı scan. | CI dakika israfı, karmaşa                  |
| **P1-2** | **2 CacheService kopyası**                       | `Context7CacheService.php` ve `Cache/Context7CacheService.php` — namespace farklı ama aynı isim                                                                                                        | Hangisi doğru?                             |
| **P1-3** | **Context7ComplianceChecker hayalet servis**     | 15 satır, hardcoded `return ['compliance' => 98.9, 'violations' => 12]`. Hiçbir yerde kullanılmıyor.                                                                                                   | Yanıltıcı — dashboard yanlış data gösterir |
| **P1-4** | **Quality Gate 7 adım orchestration complexity** | `quality-gate.sh` 263 satır, 7 external script/command çağırıyor. Bir adım fail olursa hangisi olduğu belirsiz. Step numaraları: 0, 0.1, 0.5, 0.6, 0.8, 0.9, 1, 2, 3, 4, 5, 6 — sıra mantıksız.        | Debug zorluğu                              |
| **P1-5** | **Bekçi MCP Server iki kopyası**                 | `bekci-mcp-server.cjs` ve `bekci-mcp-server.js` — hangisi aktif?                                                                                                                                       | Bakım çift iş                              |
| **P1-6** | **context7:phase-scan basit str_contains**       | Phase 1'de `status`, `active` gibi kelimeleri `str_contains` ile arıyor — hiçbir whitelist yok. `IntegrityScan` zaten bunu çok daha akıllı yapıyor.                                                    | Yanlış sonuç, çift iş                      |

### 🟡 P2 — Kozmetik Tekrar

| #        | Çakışma                                                        | Detay                                                          |
| -------- | -------------------------------------------------------------- | -------------------------------------------------------------- |
| **P2-1** | **3 test command (CrmTest, AdvisorTest, TeamTest)**            | Bunlar artisan command olarak yazılmış ama PHPUnit test olmalı |
| **P2-2** | **context7-advisor.php + archive/batch_context7_refactor.php** | Eski scriptler, muhtemelen kullanılmıyor                       |
| **P2-3** | **IDE extension icons (3 boyut)**                              | Fonksiyonel değil ama gereksiz yer kaplıyor                    |
| **P2-4** | **`.context7ignore` vs `.bekciignore`**                        | İki ayrı ignore dosyası aynı amaca hizmet ediyor               |

---

## 4) Tek Komut "Guardian" Önerisi

### Mevcut Durum

```
quality-gate.sh
  ├── Step 0:   compile-api-contract.cjs
  ├── Step 0.1: dap-drift-check.cjs
  ├── Step 0.5: governance:verify (core-integrity-check.cjs)
  ├── Step 0.6: precheck-retention.cjs
  ├── Step 0.8: dap:legacy
  ├── Step 0.9: wizard-cascade-guard.cjs
  ├── Step 1:   php artisan test --filter=FeaturesNonEmptyTest
  ├── Step 2:   php artisan test (full suite)
  ├── Step 3:   php artisan context7:integrity-scan     ← ÇAKIŞAN 2 SCANNER!
  ├── Step 4:   php artisan bekci:wizard-contract
  ├── Step 5:   code-quality-checks.sh
  └── Step 6:   npm run build
```

### Önerilen Yapı: `php artisan guardian:scan`

```php
// app/Console/Commands/GuardianScanCommand.php
class GuardianScanCommand extends Command
{
    protected $signature = 'guardian:scan
        {--quick : Sadece P0 kontroller}
        {--full : Tüm kontroller dahil CI}';

    public function handle(): int
    {
        $exitCode = 0;

        // Phase 1: Field Naming (TEK kaynak: Context7IntegrityScan v2)
        $exitCode |= Artisan::call('context7:integrity-scan');

        // Phase 2: Wizard Contract (TEK kaynak: BekciWizardContract)
        $exitCode |= Artisan::call('bekci:wizard-contract');

        // Phase 3: Dark Mode (TEK kaynak: BekciAesthetics)
        if (!$this->option('quick')) {
            $exitCode |= Artisan::call('bekci:aesthetics');
        }

        // Phase 4: Tests
        $exitCode |= Artisan::call('test', ['--stop-on-failure' => true]);

        return $exitCode;
    }
}
```

### quality-gate.sh Sadeleştirmesi

```bash
# YENİ: 4 adım (eskisi 12 adım)
# Step 1: Guardian Scan (Context7 + Bekçi + Tests)
php artisan guardian:scan --full

# Step 2: Governance Core Integrity (hash check)
npm run governance:verify

# Step 3: Frontend Build
npm run build

# Step 4: API Contract Compilation
node scripts/compile-api-contract.cjs
```

### CI Workflow Sadeleştirmesi

```yaml
# TEK WORKFLOW: .github/workflows/guardian.yml
# Diğer 7 workflow DEPRECATED
name: Guardian Gate
on:
    push: { branches: [main] }
    pull_request: { branches: [main] }
jobs:
    guardian:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4
            - uses: shivammathur/setup-php@v2
              with: { php-version: '8.2' } # Production ile aynı!
            - run: composer install
            - run: npm ci
            - run: php artisan guardian:scan --full
            - run: npm run governance:verify
            - run: npm run build
```

---

## 5) Sadeleştirme Planı

### A) Hemen (Bugün) — Düşük Risk

| #      | Aksiyon                                                                             | Dosyalar | Risk  | Rollback                        |
| ------ | ----------------------------------------------------------------------------------- | -------- | ----- | ------------------------------- |
| **A1** | `Context7IntegrityScanner.php` signature'ını değiştir: `context7:integrity-scan-v1` | 1 dosya  | Düşük | Geri al, ismi eski haline getir |
| **A2** | `Context7ComplianceChecker.php` hayalet servisi sil                                 | 1 dosya  | Sıfır | `git checkout -- dosya`         |
| **A3** | `bekci-enforcement.yml` PHP version'ı `11.0` → `8.2` yap                            | 1 dosya  | Düşük | CI'da patch                     |

### B) Bu Hafta — Orta Risk

| #      | Aksiyon                                                                                             | Dosyalar | Risk  | Rollback                       |
| ------ | --------------------------------------------------------------------------------------------------- | -------- | ----- | ------------------------------ |
| **B1** | `GuardianScanCommand.php` oluştur, quality-gate.sh'ı 4 adıma indir                                  | 2 dosya  | Orta  | Eski quality-gate.sh'a dön     |
| **B2** | `Context7SmartDetectCommand.php` ve `Context7PhaseScanCommand.php` deprecate et                     | 2 dosya  | Düşük | Geri yükle                     |
| **B3** | CI workflow'ları tekleştir: `guardian.yml` oluştur, diğer 3'ü soft-deprecate (sadece cron'a çek)    | 4 dosya  | Orta  | Eski workflow'ları aktifleştir |
| **B4** | Forbidden field SSOT: `config/context7_guard.php`'ye tek array, diğer tüm scanner'lar oradan okusun | 5 dosya  | Orta  | Config geri al                 |
| **B5** | `.context7ignore` + `.bekciignore` birleştir → `.guardianignore`                                    | 3 dosya  | Düşük | İki dosyayı geri getir         |

### C) Sonra (Perf / Kozmetik)

| #      | Aksiyon                                                                                                                            | Dosyalar | Risk                                 |
| ------ | ---------------------------------------------------------------------------------------------------------------------------------- | -------- | ------------------------------------ |
| **C1** | `Context7IntegrityScanner.php` (1123 satır, eski scanner) tamamen kaldır. DB schema check mantığını `bekci:wizard-contract`'a taşı | 2 dosya  | Yüksek — DB doğrulaması kaybolabilir |
| **C2** | `BekciAuditCommand.php` → forbidden field taramasını kaldır, sadece Telescope audit yapsın                                         | 2 dosya  | Orta                                 |
| **C3** | Test artisan commands (CrmTest, AdvisorTest, TeamTest) → PHPUnit'e taşı                                                            | 3 dosya  | Düşük                                |
| **C4** | Bekçi MCP Server kopyasını tekleştir (cjs vs js)                                                                                   | 2 dosya  | Düşük                                |
| **C5** | Context7 CacheService kopyasını tekleştir                                                                                          | 2 dosya  | Orta — reference chain kırılabilir   |

---

## 6) Sonuç

### Sayılarla Karmaşa

| Metrik                   |   Mevcut   |       Hedef (Sadeleşme Sonrası)       | Kazanım |
| ------------------------ | :--------: | :-----------------------------------: | :-----: |
| Governance dosya sayısı  |     93     |                  ~60                  |  -35%   |
| Artisan komut (scanner)  |     5      | 2 (IntegrityScan v2 + WizardContract) |  -60%   |
| CI workflow (governance) |     7      |      2 (guardian + schema-sync)       |  -71%   |
| Forbidden field kaynağı  | 4 ayrı yer |     1 (config/context7_guard.php)     |  -75%   |
| Quality gate adımı       |     12     |                   4                   |  -67%   |
| Hayalet servis           |     1      |                   0                   |  -100%  |

### Kritik Aksiyon (Bugün)

> **P0-1 çözümü zorunlu:** `Context7IntegrityScanner.php` signature çakışması `context7:integrity-scan` komutunu güvenilmez kılıyor. **Hemen signature değiştirilmeli.**

### Mimari Karar

Bu rapor, [ADR protokolü](../../.github/copilot-instructions.md) gereği bir Architectural Decision Record'dur.

- **Context:** 3 governance sistemi organik büyüme ile karmaşıklaştı
- **Decision:** Tek "Guardian" komutu altında birleştir, SSOT forbidden field listesi oluştur
- **Consequences:** CI süresi azalır, bakım maliyeti düşer, false positive riski azalır
- **Alternatives Considered:** Hepsini kaldırıp sıfırdan yazmak (reddedildi — risk çok yüksek)
