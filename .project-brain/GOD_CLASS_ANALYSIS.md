# God Class Analiz Raporu

**Tarih:** 2026-10-09
**Konu:** God Class ve Legacy Service Analizi

---

## 1. YalihanCortex (2,409 satir)

### Sorumluluklar
YalihanCortex tek bir sınıfta **34 public method** içeriyor:

| Kategori | Method Sayisi | Ornekler |
|----------|---------------|----------|
| Matching | 3 | detectBuyerMatches, matchForSale |
| Prediction | 4 | predictDeal, predictSalesForecast |
| Analysis | 8 | analyzeMarketTrends, analyzeQualityOutcomes |
| Generation | 6 | generateIlanTitle, generateDescription |
| Churn | 2 | calculateChurnRisk, getTopChurnRisks |
| Notifications | 2 | sendNotification, broadcastNotification |
| Multilingual | 2 | generateMultilingualTitle/Description |
| Context/Market | 4 | analyzeContext, analyzeTeamPerformance |
| Miscellaneous | 3 | processVoiceSearch, triggerN8nWorkflow |

### Kompozisyon Yapisi
YalihanCortex aslinda Facade pattern kullaniyor:

```php
protected CortexMatchingService $matchingService;
protected CortexIntelligenceService $intelligenceService;
protected CortexContentService $contentService;
protected CortexPredictionService $predictionService;
protected CortexQualityService $qualityService;
protected CortexTeamService $teamService;
```

Her method bu service'lere delegate ediyor:
```php
public function detectBuyerMatches(Ilan $ilan): array
{
    return $this->matchingService->detectBuyerMatches($ilan);
}
```

### Parcalanabilir mi?
**Hayir gerek yok** — Zaten kompozisyon ile parçalanmış durumda. Sadece facade.

---

## 2. AIController (1,114 satir)

### Endpoint'ler (27 adet)

| Endpoint | Method | Satir |
|----------|--------|-------|
| /ai/briefing | GET | 99 |
| /ai/analyze | POST | 112 |
| /ai/churn/{id} | GET | 156 |
| /ai/top-churns | GET | 200 |
| /ai/suggest | POST | 234 |
| /ai/generate | POST | 263 |
| /ai/health | GET | 292 |
| /ai/video/start | POST | 311 |
| /ai/video/status | GET | 332 |
| /ai/providers | GET | 354 |
| /ai/provider/switch | POST | 370 |
| /ai/stats | GET | 399 |
| /ai/logs | GET | 427 |
| /ai/suggest-title | POST | 474 |
| /ai/price-suggest | POST | 698 |
| /ai/find-matches | POST | 756 |
| /ai/generate-description | POST | 828 |
| /ai/feedback | POST | 920 |
| /ai/negotiation | GET | 985 |

### Logic Dagilimi

| Tip | Sayi | Durum |
|-----|------|-------|
| Controller logic | 3 | buildTitlePrompt, buildDescriptionPrompt, buildPricePrompt |
| Cortex call | 24 | Tüm logic service'e delegate |
| Validation | 1 | Request validation |

### Parcalanabilir mi?
**Olasi** — Endpoint'ler AI domaininde alt controller'lara ayrilabilir:
- AIController (core)
- AIVideoController (video render)
- AIMarketController (trends, stats)
- AIChurnController (churn analysis)

**Ancak:** Simdilik gerek yok — mevcut yapı çalışıyor.

---

## 3. LEGACY Service Kullanimi

### IlanService Kullanimi

| Dosya | Kullanim |
|-------|----------|
| app/Http/Controllers/Admin/IlanCrudController.php | CRUD islemleri |
| app/Http/Controllers/Owner/OwnerIlanController.php | Owner panel |
| app/Services/Marketing/AssetEngine.php | Asset olusturma |
| app/Services/Marketing/DynamicSloganService.php | Slogan uretimi |
| database/seeders/ | Test/demo verileri |

### IlanBulkService Kullanimi

| Dosya | Kullanim |
|-------|----------|
| app/Http/Controllers/Admin/IlanBulkController.php | Bulk islemler |
| app/Http/Controllers/Api/V1/BulkManagementController.php | API bulk |

### LeadService Kullanimi

| Dosya | Kullanim |
|-------|----------|
| app/Http/Controllers/Api/V1/MobileLeadController.php | Mobile API |
| app/Http/Controllers/Api/WhatsAppWebhookController.php | WhatsApp |
| app/Http/Controllers/Api/InstagramWebhookController.php | Instagram |
| app/Http/Controllers/Api/FacebookWebhookController.php | Facebook |

### Durum Degerlendirmesi

| Service | Deprecated mi? | Kullanim | Durum |
|---------|---------------|----------|-------|
| IlanService | Hayir | 4 dosya | **AKTIF** |
| IlanBulkService | Hayir | 2 dosya | **AKTIF** |
| LeadService | Hayir | 4 dosya | **AKTIF** |

**NOT:** Bunlar LEGACY degil — aktif kullanımdaki service'ler.

---

## 4. Mimari Degerlendirme

### God Class Riskleri

| Sinif | Risk | Sebep |
|-------|------|-------|
| YalihanCortex | Dusuk | Zaten facade pattern + composition |
| AIController | Orta | 27 endpoint tek dosyada |

### Mimari Kalite

| Ozellik | Durum | Not |
|---------|-------|-----|
| SRP uyumu | Orta | YalihanCortex facade, iyi |
| Controller boyutu | Yuksel | AIController 1114 satir |
| Service ayrimi | Iyi | Domain service'ler mevcut |

---

## 5. Oneriler

### Kisa Vadeli (Dusuk Oncelik)
1. **AIController** — Endpoint'leri alt controller'lara ayir (opsiyonel)

### Orta Vadeli
2. **IlanService/LeadService** — Namespace temizligi (App\Services\Ilan\IlanService vs App\Services\IlanService)

### Yapilmasi Gerekenler
- **Hayir** — Simdilik yapilacak birsey yok
- Tum service'ler aktif kullanımda
- God class riski dusunuk seviyede

---

## 6. Sonuc

| Analiz | Durum |
|--------|-------|
| YalihanCortex | **Facade Pattern** — Sorun yok |
| AIController | **Monolitik ama calisiyor** — Refactor opsiyonel |
| Legacy Service'ler | **Hepsi AKTIF** — Kullanilmayan yok |

**Toplam:** 10 dosya bu service'leri kullaniyor — aktif kod.

---

## Rapor Durumu
**Analiz tamam — degisiklik onerme yok.**
