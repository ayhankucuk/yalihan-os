# 07_HANDOFF.md — Sprint 6.3 → Sprint 6.4

## Sprint 6.3 Handoff

**From:** Sprint 6.3 (Kilo Agent)
**To:** Sprint 6.4 Owner (AI Vision + Publishing Intelligence)
**Date:** 2026-07-08

---

## What Is Ready for Sprint 6.4

### Stable Interfaces

#### MediaIntelligenceEngine
```php
$engine = app(MediaIntelligenceEngine::class);
$result = $engine->analyze($ilanId, $dispatchEvents = true);
// Returns: MediaAnalysisDTO
```

#### MediaController
```
POST /api/media/analyze  → MediaAnalysisDTO
GET  /api/media/score/{id} → health score (cached)
```

#### IlanService
```php
$ilan = Ilan::find($id);
$ilan->media_health_score   // 0-100
$ilan->media_quality_score  // 0-100
$ilan->eksik_odalar         // ['pool', 'view', ...]
$ilan->hero_fotograf_id     // int|null
$ilan->media_tamamlanma_oran // 0-100
```

---

## Sprint 6.4 priorities

### 1. AI Vision API Entegrasyonu

Replace `RoomDetectionService` with GPT-4 Vision:
```
Input:  photo URL or base64
Output: { oda_turu, guven_skoru, kalite_puani, kalite_ayrinti }
```

Replace `ImageQualityEngine` with AI-based quality analysis.

**Files to change:**
- `app/Services/Media/RoomDetectionService.php` → AI version
- `app/Services/Media/ImageQualityEngine.php` → AI version
- Keep interface the same for `MediaIntelligenceEngine`

### 2. Real Photo Pipeline Test

Current tests use mock data. Need end-to-end test with real photos.

### 3. (Optional) Workspace Tam Entegrasyon

`WorkspaceSummaryService` → media payload fully integrated.

---

## Key Files for Sprint 6.4

| File | Role |
|------|------|
| `app/Services/Media/MediaIntelligenceEngine.php` | Pipeline orchestrator — DO NOT CHANGE interface |
| `app/Services/Media/RoomDetectionService.php` | Replace with AI |
| `app/Services/Media/ImageQualityEngine.php` | Replace with AI |
| `app/Services/Media/CoverageAnalyzer.php` | Keep — rule-based |
| `app/Services/Media/HeroImageSelector.php` | Keep — score formula stable |
| `app/DTOs/Media/MediaAnalysisDTO.php` | DO NOT CHANGE |
| `app/DTOs/Media/MediaPhotoDTO.php` | DO NOT CHANGE |
| `app/Http/Controllers/Api/MediaController.php` | Keep |
| `tests/Feature/Api/MediaIntelligenceApiTest.php` | Extend with AI tests |

---

## Known Technical Debt

1. **IlanFotografiFactory yok** — testlerde `IlanFotografi::create()` kullanılıyor
2. **ImageQualityEngine simulated** — gerçek image processing yerine random scores
3. **No real photo upload** — testlerde URL string kullanılıyor

---

## What NOT to Touch in Sprint 6.4

- `MediaIntelligenceEngine.php` interface
- `MediaAnalysisDTO` structure
- `MediaPhotoDTO` structure
- API routes
- `IlanService.getMediaSummary()`
- `IlanCrudController.show()`
