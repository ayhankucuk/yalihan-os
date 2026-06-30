# 🧠 Yalıhan Cortex: AI Orkestrasyon Mimarisi

**Oturum:** 38 (Detaylı Analiz)
**Tarih:** 2025-05-24
**Durum:** ✅ Production-Ready (149 AI Servisi Aktif)

---

## 📊 Sistem Özeti

Yalıhan sisteminde **toplam 149 adet AI destekli PHP sınıfı** bulunmaktadır. Bu sınıflar 3 ana katmanda organize edilmiştir:

```
app/Services/AI/
├── 🧠 YalihanCortex.php (Ana Beyin - 3024 satır)
├── 🎯 AIOrchestrator.php (Failover & Resilience)
├── 📦 102 Specialized Service (Domain-specific AI)
├── 🔧 14 Cortex Domain Services
├── 🛡️ 10 Copilot Services
├── 🔌 8 Provider Implementations
└── 📊 15 Support/Monitoring Services
```

---

## 🎯 Yalıhan Cortex Ne İş Yapar?

[`YalihanCortex`](app/Services/AI/YalihanCortex.php:66) sınıfı, sistemdeki **tüm AI operasyonlarının merkezi orkestratörüdür**. Görevleri:

### 1. 🧩 Dependency Injection Hub (32 Servis)

YalihanCortex constructor'ında **32 farklı AI servisi** inject edilir:

```php
// Ana Domain Servisleri (6 adet)
protected CortexMatchingService $matchingService;      // Alıcı-portföy eşleştirme
protected CortexContentService $contentService;        // İçerik üretimi (başlık, açıklama)
protected CortexPredictionService $predictionService;  // Tahminleme (churn, deal)
protected CortexIntelligenceService $intelligenceService; // Piyasa analizi
protected CortexQualityService $qualityService;        // Kalite denetimi
protected CortexTeamService $teamService;              // Takım performansı

// Özelleşmiş Motorlar (26 adet)
protected SmartPropertyMatcherAI $propertyMatcher;
protected KisiChurnService $churnService;
protected OpportunityDetectionService $opportunityDetection;
protected BuyerMatchDetectionService $buyerMatchDetection;
protected DealPredictionService $dealPrediction;
// ... ve 21 servis daha
```

### 2. 🔄 Failover & Provider Orchestration

YalihanCortex, AI provider'lar arası otomatik failover sağlar:

```php
protected array $fallbackProviders = [
    'ollama' => ['deepseek', 'openai', 'gemini'],
    'openai' => ['deepseek', 'ollama', 'gemini'],
    'gemini' => ['openai', 'deepseek', 'ollama'],
];
```

**Akış:**
1. İstek gelir → [`AIOrchestrator`](app/Services/AI/AIOrchestrator.php) devreye girer
2. Primary provider (örn: OpenAI) çağrılır
3. Başarısız olursa → Fallback chain'e geçer (DeepSeek → Ollama → Gemini)
4. Tüm telemetri [`AiTelemetryService`](app/Services/AI/Monitoring/AiTelemetryService.php) ile loglanır

### 3. 🛡️ Budget Guard & Cost Control

Her AI çağrısı öncesi [`AiBudgetGuard`](app/Services/AI/Monetization/AiBudgetGuard.php) devreye girer:

```php
private function guardCostBudget(string $provider): void
{
    $budget = $this->costGuard->checkBudget($provider);
    if (!$budget['allowed']) {
        throw new \RuntimeException('AI bütçe sınırı aşıldı');
    }
}
```

### 4. 📊 Telemetry & Observability

Her Cortex kararı [`logCortexDecision()`](app/Services/AI/YalihanCortex.php:730) ile kaydedilir:

```php
private function logCortexDecision(
    string $decisionType,    // 'buyer_match', 'deal_prediction', vb.
    array $context,          // İstek parametreleri
    float $durationMs,       // Milisaniye cinsinden süre
    bool $success            // Başarı durumu
): void
```

---

## 🏗️ Cortex Domain Servisleri (6 Çekirdek)

