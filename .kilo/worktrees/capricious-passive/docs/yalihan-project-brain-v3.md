# Yalıhan Emlak — Proje Bağlam Dosyası v3.1 (AI Eğitim Referansı)

**Proje:** Yalıhan Emlak — Bodrum Emlak Platformu (PropTech)
**Versiyon:** v3.1.0 | SAB v6.1.1
**Tarih:** 18 Haziran 2026
**Önceki Versiyon:** v3.0.0 (16 Mayıs 2026)
**Amaç:** Bu döküman Gemini / ChatGPT / Claude gibi harici AI asistanların projeyi tam bağlamıyla anlayabilmesi için hazırlanmıştır.
**Kural:** Bu dosya referans kaynağıdır, SSOT değildir. SSOT her zaman proje dosyalarıdır.

---

## v2 → v3 DEĞİŞİKLİK ÖZETİ (6 Nisan → 16 Mayıs 2026)

| Alan | v2 | v3 |
|------|----|----|
| Domain sayısı | 15 | **16** (Owner Portal eklendi) |
| Controller | 319 | **327** |
| Servis | 170+ | **567** |
| AI Servis | 94+ | **145** |
| Model | 130+ | **189** |
| Route dosyası | 59 | **48** (temizlendi) |
| Hardcoded JS URL | 240+ | **~120** (yarıya indi) |
| APIConfig adoption | 3 dosya | **48 dosya** |
| YalihanCortex deps | 30 | **35+** (büyüdü) |
| SAB versiyonu | 6.1.0 | **6.1.1** |
| Test sayısı | — | **2.066** |

## v3 → v3.1 DEĞİŞİKLİK ÖZETİ (16 Mayıs → 18 Haziran 2026)

