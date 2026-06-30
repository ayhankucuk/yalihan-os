# Yalıhan Emlak — Model Kataloğu (Model SSOT)

> ⚠️ **Authority Rule:** Bu doküman READ-ONLY sistem yansımasıdır.
> Source of truth = code + DB + config. Kod ile doküman çelişirse → kod kazanır.
>
> Son Güncelleme: 2026-04-11
> Kaynak: `app/Models/` (148 dosya + 5 subdirectory)
> Detaylı domain tanımları için: `docs/architecture/domains.md`

---

## Hızlı Arama Tablosu

"Bu model hangi domain'e ait?" sorusuna anında cevap.

### Property Domain (İlan)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `Ilan` | `ilanlar` | IlanCrudController, IlanService, Wizard, Search |
| `IlanKategori` | `ilan_kategorileri` | IlanKategoriController, FeatureTemplateResolver |
| `IlanResim` | `ilan_resimleri` | PhotoController, IlanPhotoController |
| `IlanFotografi` | `ilan_fotograflari` | Photo yönetimi |
| `IlanMetin` | `ilan_metinleri` | İlan detay içeriği |
| `IlanNot` | `ilan_notlari` | İlan notu ekleme |
| `IlanTaslak` | `ilan_taslaklari` | WizardDraftService |
| `IlanPriceHistory` | `ilan_price_history` | Fiyat takibi |
| `IlanReservation` | `ilan_reservations` | ReservationService, IlanCalendarController |
| `IlanTakvimSync` | `ilan_takvim_sync` | CalendarSyncService |
| `IlanFavori` | `ilan_favorileri` | FavoriService |
| `IlanGoruntulenmeGunluk` | `ilan_goruntulenme_gunluk` | Analytics |
| `IlanEmbedding` | `ilan_embeddings` | EmbeddingService |
| `IlanOzellik` | `ilan_ozellikleri` | İlan-özellik pivot |
| `ListingStateTransition` | `listing_state_transitions` | Publish lifecycle |
| `ListingTranslation` | `listing_translations` | Çeviri |
| `Photo` | `photos` | FlexibleStorageManager |
| `AdvisorPhoto` | `advisor_photos` | Danışman fotoğrafı |
| `YayinTipi` | `yayin_tipleri` | PropertyTypeController |
| `AltKategoriYayinTipi` | `alt_kategori_yayin_tipleri` | Kategori matrisi |
| `PropertyConfigVersion` | `property_config_versions` | PropertyHubVersionController |
| `PropertyReservation` | `property_reservations` | ReservationService |

### Property / Yazlık-Kiralama (Alt Domain)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `YazlikDetail` | `yazlik_details` | YazlikKiralamaController |
| `YazlikFiyatlandirma` | `yazlik_fiyatlandirma` | VillaPricingCalculatorService |
| `YazlikRezervasyon` | `yazlik_rezervasyonlar` | ReservationService |
| `Season` | `seasons` | TakvimController |
| `RentalEvKarti` | `rental_ev_kartlari` | RentalKpiService |
| `PropertyAvailability` | `property_availabilities` | Calendar |
| `PropertyCalendarFeed` | `property_calendar_feeds` | ICalParserService |
| `PropertySeasonalRate` | `property_seasonal_rates` | VillaPricingCalculatorService |
| `PropertySubscription` | `property_subscriptions` | Subscription |

### CRM Domain (Müşteri)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `Kisi` | `kisiler` | KisiController, CRMController, Pipeline |
| `KisiAktivite` | `kisi_aktiviteleri` | ActivityController |
| `KisiEtkilesim` | `kisi_etkilesimleri` | CRM timeline |
| `Lead` | `leads` | LeadController, LeadService |
| `LeadActivity` | `lead_activities` | Lead timeline |
| `LeadMessage` | `lead_messages` | Lead mesajları |
| `LeadEmbedding` | `lead_embeddings` | EmbeddingService |
| `AILeadScore` | `ai_lead_scores` | CRM scoring |
| `Talep` | `talepler` | TalepController |
| `IlanTalepEslesme` | `ilan_talep_eslesmeleri` | Eşleştirme |
| `Eslesme` | `eslesmeler` | EslesmeController |
| `MatchingFeedback` | `matching_feedbacks` | MatchingFeedbackController |
| `IletimKaydi` | `iletim_kayitlari` | IletisimService |
| `FollowUpTask` | `follow_up_tasks` | CRM takip |
| `DanismanChatSession` | — | Danışman chat |
| `DanismanChatMessage` | — | Danışman chat mesajı |
| `Etiket` | `etiketler` | CRM etiketleri |
| `BuyerMatchLog` | — | BuyerMatch motoru |
| `BuyerMatchSnapshot` | — | BuyerMatch snapshot |

