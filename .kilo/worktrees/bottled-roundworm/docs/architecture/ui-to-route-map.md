# UI to Route Map

> STATUS: REFERENCE ONLY — NOT SSOT
> Kaynak: `routes/admin.php`, `routes/admin-ai.php`, `config/menus.php`

---

## L1: BUSINESS

| UI Page | URL | Controller | Main Service | Domain |
|---------|-----|------------|--------------|--------|
| Ana Dashboard | `/admin/dashboard` | `DashboardController` | — | Business |
| Agent Dashboard | `/admin/dashboard/agent` | `AgentDashboardController` | — | Business |
| Investor Dashboard | `/admin/dashboard/investor` | `InvestorDashboardController` | — | Business |
| İlanlarım | `/admin/ilanlarim` | `MyListingsController` | — | Property |
| Tüm İlanlar | `/admin/ilanlar` | `IlanCrudController` | `IlanService` | Property |
| Yeni İlan (Wizard) | `/admin/ilanlar/create-wizard` | `IlanCrudController` | `WizardOrchestrator` | Property |
| Draft Kaydet | `/admin/ilanlar/draft` | `IlanDraftController` | `WizardDraftService` | Property |
| Danışmanlar | `/admin/danisman` | `DanismanController` | — | Property |
| CRM Dashboard | `/admin/crm` | `CRMController` | — | CRM |
| CRM Dashboard V2 | `/admin/crm/dashboard-v2` | `CRMDashboardController` | — | CRM |
| Pipeline Kanban | `/admin/crm/pipeline` | `PipelineController` | — | CRM |
| Kişiler | `/admin/kisiler` | `KisiController` | — | CRM |
| Talepler | `/admin/talepler` | `TalepController` | — | CRM |
| Eşleştirmeler | `/admin/eslesmeler` | `EslesmeController` | `Matching\*` | CRM + AI |
| Takımlar | `/admin/takim-yonetimi/takimlar` | `TakimController` | — | Operations |
| Görevler | `/admin/takim-yonetimi/gorevler` | `GorevController` | — | Operations |
| Projeler | `/admin/takim-yonetimi/projeler` | `ProjeController` | — | Operations |
| Kanban Board | `/admin/takim-yonetimi/board` | `TakimController::board` | — | Operations |
| Finansal İşlemler | `/admin/finans/islemler` | `FinansalIslemController` | — | Finance |
| Finance Dashboard | `/admin/finance/dashboard` | `FinanceController` | — | Finance |
| Komisyonlar | `/admin/finance/commissions` | `FinanceController` | — | Finance |
| Primler | `/admin/finance/bonuses` | `FinanceController` | — | Finance |
| Agent Wallet | `/admin/my-wallet` | `WalletController` | — | Finance |
| Bildirimler | `/admin/notifications` | `NotificationController` | `NotificationService` | Business |

## L2: PROPERTY ENGINE

| UI Page | URL | Controller | Main Service | Domain |
|---------|-----|------------|--------------|--------|
| PropertyHub Dashboard | `/admin/property-hub` | `PropertyHubController` | `PropertyHubOrchestrator` | Property Engine |
| Özellik Havuzu | `/admin/property-hub/features` | `PropertyHubController` | — | Property Engine |
| Şablonlar | `/admin/property-hub/templates` | `PropertyHubController` | `TemplateResolver` | Property Engine |
| AI Şablon Tasarla | `/admin/property-hub/templates/ai-design/*` | `TemplateAiDesignController` | `TemplateAiDesignMutationService` | Property Engine + AI |
| Özellik Paketleri | `/admin/property-hub/packs` | `PropertyHubController` | — | Property Engine |
| Özellik Kategorileri | `/admin/ozellikler/kategoriler` | `OzellikKategoriController` | — | Property Engine |
| Kategori Matrisi | `/admin/property-type-manager` | `PropertyTypeController` | — | Property Engine |
| Field Dependencies | `/admin/property-type-manager/{id}/field-dependencies` | `FieldDependencyController` | `DependencyRuleEvaluator` | Property Engine |
| Feature Assignments | `/admin/property-type-manager/.../feature-assignments` | `FeatureAssignmentController` | `FeatureAssignmentValidator` | Property Engine |
| Bağımlılık Kuralları | `/admin/property-hub/dependency-rules` | `DependencyRuleController` | — | Property Engine |
| TKGM Parsel | `/admin/tkgm-parsel` | `TKGMParselController` | `TkgmQuery` | Property Engine |
| İlan Kategorileri | `/admin/ilan-kategorileri` | `IlanKategoriController` | — | Property Engine |
| Nexus Studio | `/admin/ilan-kategorileri/{id}/nexus-studio` | `IlanKategoriController` | — | Property Engine |
| Config Options | `/admin/config-options` | `ConfigOptionController` | — | Property Engine |

