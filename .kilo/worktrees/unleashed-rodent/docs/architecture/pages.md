# Yalıhan Emlak — Sayfa Haritası (Page SSOT)

> ⚠️ **Authority Rule:** Bu doküman READ-ONLY sistem yansımasıdır.
> Source of truth = code + DB + config. Kod ile doküman çelişirse → kod kazanır.
>
> Son Güncelleme: 2026-04-11
> Kaynak: `config/menus.php` + `routes/admin.php` + `routes/admin-ai.php`

---

## Mimari Katmanlar

```
L1: BUSINESS        — Günlük operasyonel kullanım
L2: PROPERTY ENGINE  — Şema, özellik, şablon yönetimi
L3: INTELLIGENCE     — Cortex (AI beyin) + Governance (SAB karar motoru)
L4: AUTOMATION       — Çalıştırma, dış kanallar, entegrasyonlar
L5: SYSTEM           — Altyapı, teknik yönetim, ayarlar
```

---

## L1: BUSINESS — Günlük Operasyonel Kullanım

### 1️⃣ Dashboard

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **Ana Dashboard** | `/admin/dashboard` | `DashboardController` | — | Ana panel: İlan, müşteri, görev özetleri |
| **Agent Dashboard** | `/admin/dashboard/agent` | `AgentDashboardController` | — | Danışman üretkenlik metrikleri |
| **Investor Dashboard** | `/admin/dashboard/investor` | `InvestorDashboardController` | — | Yatırımcı görünümü (CQRS Read Model) |
| **Dashboard Stats API** | `/admin/dashboard/stats` | `DashboardController::getDashboardStats` | — | AJAX: Dashboard istatistikleri |

---

### 2️⃣ İlanlar & Portföy

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **İlanlarım** | `/admin/ilanlarim` | `MyListingsController` | — | Danışmanın kendi ilanları (ownership-aware) |
| **İlanlarım AI Analiz** | `/admin/ilanlarim/ai-analysis` | `MyListingsController::aiAnalysis` | — | AI portföy analizi |
| **Tüm İlanlar** | `/admin/ilanlar` | `IlanCrudController` | `IlanService` | İlan CRUD listesi |
| **Yeni İlan (Wizard)** | `/admin/ilanlar/create-wizard` | `IlanCrudController::createWizard` | `WizardOrchestrator` | 3 adımlı ilan oluşturma |
| **İlan Düzenle** | `/admin/ilanlar/{ilan}/edit` | `IlanCrudController::edit` | `IlanService` | İlan düzenleme |
| **Draft Kaydet** | `/admin/ilanlar/draft` | `IlanDraftController` | `WizardDraftService` | Taslak kaydet/yükle/temizle |
| **İlan Arama** | `/admin/ilanlar/search` | `IlanSearchController` | — | AJAX: İlan arama/filtreleme |
| **Danışmanlar** | `/admin/danisman` | `DanismanController` | — | Danışman CRUD + performans raporu |
| **İlan Takvimi** | `/admin/ilanlar/{ilan}/calendar` | `IlanCalendarController` | `ReservationService` | Takvim + rezervasyon yönetimi ⚠️ Sidebar'da yok |
| **İlan Fotoğrafları** | İlan edit içinde | `IlanPhotoController`, `PhotoController` | `FlexibleStorageManager` | Fotoğraf CRUD |
| **İlan Kalite Dashboard** | Route mevcut | `IlanQualityDashboardController` | `ListingQualityService` | Kalite skoru görünümü ⚠️ Sidebar'da yok |

**Yazlık Kiralama** ⚠️ Sidebar'da yok

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **Yazlık Listesi** | `/admin/yazlik-kiralama` | `YazlikKiralamaController` | `YazlikKiralamaService` | Yazlık ilan CRUD |
| **Yazlık Takvim** | `/admin/yazlik-kiralama/takvim` | `TakvimController` | — | Sezon takvimi + rezervasyon |
| **Sezon Yönetimi** | `/admin/yazlik-kiralama/takvim/sezonlar` | `TakvimController::sezonlar` | — | Sezon CRUD |

