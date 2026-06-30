# Yalıhan Emlak — Domain Haritası (Domain SSOT)

> ⚠️ **Authority Rule:** Bu doküman READ-ONLY sistem yansımasıdır.
> Source of truth = code + DB + config. Kod ile doküman çelişirse → kod kazanır.
>
> Son Güncelleme: 2026-04-11
> Kaynak: `app/Models/`, `app/Services/`, `docs/platform-architecture-consolidation.md`

---

## Domain Mimarisi

```
┌─────────────────────────────────────────────────┐
│  Property Domain         CRM Domain             │
│  (İlan, Foto, Fiyat)     (Kişi, Lead, Talep)    │
├─────────────────────────────────────────────────┤
│  Feature/Template Domain                         │
│  (Özellik, Şablon, Atama, Paket)                │
├─────────────────────────────────────────────────┤
│  AI Domain               Governance Domain       │
│  (Öneri, Experiment)     (Karar, Rollback)       │
├─────────────────────────────────────────────────┤
│  Finance Domain          Intelligence Domain     │
│  (Ledger, FX, Kira)      (Projection, Market)   │
├─────────────────────────────────────────────────┤
│  Location Domain                                 │
│  (İl, İlçe, Mahalle — Canonical SSOT)           │
└─────────────────────────────────────────────────┘
```

---

## 1. Property Domain

**Tanım:** İlan yaşam döngüsü — oluşturma, düzenleme, yayınlama, arşivleme + bağlı medya, fiyatlandırma ve rezervasyon.

**Owner Servisler:** `IlanService`, `IlanReferansService`, `IlanDataProviderService`, `IlanVerticalDomainService`

**Ana Modeller:**
| Model | Tablo | Amaç |
|-------|-------|------|
| `Ilan` | `ilanlar` | Ana ilan modeli (72K satır kod — en büyük model) |
| `IlanKategori` | `ilan_kategorileri` | Kategori hiyerarşisi (ana/alt) |
| `IlanResim` / `IlanFotografi` | `ilan_resimleri` / `ilan_fotograflari` | İlan görselleri |
| `IlanMetin` | `ilan_metinleri` | İlan açıklama metinleri |
| `IlanNot` | `ilan_notlari` | İlan notları |
| `IlanTaslak` | `ilan_taslaklari` | Wizard draft verileri |
| `IlanPriceHistory` | `ilan_price_history` | Fiyat geçmişi |
| `IlanReservation` | `ilan_reservations` | Rezervasyon |
| `IlanTakvimSync` | `ilan_takvim_sync` | Takvim senkronizasyonu |
| `IlanFavori` | `ilan_favorileri` | Favori ilanlar |
| `IlanGoruntulenmeGunluk` | `ilan_goruntulenme_gunluk` | Günlük görüntülenme |
| `IlanEmbedding` | `ilan_embeddings` | Vektör embedding |
| `ListingStateTransition` | `listing_state_transitions` | Yaşam döngüsü geçişleri |
| `ListingTranslation` | `listing_translations` | İlan çevirileri |
| `Photo` | `photos` | Fotoğraf (polymorphic) |
| `YayinTipi` | `yayin_tipleri` | Yayın tipleri (Satılık, Kiralık vb.) |
| `AltKategoriYayinTipi` | `alt_kategori_yayin_tipleri` | Alt kategori - yayın tipi pivot |
| `PropertyConfigVersion` | `property_config_versions` | İlan config versiyon geçmişi |

**Yazlık/Kiralama (Alt Domain):**
| Model | Tablo | Amaç |
|-------|-------|------|
| `YazlikDetail` | `yazlik_details` | Yazlık detay bilgileri |
| `YazlikFiyatlandirma` | `yazlik_fiyatlandirma` | Yazlık fiyatlandırma |
| `YazlikRezervasyon` | `yazlik_rezervasyonlar` | Yazlık rezervasyonları |
| `Season` | `seasons` | Sezon tanımları |
| `RentalEvKarti` | `rental_ev_kartlari` | Kiralık ev kartı |
| `PropertyAvailability` | `property_availabilities` | Müsaitlik |
| `PropertyCalendarFeed` | `property_calendar_feeds` | iCal feed |
| `PropertySeasonalRate` | `property_seasonal_rates` | Sezonluk fiyat |
| `PropertySubscription` | `property_subscriptions` | Abonelik |

**Sınırlar:** Property Domain, AI motorlarını çağırmaz. AI önerileri Feature/Template ve AI Domain'den gelir.

**İlişkili Domainler:** Feature/Template (özellik ataması), Location (il/ilçe/mahalle), CRM (talep eşleştirme)

