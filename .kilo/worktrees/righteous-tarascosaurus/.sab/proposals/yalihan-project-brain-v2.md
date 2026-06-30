# Yalıhan Emlak — Proje Bağlam Dosyası v2 (AI Eğitim Referansı)

> **⚠️ DOCUMENT STATUS: REFERENCE — NOT SSOT**
> Bu dosya mimari bağlam ve kavramsal rehber olarak kullanılır.
> Operasyonel karar için tek başına YETERSİZDİR.
> Stale komutlar veya "tamamlandı" ifadeleri runtime gerçeğini yansıtmayabilir.
> **Authority hiyerarşisi:** Human → real code + schema → `.sab/authority.json` → SAB enforcement → bu dosya
> **Son revizyon:** 7 Nisan 2026 — Stale write-refactor claims updated, event gap closed.

**Proje:** Yalıhan Emlak — Bodrum Emlak Platformu (PropTech)
**Versiyon:** v1.7.0-self-protecting | Maturity L5
**Tarih:** 7 Nisan 2026
**Amaç:** Bu döküman Gemini / ChatGPT gibi harici AI asistanların projeyi tam bağlamıyla anlayabilmesi için hazırlanmıştır.
**Kural:** Bu dosya referans kaynağıdır, SSOT değildir. SSOT her zaman proje dosyalarıdır.

---

## İÇİNDEKİLER

1. Tech Stack
2. Proje Amacı
3. Domain Haritası (15 Domain)
4. Mimari Prensipler & SSOT
5. Context7 — Yasaklı Alan İsimleri
6. Servis Bağımlılık Grafiği
7. Kritik Zincirler (5 Chain)
8. Property Engine
9. AI Sözleşmesi
10. İlan (Listing) Sözleşmesi
11. Wizard Konumlandırma Kararı
12. Governance & Kalite Kuralları
13. Coding Guardrails
14. Performans Bütçesi
15. Telemetri & Gözlemlenebilirlik
16. Frontend Route Kırılganlık Haritası
17. Domain Boundary Violations (16 Violation)
18. Cross-Domain Import İhlalleri (19 İhlal)
19. Fix Priority Plan (18 Fix, 4 Phase)
20. Aktif Migration Planları
21. Tamamlanmış İşler (Tekrar Önerme)
22. God Class Risk Tablosu
23. Template SSOT Kaosu
24. SAB Governance Sistemi
25. MCP Server Ekosistemi
26. V2 Model Bridge Pattern
27. Bilinen Sorunlar & Aktif Borçlar
28. Dizin Yapısı
29. Çalışma Prensipleri
30. Dosya İstatistikleri

---

## 1. TECH STACK

| Katman | Teknoloji |
|--------|-----------|
| Backend | Laravel 10 (PHP 8.1+) |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Build | Vite |
| Database | MySQL |
| Cache/Queue | Redis |
| AI Providers | Ollama (varsayılan), Gemini, OpenAI, Claude, DeepSeek |
| Monitoring | Laravel Telescope (staging), custom telemetry |
| CI/CD | GitHub Actions |
| Governance | SAB (System Architecture Boundary) + Context7 |

---

## 2. PROJE AMACI

Bodrum bölgesine odaklı emlak yönetim platformu. Admin paneli üzerinden ilan CRUD, AI destekli içerik üretimi, CRM, danışman yönetimi, portföy analizi ve karar motoru (governance) içerir. Public frontend villa kiralama ve emlak ilanları sunar. Tüm AI işlemleri Warning Mode'da çalışır — kullanıcı aksiyonunu asla bloke etmez.

---

## 3. DOMAIN HARİTASI (15 Domain)

| # | Domain | Route Prefix | Anahtar Controller | Controller Sayısı | Açıklama |
|---|--------|-------------|-------------------|---|----------|
| D01 | **Listing** | `admin/ilanlar/*` | `IlanCrudController` | 15+ | İlan CRUD, yaşam döngüsü, arama, draft, fotoğraf |
| D02 | **Wizard** | `api/v1/wizard/*` | `WizardContextController` | 5 | İlan oluşturma sihirbazı (**Listing sub-domain**) |
| D03 | **Property Engine** | `admin/property-hub/*` | `PropertyHubController` | 25+ | Kategori, şablon, özellik yönetimi |
| D04 | **AI / Cortex** | `admin/ai/*`, `api/v1/ai/*` | `AISettingsController` | 40+ | AI orkestrasyon, içerik üretimi, telemetri |
| D05 | **Governance** | `admin/governance/*` | `DecisionEngineController` | 7 | Karar motoru, otonom kontrol |
| D06 | **CRM** | `admin/crm/*` | `CRMController` | 13 | Kişi, talep, lead scoring |
| D07 | **Advisor** | `admin/danisman/*` | `OpportunityController` | 16 | Danışman yönetimi, portföy |
| D08 | **Integrations** | `api/v1/webhook/*` | `WhatsAppWebhookController` | — | WhatsApp, n8n, harici entegrasyonlar |
| D09 | **Location** | `api/v1/location/*` | `LocationController` | — | Konum, harita, POI, geocoding |
| D10 | **Analytics** | `admin/analytics/*` | `AnalyticsDashboardController` | — | Raporlama, dashboard |
| D11 | **Settings** | `admin/ayarlar/*` | `AyarlarController` | — | Genel ayarlar |
| D12 | **Public** | `yazliklar/*`, blog | `VillaController` | — | Herkese açık sayfalar |
| D13 | **Finance** | `admin/finans/*` | `Modules\Finans\*` | — | Finansal modül |
| D14 | **Team Mgmt** | `admin/takim-yonetimi/*` | `Modules\TakimYonetimi\*` | — | Takım yönetimi |
| D15 | **Rental** | `yazliklar/*`, `api/v1/yazlik-kiralama` | `YazlikKiralamaController` | — | Yazlık kiralama |

### Toplam Envanter

| Metrik | Sayı |
|--------|------|
| Controller | 319 |
| Route dosyası | 59 (3073+ satır core) |
| Servis | 170+ |
| Model | 130+ |

---

## 4. MİMARİ PRENSİPLER & SSOT

### 4.1 Single Source of Truth (SSOT)

| Konu | SSOT | Dosya/Konum |
|------|------|-------------|
| İlan CRUD (Write Authority) | `IlanCrudService::store()` | `app/Services/Ilan/IlanCrudService.php` |
| Özellik Şeması | `feature_assignments` DB tablosu | Hardcode yasak — veri yoksa boş `[]` döner |
| Feature Resolution | `FeatureTemplateResolver` | `app/Services/Ups/FeatureTemplateResolver.php` |
| AI Provider Config | `config/ai.php` | Provider, fallback, API key, bütçe |
| AI Orkestrasyon | `YalihanCortex` | `app/Services/AI/YalihanCortex.php` |
| Template Kuralları | `GovernedRuleRegistry` | Snapshot-based rule engine |
| Durum Geçişleri | `ListingStateMachine` | `app/Services/Listing/ListingStateMachine.php` |
| Governance SSOT | `authority.json` | `.sab/authority.json` |

