# Yalıhan Emlak — Proje Bağlam Dosyası (AI Eğitim Referansı)

**Proje:** Yalıhan Emlak — Bodrum Emlak Platformu (PropTech)
**Versiyon:** v1.7.0-self-protecting | Maturity L5
**Tarih:** 6 Nisan 2026
**Amaç:** Bu döküman Gemini / ChatGPT gibi harici AI asistanların projeyi anlayabilmesi için hazırlanmıştır.

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

---

## 2. PROJE AMACI

Bodrum bölgesine odaklı emlak yönetim platformu. Admin paneli üzerinden ilan CRUD, AI destekli içerik üretimi, CRM, danışman yönetimi, portföy analizi ve karar motoru (governance) içerir. Public frontend villa kiralama ve emlak ilanları sunar.

---

## 3. DOMAIN HARİTASI (15 Domain)

| # | Domain | Route Prefix | Anahtar Controller | Açıklama |
|---|--------|-------------|-------------------|----------|
| 1 | **Listing** | `admin/ilanlar/*` | `IlanCrudController` | İlan CRUD, yaşam döngüsü, arama, draft, fotoğraf |
| 2 | **Wizard** | `api/v1/wizard/*` | `WizardContextController` | İlan oluşturma sihirbazı (Listing sub-domain) |
| 3 | **Property Engine** | `admin/property-hub/*` | `PropertyHubController` | Kategori, şablon, özellik yönetimi |
| 4 | **AI / Cortex** | `admin/ai/*`, `api/v1/ai/*` | `AISettingsController` | AI orkestrasyon, içerik üretimi, telemetri |
| 5 | **Governance** | `admin/governance/*` | `DecisionEngineController` | Karar motoru, otonom kontrol |
| 6 | **CRM** | `admin/crm/*` | `CRMController` | Kişi, talep, lead scoring |
| 7 | **Advisor** | `admin/danisman/*` | `OpportunityController` | Danışman yönetimi, portföy |
| 8 | **Integrations** | `api/v1/webhook/*` | `WhatsAppWebhookController` | WhatsApp, n8n, harici entegrasyonlar |
| 9 | **Location** | `api/v1/location/*` | `LocationController` | Konum, harita, POI, geocoding |
| 10 | **Analytics** | `admin/analytics/*` | `AnalyticsDashboardController` | Raporlama, dashboard |
| 11 | **Settings** | `admin/ayarlar/*` | `AyarlarController` | Genel ayarlar |
| 12 | **Public** | `yazliklar/*`, blog | `VillaController` | Herkese açık sayfalar |
| 13 | **Finance** | `admin/finans/*` | `Modules\Finans\*` | Finansal modül |
| 14 | **Team Mgmt** | `admin/takim-yonetimi/*` | `Modules\TakimYonetimi\*` | Takım yönetimi |
| 15 | **Rental** | `yazliklar/*`, `api/v1/yazlik-kiralama` | `YazlikKiralamaController` | Yazlık kiralama |

---

## 4. MİMARİ PRENSİPLER

### 4.1 Single Source of Truth (SSOT)

| Konu | SSOT |
|------|------|
| İlan CRUD | `IlanCrudController` + `IlanCrudService::store()` |
| Özellik Şeması | `feature_assignments` DB tablosu (hardcode yasak) |
| Feature Resolution | `App\Services\Ups\FeatureTemplateResolver` |
| AI Provider Config | `config/ai.php` |
| AI Orkestrasyon | `YalihanCortex` (fallback zinciri yönetimi) |
| Template Kuralları | `GovernedRuleRegistry` (snapshot-based) |
| Durum Geçişleri | `ListingStateMachine` |

### 4.2 Modüler Yapı

- **Modül Path:** `app/Modules/{ModuleName}`
- **Controller:** `App\Modules\Emlak\Controllers\FeatureController`
- **View:** `return view('emlak::features.index')`
- **Shared Logic:** `App\Modules\BaseModule`

### 4.3 Mimari Model