---

## 2. CRM Domain

**Tanım:** Müşteri, lead ve talep yönetimi — pipeline, aktivite takibi, eşleştirme.

**Owner Servisler:** `LeadService`, `IletisimService`, `CRMIntelligenceService`

**Ana Modeller:**
| Model | Tablo | Amaç |
|-------|-------|------|
| `Kisi` | `kisiler` | Ana müşteri modeli (18K satır kod) |
| `KisiAktivite` | `kisi_aktiviteleri` | Müşteri aktivite kaydı |
| `KisiEtkilesim` | `kisi_etkilesimleri` | Müşteri etkileşim |
| `Lead` | `leads` | Potansiyel müşteri |
| `LeadActivity` | `lead_activities` | Lead aktivite |
| `LeadMessage` | `lead_messages` | Lead mesajları |
| `LeadEmbedding` | `lead_embeddings` | Lead vektör embedding |
| `Talep` | `talepler` | Alıcı talepleri |
| `IlanTalepEslesme` | `ilan_talep_eslesmeleri` | İlan-talep eşleşme |
| `Eslesme` | `eslesmeler` | Talep-ilan eşleştirmesi |
| `MatchingFeedback` | `matching_feedbacks` | Eşleştirme geri bildirim |
| `IletimKaydi` | `iletim_kayitlari` | İletişim kayıtları |
| `FollowUpTask` | `follow_up_tasks` | Takip görevleri |
| `DanismanChatSession` / `DanismanChatMessage` | — | Danışman chat |
| `Etiket` | `etiketler` | CRM etiketleri |

**Sınırlar:** CRM Domain ilanları okur ama değiştirmez. Eşleştirme AI Domain'den sinyal alır.

**İlişkili Domainler:** Property (eşleştirme), AI (matching scoring), Finance (müşteri bazlı gelir)

---

## 3. Feature/Template Domain

**Tanım:** İlan özellik şeması — özellik tanımları, kategori grupları, şablon atamaları, paketler.

**Owner Servisler:** `FeatureAssignmentValidator`, `FeatureTemplateResolver`, `TemplateResolver`, `PropertyHubOrchestrator`

**Ana Modeller:**
| Model | Tablo | Amaç |
|-------|-------|------|
| `Feature` | `property_features` | Özellik tanımı (oda sayısı, m2 vb.) |
| `FeatureCategory` | `feature_categories` | Semantik özellik grupları |
| `FeatureAssignment` | `feature_assignments` | Polymorphic özellik ataması |
| `FeaturePack` | `feature_packs` | Özellik paketleri |
| `FeaturePackItem` | `feature_pack_items` | Paket içi özellik |
| `CategoryFeatureWhitelist` | `category_feature_whitelists` | Kategori bazlı whitelist |
| `MasterTemplate` | `master_templates` | Master şablon tanımı |
| `UpsTemplate` | `ups_templates` | UPS şablon |
| `YayinTipiSablonu` | `yayin_tipi_sablonlari` | Yayın tipi şablonları |
| `YayinTipiPivotAtama` | `yayin_tipi_pivot_atamalari` | Yayın tipi pivot ataması |
| `KategoriYayinTipiFieldDependency` | `kategori_yayin_tipi_field_dependencies` | Alan bağımlılıkları |
| `TemplateChangeLog` | `template_change_logs` | Şablon değişiklik geçmişi |
| `TemplateDesignAudit` | `template_design_audits` | AI tasarım audit |
| `TemplateAuditLog` | `template_audit_logs` | Şablon audit log |
| `Ozellik` | `ozellikler` | Legacy özellik modeli |
| `OzellikKategori` | `ozellik_kategorileri` | Legacy özellik kategorisi |

**Sınırlar:** Bu domain ilanı kendisi oluşturmaz; Wizard üzerinden Property Domain'e hizmet verir.