---

### 3️⃣ CRM & Müşteri

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **CRM Dashboard** | `/admin/crm` | `CRMController` | — | CRM ana panel |
| **CRM Dashboard V2** | `/admin/crm/dashboard-v2` | `CRMDashboardController` | — | Gelişmiş CRM paneli |
| **Pipeline Kanban** | `/admin/crm/pipeline` | `PipelineController` | — | Müşteri pipeline (sürükle-bırak) |
| **Lead Sources** | `/admin/crm/lead-sources` | `CRMDashboardController::leadSourceAnalytics` | — | Lead kaynak analizi |
| **Kişiler** | `/admin/kisiler` | `KisiController` | — | Kişi CRUD + AI analiz |
| **Kişilerim** | `/admin/kisilerim` | `KisiController::kisilerim` | — | Danışmanın kendi kişileri |
| **Talepler** | `/admin/talepler` | `TalepController` | — | Talep CRUD + eşleşen ilanlar |
| **Eşleştirmeler** | `/admin/eslesmeler` | `EslesmeController` | `Matching\*` | Talep-İlan eşleştirme (AI destekli) |
| **Talep-Portföy** | `/admin/talep-portfolyo` | `TalepPortfolyoController` | — | Talep bazlı portföy eşleştirme ⚠️ Sidebar'da yok |
| **Leads** | `/admin/leads` | `LeadController` | `LeadService` | Lead listesi (read-only) ⚠️ Sidebar'da yok |
| **Toplu Kişi** | `/admin/bulk-kisi` | `BulkKisiController` | — | Toplu kişi import/export ⚠️ Sidebar'da yok |
| **Kişi Notları** | `/admin/kisi-not` | `KisiNotController` | — | Kişi not CRUD ⚠️ Sidebar'da yok |
| **Eşleştirme Geri Bildirim** | `/matching/feedback` | `MatchingFeedbackController` | — | Eşleştirme sonuç takibi ⚠️ Sidebar'da yok |

---

### 4️⃣ Takım & Operasyon

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **Takımlar** | `/admin/takim-yonetimi/takimlar` | `TakimController` | — | Takım CRUD + üye yönetimi |
| **Takım Performans** | `/admin/takim-yonetimi/takimlar/performans` | `TakimController::performans` | — | Takım performans raporları |
| **Görevler** | `/admin/takim-yonetimi/gorevler` | `GorevController` | — | Görev CRUD + toplu atama |
| **Görev Raporları** | `/admin/takim-yonetimi/gorevler/raporlar` | `GorevController::raporlar` | — | Görev tamamlanma raporları |
| **Projeler** | `/admin/takim-yonetimi/projeler` | `ProjeController` | — | Proje CRUD |
| **Kanban Board** | `/admin/takim-yonetimi/board` | `TakimController::board` | — | Görev Kanban panosu |

---

### 5️⃣ Finans & Satış

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **Finansal İşlemler** | `/admin/finans/islemler` | `FinansalIslemController` | — | İşlem CRUD + AI analiz |
| **Finance Dashboard** | `/admin/finance/dashboard` | `FinanceController` | — | Finans ana paneli |
| **Komisyonlar** | `/admin/finance/commissions` | `FinanceController::commissions` | — | Hakediş onay/ödeme |
| **Tahsilatlar** | `/admin/finance/transactions` | `FinanceController::transactions` | — | Tahsilat kayıt/doğrulama |
| **Primler** | `/admin/finance/bonuses` | `FinanceController::bonuses` | — | Prim hesaplama/ödeme |
| **Satışlar** | `/admin/satislar/create` | Redirect → İstatistikler | — | Satış istatistikleri |
| **Agent Wallet** | `/admin/my-wallet` | `WalletController` | — | Danışman self-service: komisyon, prim, performans ⚠️ Sidebar'da yok |

---