YalihanCortex, iş mantığını **6 domain servisine** delege eder:

### 1. [`CortexMatchingService`](app/Services/AI/Domains/CortexMatchingService.php:22)
**Görev:** Alıcı-portföy eşleştirme
**Metodlar:**
- `detectBuyerMatches(Ilan $ilan)` → İlan için potansiyel alıcılar
- `matchForSale(Talep $talep)` → Talep için uygun portföyler

### 2. [`CortexContentService`](app/Services/AI/Domains/CortexContentService.php:20)
**Görev:** AI destekli içerik üretimi
**Metodlar:**
- `generateVideoScript(Ilan $ilan)` → Pazarlama video scripti
- `optimizeIlanTitle(array $data)` → SEO-friendly başlık

### 3. [`CortexPredictionService`](app/Services/AI/Domains/CortexPredictionService.php:19)
**Görev:** Tahminleme ve risk analizi
**Metodlar:**
- `predictDeal(Ilan $ilan)` → Satış olasılığı skoru
- `calculateChurnRisk(Kisi $kisi)` → Müşteri kaybı riski
- `getTopChurnRisks(int $limit)` → En riskli müşteriler
- `getNegotiationStrategy(Kisi $kisi)` → Pazarlık stratejisi

### 4. [`CortexIntelligenceService`](app/Services/AI/Domains/CortexIntelligenceService.php:18)
**Görev:** Piyasa analizi ve değerleme
**Metodlar:**
- `priceValuation(Ilan $ilan)` → TKGM + finansal analiz ile değerleme

### 5. [`CortexQualityService`](app/Services/AI/Domains/CortexQualityService.php:18)
**Görev:** Kalite denetimi ve kategorizasyon
**Metodlar:**
- `suggestCategory(string $featureName)` → Özellik için kategori önerisi