| Alan | v3 (Mayıs) | v3.1 (Haziran) |
|------|-----------|----------------|
| Ghost model (`Deprecated\*`) | 10 referans aktif | **0** — B-006 KAPALI |
| Model sayısı | 189 | **199** (+10 fiziksel dosya) |
| Context7 naming violations | Kisi.php aktif | **Temizlendi** (email→eposta) |
| PHP parse error | `HealthExplainService` bozuk | **Düzeltildi** |
| docs/archive/ | 218 dosya mevcut | **Silindi** (Oturum 59) |
| Sprint 2 aktif görev | — | **4** (#19, #28, #58, #60) |
| Laravel versiyonu | Laravel 10 (hatalı) | **Laravel 11 / PHP 8.2** |

---

## İÇİNDEKİLER

1. Tech Stack
2. Proje Amacı
3. Domain Haritası (16 Domain)
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
18. Cross-Domain Import İhlalleri
19. Fix Priority Plan (18 Fix, 4 Phase) — Güncel Durum
20. Aktif Migration Planları
21. Tamamlanmış İşler (Tekrar Önerme!)
22. God Class Risk Tablosu
23. Template SSOT Kaosu
24. SAB Governance Sistemi
25. MCP Server Ekosistemi
26. V2 Model Bridge Pattern
27. Owner Portal (D16) — YENİ
28. Bilinen Sorunlar & Aktif Borçlar
29. Dizin Yapısı
30. Çalışma Prensipleri
31. Dosya İstatistikleri

---

## 1. TECH STACK

| Katman | Teknoloji |
|--------|-----------|
| Backend | Laravel 11 / PHP 8.2 |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Build | Vite |
| Database | MySQL (prod), SQLite (test) |
| Cache/Queue | Redis + Laravel Horizon |
| AI Providers | Ollama (birincil) → DeepSeek (`deepseek-chat` / `deepseek-reasoner`) → OpenAI / Gemini / Claude |
| Monitoring | Laravel Telescope (staging), custom telemetry |
| CI/CD | GitHub Actions (`core-ci.yml` — tek aktif pipeline) |
| Governance | SAB v6.1.1 + Context7 + Bekçi MCP (AST tabanlı) |
| Orkestrasyon | N8N (`https://n8n.yalihanemlak.com.tr`) |

> **v2'den fark:** `sab-guard.yml`, `postseal-guard.yml`, `gold-line.yml` superseded — `core-ci.yml` tek CI.
> **⚠️ NOT:** `deepseek-v4-flash` geçersiz model adı. `deepseek-chat` veya `deepseek-reasoner` kullan.

---

## 2. PROJE AMACI

Bodrum bölgesine odaklı emlak yönetim platformu. Admin paneli üzerinden ilan CRUD, AI destekli içerik üretimi, CRM, danışman yönetimi, portföy analizi ve karar motoru (governance) içerir. Public frontend villa kiralama ve emlak ilanları sunar. **Owner Portal** ile mülk sahipleri kendi ilanlarını, tekliflerini ve belgelerini takip edebilir. Tüm AI işlemleri Warning Mode'da çalışır — kullanıcı aksiyonunu asla bloke etmez.

---

## 3. DOMAIN HARİTASI (16 Domain)

| # | Domain | Route Prefix | Anahtar Controller | Açıklama |
|---|--------|-------------|-------------------|----------|
| D01 | **Listing** | `admin/ilanlar/*` | `IlanCrudController` | İlan CRUD, yaşam döngüsü, draft, fotoğraf |
| D02 | **Wizard** | `api/v1/wizard/*` | `WizardContextController` | İlan oluşturma sihirbazı (Listing sub-domain) |
| D03 | **Property Engine** | `admin/property-hub/*` | `PropertyHubController` | Kategori, şablon, özellik yönetimi |
| D04 | **AI / Cortex** | `admin/ai/*`, `api/v1/ai/*` | `AISettingsController` | AI orkestrasyon, içerik üretimi, telemetri |
| D05 | **Governance** | `admin/governance/*` | `DecisionEngineController` | Karar motoru, otonom kontrol |
| D06 | **CRM** | `admin/crm/*` | `CRMController` | Kişi, talep, lead scoring |
| D07 | **Advisor** | `admin/danisman/*` | `OpportunityController` | Danışman yönetimi, portföy |
| D08 | **Integrations** | `api/v1/webhook/*` | `WhatsAppWebhookController` | WhatsApp, n8n, harici entegrasyonlar |
| D09 | **Location** | `api/v1/location/*` | `LocationController` | Konum, harita, POI, geocoding |
| D10 | **Analytics** | `admin/analytics/*` | `AnalyticsDashboardController` | Raporlama, dashboard |
| D11 | **Settings** | `admin/ayarlar/*` | `AyarlarController` | Genel ayarlar |
| D12 | **Public** | `yazliklar/*`, blog | `VillaController` | Herkese açık sayfalar |
| D13 | **Finance** | `admin/finans/*` | `Modules\Finans\*` | Finansal modül |
| D14 | **Team Mgmt** | `admin/takim-yonetimi/*` | `Modules\TakimYonetimi\*` | Takım yönetimi |
| D15 | **Rental** | `yazliklar/*`, `api/v1/yazlik-kiralama` | `YazlikKiralamaController` | Yazlık kiralama |
| D16 | **Owner Portal** ⭐ YENİ | `/owner/*` | `OwnerAuthController` | Mülk sahibi self-servis portalı |

### Toplam Envanter

| Metrik | Sayı |
|--------|------|
| Controller | 327 |
| Route dosyası | 48 |
| Servis | 567 |
| AI Servis | 145 |
| Model | 189 |

---

## 4. MİMARİ PRENSİPLER & SSOT

### 4.1 Single Source of Truth (SSOT)

| Bileşen | SSOT |
|---------|------|
| Ilan write | `IlanCrudService::store()` |
| Template resolution | `Ups\FeatureTemplateResolver` |
| Wizard context | `/api/v1/wizard/context` |
| Governance kararları | `.sab/authority.json` |
| CI pipeline | `.github/workflows/core-ci.yml` |
| Owner auth | `OwnerLoginToken` (magic-link/OTP) |

### 4.2 Mimari Model

**Model 2:** AI as Intelligence Layer — AI servisler orkestrasyon katmanında, domain'lere sızmaz.

### 4.3 İzin Verilen Servis Kontratları

```
Controller → Service → Repository → Model
Controller → Service → External API
Service → Service (aynı domain)
AI Service → Domain Service (read-only harness)
```

### 4.4 Yasaklı Doğrudan Import'lar

```
Controller → DB::table() [YASAK]
AI Service → Repository (write) [YASAK]
Cross-domain Model CRUD [YASAK]
ORM bypass [YASAK]
```

---

## 5. CONTEXT7 — YASAKLI ALAN İSİMLERİ (KRİTİK)

Context7, kodda kullanılması yasak alan adlarını tanımlar. Bu alanlar yerine Context7 uyumlu karşılıkları kullanılmalıdır.

| Yasaklı | Context7 Karşılığı |
|---------|--------------------|
| `status` | `yayin_durumu` / `durum_kodu` / `aktiflik_durumu` |
| `active`, `is_active`, `aktif` | `aktiflik_durumu` |
| `email` | `eposta` |
| `order`, `sort_order` | `display_order` |
| `featured`, `is_featured` | `one_cikan` |
| `featured_image` | `kapak_resmi` |
| `latitude`, `enlem` | `lat` |
| `longitude`, `boylam` | `lng` |
| `city`, `sehir` | `il` / `il_adi` |
| `musteriler` | `kisiler` |
| `status_code` (ai_logs) | `aktiflik_kodu` |
| `http_status_code` | `http_durum_kodu` |
| `property_type`, `emlak_tipi` | `ana_kategori_id` (FK) |
| `property_category` | `alt_kategori_id` (FK) |
| `type` | `tur` veya domain-specific |
| `name` | domain-specific (örn. `baslik`) |

**Kontrol:** `php artisan sab:integrity-scan`
**Bypass:** `// context7-ignore` satır bazında, `@sab-ignore-catch` catch doc comment'e
**Kanonik isim sorgusu:** `mcp get_canonical <field>`

---

## 6. SERVİS BAĞIMLILIK GRAFİĞİ

### İstatistikler

| Katman | Sayı |
|--------|------|
| Toplam servis | 567 |
| AI servisi | 145 |
| Intelligence servisi | 23 |
| Cortex servisi | 6 |
| Governance servisi | 8 |

### Namespace Belirsizliği (Aktif Risk)

4 Advisor servisi `Services/AI/` altında yanlış yerde:
- `AdvisorAnalyticsService` → `Services/Advisor/` olmalı
- `AdvisorCommandCenterService` → `Services/Advisor/` olmalı
- `DanismanAIService` → `Services/Advisor/` olmalı
- `ConversationalAdvisorService` → `Services/Advisor/` olmalı

2 CRM servisi `Services/AI/` altında yanlış yerde:
- `KisiChurnService` → `Services/CRM/` olmalı
- `LeadScoreCalculator` → `Services/CRM/` olmalı

---

## 7. KRİTİK ZİNCİRLER (5 Chain)

### Chain 1: Wizard Context Resolution (EN KRİTİK)
```
WizardContextController
  → WizardOrchestrator (8 dep)
    → FeatureTemplateResolver (Ups\ — SSOT)
      → EffectiveWizardSchemaResolver
```

### Chain 2: Data Write Path (WRITE AUTHORITY)
```
Request → StoreIlanRequest (validation)
  → IlanCrudController::store()
    → IlanCrudService::store() ← TEK WRITE AUTHORITY
      → IlanRepository ← TEK DB WRITE
```

### Chain 3: AI Inference
```
AIController / Cortex endpoint
  → YalihanCortex (35+ dep — GOD CLASS, #19 dekompoze ediliyor)
    → OllamaService (birincil, local)
      ↓ fail → DeepSeekService (deepseek-chat / deepseek-reasoner)
        ↓ fail → OpenAIService | GeminiService | ClaudeService
          → AiBudgetGuard (maliyet kontrolü)
```
⚠️ `deepseek-v4-flash` geçersiz model adı — `deepseek-chat` veya `deepseek-reasoner` kullan.

### Chain 4: PE V3 Engine
```
PropertyHubController (28 method)
  → PropertyFeatureService
    → FeatureAssignmentRepository
```

### Chain 5: Owner Portal Auth (YENİ — D16)
```
/owner/login → OwnerAuthController::sendLoginLink()
  → OwnerLoginToken (magic-link) → Log [TODO: Mail]
    → check.owner middleware
      → Owner dashboard/ilanlar/teklifler/mesajlar/belgeler
```

---

## 8. PROPERTY ENGINE (ÖZELLİK MOTORU)

### Scope Priority (Atama Önceliklendirmesi)
1. İlan özel atama
2. Alt kategori şablonu
3. Ana kategori şablonu
4. Global varsayılan

### Kurallar
- Feature assign = Repository üzerinden
- Arsa için `feature_assignments` boş (data eksikliği, bug değil)
- PropertyHubController AI method'ları Cortex harness üzerinden

---

## 9. AI SÖZLEŞMESİ

### Providers & Fallback
```
Ollama (varsayılan, local)
  ↓ fail
DeepSeek
  ↓ fail
OpenAI / Gemini / Claude
```

### AI UI Kuralı (Warning Mode — KRİTİK)
- `cortexScore=0` → Taslak Olarak Kaydet (her zaman tıklanabilir)
- `cortexScore<40` → Düşük Skorla Kaydet (sarı)
- `cortexScore>=40` → Yayınla (yeşil)
- **AI asla kullanıcıyı bloke etmez**

### Maliyet Bütçesi
- `AiBudgetGuard` her AI çağrısını denetler
- `config/ai.php` → provider config
- `config/telemetry-events.php` → allowlist

---

## 10. İLAN (LİSTİNG) SÖZLEŞMESİ

### Yaşam Döngüsü
```
Taslak → Hazır → Yayında → Pasif → Arşiv
```
- `ListingStateMachine` → bypass yasak
- `ListingLifecycleService` → durum geçişleri

### Kişi Kuralları
- `ilan_sahibi_id` → `kisiler` tablosu
- `danisman_id` → `users` tablosu
- `ilgili_kisi_id` → `kisiler` tablosu

---

## 11. WIZARD KONUMLANDIRMA KARARI

Wizard, Listing domain'inin sub-domain'idir. Ayrı domain değil.

```
admin/ilanlar/create → Wizard entry
api/v1/wizard/context → Wizard context API (8+ dosya bağımlı)
api/v1/wizard/* → Wizard actions
```

**Kritik:** `/api/v1/wizard/context` URL'si 8+ JS dosyasında hardcoded. Route prefix değişimi sistemi kırar.

---

## 12. GOVERNANCE & KALİTE KURALLARI

### Quality Gate Zinciri (Sıralı — Bypass YASAK)
```
1. php artisan sab:integrity-scan
2. php artisan guard:cqrs
3. php artisan guard:routes:v2
4. php artisan quality:gate
5. php artisan test --compact
6. php artisan sab:preflight --profile=release
```

### Deployment Kuralları
- Her PR governance gate'ten geçmeli
- `authority.json` agent tarafından değiştirilemez
- Tenant isolation ihlali = en ağır ihlal
- Direct ORM bypass = ihlal

---

## 13. CODING GUARDRAILS

- `IlanCrudService::store()` → DOKUNMA
- `StoreIlanRequest` → YÜKSEK RİSK (validation zinciri kırılabilir)
- `ListingStateMachine` → BYPASS YASAK
- `FeatureTemplateResolver` (Ups\) → SSOT KORU
- `Wizard flow` → her değişiklik Listing'i etkiler
- `YalihanCortex` → AI orchestrator, doğrudan bypass yasak
- `authority.json` → Governance SSOT, agent değiştiremez
- `DB::table()` Controller içinde → YASAK
- Backslash facade → YASAK
- Context7 yasaklı alan → YASAK

---

## 14. PERFORMANS BÜTÇESİ

### API Response Times (p95)

| Endpoint | Hedef | CI Fail |
|----------|-------|---------|
| Wizard context API | < 400ms | > 400ms ❌ |
| AI generation | < 3s | > 3s ❌ |
| Dashboard load | < 1.5s | > 1.5s ❌ |
| Telemetri error rate | < 2% | > 2% ❌ |
| N+1 query | 0 | > 0 ❌ |

---

## 15. TELEMETRİ & GÖZLEMLENEBİLİRLİK

### Zorunlu Telemetri
- Her async işlem: `tStart()` / `tEnd()` latency ölçümü
- Her API isteği: `duration_ms`, `http_durum_kodu`, `basarili`, `istek_url`
- Frontend: `window.addEventListener('error')`
- Alpine.js: `Alpine.onError()`

### Event Allowlist
- `config/telemetry-events.php` → tüm event adları burada tanımlı
- Rastgele event adı YASAK

### Log Dosyaları

| Log | Path | Retention |
|-----|------|-----------|
| Telemetri | `storage/logs/telemetry-YYYY-MM-DD.log` | 30 gün |
| Backend | `storage/logs/laravel.log` | — |
| Security | `storage/logs/security.log` | 90 gün |
| Bekçi | `storage/logs/bekci.log` | 90 gün |

---

## 16. FRONTEND ROUTE KIRILGANLIK HARİTASI

### Güncel Durum (v3 — Mayıs 2026)

| Metrik | v2 (Nisan) | v3 (Mayıs) | Delta |
|--------|-----------|-----------|-------|
| Hardcoded JS URL | 240+ | ~120 | ✅ -50% |
| `window.APIConfig` kullanan dosya | 3 | **48** | ✅ +45 |
| route() helper (Blade) | 80+ | 80+ | — |
| Hardcoded /admin/ (Blade) | 25+ | 25+ | — |

### Hâlâ Kritik

| Route Prefix | Risk |
|--------------|------|
| `/api/v1/wizard/context` | 🔴 8+ dosya bağımlı |
| `/admin/ilanlar/*` | 🔴 Route değişimi sistemi kırar |
| `/admin/copilot/*` | 🔴 Copilot durur |

---

## 17. DOMAIN BOUNDARY VIOLATIONS (16 Violation)

| ID | Severity | Problem | Durum |
|----|----------|---------|-------|
| V01 | 🔴 | `Api\AIController` CRM CRUD sızıntısı | ⏳ FIX-16 |
| V02 | 🔴 | 14+ Admin controller Api prefix altında | ⏳ FIX-18 |
| V03 | 🔴 | WizardCopilotActionController admin prefix'te | ✅ ACCEPTED |
| V04 | 🔴 | `Api\Admin\EventsController` cross-domain inheritance | ⚠️ Kısmen (ignore tag eklendi) |
| V05 | 🟠 | `PropertyHubController` God Class, 4 AI bypass | ⏳ FIX-11 |
| V06 | 🟠 | `DecisionEngineController` 27 method | ⏳ FIX-12 |
| V07 | 🟠 | `IlanAITitleDescriptionController` cross-domain | ⏳ FIX-08 |
| V08 | 🟠 | `DanismanAIController` triple mix | ⏳ FIX-13 |
| V09 | 🟠 | Template SSOT Chaos (11 controller) | ⏳ FIX-17 |
| V10-V16 | 🟡/🟢 | Rename, prefix, dead import | ⏳ Bekliyor |

---

## 18. CROSS-DOMAIN IMPORT İHLALLERİ

| ID | Controller | İhlal | Severity |
|----|-----------|-------|----------|
| X01 | `Api\AIController` | Kisi, Talep (CRM CRUD) | 🔴 |
| X02 | `Api\AIController` | Ilan model | 🔴 |
| X06-X09 | `PropertyHubController` | OllamaService doğrudan | 🟠 |
| X15 | `DanismanAIController` | D04+D07 karışık | 🟠 |

---

## 19. FIX PRIORITY PLAN — GÜNCEL DURUM (Mayıs 2026)

| # | Fix | Phase | Durum |
|---|-----|-------|-------|
| FIX-01 | Dead IlanController import sil | P1 | ⏳ **Hâlâ açık** |
| FIX-02 | Dead routes temizle | P1 | ⏳ Bekliyor |
| FIX-03 | EventsController inheritance | P1 | ⚠️ `@sab-ignore-thin` eklendi, tam düzeltilmedi |
| FIX-04 | V03 → ACCEPTED reclassify | P1 | ✅ Tamamlandı |
| FIX-05 | V02 → TRACKED DEBT | P1 | ✅ Tamamlandı |
| FIX-06 | AIController CRM → AICrmGatewayService | P2 | ⏳ |
| FIX-07 | PropertyHub AI → PropertyAIService | P2 | ⏳ |
| FIX-08 | IlanAITitleDescriptionController verify | P2 | ⏳ |
| FIX-09 | IlanAIQualityController rename | P2 | ⏳ |
| FIX-10 | IlanQualityDashboardController rename | P2 | ⏳ |
| FIX-11 | PropertyHubController → 4 controller split | P3 | ⏳ |
| FIX-12 | DecisionEngineController → 4 controller split | P3 | ⏳ |
| FIX-13 | DanismanAIController split | P3 | ⏳ |
| FIX-14 | PortfolioDoctorController route taşı | P3 | ⏳ |
| FIX-15 | CRMController internal split | P3 | ⏳ |
| FIX-16 | AIController full domain split | P4 | ⏳ |
| FIX-17 | Template SSOT consolidation | P4 | ⏳ |
| FIX-18 | Api/Admin namespace migration | P4 | ⏳ |

**Özet:** 18 fix'ten 2'si tamamlandı, 1'i kısmen, 15'i bekliyor.

---

## 20. AKTİF MİGRATION PLANLARI

| Plan | Dosya | Durum |
|------|-------|-------|
| Domain Separation (22 item, 4 phase) | `docs/plans/` | Phase 1 ✅, Phase 2-4 ⏳ |
| AI Domain Migration (21 item) | `docs/plans/` | ⏳ Bekliyor |
| Fix Priority Plan (18 fix) | `docs/plans/` | 2/18 tamamlandı |

---

## 21. TAMAMLANMIŞ İŞLER (TEKRAR ÖNERME!)

Bu işler **tamamlandı**. Agent'lar bunları tekrar önerirse yanlış.

| İş | Tarih | Detay |
|----|-------|-------|
| ✅ Write Authority Refactor | Mart 2026 | Tüm write'lar IlanCrudService::store() üzerinden |
| ✅ V2→V1 Model Bridge | Mart 2026 | `Ilan::findOrFail($v2Ilan->id)` pattern |
| ✅ Legacy IlanService @deprecated | Mart 2026 | Write methods quarantine |
| ✅ DS-01~DS-05 Phase 1 Safe Sprint | Nisan 2026 | Naming, documentation, alias |
| ✅ SV-07: OpenAIService fix | Nisan 2026 | AiBudgetGuard → constructor injection |
| ✅ FE-04: Telemetry APIConfig | Nisan 2026 | window.APIConfig normalized |
| ✅ Drive sync otomasyonu | Nisan 2026 | 30 dakikada bir, launchd + rclone |
| ✅ CSP Hardening (Phase 15) | Mayıs 2026 | nonce-based strict-dynamic, unsafe-eval kaldırıldı |
| ✅ Authority Hardening (Phase 16) | Mayıs 2026 | danisman_id filtreleri admin-only guard |
| ✅ Owner Portal Task #14-18 | Mayıs 2026 | Auth, ilanlar, teklifler, mesajlar, belgeler |
| ✅ CI Gate: 287 → 724 passing test | Mayıs 2026 | SQLite uyumsuzluğu giderildi |
| ✅ B-006 Ghost Model Cleanup (P5A-P5F) | Haziran 2026 | 10 `Deprecated\*` model fiziksel dosya oluşturuldu, tüm import düzeltildi |
| ✅ Kisi.php Context7 Naming (email→eposta) | Haziran 2026 | getCrmScoreAttribute, logOnly, PHPDoc, gereksiz `use Model` import kaldırıldı |
| ✅ HealthExplainService PHP parse error | Haziran 2026 | Unicode typographic quotes → ASCII — commit `7c186dd0` |
| ✅ docs/ MD Audit & Reorganizasyon (Oturum 59) | Haziran 2026 | 218 dosya arşiv silindi, `docs/archive/` + `.sab/proposals/` temizlendi |

---

## 22. GOD CLASS RİSK TABLOSU

| Sınıf | v2 Durum | v3 Durum | Aksiyon |
|-------|---------|---------|---------|
| `YalihanCortex` | 30 dep | **35+ dep** 🔴 BÜYÜDÜ | FIX-16 acil |
| `Api\AIController` | 30+ method | **25 method** 🟡 Küçüldü | FIX-16 (P4) |
| `PropertyHubController` | 34 method | **28 method** 🟡 Küçüldü | FIX-11 (P3) |
| `DecisionEngineController` | 27 method | **27 method** 🔴 Değişmedi | FIX-12 (P3) |
| `CRMController` | 18 method | — | FIX-15 (P3) |
| `DanismanAIController` | 15+ method | — | FIX-13 (P3) |
| `WizardOrchestrator` | 8 dep | — | İzle |

---

## 23. TEMPLATE SSOT KAOSU

11 controller aynı "template" kavramını yönetiyor — FIX-17 (P4) ile çözülecek. Detay için bkz. v2.

---

## 24. SAB GOVERNANCE SİSTEMİ

**SAB Versiyon:** 6.1.1

```
.sab/
├── authority.json          ← Governance SSOT (v6.1.1)
├── sab-master-prompt.md    ← v2.1.0 (FROZEN, 11 Nisan güncellendi)
├── latest/                 ← Son authority snapshot
├── history/                ← audit.log, decisions.log
├── proposals/              ← ⚠️ 3 dosya Oturum 59 (2026-06-16)'da SİLİNDİ
├── snapshots/              ← Otomatik yedekler
└── sprint-reports/         ← Sprint raporları
```

**CI Pipeline:** `.github/workflows/core-ci.yml` (tek aktif)

---

## 25. MCP SERVER EKOSİSTEMİ

| Server | Amaç | Araçlar |
|--------|------|---------|
| `yalihan-bekci` | Governance guardian | `validate_file`, `check_violation`, `get_canonical`, `get_project_health`, `record_learning` |
| `context7` | Güncel dokümantasyon sorgusu | `resolve-library-id`, `query-docs` |
| `chrome-devtools` | Browser debugging | DevTools araçları |

**Her yeni alan adı için:** `get_canonical` çağır.
**Her dosya yazımından önce:** `validate_file` ile bekçi kontrolü yap.

---

## 26. V2 MODEL BRIDGE PATTERN

`App\Models\Ilan` (V1) ve `App\Models\V2\Ilan` **BAĞIMSIZ** modellerdir. Aynı tabloyu paylaşır, V2 V1'i extend etmez.

```php
// V2 Action'da V1 servise bridge
$v1Ilan = \App\Models\Ilan::findOrFail($v2Ilan->id);
app(IlanCrudService::class)->store($v1Ilan, $data);
```

---

## 27. OWNER PORTAL (D16) — YENİ ⭐

Owner Portal Mayıs 2026'da tamamlandı (Task #14–18).

### Mimarisi

| Controller | Sorumluluk |
|-----------|-----------|
| `OwnerAuthController` | Magic-link / OTP şifresiz giriş |
| `OwnerIlanController` | Mülk sahibinin ilanları (user_id bazlı) |
| `OwnerTeklifController` | Gelen teklifler + sistem eşleşmeleri |
| `OwnerMesajController` | Danışmanla WhatsApp benzeri mesajlaşma |
| `OwnerBelgeController` | Güvenli belge indirme (Tapu, Sözleşme) |
| `OwnerReportController` | Raporlar (⏳ Task #19 UI polisajı bekliyor) |
| `OwnerDashboardController` | Ana panel |

### Yeni Modeller
`OwnerLoginToken`, `Teklif`, `Mesaj`, `Belge`, `OwnerReportExport`, `OwnerReportMetric`, `OwnerReportRow`

### Açık Borçlar
- `sendLoginLink()` → `Mail::to()` hâlâ TODO, token Log'a yazılıyor
- Task #19 Raporlar UI polisajı bekliyor
- Task #20 UI/UX & Layout (Toast, dark mode, responsive)

### Route Yapısı
```
/owner/login          → public (throttle:20,1)
/owner/dashboard      → auth (check.owner middleware)
/owner/ilanlar        → auth
/owner/teklifler      → auth
/owner/mesajlar       → auth
/owner/belgeler       → auth
/owner/raporlar       → auth
```

---

## 28. BİLİNEN SORUNLAR & AKTİF BORÇLAR

### 🔴 Kritik (Değişmedi veya Kötüleşti)
- **YalihanCortex God Class:** 35+ dep — Sprint 2 #19 dekompoze başladı (aktif görev)
- **Api\AIController:** 25 method, CRM model import ihlali (Kisi, Talep CRUD)
- **~120 Hardcoded Frontend URL:** v2'ye göre iyileşti ama hâlâ kritik sayı
- **Wizard context hardcoded:** 8+ dosya
- **#28** `app/Domains/` → `app/Domain/` dizin birleştirmesi bekliyor (Sprint 2)

### 🟡 Dikkat
- Owner Portal mail entegrasyonu eksik (login token Log'a yazılıyor)
- GovernanceDecision hash chain MySQL migrate bekliyor (Global Seal için)
- FeatureTemplateResolver dual namespace (Ups vs Wizard)
- DanismanAIController karışık sorumluluk
- Advisor servisleri yanlış namespace (AI/ altında)
- Dual sistem: CRM V1/V2, Finance modül çakışması
- **#58** `DriftDetectionService` çift implementasyon — kanonik seçim bekliyor
- **#60** İki `ModuleServiceProvider` isim çakışması — çözüm bekliyor

### 🟢 Kontrol Altında
- B-006 Ghost Model Cleanup: ✅ KAPALI (10 `Deprecated\*` model temizlendi)
- Kisi.php `email` → `eposta` Context7 naming: ✅ Temizlendi

### 🟢 Kontrol Altında
- Target Architecture kararı verildi (Model 2)
- CSP Hardening tamamlandı (unsafe-eval kaldırıldı)
- Authority Hardening tamamlandı
- Write Authority Refactor tamamlandı
- APIConfig adoption 3 → 48 dosya

---

## 29. DİZİN YAPISI

```
app/
├── Http/Controllers/
│   ├── Admin/       # Ana admin panel
│   ├── Api/         # API (V1, V2, Admin namespace)
│   ├── AI/          # AI namespace
│   └── Owner/       # Owner Portal (YENİ — D16)
├── Models/
│   ├── V2/          # Bağımsız V2 modeller
│   ├── Belge.php    # Owner Portal
│   ├── Mesaj.php    # Owner Portal
│   ├── Teklif.php   # Owner Portal
│   └── OwnerLoginToken.php
├── Services/
│   ├── AI/          # 145+ AI servisi
│   ├── Cortex/      # 6 servis
│   ├── Governance/  # 8 servis
│   ├── Intelligence/# 23 servis
│   └── Ilan/        # Write authority
routes/
├── admin.php        # 1687 satır monolitik
├── admin/           # Modular: ai.php, ilanlar.php
├── web.php          # Public + Owner Portal routes
docs/
├── architecture/    # Domain, flow, service-ownership...
├── known-debt.md    # Teknik borç listesi
├── plans/           # Migration planları
└── yalihan-project-brain-v3.md  ← BU DOSYA
.sab/                # SAB governance (SSOT)
```

---

## 30. ÇALIŞMA PRENSİPLERİ

### Kod Yazmadan Önce
1. `php artisan db:table [tablo]` ile şema kontrol et
2. Context7 yasaklı alanları kontrol et
3. Etkilenen domain'i belirle (D01–D16)
4. SSOT'yi belirle
5. Risk sınıflandırması yap (LOW / MEDIUM / HIGH)

### Geliştirme Döngüsü
1. Context7 uyumlu test yaz (önce fail etmeli)
2. En az kod ile testi geçir
3. `php artisan sab:integrity-scan` çalıştır
4. Commit

### Her Değişiklik Öncesi Kontrol Listesi
- [ ] Yasaklı Context7 alanı (status, active, order) kullandım mı?
- [ ] Backslash facade kullandım mı?
- [ ] DB şemasını kontrol ettim mi?
- [ ] Dark Mode uyumlu mu?
- [ ] Telemetri ekledim mi (yeni async)?
- [ ] Performans bütçesini aşıyor mu?
- [ ] Frontend hardcoded URL kontrolü yaptım mı?
- [ ] Cross-module etki kontrolü yaptım mı?
- [ ] ADR gerekli mi?
- [ ] Rollback planı var mı?

---

## 31. DOSYA İSTATİSTİKLERİ

| Dosya/Kategori | v2 (Nisan) | v3 (Mayıs) | v3.1 (Haziran) |
|----------------|-----------|-----------|----------------|
| Controller | 319 | **327** | 327 |
| Route dosyası | 59 | **48** | 48 |
| Servis | 170+ | **567** | 567+ |
| AI Servisi | 94+ | **145** | 145+ |
| Model | 130+ | **189** | **199** (+10 ghost) |
| Test | — | **2.066** | 2.066+ |
| admin.php satır | 1710 | **1687** | 1687 |
| Hardcoded JS URL | 240+ | **~120** | ~120 |
| APIConfig adoption | 3 dosya | **48 dosya** | 48 |
| Domain boundary violation | 16 | **16** (2 resolved) | **14** aktif |
| Bekleyen fix | 18 | **15** | 15 |
| Ghost model referansı | — | 10 aktif | **0** ✅ |
| Sprint 2 aktif görev | — | — | **4** (#19,#28,#58,#60) |
| Context7 naming violations | — | Kisi.php aktif | **Temizlendi** ✅ |

---

## DOSYA REFERANSLARI

| Döküman | Konum |
|---------|-------|
| Architecture | `docs/architecture/` |
| Known Debt | `docs/known-debt.md` |
| Migration Planları | `docs/plans/` |
| Authority JSON | `.sab/authority.json` |
| SAB Master Prompt | `.sab/sab-master-prompt.md` |
| Sprint Raporları | `.sab/sprint-reports/` |
| Copilot Instructions | `.github/copilot-instructions.md` |
| **Bu Dosya (v3)** | `docs/yalihan-project-brain-v3.md` |
| Önceki Versiyon (v2) | `_archived/yalihan-project-brain-v2.md` |

---

*Bu döküman `docs/`, `.sab/` ve mevcut proje kaynak kodundan üretilmiştir.*
*Versiyon: v3.1.0 | SAB v6.1.1 | 18 Haziran 2026*
*Bekçi herzaman uyanık.*
