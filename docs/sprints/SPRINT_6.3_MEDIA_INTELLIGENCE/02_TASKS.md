# 02_TASKS.md — Sprint 6.3

## Sprint Tasks

| # | Task | Status | Evidence |
|---|------|--------|----------|
| 1 | MediaIntelligenceEngine orchestrator | ✅ | `app/Services/Media/MediaIntelligenceEngine.php` |
| 2 | RoomDetectionService (10 oda türü) | ✅ | `app/Services/Media/RoomDetectionService.php` |
| 3 | ImageQualityEngine (blur/brightness/exposure/sharpness) | ✅ | `app/Services/Media/ImageQualityEngine.php` |
| 4 | CoverageAnalyzer | ✅ | `app/Services/Media/CoverageAnalyzer.php` |
| 5 | HeroImageSelector | ✅ | `app/Services/Media/HeroImageSelector.php` |
| 6 | WorkspaceMediaService | ✅ | `app/Services/Media/WorkspaceMediaService.php` |
| 7 | Media DTOs (4 DTO) | ✅ | `app/DTOs/Media/*.php` |
| 8 | Media Events (3 event) | ✅ | `app/Events/Media/*.php` |
| 9 | AnalyzeMediaJob | ✅ | `app/Jobs/AnalyzeMediaJob.php` |
| 10 | MediaController + MediaAnalyzeRequest | ✅ | `app/Http/Controllers/Api/MediaController.php` |
| 11 | API routes (analyze + score) | ✅ | `routes/api.php` |
| 12 | media-intelligence-card.blade.php | ✅ | `resources/views/components/media-intelligence-card.blade.php` |
| 13 | Database migrations (2) | ✅ | `database/migrations/2026_07_08_*` |
| 14 | ilan_metinleri migration (test fix) | ✅ | `database/migrations/2026_07_08_*` |
| 15 | kapak_fotografi migration (test fix) | ✅ | `database/migrations/2026_07_08_*` |
| 16 | Unit tests: CoverageAnalyzer (5) | ✅ | `tests/Unit/Services/Media/CoverageAnalyzerTest.php` |
| 17 | Unit tests: HeroImageSelector (5) | ✅ | `tests/Unit/Services/Media/HeroImageSelectorTest.php` |
| 18 | Unit tests: DTOs (7) | ✅ | `tests/Unit/Services/Media/MediaDTOsTest.php` |
| 19 | Unit tests: MediaLogic (9) | ✅ | `tests/Unit/Services/Media/MediaLogicTest.php` |
| 20 | Feature tests: MediaIntelligenceApiTest (11) | ✅ | `tests/Feature/Api/MediaIntelligenceApiTest.php` |
| 21 | API contract update (success/data/meta/error) | ✅ | `app/Http/Controllers/Api/MediaController.php` |
| 22 | IlanService getMediaSummary() | ✅ | `app/Services/Ilan/IlanService.php` |
| 23 | Ilan model: eksik_odalar cast | ✅ | `app/Models/Ilan.php` |
| 24 | Closure docs | ✅ | `docs/sprints/SPRINT_6.3_MEDIA_INTELLIGENCE/` |

---

## AI Vision API — Sprint 6.4 Kapsamı (OUT)

Aşağıdakiler Sprint 6.4'e kaldı:
- GPT-4 Vision tabanlı oda tespiti
- AI tabanlı kalite analizi
- AI tabanlı metin açıklaması üretimi

## Gerçek Fotoğraf ile Pipeline Test — Sprint 6.4 Kapsamı (OUT)

Testler mock'suz gerçek veri ile çalıştırılacak.
