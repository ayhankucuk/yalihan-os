# Architecture Lite (Yalıhan 2026)

> STATUS: Quick Reference (Not Authority)
> Son Güncelleme: 2026-05-14
> Detaylı SSOT: `docs/architecture/` (pages.md, domains.md, models.md, flows.md)

## 0. Global Configuration (SSOT)

- **PHP:** `8.2` (Production Standard)
- **Database:** `yalihan2026` (MySQL 8.0)
- **Production IP:** `168.138.101.124`
- **Domains:** `panel.yalihanemlak.com.tr`, `n8n.yalihanemlak.com.tr`
- **AI Primary Model:** `deepseek-chat` (V3/R1) — *NOT v4-flash*
- **Auth Pattern:** Repository Authority + Tenant Isolation

---

## 1. Side Menu Map

### L1: BUSINESS

**Dashboard**
- Ana Dashboard → `DashboardController`
- Agent Dashboard → `AgentDashboardController`
- Investor Dashboard → `InvestorDashboardController`

**İlanlar & Portföy**
- İlanlarım → `MyListingsController`
- Tüm İlanlar → `IlanCrudController`
- Yeni İlan (Wizard) → `IlanCrudController::createWizard` + `WizardOrchestrator`
- Danışmanlar → `DanismanController`

**CRM & Müşteri**
- CRM Dashboard → `CRMController`
- Kişiler → `KisiController`
- Kişilerim → `KisiController::kisilerim`
- Talepler → `TalepController`
- Eşleştirmeler → `EslesmeController` 🏷️ AI

**Takım & Operasyon**
- Takımlar → `TakimController`
- Görevler → `GorevController`
- Projeler → `ProjeController`
- Kanban Board → `TakimController::board`

**Finans & Satış**
- Finansal İşlemler → `FinansalIslemController` (Legacy V1)
- Finance Dashboard → `FinanceController` (Modern V2)
- Agent Wallet → `WalletController` ⚠️ Dual System Conflict (V1 vs V2)
- Komisyonlar / Primler / Tahsilatlar → `FinanceController`

**Bildirimler**
- Bildirimler → `NotificationController`

### L2: PROPERTY ENGINE

- Dashboard → `PropertyHubController`
- Özellik Havuzu → `PropertyHubController` (Feature CRUD)
- Şablonlar → `PropertyHubController` + `TemplateAiDesignController`
- Özellik Paketleri → `PropertyHubController` (Pack CRUD)
- Özellik Kategorileri → `OzellikKategoriController`
- Kategori Matrisi → `PropertyTypeController` + `FieldDependencyController` + `FeatureAssignmentController`
- Bağımlılık Kuralları → `DependencyRuleController`
- TKGM Parsel → `TKGMParselController`

### L3: INTELLIGENCE

**Cortex (AI)**
- AI Dashboard → `admin/ai.php` routes
- Cortex Analytics → `CortexAnalyticsController`
- Cortex Monitoring → `AiMonitorController`
- AI Alan Önerileri → `FieldSuggestionController` + `AiFieldSuggestionEngine`
- Kullanım & Maliyet → AI statistics routes
- İstatistikler → `IstatistikController`
- Tüm Raporlar → `ReportingController`
- Portfolio Doctor → Advisor routes 🏷️ AI

**Governance (SAB)**
- AI Kontrol Merkezi → `DecisionEngineController`
- Karar Kuyruğu → `DecisionEngineController::reviewQueue`
- Governance Dashboard → `GovernanceController::dashboard`
- Özellik Sağlık Matrisi → `UpsGovernanceController`
- AI Governance → `AIGovernanceController`
- Denetim Kayıtları → `UPS\AuditLogController`
- Otonom Kontrol → `DecisionEngineController::autonomyPanel`
- Aksiyon Döngüsü → `DecisionEngineController::actionDashboard`
- Yalıhan Bekçi (v2.1) → `YalihanBekciController` 🛡️ Cognitive
- The Learner → `LEARNED_PATTERNS.json` 🧠 Living Memory

### L4: AUTOMATION HUB

- Telegram Bot → `TelegramBotController` 🤖
- n8n Workflows → `IntegrationsController::n8nWorkflows`
- Entegrasyonlar → `IntegrationsController`
- Sesli Arama → `IntegrationsController::voiceSearchSettings`

### L5: SYSTEM

- Sistem Sağlığı → `UpsHealthController`
- Telescope → `/telescope` (external)
- Horizon → `/horizon` (external)
- Kullanıcılar → `UserController`
- Genel Ayarlar → `AyarlarController`
- AI Ayarları → `AISettingsController`
- Adres Yönetimi → `AddressManagementController`