## L3: INTELLIGENCE

| UI Page | URL | Controller | Main Service | Domain |
|---------|-----|------------|--------------|--------|
| AI Dashboard | `/admin/ai/dashboard` | AI routes | — | Cortex |
| Cortex Analytics | `/admin/cortex` | `CortexAnalyticsController` | — | Cortex |
| Cortex Monitoring | `/admin/ai-monitor` | `AiMonitorController` | — | Cortex |
| AI Alan Önerileri | `/admin/property-hub/field-suggestions` | `FieldSuggestionController` | `AiFieldSuggestionEngine` | Cortex |
| AI İstatistikler | `/admin/ai/statistics` | AI routes | — | Cortex |
| AI Telemetry | `/admin/ai/telemetry` | `AITelemetryController` | — | Cortex |
| İstatistikler | `/admin/analitik/istatistikler` | `IstatistikController` | — | Cortex |
| Raporlar | `/admin/reports` | `ReportingController` | `ReportService` | Cortex |
| AI Governance | `/admin/analytics/ai-governance` | `AIGovernanceController` | — | Governance |
| Governance Dashboard | `/admin/governance` | `GovernanceController` | `GovernanceDashboardService` | Governance |
| Karar Kuyruğu | `/admin/governance/review-queue` | `DecisionEngineController` | `GovernanceService` | Governance |
| Karar Detay | `/admin/governance/decisions/{id}` | `DecisionEngineController` | `GovernanceService` | Governance |
| Özellik Sağlık Matrisi | `/admin/governance/feature-health` | `UpsGovernanceController` | — | Governance |
| Otonom Kontrol | `/admin/governance/autonomy` | `DecisionEngineController` | — | Governance |
| Aksiyon Döngüsü | `/admin/governance/action-dashboard` | `DecisionEngineController` | — | Governance |
| Yalıhan Bekçi | `/admin/yalihan-bekci` | `YalihanBekciController` | — | Governance |
| Denetim Kayıtları | `/admin/ups/audit-log` | `UPS\AuditLogController` | — | Governance |

## L4: AUTOMATION

| UI Page | URL | Controller | Main Service | Domain |
|---------|-----|------------|--------------|--------|
| Telegram Bot | `/admin/telegram-bot` | `TelegramBotController` | `TelegramService` | Automation |
| n8n Workflows | `/admin/integrations/n8n-workflows` | `IntegrationsController` | `N8nService` | Automation |
| Entegrasyonlar | `/admin/integrations` | `IntegrationsController` | — | Automation |
| Sesli Arama | `/admin/voice-search/settings` | `IntegrationsController` | `VoiceCommandProcessor` | Automation |

## L5: SYSTEM

| UI Page | URL | Controller | Main Service | Domain |
|---------|-----|------------|--------------|--------|
| Sistem Sağlığı | `/admin/ups/health` | `UpsHealthController` | — | System |
| Kullanıcılar | `/admin/kullanicilar` | `UserController` | — | System |
| Genel Ayarlar | `/admin/ayarlar` | `AyarlarController` | `SettingService` | System |
| AI Ayarları | `/admin/ai-settings` | `AISettingsController` | — | System |
| Adres Yönetimi | `/admin/address-management` | `AddressManagementController` | `AddressSyncService` | System |
| Blog | `/admin/blog` | `BlogController` | — | System |
| Page Analyzer | `/admin/page-analyzer` | `PageAnalyzerController` | — | System |
| Market Intelligence | `/admin/market-intelligence/dashboard` | `MarketIntelligenceController` | — | Intelligence |
| Cache Stats | `/monitoring/cache/stats` | `CacheStatsController` | — | System |
| Health Monitor | `/monitoring/health` | `HealthController` | — | System |