**Model 2 — AI as Shared Intelligence Layer:**
- AI domain kendi servislerini (provider, inference, cost, telemetry) sahiplenir
- Diğer domainler AI'ı "harness point" üzerinden çağırır
- AI doğrudan başka domain'in modellerine (Kisi, Ilan vb.) erişmez — servis katmanı üzerinden

---

## 5. CONTEXT7 — YASAKLI ALAN İSİMLERİ (KRİTİK)

**Context7** sistemi tüm veritabanı ve kod alanlarında Türkçe kanonik isimlendirmeyi zorunlu kılar.

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

**Context7 doğrulama:** `php artisan sab:integrity-scan`

---

## 6. SERVİS BAĞIMLILIK GRAFİĞİ

### Kritik Zincirler

**Wizard Context Resolution:**
```
WizardController → WizardOrchestrator
  → FeatureTemplateResolver(Ups) [SSOT]
  → SmartFieldGeneration → AiWallet + AiPricing
  → TemplateService → FeatureTemplateResolver + UpsCacheService
  → AiTelemetry, VisionAnalysis, AiLearning, AiExperiment
  → ListingQuality
```

**Data Write Path:**
```
IlanCrudController → IlanCrudService
  → IlanReferansService + NumberToTextConverter + ListingStateMachine
  → IlanFeatureService → FeatureTemplateResolver
  → UpsCacheService
```

**AI Inference:**
```
Controller → YalihanCortex (30 deps — GOD CLASS riski)
  → OllamaService → AiTelemetryService
  → (AI sub-services)
```

### Servis İstatistikleri

| Domain | Servis Sayısı | Giriş Noktası |
|--------|--------------|---------------|
| AI | 94+ | `YalihanCortex` |
| Property Engine | 11+ | `EngineOrchestrator` |
| Wizard | 5 | `WizardOrchestrator` |
| Listing | 4 | `IlanCrudService` |
| CRM | 3+ | `KisiScoringService` |
| Governance | 8 | `GovernanceObservabilityService` |

---

## 7. PROPERTY ENGINE (ÖZELLİK MOTORU)

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

---

## 8. AI SÖZLEŞMESİ

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

### Maliyet Bütçesi

| Ortam | Günlük Bütçe | Token/İstek |
|-------|-------------|-------------|
| Production | $50/gün | 4K token |
| Staging | $10/gün | 2K token |
| Development | $2/gün | 1K token |

### AI Domain Sahipliği

**Sahip olduğu:**
- Provider seçimi ve fallback
- Prompt execution ve inference
- Token/cost muhasebesi
- Telemetri ve monitoring
- İçerik üretimi core
- Scoring/recommendation core
- NLP processing (Bodrum-spesifik)
- Vision/image analizi

**Sahip olMAması gereken:**
- İlan CRUD / yaşam döngüsü (→ Listing)
- Feature tanımları / template resolution (→ Property Engine)
- CRM Kişi/Talep CRUD (→ CRM)
- Kategori/yayın-tipi şeması (→ Property Engine)

---

## 9. İLAN (LİSTİNG) SÖZLEŞMESİ

### Form Süreci
- Tüm ilan formu `wizardComponent` (Alpine.js) state machine üzerinden çalışır
- Backend sözleşmesi: `StoreIlanRequest` → `admin.ilanlar.store`
- Frontend'den gelen `junction_id`, `prepareForValidation()` içinde `yayin_tipi_id`'ye çevrilir

### Kişi Kuralları
- **İlan Sahibi:** ZORUNLU
- **Danışman:** ZORUNLU (sistem kullanıcısı)
- **İlgili Kişi:** Opsiyonel

### Yaşam Döngüsü
- Yeni ilanlar `yayin_durumu: 0` (draft) ile başlar
- Geçişler `ListingStateMachine` üzerinden kontrol edilir
- Write Authority: `IlanCrudService::store()` tek write kaynağıdır

---

## 10. GOVERNANCE & KALİTE KURALLARI