---

## 2. Page Purpose (Kritik Sayfalar)

### Şablonlar (`/admin/property-hub/templates`)

**Amaç:** Feature template yönetimi — hangi kategori+yayın tipi hangi özellikleri gösterecek

**Core:**
- `FeatureAssignment` (polymorphic atama)
- `YayinTipiSablonu` / `MasterTemplate`
- `FeatureTemplateResolver` (çözümleme motoru — 12K satır)

**Risk:** ⚠️ Yanlış scope → Wizard boş döner (en yaygın bug)

---

### Template Edit (`/property-hub/templates/edit`)

**Amaç:** Kategori + listing_type bazlı feature atama/çıkarma

**Risk:** ⚠️ UI'da feature dolu görünür ama resolver NULL scope nedeniyle boş dönebilir

---

### AI Alan Önerileri (`/property-hub/field-suggestions`)

**Amaç:** AI ile field/feature önerisi üretme, onaylama, reddetme, uygulama

**Flow:**
```
AiFieldSuggestionEngine → suggestion üret
  → index (listele)
  → approve/reject (karar)
  → apply (ilana uygula)
  → rollback (geri al)
```

**Risk:** ⚠️ Öneri yok = AI provider down olabilir (false empty)

---

### Cortex Monitoring (`/admin/ai-monitor`)

**Amaç:** AI sağlık, latency, cost, provider durumu

**Risk:** ⚠️ 0 değerler = veri yok olabilir, sistem "healthy" görünür ama aslında ölü

---

### Governance Dashboard (`/admin/governance`)

**Amaç:** Proposal + audit + authority görünümü — `GovernanceDashboardService` ile parse

**Core:** `GovernanceDashboardService` (3-path authority fallback, malformed JSON resilience)

**Risk:** ⚠️ Watcher durmuş olabilir ama dashboard "healthy" gösterir

---

### Karar Kuyruğu (`/admin/governance/review-queue`)

**Amaç:** Onay/red bekleyen AI kararları — approve/reject/rollback/suppress

**Risk:** ⚠️ Yetkisiz onay → permission kontrolü atlanırsa kritik kararlar sessizce uygulanır

---

### Özellik Sağlık Matrisi (`/admin/governance/feature-health`)

**Amaç:** Feature assignment + template bütünlüğü — orphan, unused, missing feature tespiti

**Core:** `UpsGovernanceController` + AI proposal üretimi

---

### Otonom Kontrol (`/admin/governance/autonomy`)

**Amaç:** AI otonom seviyesi — Level 0 (manual) → Level 3 (autonomous)

**Kontroller:** Safe mode, dry run, action budget, pause/resume

**Risk:** ⚠️ Level 3'te AI bağımsız aksiyon alır — budget aşımı cascade failure tetikleyebilir

---

## 3. Core Models

| Model | Domain | Ne İşe Yarar |
|-------|--------|-------------|
| `Feature` | Property Engine | Tüm alanların tanımı (oda sayısı, m2 vb.) |
| `FeatureAssignment` | Property Engine | Template + kategori + listing_type bağı (polymorphic) |
| `MasterTemplate` / `YayinTipiSablonu` | Property Engine | Feature set container |
| `Ilan` | Property | Ana ilan modeli (72K satır — en büyük) |
| `Kisi` | CRM | Ana müşteri modeli (18K satır) |
| `AiFieldSuggestion` | AI | AI alan önerisi |
| `GovernanceDecision` | Governance | Karar kaydı (9.7K satır) |
| `GovernanceRollback` | Governance | Geri alma kaydı |
| `GovernanceAuditLog` | Governance | Denetim logu |
| `Il` / `Ilce` / `Mahalle` | Location | Canonical adres SSOT |

---

## 4. Core Flows

### Wizard Flow (İlan Oluşturma)
```
UI (create-wizard)
  → IlanCrudController::createWizard
    → WizardOrchestrator
      → Step 1: Lokasyon (Il/Ilce/Mahalle — Canonical SSOT)
      → Step 2: Kategori + Yayın Tipi
        → FeatureTemplateResolver::resolve()
          → 1. Exact match (kategori + yayin_tipi)
          → 2. Fallback: ana_kategori
          → 3. Fallback: sadece yayin_tipi (kategori NULL)
          → 4. Fallback: global (her şey NULL)
        → Feature listesi döner
      → Step 3: Detay + Fiyat + Foto
    → store → Draft veya Persist
```

**KRİTİK:**
- Exact match + 3 fallback katmanı olmalı
- NULL scope unutulursa → **0 feature** (en yaygın bug)
- Kategori değişince mevcut özellikler temizlenmeli

