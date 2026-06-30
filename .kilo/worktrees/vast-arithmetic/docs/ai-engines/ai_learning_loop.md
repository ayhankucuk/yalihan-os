# AI Learning Loop — Yalıhan Real Estate Intelligence

> **SSOT Öğrenme Döngüsü Referansı**
> Son Güncelleme: 2026-03-07

---

## Tanım

Yalıhan yalnızca AI öneri üreten değil, kullanıcı aksiyonlarından öğrenen bir gayrimenkul zeka platformudur.

AI katmanı; fırsat skoru, alıcı eşleşmesi, satış tahmini ve fiyat önerilerini yalnızca üretmekle kalmaz — kullanıcı davranışı, gerçek satış sonucu ve operasyon telemetry'si üzerinden öğrenme döngüsü kurar.

---

## Genel Döngü

```
1. Projection verisi oluşur
   ↓
2. AI motoru skor / tahmin üretir
   ↓
3. Kullanıcı bu skora göre aksiyon alır
   ↓
4. Gerçek sonuç oluşur
   ↓
5. Sonuç telemetry / log katmanına yansır
   ↓
6. Tahmin ile gerçek sonuç karşılaştırılır
   ↓
7. Model kalite metriği oluşur
   ↓
8. Sonraki tuning / ranking / weighting sinyali üretilir
   ↓
   (1. adıma dön)
```

---

## Repo İçi Altyapı

| Katman             | Servisler                    | Dosya                                              |
| ------------------ | ---------------------------- | -------------------------------------------------- |
| **Telemetry**      | `AiTelemetryService`         | `Services/AI/AiTelemetryService.php`               |
|                    | `AiTelemetryAggregator`      | `Services/AI/Monitoring/AiTelemetryAggregator.php` |
|                    | `DealTelemetryService`       | `Services/AIDeal/DealTelemetryService.php`         |
|                    | `BuyerMatchTelemetryService` | `Services/AIMatch/BuyerMatchTelemetryService.php`  |
| **Learning**       | `AiLearningSignalService`    | `Services/AI/AiLearningSignalService.php`          |
|                    | `CortexLearningService`      | `Services/AI/CortexLearningService.php`            |
|                    | `AutoLearningService`        | `Services/Intelligence/AutoLearningService.php`    |
|                    | `TKGMLearningService`        | `Services/Intelligence/TKGMLearningService.php`    |
| **Feedback**       | `MatchingFeedbackService`    | `Services/Matching/MatchingFeedbackService.php`    |
| **Monitoring**     | `CortexMonitoringService`    | `Services/AI/CortexMonitoringService.php`          |
| **Telemetry Logs** | `ai_query_logs`              | Tablo: NLP sorgu loglari                           |
|                    | `ai_translation_logs`        | Tablo: Çeviri loglari                              |
|                    | `buyer_match_logs`           | Tablo: Eşleşme loglari                             |
|                    | `ai_deal_prediction_logs`    | Tablo: Tahmin loglari                              |

---

## Motor Bazlı Döngüler

### 1. Opportunity Loop

```
Projection (ListingVelocityProjection, MarketTrendProjection)
    ↓
OpportunityScoringService → fırsat skoru üretir
    ↓
Danışman ilanı açtı mı? (kullanıcı aksiyonu)
    ↓
Müşteri dönüş geldi mi? (sonuç sinyali)
    ↓
İlan satıldı mı? (gerçek sonuç)
    ↓
AiTelemetryService → skor isabeti kaydedilir
    ↓
AiLearningSignalService → bir sonraki scoring için ağırlık güncellemesi
```

**Telemetry tablosu:** `ai_query_logs`
**Mevcut servisler:** `OpportunityDetectionService`, `OpportunityScoringService`, `AiTelemetryService`

### 2. Buyer Match Loop

```
Projection (BuyerIntentProjection, TalepMatchProjection)
    ↓
BuyerMatchScoringService → eşleşme skoru üretir
    ↓
Danışman iletişime geçti mi?
    ↓
Randevu oluştu mu?
    ↓
Teklif verildi mi?
    ↓
Eşleşme doğru muydu? (satış sonucu)
    ↓
BuyerMatchTelemetryService → isabet kaydı
    ↓
MatchingFeedbackService → ağırlık sinyali
```

**Telemetry tablosu:** `buyer_match_logs`
**Mevcut servisler:** `BuyerMatchDetectionService`, `BuyerMatchScoringService`, `BuyerMatchTelemetryService`, `MatchingFeedbackService`

### 3. Deal Predictor Loop

```
Projection (ListingVelocityProjection, MarketTrendProjection)
    ↓
DealScoringService → satış tahmini + süre tahmini
    ↓
Gerçek satış süresi ne oldu?
    ↓
DealTelemetryService → tahmin isabeti kaydı
    ↓
CortexLearningService → model ağırlık güncellemesi
```

**Telemetry tablosu:** `ai_deal_prediction_logs`
**Mevcut servisler:** `DealPredictionService`, `DealScoringService`, `DealTelemetryService`
**Snapshot:** `deal_prediction_snapshots` (günlük izleme)

### 4. Price Advisor Loop

```
MarketIntelligenceService + CortexPriceForecastService
    ↓
Fiyat aralığı önerisi üretilir
    ↓
Danışman bu fiyatı kullandı mı?
    ↓
İlan ne sürede satıldı?
    ↓
Fiyat önerisi başarılı mıydı?
    ↓
AiTelemetryService → isabet kaydı
    ↓
AutoLearningService → bölge ağırlık güncelleme sinyali
```

**Mevcut servisler:** `CortexPriceForecastService`, `MarketIntelligenceService`, `AiTelemetryService`, `AutoLearningService`

---

## Auditability (Denetlenebilirlik)

Tüm AI kararları şu katmanlarda denetlenebilir:

| Katman          | Araç                                                                       |
| --------------- | -------------------------------------------------------------------------- |
| **Log**         | `ai_query_logs`, `buyer_match_logs`, `ai_deal_prediction_logs`             |
| **Snapshot**    | `buyer_match_snapshots`, `deal_prediction_snapshots`, `proj_kpi_snapshots` |
| **Telemetry**   | `AiTelemetryService`, `AiTelemetryAggregator`                              |
| **Explanation** | `DealExplanationService`, `OpportunityFormatterService`                    |
| **Monitoring**  | `CortexMonitoringService`                                                  |

---

## Explainability (Açıklanabilirlik)

Her AI kararının neden o skoru ürettiği açıklanmalıdır:

| Motor          | Explanation Servisi                             |
| -------------- | ----------------------------------------------- |
| Opportunity    | `OpportunityFormatterService`                   |
| Buyer Match    | `BuyerMatchFormatterService` (6 dilde açıklama) |
| Deal Predictor | `DealExplanationService`                        |
| Price Advisor  | _(planlanıyor)_                                 |

---

## Öğrenme Sinyali Akışı

```
Kullanıcı aksiyonu → Telemetry log
    ↓
AiTelemetryAggregator → günlük / haftalık aggregasyon
    ↓
AiLearningSignalService → sinyal üretimi
    ↓
CortexLearningService → ağırlık güncelleme önerisi
    ↓
AutoLearningService → otomatik ayarlama (güvenli sınır içinde)
```

---

## Production Seal

Bu döngü aşağıdaki standartlara uygundur:

- ✅ Telemetry-first (her karar loglanır)
- ✅ Explainable AI (her skor açıklanır)
- ✅ Feedback loop (kullanıcı aksiyonu → sinyal)
- ✅ Auditable (snapshot + log + monitoring)
- ✅ Safe learning (AutoLearning güvenli sınır içinde)