### Feature/Template Domain (Özellik Şeması)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `Feature` | `property_features` | PropertyHubController, FeatureAssignmentController |
| `FeatureCategory` | `feature_categories` | OzellikKategoriController |
| `FeatureAssignment` | `feature_assignments` | FeatureAssignmentController (polymorphic) |
| `FeaturePack` | `feature_packs` | PropertyHubController, UpsFeaturePackController |
| `FeaturePackItem` | `feature_pack_items` | Pack içeriği |
| `CategoryFeatureWhitelist` | `category_feature_whitelists` | UpsFeatureWhitelistController |
| `MasterTemplate` | `master_templates` | PropertyHubController |
| `UpsTemplate` | `ups_templates` | UpsTemplateController |
| `YayinTipiSablonu` | `yayin_tipi_sablonlari` | TemplateController, FeatureTemplateResolver |
| `YayinTipiPivotAtama` | `yayin_tipi_pivot_atamalari` | Pivot atama |
| `KategoriYayinTipiFieldDependency` | `kategori_yayin_tipi_field_dependencies` | FieldDependencyController |
| `TemplateChangeLog` | `template_change_logs` | Template geçmişi |
| `TemplateDesignAudit` | `template_design_audits` | TemplateAiDesignController |
| `TemplateAuditLog` | `template_audit_logs` | Audit |
| `Ozellik` | `ozellikler` | OzellikController (legacy) |
| `OzellikKategori` | `ozellik_kategorileri` | OzellikKategoriController (legacy) |

### AI Domain (Yapay Zeka)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `AiFieldSuggestion` | `ai_field_suggestions` | FieldSuggestionController |
| `AiSuggestionAction` | `ai_suggestion_actions` | Suggestion governance |
| `AiExperiment` | `ai_experiments` | AI experiment |
| `AiFeatureUsage` | `ai_feature_usages` | AI istatistikleri |
| `AiLog` | `ai_logs` | AIService logging |
| `AiPromptLog` | `ai_prompt_logs` | AIPromptManager |
| `AiProviderProfile` | `ai_provider_profiles` | AISettingsController |
| `AiProviderDecision` | `ai_provider_decisions` | Provider seçimi |
| `AiSaglayiciProfili` | `ai_saglayici_profilleri` | TR alias |
| `AiOptimizationRun` | `ai_optimization_runs` | Optimizasyon |
| `AiEsikProfili` | `ai_esik_profilleri` | Eşik profili |
| `AiOgrenmeSinyali` | `ai_ogrenme_sinyalleri` | Öğrenme sinyali |
| `AiThresholdOverride` | `ai_threshold_overrides` | Override |
| `OptimizerSuggestion` | `optimizer_suggestions` | Optimizasyon önerisi |
| `AgentMemory` | `agent_memories` | AI ajan hafızası |
| `AgentRun` | `agent_runs` | AI ajan çalışması |
| `CopilotActionLog` | `copilot_action_logs` | Copilot loglama |
| `DealPredictionLog` | — | Deal tahmin logu |
| `DealPredictionSnapshot` | — | Deal tahmin snapshot |

### Governance Domain (Yönetişim)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `GovernanceDecision` | `governance_decisions` | DecisionEngineController |
| `GovernanceRollback` | `governance_rollbacks` | Rollback aksiyonu |
| `GovernanceSuppression` | `governance_suppressions` | Suppression listesi |
| `GovernanceAuditLog` | `governance_audit_logs` | Audit log |
| `GovernanceIncident` | `governance_incidents` | Olay kaydı |
| `RuleDefinition` | `rule_definitions` | Kural tanımı |
| `SystemLearningTransaction` | `system_learning_transactions` | Sistem öğrenme |

### Finance Domain (Finans)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `FinansalIslem` | `finansal_islemler` | FinansalIslemController |
| `FinancialTransaction` | `financial_transactions` | FinanceController |
| `LedgerEntry` | `ledger_entries` | FinancialLedgerService |
| `LedgerAccount` | `ledger_accounts` | Hesap planı |
| `LedgerBalance` | `ledger_balances` | Bakiye |
| `FxRate` | `fx_rates` | FxService |
| `ExchangeRate` | `exchange_rates` | TCMBCurrencyService |
| `Currency` | `currencies` | Para birimi |
| `CountryFinancialRule` | `country_financial_rules` | Ülke kuralı |
| `RentalGelirKalemi` | `rental_gelir_kalemleri` | Kira geliri |
| `RentalGiderKalemi` | `rental_gider_kalemleri` | Kira gideri |
| `ExpenseItem` | `expense_items` | Gider kalemi |
| `PropertyExpense` | `property_expenses` | Mülk gideri |

