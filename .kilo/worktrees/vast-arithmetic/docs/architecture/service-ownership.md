# Service Ownership

> STATUS: REFERENCE ONLY — NOT SSOT
> Kurallar: Thin Controller, Service Layer zorunlu, Cross-domain write yasak

---

## Property Engine Domain

| Service | Purpose | Write Authority | Risk |
|---------|---------|-----------------|------|
| `FeatureTemplateResolver` | Wizard'a hangi feature'lar gelecek belirler | ❌ Read-only | NULL scope → 0 feature |
| `FeatureAssignmentValidator` | Feature atama geçerliliği kontrol | ❌ Read-only | — |
| `PropertyHubOrchestrator` | Property Hub tüm işlemler | ✅ Feature/Template | Büyük orchestrator |
| `TemplateResolver` | Template çözümleme | ❌ Read-only | Scope mismatch |
| `TemplateAiDesignMutationService` | AI ile şablon tasarımı | ✅ Template | AI provider down |
| `DependencyRuleEvaluator` | Alan bağımlılığı değerlendirme | ❌ Read-only | Circular dependency |
| `IlanService` | İlan CRUD orchestrator | ✅ Ilan | Büyük servis |
| `IlanReferansService` | İlan referans numarası | ✅ Ilan ref | — |
| `IlanDataProviderService` | İlan veri sağlayıcı | ❌ Read-only | — |
| `IlanVerticalDomainService` | Dikey alan validasyonu | ❌ Read-only | — |

---

## Wizard Domain

| Service | Purpose | Write Authority | Risk |
|---------|---------|-----------------|------|
| `WizardOrchestrator` | Wizard 3 adım orkestrasyon | ❌ Delegasyon | Legacy, basit |
| `WizardContextService` | Wizard context build (11K satır) | ❌ Read-only | Büyük servis |
| `WizardDraftService` | Draft kaydet/yükle/temizle | ✅ Draft | Son yazan kazanır |
| `WizardGateService` | Validation gate'leri | ❌ Read-only | — |
| `WizardAIAssistantService` | AI asistan (opsiyonel) | ❌ Suggestion only | Provider down |
| `EffectiveWizardSchemaResolver` | Wizard şema çözümleme | ❌ Read-only | — |
| `EffectiveListingTypeResolver` | Yayın tipi çözümleme | ❌ Read-only | — |

---

## Cortex / AI Domain

| Service | Purpose | Write Authority | Risk |
|---------|---------|-----------------|------|
| `AIService` | Ana AI servis orkestratör | ✅ AI logs | Provider kesintisi |
| `AICoreSystem` | AI core system | ✅ AI data | — |
| `AIPromptManager` | Prompt yönetimi | ✅ Prompt logs | — |
| `AiFieldSuggestionEngine` | AI alan önerisi üretme | ✅ Suggestions | False empty |
| `ListingSmartSuggestionService` | İlan bazlı AI öneri (5 pipeline) | ❌ Suggestion only | Auto-save YASAK |
| `SmartSuggestionTelemetryService` | Accept/Reject telemetrisi | ✅ Telemetry | — |
| `PropertyFeatureSuggestionService` | Feature bazlı öneri | ✅ Suggestions | — |
| `CortexOrchestrator` | Tüm AI operasyonları | ✅ AI data | Büyük orchestrator |
| `CortexRoutingService` | Task-based provider seçimi | ❌ Read-only | Failover chain |

---

## Governance Domain

| Service | Purpose | Write Authority | Risk |
|---------|---------|-----------------|------|
| `GovernanceService` | Karar üretme/onaylama/reddetme | ✅ Decisions | Permission bypass |
| `GovernanceDashboardService` | Dashboard veri parse | ❌ Read-only | Malformed JSON |
| `GovernanceObservabilityService` | Gözlemlenebilirlik | ❌ Read-only | — |
| `GovernanceTransitionGuard` | Geçiş kuralları kontrolü | ❌ Read-only | — |
| `EloquentGovernanceAuditLogger` | Audit log yazma | ✅ Audit (append-only) | — |

---

## Finance Domain

| Service | Purpose | Write Authority | Risk |
|---------|---------|-----------------|------|
| `FinancialLedgerService` | Çift taraflı muhasebe | ✅ Ledger (immutable) | Update/delete YASAK |
| `FxService` | Döviz kuru | ✅ FX rates | — |
| `TCMBCurrencyService` | TCMB kur çekme | ✅ Exchange rates | API down |
| `RentalKpiService` | Kira KPI hesaplama | ❌ Read-only | — |
| `VillaPricingCalculatorService` | Yazlık fiyat hesaplama | ❌ Read-only | — |

---

## Location Domain

| Service | Purpose | Write Authority | Risk |
|---------|---------|-----------------|------|
| `LocationService` | Konum sorgulama | ❌ Read-only | — |
| `AddressSyncService` | Adres senkronizasyonu | ✅ Location tables | API down |
| `TurkiyeAPIService` | TürkiyeAPI entegrasyonu | ✅ Location sync | External API |
| `NominatimService` | Geocoding | ❌ Read-only | Rate limit |

---

## CRM Domain

| Service | Purpose | Write Authority | Risk |
|---------|---------|-----------------|------|
| `LeadService` | Lead yönetimi | ✅ Leads | — |
| `IletisimService` | İletişim kaydı | ✅ İletim | — |
| `CRMIntelligenceService` | CRM analiz | ❌ Read-only | — |

---

## Cross-Domain Rules

1. **Thin Controller:** Controller sadece request → service → response
2. **No direct DB write:** UI/Controller'dan doğrudan DB yazımı yasak
3. **Cross-domain write:** Sadece açıkça belgelenmiş servis sınırları üzerinden
4. **Auto-save YASAK:** AI önerileri kesinlikle otomatik kaydedilmez
5. **Immutable Ledger:** `LedgerEntry` update/delete → `RuntimeException`