**İlişkili Domainler:** Property (wizard'da kullanılır), AI (suggestion üretimi), Governance (health matrix)

> **⚠️ Kritik Risk:** FeatureTemplateResolver'da NULL scope kontrolü yapılmazsa Wizard 0 feature döndürür. Bkz: `flows.md` #4

---

## 4. AI Domain

**Tanım:** AI öneri üretimi, experiment, prompt yönetimi ve AI provider orkestrasyon.

**Owner Servisler:** `AIService`, `AICoreSystem`, `AIPromptManager`, `PropertyFeatureSuggestionService`, `AiFieldSuggestionEngine`

**Ana Modeller:**
| Model | Tablo | Amaç |
|-------|-------|------|
| `AiFieldSuggestion` | `ai_field_suggestions` | AI alan önerisi |
| `AiSuggestionAction` | `ai_suggestion_actions` | Öneri aksiyonu (accept/reject) |
| `AiExperiment` | `ai_experiments` | A/B deney tanımı |
| `AiFeatureUsage` | `ai_feature_usages` | AI feature kullanım istatistikleri |
| `AiLog` | `ai_logs` | AI istek logu |
| `AiPromptLog` | `ai_prompt_logs` | Prompt geçmişi |
| `AiProviderProfile` | `ai_provider_profiles` | AI provider (OpenAI, Ollama vb.) |
| `AiProviderDecision` | `ai_provider_decisions` | Provider seçim kararı |
| `AiSaglayiciProfili` | `ai_saglayici_profilleri` | Provider profili (TR alias) |
| `AiOptimizationRun` | `ai_optimization_runs` | Optimizasyon çalışması |
| `AiEsikProfili` | `ai_esik_profilleri` | AI eşik profili |
| `AiOgrenmeSinyali` | `ai_ogrenme_sinyalleri` | Öğrenme sinyali |
| `AiThresholdOverride` | `ai_threshold_overrides` | Eşik override |
| `AILeadScore` | `ai_lead_scores` | Lead scoring |
| `OptimizerSuggestion` | `optimizer_suggestions` | Optimizasyon önerisi |
| `AgentMemory` | `agent_memories` | AI ajan hafızası |
| `AgentRun` | `agent_runs` | AI ajan çalışma kaydı |
| `CopilotActionLog` | `copilot_action_logs` | Copilot aksiyon logları |
| `BuyerMatchLog` / `BuyerMatchSnapshot` | — | Alıcı eşleşme logları |
| `DealPredictionLog` / `DealPredictionSnapshot` | — | Satış tahmin logları |

**Sınırlar:** AI Domain **kesinlikle auto-save yapmaz**. Önerir, kullanıcı onaylarsa uygulanır.

**İlişkili Domainler:** Feature/Template (suggestion target), Governance (onay/red akışı), CRM (lead scoring)

---

## 5. Governance Domain

**Tanım:** SAB karar motoru — AI kararlarının onaylanması, reddi, rollback edilmesi, bastırılması.

**Owner Servisler:** `GovernanceService`, `GovernanceDashboardService`, `GovernanceObservabilityService`

**Ana Modeller:**
| Model | Tablo | Amaç |
|-------|-------|------|
| `GovernanceDecision` | `governance_decisions` | Karar kaydı (9.7K satır — büyük model) |
| `GovernanceRollback` | `governance_rollbacks` | Rollback kaydı |
| `GovernanceSuppression` | `governance_suppressions` | Bastırma kaydı |
| `GovernanceAuditLog` | `governance_audit_logs` | Denetim logu |
| `GovernanceIncident` | `governance_incidents` | Olay kaydı |
| `RuleDefinition` | `rule_definitions` | Kural tanımı |
| `SystemLearningTransaction` | `system_learning_transactions` | Sistem öğrenme işlemi |

**Sınırlar:** Governance kendi başına aksiyon almaz; AI Domain'in ürettiği kararları kontrol eder.

**İlişkili Domainler:** AI (karar onayı), Feature/Template (health matrix), Property (listing quality)

---

## 6. Finance Domain

**Tanım:** Finansal işlemler, ledger, döviz kuru, kiralık gelir/gider.

**Owner Servisler:** `FinancialLedgerService`, `FxService`, `TCMBCurrencyService`, `RentalKpiService`

**Ana Modeller:**
| Model | Tablo | Amaç |
|-------|-------|------|
| `FinansalIslem` | `finansal_islemler` | Finansal işlem |
| `FinancialTransaction` | `financial_transactions` | İşlem detayı |
| `LedgerEntry` | `ledger_entries` | Muhasebe kaydı |
| `LedgerAccount` | `ledger_accounts` | Hesap planı |
| `LedgerBalance` | `ledger_balances` | Hesap bakiyesi |
| `FxRate` | `fx_rates` | Döviz kuru |
| `ExchangeRate` | `exchange_rates` | Kur |
| `Currency` | `currencies` | Para birimi |
| `CountryFinancialRule` | `country_financial_rules` | Ülke finansal kural |
| `RentalGelirKalemi` | `rental_gelir_kalemleri` | Kira gelir kalemi |
| `RentalGiderKalemi` | `rental_gider_kalemleri` | Kira gider kalemi |
| `ExpenseItem` | `expense_items` | Gider kalemi |
| `PropertyExpense` | `property_expenses` | Mülk gideri |

**Sınırlar:** Finance Domain property değiştirmez; sadece gelir/gider takibi yapar.

**İlişkili Domainler:** Property (mülk bazlı gider), CRM (müşteri bazlı gelir)

---

## 7. Location Domain (Canonical SSOT)

**Tanım:** İdari adres hiyerarşisi — runtime API bağımlılığından arındırılmış, yerel DB'den servis edilir.

**Owner Servisler:** `LocationService`, `AddressSyncService`, `TurkiyeAPIService`, `NominatimService`

**Ana Modeller:**
| Model | Tablo | Amaç |
|-------|-------|------|
| `Il` | `cities` | İl (province) |
| `Ilce` | `districts` | İlçe (district) |
| `Mahalle` | `neighborhoods` | Mahalle (neighborhood) |
| `Ulke` | `ulkeler` | Ülke |

**Mühürleme:** Bu tablolar TürkiyeAPI'den sync edilir ama runtime'da API çağrısı yapılmaz. Wizard Step 1 bu canonical yapıdan çalışır.

**İlişkili Domainler:** Property (ilan lokasyonu), CRM (müşteri adresi)

---

## 8. Intelligence Domain (Projection + Market)

**Tanım:** CQRS read-only projection tabloları + pazar istihbarat sinyalleri. AI Engine Layer'ın girdi katmanı.

**Owner Servisler:** `MarketIntelligence\*`, `Intelligence\*`, `AIDeal\*`, `Cortex\*`

**Ana Modeller (Projections):**
| Model | Tablo | Amaç |
|-------|-------|------|
| `Projections\*` | `listing_*_projections` | CQRS read model projeksiyonları |
| `PropertyGrowthProjection` | `property_growth_projections` | Mülk büyüme tahmini |
| `PropertyEngineShadowEvent` | `property_engine_shadow_events` | Shadow event |
| `ProjectHealthSnapshot` | `project_health_snapshots` | Proje sağlık anlık görüntüsü |
| `MarketListing` | `market_listings` | Pazar ilan verisi |
| `PointOfInterest` | `points_of_interest` | Çevre noktaları |
| `CortexNeuralConnection` | `cortex_neural_connections` | Cortex sinir ağı bağlantısı |
| `TkgmLearningPattern` | `tkgm_learning_patterns` | TKGM öğrenme pattern'i |
| `TkgmQuery` | `tkgm_queries` | TKGM sorgu geçmişi |
| `OwnerReportExport` / `OwnerReportMetric` / `OwnerReportRow` | — | Mal sahibi raporları |

**Sınırlar:** Projection tabloları **sadece okunur** — raw tablo değiştirmez (CQRS).

**İlişkili Domainler:** Property (kaynak veri), AI (sinyal tüketici), Governance (sinyal okuyucu)

---

## Yardımcı Modeller (Cross-Domain)

| Model | Tablo | Amaç |
|-------|-------|------|
| `User` | `users` | Kullanıcı / Danışman (Agent zemini) |
| `Role` | `roles` | Rol tanımı |
| `Setting` | `settings` | Sistem ayarları |
| `Notification` | `notifications` | Bildirimler |
| `TelegramNotification` | `telegram_notifications` | Telegram bildirimleri |
| `Event` | `events` | Sistem olayları |
| `Language` | `languages` | Dil tanımları |
| `Site` | `sites` | Site tanımı |
| `SiteApartman` | `site_apartmanlar` | Site/Apartman |
| `Demirbas` | `demirbaslar` | Demirbaş |
| `Proje` | `projeler` | Proje (Takım Yönetimi) |
| `Gorev` | `gorevler` | Görev |
| `TakimUyesi` | `takim_uyeleri` | Takım üyesi |
| `VipTercihMatrisi` | `vip_tercih_matrisi` | VIP tercih matrisi |
| `SavedSearch` | `saved_searches` | Kayıtlı arama |
| `AnalyticsDashboardFilter` / `AnalyticsMetric` / `AnalyticsReport` | — | Analitik |
| `PipelineRun` / `PipelineStep` | — | Pipeline çalışma adımları |
| `AnahtarYonetimi` | `anahtar_yonetimleri` | Anahtar yönetimi |
| `AdvisorPhoto` | `advisor_photos` | Danışman fotoğrafları |
| `UserDevice` | `user_devices` | Kullanıcı cihazı |
| `RefSequence` | `ref_sequences` | Referans numarası |
| `BaseModel` | — | Tüm modeller için base class |
| `TestEntity` | — | Test entity |
| `NameAttribute` | — | İsim attribute helper |
