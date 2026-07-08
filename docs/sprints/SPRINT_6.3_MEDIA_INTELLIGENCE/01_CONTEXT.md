# 01_CONTEXT.md — Sprint 6.3

## Era: ERA III — Ürün Aşaması

Sprint 6.3, YALIHAN OS ERA III (Ürün Aşaması) kapsamında Media Intelligence sprint'idir.

---

## Önceki Sprint: Sprint 6.2 — Location Intelligence ✅

Sprint 6.2 Location Intelligence ile:
- GeocodingService (Nominatim + AdresDB fallback)
- LocationOrchestrator (full pipeline)
- Location API (3 endpoints)
- IlanLocationSyncService + SyncIlanLocationJob
- Dashboard Widget (Alpine.js)
- IlanService cache entegrasyonu
- Unit + Feature testler
- POI Database (194 Bodrum POI)

tamamlandı ve `v6.2-location-intelligence-certified` tag'i oluşturuldu.

---

## Bu Sprint: Sprint 6.3 — Media Intelligence Core

### Soru
"Bir gayrimenkul ilanının fotoğraf portföyü ne kadar kaliteli, eksik olan oda türleri neler, ve en iyi kapak fotoğrafı hangisi?"

### Yanıt → Media Intelligence Pipeline

```
Photo Upload
    ↓
Room Detection (RoomDetectionService)
    → Kural tabanlı: pool, view, living_room, bedroom, kitchen, bathroom, terrace, garden, exterior
    ↓
Quality Analysis (ImageQualityEngine)
    → Laplacian blur, brightness, exposure, sharpness detection
    ↓
Coverage Analysis (CoverageAnalyzer)
    → Eksik oda türlerini tespit eder
    ↓
Hero Selection (HeroImageSelector)
    → Kapak fotoğrafı seçimi (oda_turu önceliği + kalite)
    ↓
Media Health Score
    → 0-100 arası portföy sağlık skoru
    ↓
Events: MediaAnalyzed, HeroImageSelected, MediaHealthUpdated
    ↓
Async Job: AnalyzeMediaJob (queue)
```

---

## AI Vision Roadmap

Sprint 6.4 → GPT-4 Vision API entegrasyonu
- AI tabanlı oda tespiti
- AI kalite analizi
- AI metin açıklaması

Sprint 6.5 → Publishing Intelligence
- Medya + Location + Pricing + Readiness birleşik score