### 6. [`CortexTeamService`](app/Services/AI/Domains/CortexTeamService.php:15)
**Görev:** Takım performans analizi
**Metodlar:**
- (Detaylar implementation'da)

---

## 🤖 AI Copilot Servisleri (10 Adet)

Copilot servisleri, danışman iş akışını otonomlaştırır:

### Çekirdek Copilot'lar

| Servis | Görev | Dosya |
|--------|-------|-------|
| [`CopilotOrchestrator`](app/Services/AI/Copilot/CopilotOrchestrator.php:14) | Ana orkestrasyon kapısı | 454 satır |
| [`CRMCopilotService`](app/Services/AI/Copilot/CRMCopilotService.php:14) | CRM iş akışı optimizasyonu | - |
| [`CopilotAuditEngine`](app/Services/AI/Copilot/CopilotAuditEngine.php:19) | Risk ve kalite denetimi | - |
| [`CopilotPredictionEngine`](app/Services/AI/Copilot/CopilotPredictionEngine.php:22) | Tahminleme motoru | - |
| [`WizardCopilotService`](app/Services/AI/Copilot/WizardCopilotService.php:15) | İlan wizard asistanı | - |
| [`LocationCopilotService`](app/Services/AI/Copilot/LocationCopilotService.php:16) | Konum doğrulama | - |
| [`BrokerCopilotService`](app/Services/AI/Copilot/BrokerCopilotService.php:16) | Broker özel işlemler | - |

### Pipeline Katmanı

| Servis | Görev | Dosya |
|--------|-------|-------|
| [`GovernanceResolver`](app/Services/AI/Copilot/Pipeline/GovernanceResolver.php) | Anayasal sınır çözümleyici | Context7 kanonik dönüşüm |
| [`PipelineDispatcher`](app/Services/AI/Copilot/Pipeline/PipelineDispatcher.php) | Pipeline yönlendirici | - |
| [`PipelineStateManager`](app/Services/AI/Copilot/Pipeline/PipelineStateManager.php) | Durum yönetimi | - |

---

## 🔌 AI Provider Implementations (8 Adet)

Yalıhan, 4 farklı AI provider'ı destekler:

### Provider Sınıfları

```
app/Services/AI/Providers/
├── DeepSeekProvider.php          → DeepSeek API (primary)
├── DeepSeekCortexProvider.php    → DeepSeek Cortex wrapper
├── OpenAICortexProvider.php      → OpenAI GPT-4/3.5
└── (Gemini & Ollama → AIService üzerinden)
```

### Provider Seçim Mekanizması

[`ProviderSelectorPolicy`](app/Services/AI/Monitoring/ProviderSelectorPolicy.php) şu kriterlere göre karar verir:

1. **Maliyet** → En ucuz provider öncelikli
2. **Latency** → Ortalama yanıt süresi
3. **Success Rate** → Son 24 saatteki başarı oranı
4. **Budget Availability** → Kalan kredi bakiyesi

---

## 📊 Monitoring & Telemetry (15 Servis)

### Telemetry Stack

```
AiTelemetryService (Ana Logger)
    ↓
AiTelemetryAggregator (Rolling Window Stats)
    ↓
ProviderScoreCalculator (Performans Skoru)
    ↓
CortexMonitoringService (Health Check)
```

### Telemetri Verileri

Her AI çağrısı şu metriklerle kaydedilir:

```php
[
    'provider' => 'openai',
    'endpoint' => 'chat/completions',
    'duration_ms' => 1234.56,
    'input_tokens' => 150,
    'output_tokens' => 300,
    'aktiflik_kodu' => 200,  // HTTP status
    'cost_usd' => 0.0045,
    'tenant_id' => 1,
    'user_id' => 42,
]
```

---

## 🎯 Özelleşmiş AI Motorları (Top 20)

### İlan & Portföy Yönetimi

| Servis | Görev |
|--------|-------|
| [`AIIlanTaslagiService`](app/Services/AI/AIIlanTaslagiService.php) | İlan taslağı oluşturma |
| [`IlanStorytellingService`](app/Services/AI/IlanStorytellingService.php) | Hikaye anlatımı (storytelling) |
| [`PortfolioDoctorService`](app/Services/AI/Portfolio/PortfolioDoctorService.php) | Portföy sağlık analizi |
| [`PropertyAIService`](app/Services/AI/PropertyAIService.php) | Gayrimenkul AI asistanı |

### CRM & Müşteri Yönetimi

| Servis | Görev |
|--------|-------|
| [`ConversationalAdvisorService`](app/Services/AI/ConversationalAdvisorService.php) | Sohbet tabanlı danışman |
| [`AdvisorCommandCenterService`](app/Services/AI/AdvisorCommandCenterService.php) | Danışman komuta merkezi |
| [`ChurnRiskService`](app/Services/AI/ChurnRiskService.php) | Müşteri kaybı riski |
| [`LeadScoreCalculator`](app/Services/AI/LeadScoreCalculator.php) | Lead skorlama |

### Piyasa & Fiyatlandırma

| Servis | Görev |
|--------|-------|
| [`MarketValuationService`](app/Services/AI/MarketValuationService.php) | Piyasa değerleme |
| [`PricingIntelligenceSyncService`](app/Services/AI/PricingIntelligenceSyncService.php) | Fiyat istihbaratı |
| [`CortexPriceForecastService`](app/Services/AI/CortexPriceForecastService.php) | Fiyat tahmini |
| [`AiPricingService`](app/Services/AI/AiPricingService.php) | AI fiyatlandırma |

### Fırsat & Eşleştirme

| Servis | Görev |
|--------|-------|
| [`OpportunityEngineService`](app/Services/AI/OpportunityEngineService.php) | Fırsat tespiti |
| [`OpportunityDetectionService`](app/Services/AI/OpportunityDetectionService.php) | Fırsat algılama |
| [`BuyerMatchQueueService`](app/Services/AI/BuyerMatchQueueService.php) | Alıcı eşleştirme kuyruğu |
| [`DealRadarService`](app/Services/AI/DealRadarService.php) | Anlaşma radarı |

### Görsel & Metin Analizi

| Servis | Görev |
|--------|-------|
| [`VisionService`](app/Services/AI/VisionService.php) | Görsel analiz (OpenAI Vision) |
| [`CortexVisionService`](app/Services/AI/CortexVisionService.php) | Cortex görsel analiz |
| [`VisionTaggingService`](app/Services/AI/Vision/VisionTaggingService.php) | Görsel etiketleme |
| [`NLPProcessor`](app/Services/AI/NLPProcessor.php) | Doğal dil işleme |

---

## 🔧 Yardımcı Servisler (Support Layer)

### Prompt Yönetimi

```
app/Services/AI/Prompts/
├── PromptRegistry.php          → Prompt şablonları
├── AiPromptRegistry.php        → AI prompt kütüphanesi
└── PromptGovernanceService.php → Prompt güvenlik denetimi
```

### Validation & Mapping

```
app/Services/AI/Validation/
├── ListingAIResponseValidator.php → AI yanıt doğrulama
└── ListingAIValidator.php         → İlan AI validasyonu

app/Services/AI/Mappers/
└── StructuredAiPayloadMapper.php  → JSON payload dönüşümü
```

---

## 📈 Sistem İstatistikleri

### Kod Metrikleri

```bash
# Toplam AI servisi
find app/Services/AI -name "*.php" -type f | wc -l
# → 149 dosya

# Toplam satır sayısı (yaklaşık)
find app/Services/AI -name "*.php" -exec wc -l {} + | tail -1
# → ~45,000 satır

# En büyük servis
wc -l app/Services/AI/YalihanCortex.php
# → 3024 satır
```

### Servis Dağılımı

| Kategori | Adet | Oran |
|----------|------|------|
| Domain Services | 14 | 9% |
| Copilot Services | 10 | 7% |
| Specialized Engines | 96 | 64% |
| Providers | 8 | 5% |
| Monitoring/Support | 21 | 15% |

---

## 🎯 Oturum 38 Hedefi: `type` Sızıntıları

Sistemde tespit edilen **20 adet `type` kullanımı** Context7 kanonik karşılıklarına dönüştürülecek:

### Refaktör Hedefleri

| Dosya | Satır | Mevcut | Hedef |
|-------|-------|--------|-------|
| [`CopilotAuditEngine`](app/Services/AI/Copilot/CopilotAuditEngine.php:34) | 34 | `$context['type']` | `$context['denetim_tipi']` |
| [`CopilotPredictionEngine`](app/Services/AI/Copilot/CopilotPredictionEngine.php:39) | 39 | `$context['type']` | `$context['tahmin_tipi']` |
| [`CRMCopilotService`](app/Services/AI/Copilot/CRMCopilotService.php:227) | 227 | `'type' => 'matching'` | `'islem_tipi' => 'eslestirme'` |
| [`GovernanceResolver`](app/Services/AI/Copilot/Pipeline/GovernanceResolver.php:80) | 80 | `'type' => 'tenant_context'` | `'sinyal_tipi' => 'kirac_baglami'` |
| [`WizardCopilotService`](app/Services/AI/Copilot/WizardCopilotService.php:244) | 244 | `'type' => 'ai_title'` | `'kanca_tipi' => 'ai_baslik'` |

---

## 🚦 Sonuç

### ✅ Yalıhan Cortex Özellikleri

1. **Merkezi Orkestrasyon** → Tüm AI çağrıları tek noktadan
2. **Failover Resilience** → 4 provider arası otomatik geçiş
3. **Budget Guard** → Maliyet kontrolü her çağrıda
4. **Full Telemetry** → Her işlem milisaniye hassasiyetle loglanır
5. **Domain Separation** → 6 çekirdek domain servisi
6. **Copilot Automation** → 10 özelleşmiş copilot motoru

### 📊 Sistem Sağlığı

```bash
# Bekçi Health Score
php artisan bekci:health
# → %75.85 (Production-Ready)

# AI Telemetry Check
php artisan ai:telemetry --last-24h
# → Success Rate: %94.2
# → Avg Latency: 1.2s
# → Total Cost: $12.45
```

---

**SEAL STATUS:** ✅ TRUE SEALED
**Production Status:** ACTIVE (149 AI Services)
**Next Action:** Oturum 38 refaktör başlatılabilir 🚀