### Risk Seviyeleri

| Seviye | Aksiyon | Örnekler |
|--------|---------|----------|
| LOW | Auto-Apply | Method refactor, rename, linter fix |
| MEDIUM | Ask First | Parametre ekleme, query değişimi |
| HIGH | Plan Only | DB schema, route/API değişimi, büyük refactor |

### Quality Gate Zinciri (Sıralı)
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

---

## 11. CODING GUARDRAILS

1. **Hardcoded URL yasak:** Sadece `route('admin.ilanlar.store')` gibi route helper'lar kullanılacak
2. **Duplicate route yasak:** Tek yetkili endpoint olmalı
3. **Deprecated controller referansı yasak**
4. **Yeni SSOT oluşturma yasak:** Mevcut SSOT'lere sadık kalınmalı
5. **DOM yapısı korunmalı:** Tailwind/grid düzeni bozulmamalı
6. **Facade import zorunlu:** `\DB::`, `\Log::` gibi backslash facade yasak → `use Illuminate\Support\Facades\DB;`
7. **Ghost Model yasak:** Her model `$fillable` veya `$guarded` tanımlamalı
8. **Ghost Method yasak:** Her method gerçek implementasyona sahip olmalı, TODO placeholder yasak
9. **N+1 yasak:** Her zaman `Model::with(['rel1', 'rel2'])->get()` kullanılmalı
10. **Dark Mode zorunlu:** Her element `dark:*` variant'ına sahip olmalı
11. **Pure Tailwind:** Custom CSS veya Neo-prefixed class yasak

---

## 12. PERFORMANS BÜTÇESİ

| Endpoint | Hedef (p95) | Maks |
|----------|------------|------|
| Wizard context API | < 400ms | 600ms |
| AI başlık üretimi | < 3s | 5s |
| Fotoğraf upload (tek) | < 2s | 3s |
| Arama autocomplete | < 100ms | 200ms |
| Dashboard yükleme | < 1s | 1.5s |

| Kural | Limit |
|-------|-------|
| Sayfa başı max query | 25 |
| Tek query max süre | 100ms (p95) |
| N+1 query | 0 (sıfır tolerans) |
| UI thread blokaj | < 50ms |

---

## 13. TELEMETRİ & GÖZLEMLENEBİLİRLİK

### Zorunlu Telemetri
- Her async işlem latency ölçmeli (`tStart()` / `tEnd()`)
- Her API isteği: `duration_ms`, `http_durum_kodu`, `basarili`, `istek_url` kaydetmeli
- Frontend hatalar `window.addEventListener('error')` ile yakalanmalı
- Alpine.js hataları `Alpine.onError()` ile yakalanmalı

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

---

## 14. BİLİNEN SORUNLAR & AKTİF BORÇLAR

### 🔴 Kritik
- **YalihanCortex God Class:** 30 constructor dependency — decomposition şart
- **PropertyHubController God Class:** 33 method, 4 AI method Cortex bypass
- **AIController God Class:** 30+ method, CRM model import ihlali
- **240+ Hardcoded Frontend URL:** route() kullanılmıyor

### 🟡 Dikkat
- FeatureTemplateResolver dual namespace (Ups vs Wizard)
- Service locator anti-pattern
- DanismanAIController karışık sorumluluk
- admin.php 1710 satır monolithic route
- Arsa için feature_assignments boş

### 🟢 Kontrol Altında
- Target Architecture kararı verildi (Model 2)
- 15 domain haritası çıkarıldı
- Phase 3 Write Authority Refactor tamamlandı
- Service Graph 60+ servis belgelendi
- Frontend Endpoint Map 240+ URL belgelendi

---

## 15. DİZİN YAPISI (ÖNEMLİ)