### 6️⃣ Bildirimler

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **Bildirimler** | `/admin/notifications` | `NotificationController` | `NotificationService` | Bildirim listesi + okundu/okunmadı |
| **Admin Notifications** | `/admin/admin-notifications` | `AdminNotificationController` | `AdminNotificationService` | Rezervasyon bildirimleri ⚠️ Sidebar'da yok |
| **Activity Events** | `/admin/activity-events` | `AdminActivityEventController` | `AdminActivityEventService` | Etkinlik akışı (read-only) ⚠️ Sidebar'da yok |

---

## L2: PROPERTY ENGINE — Şema, Özellik, Şablon

### 7️⃣ Property Engine

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **Dashboard** | `/admin/property-hub` | `PropertyHubController` | — | Property Hub ana paneli |
| **Özellik Havuzu** | `/admin/property-hub/features` | `PropertyHubController` | — | Feature CRUD + toggle/archive |
| **Özellik Oluştur** | `/admin/property-hub/features/create` | `PropertyHubController::createFeature` | — | Yeni özellik ekleme |
| **Şablonlar** | `/admin/property-hub/templates` | `PropertyHubController` | `TemplateResolver` | Şablon listesi + atama |
| **Şablon Düzenle** | `/admin/property-hub/templates/edit` | `PropertyHubController::editTemplate` | — | Şablon feature ataması |
| **AI Şablon Tasarla** | `/admin/property-hub/templates/ai-design/*` | `TemplateAiDesignController` | `TemplateAiDesignMutationService` | AI ile yapısal şablon tasarımı |
| **AI Pipeline** | `/admin/property-hub/templates/ai-pipeline/*` | `TemplateAiPipelineController` | — | Async AI şablon önerileri |
| **Özellik Paketleri** | `/admin/property-hub/packs` | `PropertyHubController` | — | Feature Pack CRUD + uygulama |
| **Özellik Kategorileri** | `/admin/ozellikler/kategoriler` | `OzellikKategoriController` | — | Semantic özellik grupları |
| **Kategori Matrisi** | `/admin/property-type-manager` | `PropertyTypeController` | — | Kategori → Yayın Tipi → Feature ataması |
| **Field Dependencies** | `/admin/property-type-manager/{id}/field-dependencies` | `FieldDependencyController` | `DependencyRuleEvaluator` | Alan bağımlılıkları (visible_if, required_if) |
| **Feature Assignments** | `/admin/property-type-manager/property-type/{id}/*` | `FeatureAssignmentController` | `FeatureAssignmentValidator` | Feature atama/kaldırma/toplu kayıt |
| **Bağımlılık Kuralları** | `/admin/property-hub/dependency-rules` | `DependencyRuleController` | — | Bağımlılık kuralı CRUD |
| **TKGM Parsel** | Route mevcut | `TKGMParselController` | `TkgmQuery` | TKGM parsel sorgulama |
| **İlan Kategorileri** | `/admin/ilan-kategorileri` | `IlanKategoriController` | — | Kategori CRUD + Nexus Studio ⚠️ Sidebar'da yok |
| **Nexus Studio** | `/admin/ilan-kategorileri/{id}/nexus-studio` | `IlanKategoriController::nexusStudio` | — | Görsel inheritance yönetimi ⚠️ Sidebar'da yok |
| **Feature Manager** | `/admin/ilan-kategorileri/{id}/feature-manager` | `IlanKategoriController::featureManager` | — | Recursive inheritance UI ⚠️ Sidebar'da yok |
| **Config Options** | `/admin/config-options` | `ConfigOptionController` | — | Kategori/Yayın Tipi bazlı config ⚠️ Sidebar'da yok |
| **Yayın Tipi Şablonları** | `/admin/property-hub/yayin-tipi-sablonlari` | `TemplateController` | — | Master template CRUD ⚠️ Sidebar'da yok |
| **UPS Feature Packs** | `/admin/ups/feature-packs` | `UpsFeaturePackController` | — | UPS özellik paketleri ⚠️ Sidebar'da yok |
| **UPS Versions** | `/admin/ups/versions` | `UpsVersionController` | — | UPS versiyon geçmişi + rollback ⚠️ Sidebar'da yok |
| **UPS Template Versions** | `/admin/ups/templates/{id}/versions` | `UPS\TemplateVersionController` | — | Şablon versiyon geçmişi + karşılaştırma ⚠️ Sidebar'da yok |
| **UPS Feature Whitelist** | `/admin/ups-feature-whitelist` | `UpsFeatureWhitelistController` | — | Kategori bazlı feature whitelist ⚠️ Sidebar'da yok |
| **Marketing Templates** | `/admin/ups/marketing/templates` | `MarketingAssetController` | — | Pazarlama asset şablonları ⚠️ Sidebar'da yok |
| **PropertyHub Analytics** | `/admin/property-hub/analytics` | `PropertyHubController::analytics` | — | PropertyHub analitikleri ⚠️ Sidebar'da yok |

