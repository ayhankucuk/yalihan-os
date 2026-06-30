# YalihanCortex Dekompoze Planı

> Sprint 2'de başlandı, Sprint 4'te tamamlanacak
> İlgili görev: #19 | ADR-021
> Son güncelleme: 2026-06-16

---

## Mevcut Durum

| Metrik | Sprint 2 Öncesi | Şu An |
|--------|----------------|-------|
| Satır sayısı | 5800+ | **2385** |
| Ayrıştırılan domain servis | 0 | **15** |
| Kalan metod (Cortex'te) | 74 | ~30 |
| Bağımlılık sayısı | 35+ | ~20 |

**FREEZE kuralı:** `YalihanCortex`'e yeni bağımlılık veya metod ekleme yasak.

---

## Tamamlanan Ayrıştırmalar (Sprint 2)

| Metod | Taşındığı Servis | Commit |
|-------|-----------------|--------|
| `suggestCategory()` | `CortexQualityService` | `5004346` |
| `getTopChurnRisks()` | `CortexPredictionService` | `5004346` |
| `analyzeMarketTrends()` | `CortexIntelligenceService` | `5004346` |

### Mevcut Domain Servisler (`app/Services/AI/`)

```
Domains/
├── CortexContentService.php       — İçerik üretimi (başlık, açıklama)
├── CortexIntelligenceService.php  — Market analiz, trend
├── CortexMatchingService.php      — Alıcı-ilan eşleştirme
├── CortexPredictionService.php    — Churn riski, deal tahmin
├── CortexQualityService.php       — İlan kalite değerlendirme
├── CortexTeamService.php          — Takım performans analizi
Analytics/
└── CortexFeatureCoverageService.php

Ayrı servisler:
├── CortexLearningService.php
├── CortexMonitoringService.php
├── CortexNLPSearch.php
├── CortexNotificationService.php
├── CortexPriceForecastService.php
├── CortexTemplateAdvisor.php
├── CortexVisionService.php
└── CortexVoiceService.php
```

---

## Kalan Metodlar — Sprint 4 Hedefleri

### Grup A — VoiceSearch (CortexVoiceService'e taşı)
Şu an `YalihanCortex`'te kalıyor, `CortexVoiceService` var ama delegation eksik.

| Metod | Hedef Servis |
|-------|-------------|
| Voice search processing metodları | `CortexVoiceService` |

### Grup B — Notification Integration (CortexNotificationService'e taşı)
`CortexNotificationService` var ama `prioritizeNotifications()` hala Cortex'te.

| Metod | Hedef Servis |
|-------|-------------|
| `prioritizeNotifications()` | `CortexNotificationService` |

### Grup C — Market/Price (CortexPriceForecastService'e taşı)

| Metod | Hedef Servis |
|-------|-------------|
| `suggestPrice()` | `CortexPriceForecastService` |
| `compareMarketPrices()` | `CortexIntelligenceService` |
| `priceValuation()` | `CortexPriceForecastService` |

### Grup D — Lead/CRM (yeni CortexCRMService)

| Metod | Hedef Servis |
|-------|-------------|
| `evaluateLead()` | Yeni `CortexCRMService` |
| `getNegotiationStrategy()` | Yeni `CortexCRMService` |

### Grup E — Report Generation (yeni CortexReportService)

| Metod | Hedef Servis |
|-------|-------------|
| `generateReport()` | Yeni `CortexReportService` |
| `analyzeQualityOutcomes()` | `CortexQualityService` |
| `analyzeMyListings()` | `CortexIntelligenceService` |

### Grup F — Core (YalihanCortex'te kalmalı)
Bu metodlar `execute()` routing ve dispatching — taşınamaz.

| Metod | Neden Kalmalı |
|-------|--------------|
| `execute()` | Ana dispatch entry point |
| `analyzeContext()` | Cross-domain context aggregation |
| `getPerformance()` | Sistem geneli performans |

---

## Sprint 4 Uygulama Sırası

```
1. Grup B — prioritizeNotifications() → CortexNotificationService
   Risk: LOW — servis zaten var

2. Grup C — price metodları → CortexPriceForecastService
   Risk: LOW — servis zaten var

3. Grup A — VoiceSearch delegation tamamla
   Risk: MEDIUM — Whisper/OpenAI bağımlılığı

4. Grup D — CortexCRMService oluştur
   Risk: MEDIUM — yeni servis, DI wire gerekli

5. Grup E — CortexReportService oluştur
   Risk: MEDIUM — yeni servis
```

---

## Hedef Mimari (Sprint 4 Sonu)

```
YalihanCortex (hedef: ~600 satır)
    ↓ delegates to:
    ├── CortexContentService      (başlık/açıklama)
    ├── CortexIntelligenceService (market analiz)
    ├── CortexMatchingService     (eşleştirme)
    ├── CortexPredictionService   (churn, deal)
    ├── CortexQualityService      (ilan kalitesi)
    ├── CortexTeamService         (takım)
    ├── CortexCRMService          (lead, müzakere) [YENİ]
    ├── CortexReportService       (raporlar)       [YENİ]
    ├── CortexPriceForecastService(fiyat)
    ├── CortexNotificationService (bildirim önceliklendirme)
    └── CortexVoiceService        (sesli arama)
```

---

## Kurallar

1. Her ayrıştırma ayrı PR — tek commit'te tüm dekompoze yasak
2. Her PR'da: servis çıkarılır → Cortex'te delegation kalır → eski metod deprecated işaretlenir
3. Mevcut interface kontratı (`CortexServiceInterface`) değişmez
4. Her yeni servis `domain/AI/Contracts/` altında interface alır
5. `YalihanCortex::execute()` asla taşınamaz — routing entry point

---

## İlgili Dosyalar

| Dosya | Rol |
|-------|-----|
| [`app/Services/AI/YalihanCortex.php`](../../app/Services/AI/YalihanCortex.php) | Ana God Object (2385 satır) |
| [`app/Domain/AI/Contracts/CortexServiceInterface.php`](../../app/Domain/AI/Contracts/CortexServiceInterface.php) | Kontrat SSOT |
| [`app/Services/AI/Domains/`](../../app/Services/AI/Domains/) | Ayrıştırılmış domain servisleri |
| [`docs/adr/2026-06-15-sprint2-architecture-decisions.md`](../adr/2026-06-15-sprint2-architecture-decisions.md) | ADR-021 |
| [`docs/known-debt.md`](../known-debt.md) | #31 — Cortex teknik borç |