```
app/
├── Actions/          # Single-use action classes
├── Console/          # Artisan commands
├── Domain/           # Domain-specific business logic
├── Domains/          # Domain layer (PropertySchema, etc.)
├── DTO/              # Data Transfer Objects
├── Enums/            # Enum definitions
├── Events/           # Event classes
├── Exceptions/       # Custom exceptions
├── Http/
│   ├── Controllers/
│   │   ├── Admin/    # Admin panel controllers
│   │   ├── Api/      # API controllers
│   │   └── ...
│   ├── Middleware/
│   └── Requests/     # Form request validation
├── Models/           # Eloquent models (V1 + V2)
├── Modules/          # Modular features (Finans, TakimYonetimi)
├── Services/
│   ├── AI/           # 94+ AI services (Cortex, NLP, Vision, etc.)
│   ├── CRM/          # CRM services
│   ├── Cortex/       # Cortex engine
│   ├── Governance/   # Decision engine services
│   ├── Ilan/         # Listing services
│   ├── Intelligence/ # Intelligence hub
│   ├── Listing/      # State machine, lifecycle
│   ├── Ups/          # Property Engine SSOT (FeatureTemplateResolver)
│   └── Wizard/       # Wizard orchestration
├── Traits/           # Shared traits
└── ValueObjects/     # Value objects

config/               # Configuration (ai.php, context7.php, etc.)
routes/
├── admin.php         # Admin routes (1710 lines — monolithic)
├── admin/            # Modular admin routes (ai.php, ilanlar.php, etc.)
├── api.php           # Public API
├── api/v1/           # Versioned API routes
└── web.php           # Public web routes
resources/
├── js/               # Alpine.js components, wizard JS
├── views/            # Blade templates
└── css/              # Tailwind CSS
```

---

## 16. ÇALIŞMA PRENSİPLERİ (WORKFLOW)

### Kod Yazmadan Önce
1. `php artisan db:table [tablo]` veya `DESCRIBE [tablo]` ile şema kontrol et
2. `config/authority.json` veya Context7 kurallarını oku
3. Yasaklı alan isimlerini kullanma

### Geliştirme Döngüsü (Context7 TDD)
1. Önce Context7 uyumlu test yaz
2. Test FAIL etmeli
3. En az kod ile testi geçir
4. `php artisan sab:integrity-scan` çalıştır
5. Commit

### Pull Request Öncesi
1. `php artisan test`
2. `php artisan sab:integrity-scan`
3. `php artisan bekci:wizard-contract`
4. `./scripts/quality-gate.sh` → exit 0 olmalı

### Her Response Öncesi Kontrol Listesi
- ❌ Yasaklı alan (status, active, order) kullandım mı?
- ❌ Backslash facade kullandım mı?
- ❌ DB şemasını kontrol ettim mi?
- ❌ Dark Mode uyumlu mu?
- ❌ Telemetri ekledim mi (yeni async)?
- ❌ Performans bütçesini aşıyor mu?
- ❌ ADR gerekli mi?
- ❌ API kontratı etkileniyor mu?

---

## 17. ADR PROTOKOLÜ

Her yapısal değişiklik için **Architectural Decision Record** zorunludur.

**Format:** Context → Decision → Consequences → Alternatives Considered
**Konum:** `docs/adr/YYYY-MM-DD-decision-title.md`

---

## 18. ÖNEMLİ NOTLAR

- **Bodrum odaklı:** NLP, lokasyon ve typo düzeltme Bodrum-spesifik
- **Türkçe adlandırma:** Veritabanı ve telemetri alanları Türkçe kanonik isimlendirme kullanır
- **Model V1 vs V2:** `App\Models\Ilan` (V1) ve `App\Models\V2\Ilan` bağımsız modellerdir, ikisi de aynı tabloyu kullanır, V2 V1'i extend etmez
- **Wizard:** Listing domain'in sub-domain'idir, bağımsız domain değildir
- **AI Warning Mode:** AI hiçbir kullanıcı aksiyonunu bloke etmez

---

*Bu döküman `.ai/context/` altındaki truth dosyalarından, `.github/copilot-instructions.md` ve aktif risk raporlarından derlenmiştir.*
