# ADR-021: Sprint 2 Mimari Kararları — Domain Birleştirme, DriftDetection Kanonik Seçim, ModuleServiceProvider Yeniden Adlandırma

**Tarih:** 2026-06-15
**Durum:** KABUL EDİLDİ
**Yazarlar:** WenOX (Oturum 10)
**İlgili Görevler:** #19, #28, #58, #60, #61

---

## Bağlam

Sprint 2 sürecinde dört ayrı mimari sorun tespit edildi ve çözüme kavuşturuldu. Bu ADR, alınan kararları ve gerekçelerini belgelemektedir.

---

## Karar 1: app/Domains/ → app/Domain/ Birleştirme (#28)

### Sorun
İki paralel namespace mevcuttu:
- `app/Domains/PropertySchema/` — Eski DDD, 9 dosya, 1 controller kullanıyor
- `app/Domain/PropertyHub/` + `AI/` — Yeni V3 pipeline, 35+ dosya

`V2TemplateResolutionEngineAdapter` köprü olarak çalışıyor ama entegrasyon tamamlanmamış.

### Karar
`app/Domains/PropertySchema/` içeriği `app/Domain/PropertyHub/` altına taşındı. Adapter kaldırıldı. `app/Domain/` tek namespace olarak seçildi.

### Gerekçe
- İki namespace bakım yükünü ikiye katlar
- `V2TemplateResolutionEngineAdapter` teknik borç üreten geçici köprüydü
- V3 pipeline aktif kullanımda — eski sistem decommission edildi

**Commit:** `6909772`

---

## Karar 2: DriftDetectionService Kanonik Seçim (#58)

### Sorun
İki `DriftDetectionService` implementasyonu:
- `App\Modules\GovernanceCore\Services\DriftDetectionService` — `ActiveConfigRegistry` tabanlı
- `App\Services\Governance\DriftDetectionService` — `YayinTipiSablon` tabanlı

### Karar
`Core\DriftDetectionService` (ActiveConfigRegistry tabanlı) kanonik olarak seçildi. `Services\Governance\DriftDetectionService` kaldırıldı.

### Gerekçe
- `GovernanceEngineInterface::detectDrift()` zaten Core implementasyonunu kullanıyordu
- ActiveConfigRegistry tabanlı implementasyon daha geniş kapsamlı
- Tek implementasyon → belirsizlik sıfıra indi

**Commit:** `a8cf352`

---

## Karar 3: ModuleServiceProvider Yeniden Adlandırma (#60)

### Sorun
İki sınıf aynı basit ismi taşıyordu:
- `App\Providers\ModuleServiceProvider`
- `App\Modules\ModuleServiceProvider`

Laravel container çakışma riski, IDE autocompletion karmaşası.

### Karar
`App\Providers\ModuleServiceProvider` → `App\Providers\CoreModuleServiceProvider` olarak yeniden adlandırıldı.

### Gerekçe
- `App\Modules\ModuleServiceProvider` modüllerin kendi provider'ı — domain-specific, yerinde kalmalı
- `CoreModuleServiceProvider` adı projenin çekirdek servis sağlayıcısı olduğunu açıkça belirtir

**Commit:** `6125ca3`

---

## Karar 4: YalihanCortex God Object Dekompoze (#19)

### Sorun
`YalihanCortex` sınıfı 5800+ satır, 35+ bağımlılık — bakımı imkansız hale gelmişti.

### Karar
Aşamalı dekompoze yapıldı:
- `suggestCategory()` → `CortexQualityService`
- `getTopChurnRisks()` → `CortexPredictionService`
- `analyzeMarketTrends()` → `CortexIntelligenceService`

Kalan: VoiceSearch + Notification integration katmanları (Sprint 4'e ertelendi).

**Sonuç:** 5800+ → 3139 satır (~2700 satır tahliye)

### Gerekçe
- God Object her sprint büyüyordu — önce durdur, sonra böl
- Domain servislere çıkarılan metodlar bağımsız test edilebilir hale geldi
- `YalihanCortex`'e yeni bağımlılık ekleme FREEZE kararı alındı

**Commit:** `5004346`

---

## Sonuçlar

- `app/Domain/` tek namespace ✅
- `Core\DriftDetectionService` kanonik ✅
- `CoreModuleServiceProvider` isim çakışması giderildi ✅
- `YalihanCortex` 3139 satıra indirildi, freeze aktif ✅

## İlgili ADR'ler

- [ADR-020: Governance Diff Viewer CLI Read Model](./020-governance-diff-viewer-cli-read-model.md)
- [ADR-2026-05-15: Bekçi v2.1 Cognitive Guardian AST](./2026-05-15-bekci-v2-1-cognitive-guardian-ast.md)