---

## L3: INTELLIGENCE — Cortex + Governance

### 8️⃣ Cortex (AI Beyin Katmanı)

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **AI Dashboard** | `/admin/ai/dashboard` | AI routes (`admin/ai.php`) | — | AI ana panel |
| **Cortex Analytics** | `/admin/cortex` | `CortexAnalyticsController` | — | Cortex gelir/analiz paneli |
| **Cortex Monitoring** | Route mevcut | `AiMonitorController` | — | AI sistem izleme |
| **AI Alan Önerileri** | `/admin/property-hub/field-suggestions` | `FieldSuggestionController` | `AiFieldSuggestionEngine` | AI alan önerisi üretme/onaylama/reddetme/uygulama |
| **Kullanım & Maliyet** | `/admin/ai/statistics` | AI routes | — | AI kullanım istatistikleri |
| **AI Telemetry** | `/admin/ai/telemetry` | `AITelemetryController` | — | Maliyet, provider performans, hata analizi |
| **İstatistikler** | `/admin/analitik/istatistikler` | `IstatistikController` | — | Genel/İlan/Satış/Finans/Müşteri istatistikleri |
| **Tüm Raporlar** | `/admin/reports` | View + `ReportingController` | `ReportService` | Raporlama ana paneli |
| **Portfolio Doctor** | `/advisor/portfolio-doctor` | Advisor routes | — | AI portföy sağlık kontrolü |
| **Intelligence Board** | `/admin/intelligence/opportunity-board` | `IntelligenceDashboardController` | — | Fırsat panosu ⚠️ Sidebar'da yok |
| **AI Core Test** | `/admin/ai-core-test` | `AICoreTestController` | — | AI test arayüzü ⚠️ Sidebar'da yok |
| **AI Category** | `/admin/ai-category` | `AICategoryController` | `AICategoryManager` | AI kategori analizi ⚠️ Sidebar'da yok |
| **Danışman AI** | `/admin/danisman-ai` | `DanismanAIController` | `AIService` | Danışman AI asistanı + prompt interface ⚠️ Sidebar'da yok |
| **AI Settings** | `/admin/ai-settings` (duplicate route) | `AISettingsController` | — | AI provider ayarları ⚠️ Sistem altında da var |
| **Visibility Metrics** | `/admin/analytics/visibility` | `VisibilityController` | — | Görünürlük metrikleri ⚠️ Sidebar'da yok |
| **Context7 Analytics** | `/admin/analytics/context7` | `AnalyticsDashboardController` | — | Proje sağlık paneli ⚠️ Sidebar'da yok |
| **Market Intelligence** | `/admin/market-intelligence/dashboard` | `MarketIntelligenceController` | — | Pazar istihbaratı ⚠️ Sidebar'da yok |

---

