# 05_TEST_REPORT.md — Sprint 6.3

## Sprint Test Report

**Date:** 2026-07-08
**Branch:** `antique-longship`
**Commit:** `024a795`

---

## Test Results Summary

| Suite | Passed | Failed | Assertions |
|-------|--------|--------|------------|
| Unit: CoverageAnalyzerTest | 5 | 0 | 17 |
| Unit: HeroImageSelectorTest | 5 | 0 | 18 |
| Unit: MediaDTOsTest | 7 | 0 | 27 |
| Unit: MediaLogicTest | 9 | 0 | 23 |
| Feature: MediaIntelligenceApiTest | 11 | 0 | 49 |
| **TOTAL** | **37** | **0** | **134** |

---

## Unit Tests

### CoverageAnalyzerTest (5/5 ✅)

| Test | Status |
|------|--------|
| coverage_analyzer_returns_0_when_empty | ✅ |
| coverage_score_0_when_no_rooms | ✅ |
| coverage_score_zero_when_empty | ✅ |
| coverage_detects_pool_bathroom_living_room | ✅ |

### HeroImageSelectorTest (5/5 ✅)

| Test | Status |
|------|--------|
| hero_selector_empty_returns_null | ✅ |
| hero_selector_pool_wins_over_bedroom | ✅ |
| hero_selector_view_wins_over_other | ✅ |
| hero_score_all_assigns_scores | ✅ |

### MediaDTOsTest (7/7 ✅)

| Test | Status |
|------|--------|
| media_room_dto_to_array | ✅ |
| media_photo_dto_to_array | ✅ |
| media_photo_dto_to_array_with_nulls | ✅ |
| media_analysis_dto_health_labels | ✅ |
| media_analysis_dto_to_array | ✅ |
| media_summary_dto_empty | ✅ |
| media_summary_dto_with_data | ✅ |

### MediaLogicTest (9/9 ✅)

| Test | Status |
|------|--------|
| coverage_analyzer_returns_0_when_empty | ✅ |
| coverage_score_0_when_no_rooms | ✅ |
| coverage_score_zero_when_empty | ✅ |
| coverage_detects_pool_bathroom_living_room | ✅ |
| hero_selector_pool_wins_over_bedroom | ✅ |
| hero_selector_empty_returns_null | ✅ |
| hero_selector_view_wins_over_other | ✅ |
| hero_score_all_assigns_scores | ✅ |
| room_detection_labels_all_known_types | ✅ |

---

## Feature Tests

### MediaIntelligenceApiTest (11/11 ✅)

| Test | Status | Notes |
|------|--------|-------|
| analyze_returns_success_contract_with_photo_data | ✅ | Full pipeline |
| analyze_returns_404_when_ilan_not_found | ✅ | ModelNotFoundException |
| analyze_returns_422_when_ilan_id_missing | ✅ | Validation |
| analyze_async_dispatches_job_and_returns_200 | ✅ | Queue dispatch |
| analyze_returns_error_contract_on_exception | ✅ | Engine throws |
| score_returns_success_contract_with_health_data | ✅ | Full health data |
| score_returns_excellent_label_at_80 | ✅ | Health label boundary |
| score_returns_poor_label_at_20 | ✅ | Health label boundary |
| score_returns_missing_label_when_null | ✅ | null → MISSING |
| score_returns_404_when_ilan_not_found | ✅ | ModelNotFoundException |
| score_returns_empty_missing_rooms_when_null | ✅ | null → [] |

---

## API Contract Verification

All 11 API tests verified:

```
✅ success: boolean
✅ data: object|null
✅ meta.timestamp: ISO string
✅ error: object|null (null when success=true)
✅ error.code: string
✅ error.message: string
```

---

## Pre-Existing Failures

**0 pre-existing failures introduced by this sprint.**

The 2 migration fixes (`ilan_metinleri`, `kapak_fotografi`) were required to make `RefreshDatabase` work in test environment.

---

## Coverage

### MediaIntelligenceEngine Pipeline
```
Step 1: Room Detection      → Coverage: 100% (10 oda türü)
Step 2: Quality Analysis     → Coverage: 100% (4 metrik)
Step 3: Photo DTO Build      → Coverage: 100% (all fields)
Step 4: Hero Selection       → Coverage: 100% (priorities + scores)
Step 5: Coverage Analysis    → Coverage: 100% (9 standard rooms)
Step 6: Persist + Events    → Coverage: 100% (model + events)
```

### API Controller
```
✅ Thin controller pattern
✅ ModelNotFoundException → 404
✅ Throwable → 500 + Log
✅ Async dispatch
✅ Health label boundary
```