### Location Domain (Konum — Canonical SSOT)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `Il` | `cities` | LocationController, Wizard Step 1 |
| `Ilce` | `districts` | Wizard Step 1, Arama |
| `Mahalle` | `neighborhoods` | Wizard Step 1, Adres |
| `Ulke` | `ulkeler` | CountryFinancialService |

### Intelligence Domain (Projection + Market)

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `PropertyGrowthProjection` | `property_growth_projections` | Büyüme tahmini |
| `PropertyEngineShadowEvent` | `property_engine_shadow_events` | Shadow event |
| `ProjectHealthSnapshot` | `project_health_snapshots` | ShadowDashboardController |
| `MarketListing` | `market_listings` | MarketIntelligenceController |
| `PointOfInterest` | `points_of_interest` | AkilliCevreAnaliziService |
| `CortexNeuralConnection` | `cortex_neural_connections` | Cortex bağlantı |
| `TkgmLearningPattern` | `tkgm_learning_patterns` | TKGM öğrenme |
| `TkgmQuery` | `tkgm_queries` | TKGMParselController |
| `OwnerReportExport` | — | Rapor export |
| `OwnerReportMetric` | — | Rapor metrik |
| `OwnerReportRow` | — | Rapor satır |
| `Projections/*` | `*_projections` | CQRS read model |

### Cross-Domain / Yardımcı

| Model | Tablo | Kullanım Yeri |
|-------|-------|---------------|
| `User` | `users` | Auth, Agent workspace, tüm sistem |
| `Role` | `roles` | Yetki sistemi |
| `Setting` | `settings` | AyarlarController |
| `Notification` | `notifications` | NotificationController |
| `TelegramNotification` | `telegram_notifications` | TelegramService |
| `Event` | `events` | Sistem olayları |
| `Language` | `languages` | Dil yönetimi |
| `Site` | `sites` | SiteController |
| `SiteApartman` | `site_apartmanlar` | SiteApartmanController |
| `Demirbas` | `demirbaslar` | Demirbaş takibi |
| `Proje` | `projeler` | ProjeController (Takım) |
| `Gorev` | `gorevler` | GorevController (Takım) |
| `TakimUyesi` | `takim_uyeleri` | TakimController |
| `VipTercihMatrisi` | `vip_tercih_matrisi` | VipFiltreService |
| `SavedSearch` | `saved_searches` | Kayıtlı arama |
| `AnalyticsDashboardFilter` | — | Analitik filtre |
| `AnalyticsMetric` | — | Analitik metrik |
| `AnalyticsReport` | — | Analitik rapor |
| `PipelineRun` | — | Pipeline çalışması |
| `PipelineStep` | — | Pipeline adımı |
| `AnahtarYonetimi` | `anahtar_yonetimleri` | AnahtarYonetimiController |
| `UserDevice` | `user_devices` | Push notification |
| `RefSequence` | `ref_sequences` | Referans numarası |
| `BaseModel` | — | Base class (abstract) |
| `TestEntity` | — | Test |
| `NameAttribute` | — | Helper |

### AI Subdirectory (`app/Models/AI/`)

> AI namespace altındaki modeller için `app/Models/AI/` dizinine bakınız.
> Bu modeller genellikle Intelligence Domain'e aittir.

### MarketIntelligence Subdirectory (`app/Models/MarketIntelligence/`)

> Market Intelligence ile ilgili modeller için `app/Models/MarketIntelligence/` dizinine bakınız.

### Projections Subdirectory (`app/Models/Projections/`)

> CQRS Projection modelleri için `app/Models/Projections/` dizinine bakınız.
> Tüm projection tabloları read-only'dir — raw tablo değiştirmez.

### V2 Subdirectory (`app/Models/V2/`)

> V2 modelleri için `app/Models/V2/` dizinine bakınız.

### Traits (`app/Models/Traits/`)

> Model trait'leri için `app/Models/Traits/` dizinine bakınız.
> `HasCountryScope` trait'i tüm modellerde zorunludur (GOVERNANCE.md).