### 9️⃣ Governance (SAB Karar Motoru)

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **AI Kontrol Merkezi** | `/admin/governance/intelligence-center` | `DecisionEngineController` | `GovernanceService` | Multi-agent intelligence center |
| **Karar Kuyruğu** | `/admin/governance/review-queue` | `DecisionEngineController::reviewQueue` | `GovernanceDashboardService` | Onay/red bekleyen kararlar |
| **Karar Detay** | `/admin/governance/decisions/{id}` | `DecisionEngineController::show` | — | Tek karar detayı + onay/red/rollback |
| **Governance Dashboard** | `/admin/governance` | `GovernanceController::dashboard` | `GovernanceDashboardService` | Governance ana paneli |
| **Özellik Sağlık Matrisi** | `/admin/governance/feature-health` | `UpsGovernanceController` | — | Feature health matrix + AI proposal |
| **AI Governance** | `/admin/analytics/ai-governance` | `AIGovernanceController` | — | AI Prompt Governance telemetrisi |
| **Denetim Kayıtları** | `/admin/ups/audit-log` | `UPS\AuditLogController` | — | UPS audit log + export |
| **Otonom Kontrol** | `/admin/governance/autonomy` | `DecisionEngineController::autonomyPanel` | — | Otonom seviye, pause/resume, dry-run, bütçe |
| **Aksiyon Döngüsü** | `/admin/governance/action-dashboard` | `DecisionEngineController::actionDashboard` | — | Decision → Action → Feedback loop |
| **Yalıhan Bekçi** | `/admin/yalihan-bekci` | `YalihanBekciController` | — | Bekçi monitoring + live data + run check |
| **Karar Geçmişi** | `/admin/governance/decision-history` | `DecisionEngineController::history` | — | Tüm karar geçmişi ⚠️ Sidebar'da yok |
| **Suppression Listesi** | `/admin/governance/suppressions` | `DecisionEngineController::suppressionList` | — | Bastırılmış kararlar ⚠️ Sidebar'da yok |

---

## L4: AUTOMATION — Çalıştırma ve Dış Kanallar

### 🔟 Automation Hub

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **Telegram Bot** | `/admin/telegram-bot` | `TelegramBotController` | `TelegramService` | Bot yönetimi, webhook, test mesaj |
| **n8n Workflows** | `/admin/integrations/n8n-workflows` | `IntegrationsController::n8nWorkflows` | `N8nService` | n8n workflow yönetimi |
| **Entegrasyonlar** | `/admin/integrations` | `IntegrationsController` | — | Entegrasyon CRUD + test |
| **Sesli Arama** | `/admin/voice-search/settings` | `IntegrationsController::voiceSearchSettings` | `VoiceCommandProcessor` | Sesli arama ayarları |
| **Bildirim Ayarları** | `/admin/notifications/settings` | `IntegrationsController::notificationSettings` | — | Bildirim kanal ayarları ⚠️ Sidebar'da yok |

---

## L5: SYSTEM — Altyapı ve Teknik Yönetim

### 1️⃣1️⃣ Sistem

| Sayfa | URL | Controller | Servis | Amaç |
|-------|-----|------------|--------|------|
| **Sistem Sağlığı** | `/admin/ups/health` | `UpsHealthController` | — | UPS sistem sağlığı + repair |
| **Telescope** | `/telescope` (external) | — | — | Laravel Telescope |
| **Horizon** | `/horizon` (external) | — | — | Laravel Horizon |
| **Kullanıcılar** | `/admin/kullanicilar` | `UserController` | — | Kullanıcı CRUD |
| **Genel Ayarlar** | `/admin/ayarlar` | `AyarlarController` | `SettingService` | Sistem ayarları + dil/para birimi |
| **AI Ayarları** | `/admin/ai-settings` | `AISettingsController` | — | AI provider/model/API key yönetimi |
| **Adres Yönetimi** | `/admin/address-management` | `AddressManagementController` | `AddressSyncService` | İl/İlçe/Mahalle canonical SSOT |
| **Adres Yönetimi (V2)** | `/admin/adres-yonetimi` | `AdresYonetimiController` | `TurkiyeAPIService` | TürkiyeAPI sync + CRUD ⚠️ Sidebar'da yok |
| **Blog Yönetimi** | `/admin/blog` | `BlogController` | — | Blog post/kategori/tag/yorum CRUD ⚠️ Sidebar'da yok |
| **Page Analyzer** | `/admin/page-analyzer` | `PageAnalyzerController` | — | SEO sayfa analizi + rerun ⚠️ Sidebar'da yok |
| **Site/Apartman** | `/admin/site-apartman` | `SiteApartmanController` | `SiteApartmanService` | Site/Apartman CRUD ⚠️ Sidebar'da yok |
| **Anahtar Yönetimi** | `/admin/anahtar-yonetimi` | `AnahtarYonetimiController` | — | Anahtar teslim/iade takibi ⚠️ Sidebar'da yok |
| **Wikimapia Search** | `/admin/wikimapia-search` | `WikimapiaSearchController` | `WikimapiaService` | Site/Apartman harita araması ⚠️ Sidebar'da yok |
| **UPS Analytics** | `/admin/ups/analytics` | `UpsAnalyticsController` | `UpsAnalyticsService` | UPS dashboard analitikleri ⚠️ Sidebar'da yok |
| **Cache Stats** | `/monitoring/cache/stats` | `CacheStatsController` | — | Cache istatistikleri ⚠️ Sidebar'da yok |
| **Health Monitor** | `/monitoring/health` | `HealthController` | — | Sistem sağlık paneli ⚠️ Sidebar'da yok |
| **Analytics Dashboard** | `/admin/analytics` | `AnalyticsController` | — | Analitik ana paneli ⚠️ Sidebar'da yok |
| **Harita** | `/admin/harita` | `MapController` | — | Harita görünümü ⚠️ Sidebar'da yok |