---

### AI Suggestion Flow
```
UI → ListingSmartSuggestionService
  → 1. Normalize (mevcut veri)
  → 2. Rule suggest (kural bazlı)
  → 3. AI suggest (LLM API — OpenAI/Ollama/Gemini)
  → 4. Consistency warn (tutarsızlık)
  → 5. Filter & Package
  → UI'a döner (Accept / Reject butonları)
```

**Auto-save YASAK** — kullanıcı onayı zorunlu

---

### Governance Flow
```
Karar üret → GovernanceDecision (status: pending)
  → review-queue (bekleyen kararlar)
    → approve → GovernanceAuditLog
    → reject → GovernanceAuditLog
    → rollback → GovernanceRollback
    → suppress → GovernanceSuppression
```

---

## 5. Known Risks (Gerçek Hayat)

| Risk | Ne Olur | Nasıl Anlaşılır |
|------|---------|-----------------|
| **Wizard 0 feature** | Wizard Step 2'de hiç özellik göstermez | FeatureTemplateResolver NULL scope kontrolü yapılmamış |
| **AI "healthy" ama ölü** | Monitoring 0 gösterir, dashboard green | Provider down ama fallback sessizce başarısız |
| **FK Constraint Blocker** | Seeder/Factory patlar, testler geçmez | `yayin_tipi_sablonlari` + `ai_feature_usages` kısıtları |
| **Model Drift** | Missing `transactions` table | Model var ama tablo yok (Finance V2) |

---

## 6. Mental Model

```
L1: BUSINESS        = günlük iş (ilan, müşteri, finans)
L2: PROPERTY ENGINE  = veri ve yapı (feature, template, schema)
L3: INTELLIGENCE     = üretim + kontrol
    ├── Cortex      = AI beyin (üretir, önerir)
    └── Governance  = SAB karar motoru (kontrol eder, onaylar)
L4: AUTOMATION       = dış dünya (Telegram, n8n, entegrasyonlar)
L5: SYSTEM           = altyapı (ayarlar, sağlık, izleme)
```

**Veri akışı:**
```
Property Engine → Feature/Template tanımla
  → Wizard kullanır
  → Cortex AI öneri üretir
  → Governance onaylar/reddeder
  → Ilan'a uygulanır
```

---

## 7. Service Katmanı (Kritik Servisler)

| Servis | Ne Yapar | Risk |
|--------|---------|------|
| `FeatureTemplateResolver` | Wizard'a hangi feature'lar gelecek belirler | NULL scope → 0 feature |
| `WizardOrchestrator` | Wizard 3 adım orkestrasyon | Legacy, basit delegasyon |
| `WizardDraftService` | Draft kaydet/yükle/temizle | Son yazan kazanır |
| `AiFieldSuggestionEngine` | AI alan önerisi üret | Provider down → boş |
| `GovernanceService` | Karar üret/onayla/reddet | Permission bypass riski |
| `GovernanceDashboardService` | Dashboard veri parse | Malformed JSON resilience |
| `ListingSmartSuggestionService` | İlan bazlı AI öneri | Auto-save yasak |
| `IlanService` | İlan CRUD orchestrator | Büyük servis |
| `LeadService` | Lead yönetimi | CRM domain boundary |
| `FinancialLedgerService` | Çift taraflı muhasebe | Immutable — update/delete yasak |

---

## 8. Sidebar'da Olmayan Gizli Sayfalar

| Sayfa | URL | Neden Gizli |
|-------|-----|-------------|
| Yazlık Kiralama | `/admin/yazlik-kiralama` | Ayrı modül |
| İlan Takvimi | `/admin/ilanlar/{ilan}/calendar` | İlan içi erişim |
| Danışman AI | `/admin/danisman-ai` | AI sub-module |
| Blog | `/admin/blog` | İçerik yönetimi |
| Market Intelligence | `/admin/market-intelligence` | Analiz |
| Config Options | `/admin/config-options` | Gelişmiş ayar |
| Nexus Studio | `.../nexus-studio` | Inheritance UI |
| UPS Versions | `/admin/ups/versions` | Geçmiş/rollback |
| Page Analyzer | `/admin/page-analyzer` | SEO |
| Monitoring | `/monitoring/*` | Cache/Health |

---

## 9. Notlar

- Bu dosya **quick reference**'tır — authority değildir
- Code gerçek kaynaktır — şüphede → kodu oku
- Detaylı SSOT: `docs/architecture/` dizini (pages.md, domains.md, models.md, flows.md)
- `config/menus.php` → sidebar'ın canonical kaynağı
- `routes/admin.php` (1711 satır) → tüm admin route'ların kaynağı
