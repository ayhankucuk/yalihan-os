# Sprint 6.3 — Media Intelligence Core

## Charter

**Sprint:** 6.3
**Start:** 2026-07-08
**Status:** CLOSED
**Owner:** YALIHAN OS AI Agent
**Branch:** `antique-longship`

---

## Mission

> "Sprint 6.2 kapandı. Sprint 6.3 ile Media Intelligence core altyapısını kuracağız — Photo Upload → Room Detection → Quality Analysis → Coverage → Hero Selection → Media Health Pipeline."

---

## Scope

### In Scope
- Media Intelligence Engine (orchestrator)
- Room Detection Service (kural tabanlı, 10 oda türü)
- Image Quality Engine (Laplacian blur, brightness, exposure, sharpness)
- Coverage Analyzer (eksik oda tespiti)
- Hero Image Selector (score formula)
- Media DTOs (MediaRoomDTO, MediaPhotoDTO, MediaAnalysisDTO, MediaSummaryDTO)
- Media Intelligence API (POST /analyze, GET /score/{id})
- Cockpit Dashboard Widget (media-intelligence-card.blade.php)
- Async Job (AnalyzeMediaJob)
- Database migrations (ilan_fotograflari + ilanlar media fields)
- Unit tests (CoverageAnalyzer, HeroImageSelector, DTOs, MediaLogic)
- Feature tests (MediaIntelligenceApiTest)
- IlanService getMediaSummary() entegrasyonu

### Out of Scope
- AI Vision API (GPT-4 Vision) — Sprint 6.4
- Gerçek fotoğraf ile pipeline test — Sprint 6.4
- WorkspaceSummaryService tam entegrasyonu — Backlog

---

## Definition of Done

| # | Criterion | Method |
|---|-----------|--------|
| 1 | MediaIntelligenceEngine pipeline çalışır | Unit test |
| 2 | API endpoints çalışır | Feature test |
| 3 | Cockpit card render olur | Manual |
| 4 | IlanService media summary çalışır | Unit test |
| 5 | 2 yeni migration SQLite uyumlu | Feature test |
| 6 | API contract: success/data/meta/error | Feature test |