---

## Danışman Self-Service (Ayrı Rol)

| Sayfa | URL | Controller | Amaç |
|-------|-----|------------|------|
| **Profil Düzenle** | `/danisman/profil` | `Danisman\ProfilController` | Danışman kendi profilini düzenler |
| **Şifre Güncelle** | `/danisman/profil/sifre` | `Danisman\ProfilController::updatePassword` | Şifre değiştirme |

---

## ⚠️ Sidebar'da Olmayan Sayfa Özeti

Aşağıdaki sayfalar aktif route olarak mevcut ama `config/menus.php` sidebar'ında görünmüyor:

**İncelenmesi gereken:** Sidebar'a eklenmeli mi, başka menüye taşınmalı mı, yoksa deprecated mı?

| Sayfa | Durum Tahmini |
|-------|--------------|
| Yazlık Kiralama | Ayrı modül — sidebar'a eklenebilir (L1) |
| İlan Takvimi / Rezervasyon | İlan detay içinde erişim |
| Talep-Portföy | Aktif ama kısmen commented out |
| Bulk Kişi / Kişi Notları | CRM derinlik sayfaları |
| Danışman AI | Cortex altına taşınabilir |
| AI Core Test / AI Category | Sistem veya Cortex altına taşınabilir |
| Market Intelligence | Cortex altına taşınabilir |
| Blog Yönetimi | Sistem altına eklenebilir |
| Config Options | Property Engine altına eklenebilir |
| Nexus Studio / Feature Manager | Property Engine derinlik sayfaları |
| UPS Versions / Template Versions | Governance veya Property Engine altında |
| Marketing Templates | Automation Hub altına eklenebilir |
| Page Analyzer | Sistem altına eklenebilir |
| Site/Apartman / Anahtar Yönetimi | İlanlar & Portföy altına eklenebilir |
| Wikimapia Search | Sistem altına eklenebilir |
| Cache Stats / Health Monitor | Sistem altında zaten `/monitoring` olarak var |

---

## 🔗 Kritik Servis Cross-Reference (Service Layer Mapping)

> Her kritik servis: Input → Output → Risk → Hangi sayfalarda kullanılıyor

### FeatureTemplateResolver

```
Input:  listing_type_id, category_id (sub veya main)
Output: Feature[] (çözümlenmiş özellik listesi)
```

**Kullanıldığı sayfalar:**
- `/admin/ilanlar/create-wizard` (Wizard Step 2) → feature listesi çeker
- `/admin/property-hub/templates` → şablon önizleme
- `/admin/property-type-manager` → kategori matrisi kontrol

**Çözümleme sırası:**
1. Exact match: `kategori_id` + `yayin_tipi_id` tam eşleşme
2. Parent fallback: `alt_kategori` → `ana_kategori` üzerinden arama
3. Listing-type only: sadece `yayin_tipi_id` (kategori NULL)
4. Global: her ikisi NULL