### 4.2 Mimari Model

**Model 2 — AI as Shared Intelligence Layer:**
- AI domain kendi servislerini (provider, inference, cost, telemetry) sahiplenir
- Diğer domainler AI'ı "harness point" üzerinden çağırır (YalihanCortex aracılığıyla)
- AI doğrudan başka domain'in modellerine erişmez — servis katmanı üzerinden
- OllamaService doğrudan DI yasak — Cortex üzerinden geçmeli

### 4.3 İzin Verilen Servis Kontratları

| Kontrat | Provider Domain | Consumer Domain(s) | Pattern |
|----------|----------------|-------------------|---------|
| `YalihanCortex` | AI | Listing, Property Engine, CRM | Tüm AI çağrıları buradan geçmeli |
| `FeatureTemplateResolver` | Property Engine | Listing (Wizard), Governance | Feature hiyerarşi SSOT |
| `TemplateResolverInterface` | Property Engine | Wizard | Laravel contract/interface |
| `ListingQualityService` | Listing | Wizard (Orchestrator) | Kalite skorlama |
| `AiTelemetryService` | AI | Tüm AI tüketiciler | Gözlemlenebilirlik |

### 4.4 Yasaklı Doğrudan Import'lar

| Import | Neden Yasak | Mevcut İhlalciler |
|--------|------------|-------------------|
| `OllamaService` (AI dışı controller'da) | Cortex bypass, cost/telemetry izlenmez | PropertyHubController (4 method) |
| `Kisi`/`Talep` (AI controller'da) | CRM modelleri AI domain'de | `Api\AIController` |
| `Ilan` (AI controller'da doğrudan CRUD) | Listing domain sorumluluğu | Read-only OK, write YASAK |

---

## 5. CONTEXT7 — YASAKLI ALAN İSİMLERİ (KRİTİK)

**Context7** sistemi tüm veritabanı ve kod alanlarında Türkçe kanonik isimlendirmeyi zorunlu kılar. Tolerans sıfır.

| ❌ YASAK | ✅ KULLAN | Açıklama |
|----------|----------|----------|
| `status` | `yayin_durumu` | İlan yayın durumu |
| `status` | `talep_durumu` | Talep durumu |
| `status` | `aktiflik_durumu` | Genel aktiflik |
| `active`, `is_active`, `aktif` | `aktiflik_durumu` | Aktiflik |
| `order`, `sort_order` | `display_order` | Sıralama |
| `featured`, `is_featured` | `one_cikan` | Öne çıkan |
| `latitude`, `enlem` | `lat` | Enlem |
| `longitude`, `boylam` | `lng` | Boylam |
| `city`, `sehir` | `il` / `il_adi` | Şehir |
| `featured_image` | `kapak_resmi` | Kapak resmi |
| `musteriler` | `kisiler` | CRM kişiler |
| `http_status_code` | `http_durum_kodu` | HTTP durum kodu |
| `success`, `ok` | `basarili` | Başarı flag |
| `url` | `istek_url` | İstek URL |
| `error` | `hata_mesaji` | Hata mesajı |
| `status_code` | `durum_kodu` | Genel durum kodu |

**Context7 doğrulama:** `php artisan sab:integrity-scan`
**Otomatik düzeltme:** `php artisan sab:integrity-scan --auto-fix`

---

## 6. SERVİS BAĞIMLILIK GRAFİĞİ

### İstatistikler

| Metrik | Sayı |
|--------|------|
| Analiz edilen servis | 60+ |
| Constructor injection | 47 explicit |
| Service locator (app/resolve) | 40+ çağrı |
| Interface binding | 8 |
| Provider | 14 |
| Cross-domain zincir | 9 |
| SSOT servis | 3 |

### Domain Bazlı Servis Sayıları

| Domain | Servis Sayısı | Giriş Noktası | Risk |
|--------|--------------|---------------|------|
| AI | 94+ | `YalihanCortex` (30 dep) | 🔴 God Class |
| Property Engine | 11+ | `EngineOrchestrator` | 🟢 Normal |
| Wizard | 5 | `WizardOrchestrator` (8 dep) | 🟡 Facade pattern |
| Listing | 4 | `IlanCrudService` (3 dep) | 🟢 Normal |
| CRM | 3+ | `KisiScoringService` | 🟢 Normal |
| Governance | 8 | `GovernanceObservabilityService` | 🟢 Normal |
| Intelligence | 23 | `CrossModuleIntelligenceService` | 🟡 Cross-domain |

### Interface → Implementation Bağlamaları

| Interface | Implementation | Provider | Tip |
|-----------|---------------|----------|-----|
| `TemplateResolverInterface` | `TemplateResolver` | AppServiceProvider + TemplateServiceProvider | singleton |
| `RuleRegistryInterface` | `GovernedRuleRegistry` | PropertyHubServiceProvider | bind |
| `CircuitBreakerInterface` | `CircuitBreaker` | AppServiceProvider | singleton |
| `AIService` | `AIService(PromptGovernanceService)` | AppServiceProvider | singleton |

### Namespace Belirsizliği (Aktif Risk)

| Sınıf | Namespace 1 | Namespace 2 | Risk |
|-------|-------------|-------------|------|
| `FeatureTemplateResolver` | `App\Services\Ups` (SSOT, 9+ kullanıcı) | `App\Services\Wizard` (scoped, 1 kullanıcı) | Import typo → farklı davranış |

### Service Locator Anti-Pattern Noktaları

| Dosya | Çağrı | Risk |
|------|------|------|
| `IlanFeatureService` L354 | `app(FeatureTemplateResolver::class)` | Gizli bağımlılık |
| `IlanFeatureController` L199 | `app(YalihanCortex::class)` | Geç bağlama |

---

## 7. KRİTİK ZİNCİRLER (5 Chain)

### Chain 1: Wizard Context Resolution (EN KRİTİK)
```
WizardController → WizardOrchestrator
  → FeatureTemplateResolver(Ups) [SSOT]
  → SmartFieldGeneration → AiWallet + AiPricing
  → TemplateService → FeatureTemplateResolver + UpsCacheService
  → AiTelemetry, VisionAnalysis, AiLearning, AiExperiment
  → ListingQuality
```
**Kırılırsa:** Wizard formu boş gelir, özellikler yüklenmez, AI önerileri çalışmaz.

### Chain 2: Data Write Path (WRITE AUTHORITY)
```
IlanCrudController → IlanCrudService
  → IlanReferansService + NumberToTextConverter + ListingStateMachine
  → IlanFeatureService → FeatureTemplateResolver
  → UpsCacheService
```
**Kırılırsa:** İlan kaydedilemez, referans numarası oluşmaz, state geçişi bozulur.

### Chain 3: AI Inference
```
Controller → YalihanCortex (30 deps — GOD CLASS riski)
  → OllamaService → AiTelemetryService
  → AiCostGuardService → AiAlertService
```
**Kırılırsa:** AI önerileri çalışmaz ama Warning Mode sayesinde kullanıcı bloke OLMAZ.

### Chain 4: PE V3 Engine
```
EngineOrchestrator
  → TemplateResolutionEngine → GovernedRuleRegistry + ConditionEvaluator
  → CircuitBreaker (error_threshold=0.05, window=300s)
  → RegistryBypassDetector
```
**Kırılırsa:** Şablon çözümleme durur, CircuitBreaker devreye girer.

### Chain 5: Intelligence Aggregation
```
ActionCenterService
  → ChurnRiskService(AI) + DemandMatchingEngine
  → CrossModuleIntelligenceService → YalihanCortex + ActionScore
  → ActionScoreService → KisiChurnService(AI)
```
**Kırılırsa:** Dashboard aksiyonları ve tahminler durur.

---

## 8. PROPERTY ENGINE (ÖZELLİK MOTORU)

### Scope Priority (Atama Önceliklendirmesi)

Aynı `feature_slug` birden fazla kapsamda atanmışsa, spesifik olan kazanır:

| Scope | Puan | Açıklama |
|-------|------|----------|
| `ai_design` | 500 | Eklemeli (additive) |
| `listing_type` | 400 | En yüksek |
| `sub_category` | 300 | Alt kategori |
| `main_category` | 200 | Ana kategori |
| `global` | 100 | En düşük |

### Kurallar
- `feature_assignments` tablosu tek şema kaynağıdır
- Blade/JS'de hardcoded özellik render yasaktır
- Kategori için assignment yoksa boş `[]` döner (bug değil, data eksikliği)
- `FeatureTemplateResolver` dual namespace: `Ups\` (SSOT, 9+ kullanıcı) vs `Wizard\` (legacy, 1 kullanıcı)
- Dependency rules: `visible_if`, `required_if`, `enabled_if` — FeatureAssignment JSON sütunları

### Sahip Olduğu

| Yüzey | Kanıt |
|-------|-------|
| Feature dictionary (Feature model) | 13K-50K feature per env |
| Feature category/grouping | `FeatureCategory` model |
| Feature assignment + inheritance | `FeatureAssignment` — polymorphic |
| Template CRUD + versioning | `TemplateController`, `UpsVersionController` |
| Publish-type schema | `YayinTipiSablonu` model |
| Category-publish-type junction | `AltKategoriYayinTipi` model |

---

## 9. AI SÖZLEŞMESİ

### Providers & Fallback

| Provider | Model | Rol |
|----------|-------|-----|
| Ollama | `llama3.1:latest` | Varsayılan (lokal) |
| Gemini | `gemini-2.5-flash` | Aktif |
| OpenAI | `gpt-3.5-turbo` | Fallback |
| Claude | `claude-3-sonnet` | Aktif |
| DeepSeek | — | Aktif |

**Fallback Zincirleri:**
- `ollama → deepseek → openai → gemini`
- `openai → deepseek → ollama → gemini`
- `gemini → openai → deepseek → ollama`

### AI UI Kuralı (Warning Mode — KRİTİK)
- AI ve Cortex değerlendirmeleri ilan kaydetmeyi **HİÇBİR ZAMAN** bloke etmez
- Hard ban kesinlikle yasaktır
- AI sadece yardımcı araçtır, karar verici değildir
- Cortex skorları düşük olsa bile ilan girişi açık kalır
- Agent bu kuralı **ASLA** değiştiremez

### Maliyet Bütçesi

| Ortam | Günlük Bütçe | Token/İstek | Aşılırsa |
|-------|-------------|-------------|----------|
| Production | $50/gün | 4K token | Fallback model'e geç |
| Staging | $10/gün | 2K token | Mock response |
| Development | $2/gün | 1K token | Cached response |

### AI Domain Sahipliği

**Sahip olduğu (140+ servis):**

| Katman | Servisler |
|--------|----------|
| Provider/Infrastructure | AIService, OllamaService, OpenAIService, FallbackAIService, ProviderSelectorService |
| Cost/Budget | AiCostGuardService, AiBudgetGuard, AiWalletService, AiPricingService |
| Telemetry | AiTelemetryService, TelemetryAggregator, AiAlertService |
| Prompt/Inference | PromptGovernanceService, PromptLibrary, AIOrchestrator, NLPProcessor |
| Content Generation | DataDrivenAIContentService, ImageBasedAIDescriptionService, SmartFieldGenerationService |
| Vision | VisionAnalysisService, LocalVisionService, VisionTaggingService |
| Monitoring | CortexMonitoringService, CortexLearningService, AiExperimentService |
| Copilot | BrokerCopilotService, WizardCopilotService, CRMCopilotService |

**Sahip olMAması gereken:**
- İlan CRUD / yaşam döngüsü (→ Listing)
- Feature tanımları / template resolution (→ Property Engine)
- CRM Kişi/Talep CRUD (→ CRM)
- Kategori/yayın-tipi şeması (→ Property Engine)
- Calendar/reservation (→ Listing/Rental)

### AI Harness Noktaları (Domain'ler Arası Köprüler)

| ID | Controller | Host Domain | AI Servisi | Thin Wrapper? |
|----|-----------|------------|-----------|---------------|
| H01 | `IlanAITitleDescriptionController` | Listing | YalihanCortex | ✅ |
| H02 | `IlanAIQualityController` | Listing | YalihanCortex | ✅ |
| H03 | `IlanPublishGateController` | Listing | YalihanCortex | ✅ |
| H04 | `IlanCrudController` (price) | Listing | YalihanCortex | ⚠️ inline çağrı |
| H05 | `WizardCopilotActionController` | Wizard | WizardCopilotService | ✅ |
| H06 | `FieldSuggestionController` | Property Engine | AiFieldSuggestionEngine | ✅ |
| H07 | `TemplateAiDesignController` | Property Engine | AI pipeline | ✅ |
| H08 | `DecisionEngineController` | Governance | Intelligence services | ⚠️ God Class |
| H09 | `DanismanAIController` | Advisor | DanismanAIService | ❌ karışık sorumluluk |

---

## 10. İLAN (LİSTİNG) SÖZLEŞMESİ

### Form Süreci
- Tüm ilan formu `wizardComponent` (Alpine.js) state machine üzerinden çalışır
- Backend sözleşmesi: `StoreIlanRequest` → `admin.ilanlar.store`
- Frontend'den gelen `junction_id`, `prepareForValidation()` içinde `yayin_tipi_id`'ye çevrilir
- Telemetri rotası: `api.wizard.telemetry.feature-action`

### Kişi Kuralları
- **İlan Sahibi:** ZORUNLU
- **Danışman:** ZORUNLU (sistem kullanıcısı)
- **İlgili Kişi:** Opsiyonel

### Yaşam Döngüsü
- Yeni ilanlar `yayin_durumu: 0` (draft) ile başlar
- Geçişler `ListingStateMachine` üzerinden kontrol edilir
- Write Authority: `IlanCrudService::store()` tek write kaynağıdır
- Hiçbir agent bu write path'i bypass edemez

### Listing Domain Controller'ları (15+)
`IlanCrudController`, `IlanSearchController`, `IlanDraftController`, `IlanPublishController`, `IlanPublishGateController`, `IlanBulkController`, `IlanValidationController`, `IlanSegmentController`, `IlanPhotoController`, `IlanCalendarController`, `IlanRaporController`, `IlanAnalizController`, `IlanFeatureController`, `Api\V2\IlanController`, `Api\V2\DraftController`

---

## 11. WIZARD KONUMLANDIRMA KARARI

**Karar:** Wizard bir **Listing sub-domain / entry flow'dur**, bağımsız domain DEĞİLDİR.

**Kanıtlar:**
1. Wizard İlan kaydı OLUŞTURMAZ — `IlanCrudService::store()` yapar (Listing domain)
2. Wizard read-only context resolver'dır — `WizardContextService::resolve()` template + features döner
3. Wizard'ın nihai hedefi `POST /admin/ilanlar` (Listing endpoint)
4. `WizardOrchestrator` Property Engine + AI servislerini agrege eden bir facade'dir

**Sonuç:** Wizard fix = Listing etkisi. Wizard refactor = kontrat riski. Her ikisi de HIGH RISK.

### Wizard Entry Flow
```
Admin UI → IlanCrudController.createWizard
  → Frontend (Alpine.js wizardComponent)
  → WizardContextController (template/feature çözümle)
  → IlanWizardController.asama1-5 (adım validasyonu)
  → POST /admin/ilanlar (IlanCrudService::store → kaydet)
```

---

## 12. GOVERNANCE & KALİTE KURALLARI

### Risk Seviyeleri

| Seviye | Aksiyon | Örnekler |
|--------|---------|----------|
| LOW | Auto-Apply | Method refactor, rename, linter fix |
| MEDIUM | Ask First | Parametre ekleme, query değişimi, service signature |
| HIGH | Plan Only — Kod Yazma | DB schema, route/API değişimi, büyük refactor, write authority |

### Quality Gate Zinciri (Sıralı — Bypass YASAK)
1. `php artisan test` — Unit & Feature testler
2. `php artisan sab:integrity-scan` — Context7 uyumluluk
3. `php artisan bekci:wizard-contract` — Wizard kontrat doğrulama
4. `./scripts/quality-gate.sh` — Tüm kalite kapısı

### Deployment Kuralları
- `main` branch'e direkt hotfix **YASAK**
- Force push **YASAK**
- Deploy öncesi quality gate **ZORUNLU**
- Rollback planı dökümanlanmalı
- Deployment penceresi: Pazartesi-Perşembe 10:00-16:00

### ADR Protokolü
- Her yapısal değişiklik için Architectural Decision Record **ZORUNLU**
- Format: Context → Decision → Consequences → Alternatives Considered
- Konum: `docs/adr/YYYY-MM-DD-decision-title.md`
- PR'da ADR referansı olmadan merge **YASAK**

---

## 13. CODING GUARDRAILS

1. **Hardcoded URL yasak:** Sadece `route('admin.ilanlar.store')` gibi route helper'lar
2. **Duplicate route yasak:** Tek yetkili endpoint
3. **Deprecated controller referansı yasak**
4. **Yeni SSOT oluşturma yasak:** Mevcut SSOT'lere sadık kal
5. **DOM yapısı korunmalı:** Tailwind/grid düzeni bozulmamalı
6. **Backslash facade yasak:** `\DB::` → `use Illuminate\Support\Facades\DB;`
7. **Ghost Model yasak:** Her model `$fillable` veya `$guarded` tanımlamalı
8. **Ghost Method yasak:** TODO placeholder yasak
9. **N+1 sıfır tolerans:** Her zaman `Model::with(['rel1', 'rel2'])->get()`
10. **Dark Mode zorunlu:** Her element `dark:*` variant
11. **Pure Tailwind:** Custom CSS yasak
12. **Silent catch yasak:** Exception yakalanınca log + re-throw zorunlu
13. **Thin Controller zorunlu:** Business logic controller'da yasak, Service Layer kullan
14. **Direct DB write yasak:** Service katmanı üzerinden
15. **CQRS projection bypass yasak**

---

## 14. PERFORMANS BÜTÇESİ

### API Response Times (p95)

| Endpoint | Hedef | Maks | Aşılırsa |
|----------|------|------|----------|
| Wizard context API | < 400ms | 600ms | Investigate + optimize |
| AI başlık üretimi | < 3s | 5s | Review prompt size |
| Fotoğraf upload (tek) | < 2s | 3s | Check storage driver |
| Arama autocomplete | < 100ms | 200ms | Add caching layer |
| Dashboard yükleme | < 1s | 1.5s | Enable query caching |

### Sistem Limitleri

| Kural | Limit |
|-------|-------|
| Sayfa başı max query | 25 |
| Tek query max süre | 100ms (p95) |
| N+1 query | 0 (sıfır tolerans) |
| UI thread blokaj | < 50ms |
| Wizard step geçişi | < 300ms |
| AI button feedback | < 16ms (1 frame) |

### CI Fail Koşulları

| Metrik | Eşik | Aksiyon |
|--------|------|---------|
| Wizard context API p95 | > 400ms | ❌ PR Fail |
| AI generation p95 | > 3s | ❌ PR Fail |
| Dashboard load p95 | > 1.5s | ❌ PR Fail |
| Telemetry error rate | > 2% | ❌ PR Fail |
| N+1 query count | > 0 | ❌ PR Fail |

---

## 15. TELEMETRİ & GÖZLEMLENEBİLİRLİK

### Zorunlu Telemetri
- Her async işlem latency ölçmeli (`tStart()` / `tEnd()`)
- Her API isteği: `duration_ms`, `http_durum_kodu`, `basarili`, `istek_url` kaydetmeli
- Frontend hatalar `window.addEventListener('error')` ile yakalanmalı
- Alpine.js hataları `Alpine.onError()` ile yakalanmalı
- Telemetri alanları **Context7 uyumlu** olmalı (status yasak → durum_kodu kullan)

### Event Allowlist
- Telemetri olayları `config/telemetry-events.php` allowlist'te tanımlı olmalı
- Rastgele event adı **YASAK**

### Log Dosyaları
| Log | Path | Retention |
|-----|------|-----------|
| Telemetri | `storage/logs/telemetry-YYYY-MM-DD.log` | 30 gün |
| Backend | `storage/logs/laravel.log` | — |
| Security | `storage/logs/security.log` | 90 gün |
| Bekçi | `storage/logs/bekci.log` | 90 gün |
| Performance | `storage/logs/performance.log` | — |

---

## 16. FRONTEND ROUTE KIRILGANLIK HARİTASI

### Genel Durum

| Metrik | Sayı |
|--------|------|
| route() helper kullanımı (Blade) | 80+ (✅ güvenli) |
| Hardcoded /admin/ paths (Blade) | 25+ (🔴 riskli) |
| Hardcoded /api/ paths (Blade) | 40+ (🔴 riskli) |
| Hardcoded URL (JS) | 174+ (🔴 riskli) |
| Toplam hardcoded endpoint | 240+ |
| `window.APIConfig` kullanan dosya | 3 (🟡 %15 adoption) |

### Route Değişikliği Etki Tablosu

| Route Prefix Değişikliği | Etkilenen Dosya | Risk |
|--------------------------|----------------|------|
| `/api/v1/wizard/*` | 8+ JS + 5+ Blade | 🔴 SİSTEM KIRILIR |
| `/admin/ilanlar/*` | 10+ JS + 10+ Blade | 🔴 SİSTEM KIRILIR |
| `/admin/copilot/*` | 1 JS (4 URL) | 🔴 Copilot durur |
| `/api/v1/location/*` | 7+ JS + 2+ Blade | 🔴 Konum cascade kırılır |
| `/api/advisor/*` | 5+ Blade | 🟠 Danışman paneli kırılır |
| `/admin/analytics/*` | 3+ Blade | 🟡 Dashboard kırılır |
| Named route değişiklikleri | 0 | ✅ route() çözer |

### APIConfig Durumu
- `resources/js/admin/route-config.js` → merkezi tanımlar (az kullanılıyor)
- Copilot, Wizard cascade, Location, Telemetry, Advisor → hepsi hardcoded

**Sonuç:** Route prefix değişikliğinden önce `grep -rn "prefix" resources/ routes/` ZORUNLU.

---

## 17. DOMAIN BOUNDARY VIOLATIONS (16 Violation)

### Özet

| Severity | Sayı |
|----------|------|
| 🔴 CRITICAL | 4 |
| 🟠 HIGH | 5 |
| 🟡 MEDIUM | 4 |
| 🟢 LOW | 3 |
| **TOPLAM** | **16** |

### Tüm Violation'lar

| ID | Dosya | Tip | Severity | Problem |
|----|-------|-----|----------|---------|
| V01 | `Api\AIController` | Cross-Domain God Class | 🔴 | 30+ method, Kisi/Talep import, CRM CRUD sızıntısı |
| V02 | `routes/api/v1/admin.php` | Wrong Prefix | 🔴 | 14+ Admin controller Api prefix altında |
| V03 | `routes/admin.php:89-92` | Wrong Prefix | 🔴 | Api\V1\WizardCopilotActionController admin prefix'te (ACCEPTED) |
| V04 | `Api\Admin\EventsController` | Cross-Domain | 🔴 | Api namespace'te Admin base class extend |
| V05 | `PropertyHubController` | God Class | 🟠 | 34 method, 4 AI method OllamaService bypass |
| V06 | `DecisionEngineController` | God Class | 🟠 | 27 method, 3 concern karışık |
| V07 | `IlanAITitleDescriptionController` | Cross-Domain | 🟠 | Ilan* adıyla AI logic |
| V08 | `DanismanAIController` | Mixed Responsibility | 🟠 | Advisor + AI + CRM triple mix |
| V09 | Template SSOT Chaos | Mixed | 🟠 | 11 controller, tek truth yok |
| V10 | `IlanAIQualityController` | Wrong Prefix | 🟡 | AI iş, Ilan* adı |
| V11 | `IlanQualityDashboardController` | Wrong Prefix | 🟡 | Analytics iş, Ilan* adı |
| V12 | `PortfolioDoctorController` | Wrong Prefix | 🟡 | AI controller, advisor route |
| V13 | `routes/admin.php:1548` | Wrong Prefix | 🟡 | Api\SearchController admin'de (commented) |
| V14 | 6 Listing Controller | Cross-Domain | 🟢 | YalihanCortex import (kabul edilebilir harness) |
| V15 | `CRMController` | God Class | 🟢 | 18 method, domain doğru ama granülarite düşük |
| V16 | `routes/api/v1/admin.php:7` | Dead Import | 🟢 | Deprecated IlanController import |

---

## 18. CROSS-DOMAIN IMPORT İHLALLERİ (19 İhlal)

### Controller Seviyesi

| ID | Controller | Import Edilen Modeller | Severity |
|----|-----------|----------------------|----------|
| X01 | `Api\AIController` | Kisi, Talep (CRM CRUD) | 🔴 CRITICAL |
| X02 | `Api\AIController` | Ilan (Listing model) | 🔴 CRITICAL |
| X06-X09 | `PropertyHubController` (4 AI method) | OllamaService doğrudan | 🟠 HIGH |
| X10 | `Admin\AI\IlanAIController` | IlanKategori, Il, Ilce, Mahalle, YayinTipiSablonu | 🟡 MEDIUM |
| X11-X14 | 4 AI controller | Ilan model (read-only) | 🟡 MEDIUM |
| X15 | `DanismanAIController` | D04+D07 karışık sorumluluk | 🟠 HIGH |

### Servis Seviyesi

| Pattern | Sayı | Değerlendirme |
|---------|------|-------------|
| AI servisleri `Ilan` import | 15+ | Read-only OK, write YASAK |
| AI servisleri `Kisi`/`Talep` import | 3 | CRUD yapıyorsa VIOLATION |
| AI servisleri hem Ilan + Kisi | 2 | `AIContractService`, `ContextCollector` — yüksek coupling |

### Advisor Servisleri AI Namespace'te (YANLIŞ YER)

| Servis | Olması Gereken |
|--------|---------------|
| `AdvisorAnalyticsService` | `Services/Advisor/` |
| `AdvisorCommandCenterService` | `Services/Advisor/` |
| `DanismanAIService` | `Services/Advisor/` |
| `ConversationalAdvisorService` | `Services/Advisor/` |
| `KisiChurnService` | `Services/CRM/` |
| `LeadScoreCalculator` | `Services/CRM/` |

---

## 19. FIX PRIORITY PLAN (18 Fix, 4 Phase)

### Dependency Graph
```
FIX-01 ─── standalone (SAFE)
FIX-02 ─── standalone (SAFE)
FIX-03 ─── standalone (SAFE)
FIX-04 ─── standalone (ACCEPTED)
FIX-05 ─── standalone (TRACKED)

FIX-06 ─── unlocks FIX-16
FIX-07 ─── unlocks FIX-11
FIX-08 ─── standalone
FIX-09 ─── standalone
FIX-10 ─── standalone

FIX-11 ─── requires FIX-07, unlocks FIX-17
FIX-12 ─── standalone
FIX-13 ─── standalone
FIX-14 ─── standalone
FIX-15 ─── standalone

FIX-16 ─── requires FIX-06
FIX-17 ─── requires FIX-11, requires ADR
FIX-18 ─── standalone but multi-sprint
```

### Phase Özet

| Phase | Kapsam | Risk | Fix Sayısı | Durum |
|-------|--------|------|-----------|-------|
| **P1 — Safe** | Dead code, import temizliği, documentation | None-Low | FIX-01 ~ FIX-05 | ⏳ Bekliyor |
| **P2 — Low Risk** | Service extraction, rename | Low | FIX-06 ~ FIX-10 | ⏳ Bekliyor |
| **P3 — Medium** | Controller split, route update | Medium | FIX-11 ~ FIX-15 | ⏳ Bekliyor |
| **P4 — High** | Architectural restructure | High | FIX-16 ~ FIX-18 | ⏳ Bekliyor |

### Fix Detay Tablosu

| # | Fix | Phase | Risk | Açıklama |
|---|-----|-------|------|----------|
| 1 | FIX-01 | P1 | None | Deprecated IlanController import sil |
| 2 | FIX-02 | P1 | None | Commented-out dead routes sil |
| 3 | FIX-03 | P1 | Low | EventsController inheritance düzelt |
| 4 | FIX-04 | P1 | None | V03 → ACCEPTED VIOLATION olarak reclassify |
| 5 | FIX-05 | P1 | None | V02 → TRACKED DEBT comment ekle |
| 6 | FIX-06 | P2 | Low | AIController CRM methods → AICrmGatewayService |
| 7 | FIX-07 | P2 | Low | PropertyHubController AI methods → PropertyAIService |
| 8 | FIX-08 | P2 | Low | IlanAITitleDescriptionController verify (MITIGATED?) |
| 9 | FIX-09 | P2 | Low | IlanAIQualityController → AIQualityController rename |
| 10 | FIX-10 | P2 | Low | IlanQualityDashboardController → AIQualityDashboardController |
| 11 | FIX-11 | P3 | Medium | PropertyHubController → 4 controller split |
| 12 | FIX-12 | P3 | Medium | DecisionEngineController → 4 controller split |
| 13 | FIX-13 | P3 | Medium | DanismanAIController → Config + AI split |
| 14 | FIX-14 | P3 | Medium | PortfolioDoctorController route AI prefix'e taşı |
| 15 | FIX-15 | P3 | Medium | CRMController internal split |
| 16 | FIX-16 | P4 | High | AIController full domain split (30+ → 4) |
| 17 | FIX-17 | P4 | High | Template SSOT consolidation (11 → hierarchy) |
| 18 | FIX-18 | P4 | High | Api/Admin namespace migration (14 controller) |

---

## 20. AKTİF MİGRATION PLANLARI

### Plan 1: Domain Separation Migration (22 item, 4 phase)
**Dosya:** `.ai/reports/domain-analysis/domain-separation-migration-plan.md`
**Phase 1 (Safe):** DS-01 ~ DS-05 — naming, documentation, alias → ✅ TAMAMLANDI
**Phase 2 (Low):** DS-06 ~ DS-11 — harness extraction, service boundaries → ⏳ Bekliyor
**Phase 3 (Medium):** DS-12 ~ DS-16 — controller/route split → ⏳ Bekliyor
**Phase 4 (High):** DS-17 ~ DS-22 — full decomposition → ⏳ Bekliyor

### Plan 2: AI Domain Migration (21 item)
**Dosya:** `.ai/reports/domain-analysis/ai-migration-plan.md`
**Hedef:** AI servislerini izole et, cross-domain import'ları temizle

### Plan 3: Fix Priority Plan (18 fix)
**Dosya:** `.ai/reports/domain-analysis/fix-priority-plan.md`
**Hedef:** 16 domain boundary violation'ı çöz

---

## 21. TAMAMLANMIŞ İŞLER (TEKRAR ÖNERME!)

Bu işler **tamamlandı veya tarihsel milestone'dur**. Agent'lar bunları tekrar önerirse yanlış — zaman kaybı.

> **⚠️ DİKKAT:** "Tamamlandı" etiketi tarihsel milestone anlamındadır.
> Runtime truth için `bash scripts/ci-guard-sab-prompt.sh` RULE 4 çıktısına bakın.
> 7 Nisan 2026 itibariyle 3 legacy `Ilan::create()` bypass hâlâ mevcuttur.

| İş | Dosya | Tarih | Detay | Runtime Durumu |
|----|-------|-------|-------|----------------|
| ✅ Write Authority Refactor (Phase 3) | 12 dosya, 9 write path | Mart 2026 | Write authority IlanCrudService::store() olarak belirlendi | ⚠️ HISTORICAL MILESTONE — 3 legacy bypass kaldı (BulkListingController, YazlikKiralamaService, PortfolioImport) |
| ✅ V2→V1 Model Bridge | V2 Actions | Mart 2026 | `Ilan::findOrFail($v2Ilan->id)` pattern | ✅ CURRENT |
| ✅ Legacy IlanService SEALED | `app/Services/IlanService.php` | Nisan 2026 | Write methods RuntimeException (create/update/delete → never) | ✅ CURRENT — read methods hâlâ aktif |
| ✅ Emlak IlanService DELETED | `app/Modules/Emlak/Services/IlanService.php` | Nisan 2026 | 0 consumer, tamamen silindi | ✅ CURRENT |
| ✅ IlanCrudService Event Gap Closed | `app/Services/Ilan/IlanCrudService.php` | Nisan 2026 | IlanCreated/Updated/Deleted events AFTER transaction commit | ✅ CURRENT |
| ✅ PriceAdvisor SAB-EXEMPT | `app/Services/AI/PriceAdvisor/PriceAdvisorService.php` | Nisan 2026 | Ghost model (new Ilan), no persistence | ✅ CURRENT |
| ✅ IlanBulkService tracking | `app/Services/Ilan/IlanBulkService.php` | Mart 2026 | Phase3-WA comment eklendi | ✅ CURRENT |
| ✅ DS-01: Wizard FeatureTemplateResolver truth | `.ai/context/` | Nisan 2026 | Ups\ = SSOT documented | ✅ CURRENT |
| ✅ DS-04: 5 legacy Ups* controller quarantine | 5 controller | Nisan 2026 | @deprecated + QUARANTINE eklendi | ✅ CURRENT |
| ✅ DS-05: Target architecture documented | `.ai/README.md` | Nisan 2026 | Model 2 referansı | ✅ CURRENT |
| ✅ SV-07: OpenAIService fix | `OpenAIService.php` | Nisan 2026 | AiBudgetGuard → constructor injection | ✅ CURRENT |
| ✅ FE-04: Telemetry APIConfig | 3 endpoint | Nisan 2026 | window.APIConfig normalized | ✅ CURRENT |
| ✅ PropertyHub AI tracking | PropertyHubController | Nisan 2026 | 4 AI method'a tracking comment | ✅ CURRENT |
| ✅ Drive sync automation | launchd + rclone | Nisan 2026 | 30 dakikada bir, 5 klasör | ✅ CURRENT |

---

## 22. GOD CLASS RİSK TABLOSU

| Controller/Service | Method Sayısı | Constructor Deps | Risk | Aksiyon |
|---|---|---|---|---|
| `YalihanCortex` | — | 30 | 🔴 CRITICAL | Decomposition şart |
| `Api\AIController` | 30+ | — | 🔴 CRITICAL | FIX-16 (P4) |
| `PropertyHubController` | 34 | — | 🟠 HIGH | FIX-11 (P3) |
| `DecisionEngineController` | 27 | — | 🟠 HIGH | FIX-12 (P3) |
| `CRMController` | 18 | — | 🟢 LOW | FIX-15 (P3) |
| `IlanAITitleDescriptionController` | 15 | — | 🟡 MEDIUM | FIX-08 (check) |
| `DanismanAIController` | 15+ | — | 🟠 HIGH | FIX-13 (P3) |
| `WizardOrchestrator` | — | 8 | 🟡 MEDIUM | Facade pattern (intentional) |
| `CopilotOrchestrator` | — | 9 (4 optional) | 🟡 MEDIUM | Nullable deps |

---

## 23. TEMPLATE SSOT KAOSU

11 controller aynı "template" kavramını yönetiyor:

| # | Controller | Namespace | Durum |
|---|-----------|-----------|-------|
| 1 | `TemplateController` | Admin | Aktif |
| 2 | `TemplateAiDesignController` | Admin | Aktif |
| 3 | `TemplateAiPipelineController` | Admin | Aktif |
| 4 | `TemplateSyncController` | Admin | Aktif |
| 5 | `TemplateVersionController` | Admin\UPS | Aktif |
| 6 | `UpsTemplateManagerController` | Admin | ⚠️ DEPRECATED |
| 7 | `GeminiTemplateController` | Api | Aktif |
| 8 | `TemplateController` | Api | Aktif |
| 9 | `TemplateController` | Api\V1 | Aktif |
| 10 | `TemplateFieldVisibilityController` | Api\V1\Admin | Aktif |
| 11 | `PropertyHubController` template methods | Admin | Aktif (inline) |

**Problem:** Kanonik TemplateService SSOT yok. Her controller kendi logic'ini implemente ediyor.
**Çözüm:** FIX-17 (P4) — ADR + consolidation gerekli.

---

## 24. SAB GOVERNANCE SİSTEMİ

### Yapı
```
.sab/
├── authority.json          ← Governance SSOT
├── latest/                 ← Son authority snapshot
├── history/                ← audit.log, decisions.log, ci-report
├── proposals/              ← Onay bekleyen değişiklikler
├── snapshots/              ← Her değişiklik öncesi yedek
└── sprint-reports/         ← Sprint raporları
```

### Mekanizma
- `authority.json` → Context7 compliance, IDE entegrasyon, forbidden fields tanımı
- Proposal sistemi: Drive'dan proposal çekip uygulama (Drive → Lokal)
- Snapshot: Her değişiklik öncesi otomatik yedek
- CI guard: `.github/workflows/sab-guard.yml`
- Google Drive sync: 30 dakikada bir (launchd)

### Drive Senkronizasyonu
| Yön | Klasör | Flag |
|-----|--------|------|
| Lokal → Drive | `snapshots/` | `--ignore-existing` |
| Lokal → Drive | `latest/` | `--checksum` |
| Lokal → Drive | `history/` | `--checksum` |
| Lokal → Drive | `sprint-reports/` | `--checksum` |
| Drive → Lokal | `proposals/` | `--ignore-existing` |

---

## 25. MCP SERVER EKOSİSTEMİ

| Server | Port | Amaç | Araçlar |
|--------|------|------|---------|
| `yalihan-bekci-mcp` | 4001 | AI learning & teaching | learn_from_action, suggest_improvement, analyze_pattern |
| `context7-validator-mcp` | 4002 | Context7 compliance | validate_file, autofix_violations, check_compliance |
| `upstash-context7-mcp` | auto | Remote config | — |

---

## 26. V2 MODEL BRIDGE PATTERN

**Kritik Bilgi:** `App\Models\Ilan` (V1) ve `App\Models\V2\Ilan` **BAĞIMSIZ** modellerdir. V2, V1'i extend ETMEZ. İkisi de `BaseModel`'i extend eder, aynı tabloyu kullanır.

### Bridge Pattern
```php
// V2 Action'da V1 servise bridge
$v1Ilan = \App\Models\Ilan::findOrFail($v2Ilan->id);
app(IlanCrudService::class)->store($v1Ilan, $data);
```

**Neden Önemli:** V2 Action'lar refactor sırasında bu bridge'i korumak zorundadır. V2 modeli doğrudan IlanCrudService'e verilemez — tip uyumsuzluğu.

---

## 27. BİLİNEN SORUNLAR & AKTİF BORÇLAR

### 🔴 Kritik
- **YalihanCortex God Class:** 30 constructor dependency — decomposition şart
- **PropertyHubController God Class:** 34 method, 4 AI method Cortex bypass
- **AIController God Class:** 30+ method, CRM model import ihlali (Kisi, Talep CRUD)
- **240+ Hardcoded Frontend URL:** route() kullanılmıyor, route prefix değişikliği sistemi kırar
- **Copilot Actions zero config:** 4 hardcoded URL, fallback yok
- **Wizard Context hardcoded:** `/api/v1/wizard/context` — 8+ dosya bağımlılığı

### 🟡 Dikkat
- FeatureTemplateResolver dual namespace (Ups vs Wizard) — truth documented
- Service locator anti-pattern (IlanFeatureService L354 tracking eklendi)
- TemplateResolverInterface dual registration (AppServiceProvider + TemplateServiceProvider)
- DanismanAIController karışık sorumluluk (Config + Chat + Analytics)
- admin.php 1710 satır monolithic route
- Arsa için feature_assignments boş (data eksikliği, bug değil)
- Route duplikasyonu: `ai-advanced.php` ↔ `admin/ai.php` çakışma
- Ollama HTTP plain text: `require_tls` default false
- 4 Advisor servisi yanlış namespace'te (AI/ altında)
- 2 CRM scoring servisi yanlış namespace'te (AI/ altında)

### 🟢 Kontrol Altında
- Target Architecture kararı verildi (Model 2: AI as Intelligence Layer)
- 15 domain haritası çıkarıldı, 8 conflict belgelendi
- Phase 3 Write Authority Refactor **HISTORICAL MILESTONE** (12 dosya, ama 3 legacy `Ilan::create()` bypass hâlâ mevcut — BulkListingController, YazlikKiralamaService, PortfolioImport)
- Root IlanService write methods SEALED (RuntimeException, Nisan 2026)
- Emlak IlanService DELETED (0 consumer, Nisan 2026)
- IlanCrudService event gap CLOSED (3 event dispatch after tx commit, Nisan 2026)
- SAB CI guard aktif: `scripts/ci-guard-sab-prompt.sh` — 4 rule, quality gate Step 3.5
- SAB Master Prompt v2.0.0 FROZEN (checksum enforced)
- Service Graph 60+ servis belgelendi
- Frontend Endpoint Map 240+ URL belgelendi
- Phase 1 Safe Sprint (DS-01/DS-04/DS-05/SV-07/FE-04) tamamlandı
- `.sab/authority.json` referansları düzeltildi
- Drive sync otomasyonu kuruldu (30 dakikada bir)

---

## 28. DİZİN YAPISI

```
app/
├── Actions/          # Single-use action classes
├── Console/          # Artisan commands (sab:integrity-scan, bekci:wizard-contract)
├── Domain/           # Domain-specific business logic
├── Domains/          # Domain layer (PropertySchema, etc.)
├── DTO/              # Data Transfer Objects
├── Enums/            # Enum definitions
├── Http/
│   ├── Controllers/
│   │   ├── Admin/    # Admin panel (IlanCrudController, PropertyHubController, CRMController...)
│   │   ├── Api/      # API (AIController, AIContentController, AdvancedAIController...)
│   │   │   ├── V1/   # Versioned (WizardController, TemplateController...)
│   │   │   ├── V2/   # V2 (IlanController, DraftController, GovernanceApiController)
│   │   │   └── Admin/ # Api\Admin (EventsController)
│   │   └── AI/       # AI namespace (IlanAIController, LocalVisionController)
│   ├── Middleware/
│   └── Requests/     # Form request (StoreIlanRequest — KRİTİK)
├── Models/           # Eloquent models
│   ├── V2/           # V2 models (Ilan — BAĞIMSIZ, V1'i extend ETMEZ)
│   └── ...           # V1 models (Ilan, Feature, Kisi, Talep, YayinTipiSablonu...)
├── Modules/          # Modular features (Finans, TakimYonetimi)
├── Services/
│   ├── AI/           # 94+ AI services (YalihanCortex, OllamaService, NLPProcessor...)
│   │   ├── Copilot/  # BrokerCopilotService, WizardCopilotService, CRMCopilotService
│   │   ├── Vision/   # VisionAnalysisService, LocalVisionService
│   │   └── Portfolio/ # PortfolioAnalysisService
│   ├── CRM/          # CRM services
│   ├── Cortex/       # Cortex engine (6 services)
│   ├── Governance/   # Decision engine services (8)
│   ├── Ilan/         # IlanCrudService (WRITE AUTHORITY), IlanReferansService
│   ├── Intelligence/ # Intelligence hub (23 services)
│   ├── Listing/      # ListingStateMachine, ListingLifecycleService
│   ├── Ups/          # FeatureTemplateResolver (SSOT), UpsCacheService
│   └── Wizard/       # WizardOrchestrator, FeatureTemplateResolver (LEGACY — kullanma)
└── ...

config/               # ai.php, context7.php, telemetry-events.php, authority.json...
routes/
├── admin.php         # 1710 satır — MONOLİTİK (refactor planı var)
├── admin/            # Modular: ai.php, ilanlar.php, ...
├── api.php           # Public API
├── api/v1/           # Versioned: ai.php, admin.php, cortex.php, wizard.php...
└── web.php           # Public web routes
.sab/                 # SAB governance (authority.json, snapshots, proposals, history)
.ai/
├── context/          # Truth dosyaları (system-map, domain-map, service-graph...)
├── exports/          # yalihan-project-brain-v2.md (bu dosya)
└── reports/          # Audit raporları, domain analysis, sprint reports
```

---

## 29. ÇALIŞMA PRENSİPLERİ

### Kod Yazmadan Önce
1. `php artisan db:table [tablo]` veya `DESCRIBE [tablo]` ile şema kontrol et
2. Context7 yasaklı alanlarını kontrol et
3. Etkilenen domain'i belirle
4. SSOT'yi belirle
5. Risk sınıflandırması yap (LOW/MEDIUM/HIGH)

### Geliştirme Döngüsü (Context7 TDD)
1. Önce Context7 uyumlu test yaz
2. Test FAIL etmeli
3. En az kod ile testi geçir
4. `php artisan sab:integrity-scan` çalıştır
5. Commit

### Her Değişiklik Öncesi Kontrol Listesi
- ❌ Yasaklı alan (status, active, order) kullandım mı?
- ❌ Backslash facade kullandım mı?
- ❌ DB şemasını kontrol ettim mi?
- ❌ Dark Mode uyumlu mu?
- ❌ Telemetri ekledim mi (yeni async)?
- ❌ Performans bütçesini aşıyor mu?
- ❌ ADR gerekli mi?
- ❌ API kontratı etkileniyor mu?
- ❌ Rollback planı var mı?
- ❌ Cross-module etki kontrolü yaptım mı?
- ❌ Frontend hardcoded URL kontrolü yaptım mı?

### Agent'lar İçin Özel Kurallar
- `IlanCrudService::store()` → DOKUNMA
- `StoreIlanRequest` → YÜKSEK RİSK
- `ListingStateMachine` → BYPASS YASAK
- `FeatureTemplateResolver` (Ups\) → SSOT KORU
- `Wizard flow` → Listing sub-domain, her değişiklik Listing etkiler
- `YalihanCortex` → AI orchestrator, doğrudan bypass yasak
- `authority.json` → Governance SSOT, agent değiştiremez

---

## 30. DOSYA İSTATİSTİKLERİ

| Dosya/Kategori | Boyut/Sayı |
|---------------|-----------|
| Controller toplam | 319 dosya |
| Route dosyası | 59 dosya |
| `admin.php` | 1710 satır |
| AI servisi | 94+ dosya |
| Intelligence servisi | 23 dosya |
| Cortex servisi | 6 dosya |
| Governance servisi | 8 dosya |
| Feature (DB) | 13K-50K per env |
| Hardcoded frontend URL | 240+ |
| Domain boundary violation | 16 |
| Cross-domain import ihlali | 19 |
| Tamamlanmış migration item | 11 |
| Bekleyen fix | 18 (4 phase) |

---

## DOSYA REFERANSLARI

| Döküman | Konum |
|---------|-------|
| System Map | `.ai/context/system-map.md` |
| Domain Map | `.ai/context/domain-map.md` |
| Service Graph | `.ai/context/service-graph-summary.md` |
| Target Architecture | `.ai/context/target-architecture.md` |
| AI Domain Map | `.ai/context/ai-domain-map.md` |
| AI Contract | `.ai/context/ai-contract.md` |
| Listing Contract | `.ai/context/listing-contract.md` |
| Property Engine Truth | `.ai/context/property-engine-truth.md` |
| Governance Rules | `.ai/context/governance-rules.md` |
| Coding Guardrails | `.ai/context/coding-guardrails.md` |
| Current Risks | `.ai/context/current-risks.md` |
| Frontend Endpoints | `.ai/context/frontend-endpoint-summary.md` |
| Domain Violations | `.ai/reports/domain-analysis/domain-boundary-violations.md` |
| Fix Priority Plan | `.ai/reports/domain-analysis/fix-priority-plan.md` |
| Migration Plan | `.ai/reports/domain-analysis/domain-separation-migration-plan.md` |
| AI Isolation Report | `.ai/reports/domain-analysis/ai-domain-isolation-report.md` |
| Authority JSON | `.sab/authority.json` |
| Copilot Instructions | `.github/copilot-instructions.md` |

---

*Bu döküman `.ai/context/`, `.ai/reports/`, `.sab/` ve `.github/copilot-instructions.md`'den derlenmiştir.*
*Son güncelleme: 7 Nisan 2026 — v2.1.0 (stale claims revised, event gap documented, write-seal applied)*