**⚠️ RISK: Global assignment leakage**
- NULL scope fallback yoksa → Wizard 0 feature döndürür (en yaygın bug)
- Template var ama resolver eşleşemez → UI dolu, wizard boş
- Kategori değişince eski feature'lar temizlenmezse → stale data

---

### AiFieldSuggestionEngine

```
Input:  kategori_id, listing_type_id, mevcut field set
Output: AiFieldSuggestion[] (AI alan önerisi seti)
```

**Kullanıldığı sayfalar:**
- `/admin/property-hub/field-suggestions` → generate/approve/reject/apply/rollback

**⚠️ RISK: False empty**
- Provider (OpenAI/Ollama) down → boş sonuç döner ama hata mesajı yok
- Auto-save YASAK — kullanıcı onayı zorunlu
- Rate limiting: 10-20 req/min/user

---

### GovernanceService

```
Input:  decision_type, context, AI/rule output
Output: GovernanceDecision (status: pending/approved/rejected/rolled_back)
```

**Kullanıldığı sayfalar:**
- `/admin/governance/review-queue` → approve/reject/rollback
- `/admin/governance/intelligence-center` → multi-agent karar koordinasyonu
- `/admin/governance/autonomy` → otonom seviye yönetimi

**⚠️ RISK: Permission bypass**
- Permission kontrolü atlanırsa kritik kararlar sessizce uygulanır
- Otonom Level 3'te budget kontrolü yoksa cascade failure
- Rollback verisi yoksa geri alma imkansız

---

### WizardOrchestrator + WizardContextService

```
Input:  HTTP form data (3 adım: lokasyon → kategori → detay)
Output: Ilan (draft veya published)
```

**Kullanıldığı sayfalar:**
- `/admin/ilanlar/create-wizard` → 3 adımlı ilan oluşturma
- `/admin/ilanlar/draft` → taslak kaydet/yükle

**Bağımlı servisler:**
- `FeatureTemplateResolver` → Step 2'de feature listesi
- `WizardDraftService` → draft persistence (lockForUpdate)
- `WizardGateService` → validation gate'leri
- `WizardAIAssistantService` → AI asistan (opsiyonel)
- `ListingSmartSuggestionService` → AI öneriler

**⚠️ RISK: Draft çakışma**
- Aynı kullanıcı + birden fazla sekme → son yazan kazanır
- Kategori değişikliği → mevcut özellikler temizlenmeli ama olmayabilir

---

### ListingSmartSuggestionService

```
Input:  Ilan (mevcut verisi)
Output: Suggestion[] (5 pipeline: normalize → rule → AI → consistency → filter)
```

**Kullanıldığı sayfalar:**
- `/admin/ilanlar/create-wizard` (Step 3) → AI öneriler
- `/admin/ilanlarim/ai-analysis` → portföy AI analizi

**⚠️ RISK: Auto-save YASAK**
- AI önerileri kesinlikle otomatik kaydedilmez
- Accept/Reject → telemetry'e girdi üretir (flywheel)

---

### GovernanceDashboardService

```
Input:  file paths (authority.json, audit logs, proposals)
Output: Dashboard data (proposals, audit, authority health)
```

**Kullanıldığı sayfalar:**
- `/admin/governance` → governance ana paneli

**⚠️ RISK: Malformed JSON resilience**
- 3-path authority fallback (dosya corrupt olabilir)
- Watcher durmuş olabilir ama dashboard healthy gösterir

---

### FinancialLedgerService

```
Input:  debit_account, credit_account, amount, currency
Output: LedgerEntry[] (çift taraflı — debit + credit)
```

**Kullanıldığı sayfalar:**
- `/admin/finance/*` → tüm finansal işlemler

**⚠️ RISK: IMMUTABLE**
- `LedgerEntry` update/delete → `RuntimeException` (Observer enforced)
- Düzeltme için yeni compensation entry gerekir
- `lockForUpdate()` concurrency protection zorunlu

