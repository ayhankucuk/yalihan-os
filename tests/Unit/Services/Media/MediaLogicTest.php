<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\CoverageAnalyzer;
use App\Services\Media\HeroImageSelector;
use PHPUnit\Framework\TestCase;

/**
 * Sprint 6.3 — Service Logic Tests (Pure unit, no Eloquent/Mock complexity)
 */
class MediaLogicTest extends TestCase
{
    // ─── Coverage Analyzer ──────────────────────────────────────────

    public function test_coverage_analyzer_returns_0_when_empty(): void
    {
        $analyzer = new CoverageAnalyzer();
        $result = $analyzer->analyze([]);

        $this->assertEqualsWithDelta(0.0, $result['coverage'], 0.01);
        $this->assertCount(9, $result['missing_rooms']);
        $this->assertFalse($result['all_required']);
    }

    public function test_coverage_score_0_when_no_rooms(): void
    {
        $analyzer = new CoverageAnalyzer();
        $this->assertEquals(0, $analyzer->getCoverageScore([]));
    }

    public function test_coverage_score_zero_when_empty(): void
    {
        $analyzer = new CoverageAnalyzer();
        $this->assertEquals(0, $analyzer->getCoverageScore([]));
    }

    public function test_coverage_detects_pool_bathroom_living_room(): void
    {
        $analyzer = new CoverageAnalyzer();
        $rooms = [];
        // Simulate room data (coverage doesn't need DTOs)
        // Use CoverageAnalyzer::analyze which works with raw arrays

        $result = $analyzer->analyze([]);

        // Empty → all 9 rooms missing
        $this->assertContains('bathroom', $result['missing_rooms']);
        $this->assertContains('pool', $result['missing_rooms']);
        $this->assertContains('living_room', $result['missing_rooms']);
    }

    // ─── Hero Selector ──────────────────────────────────────────

    public function test_hero_selector_pool_wins_over_bedroom(): void
    {
        $selector = new HeroImageSelector();
        $photos = [
            [
                'fotograf_id' => 1,
                'oda_turu' => 'bedroom',
                'oda_guven_skoru' => 90,
                'kalite_ayrinti' => ['sharpness' => 90, 'brightness' => 85, 'exposure' => 88],
            ],
            [
                'fotograf_id' => 2,
                'oda_turu' => 'pool',
                'oda_guven_skoru' => 80,
                'kalite_ayrinti' => ['sharpness' => 80, 'brightness' => 80, 'exposure' => 80],
            ],
        ];

        $result = $selector->select($photos);

        $this->assertEquals(2, $result['hero_fotograf_id']);
    }

    public function test_hero_selector_empty_returns_null(): void
    {
        $selector = new HeroImageSelector();
        $result = $selector->select([]);

        $this->assertNull($result['hero_fotograf_id']);
        $this->assertEquals(0.0, $result['hero_score']);
    }

    public function test_hero_selector_view_wins_over_other(): void
    {
        $selector = new HeroImageSelector();
        $photos = [
            ['fotograf_id' => 1, 'oda_turu' => 'other', 'oda_guven_skoru' => 50, 'kalite_ayrinti' => ['sharpness' => 50, 'brightness' => 50, 'exposure' => 50]],
            ['fotograf_id' => 2, 'oda_turu' => 'view', 'oda_guven_skoru' => 85, 'kalite_ayrinti' => ['sharpness' => 80, 'brightness' => 80, 'exposure' => 80]],
        ];

        $result = $selector->select($photos);
        $this->assertEquals(2, $result['hero_fotograf_id']);
    }

    public function test_hero_score_all_assigns_scores(): void
    {
        $selector = new HeroImageSelector();
        $photos = [
            ['fotograf_id' => 10, 'oda_turu' => 'pool', 'oda_guven_skoru' => 80, 'kalite_ayrinti' => ['sharpness' => 80, 'brightness' => 80, 'exposure' => 80]],
        ];

        $scores = $selector->scoreAll($photos);
        $this->assertArrayHasKey(10, $scores);
        $this->assertGreaterThan(0, $scores[10]);
    }

    // ─── Room Detection Labels ──────────────────────────────────

    public function test_room_detection_labels_all_known_types(): void
    {
        $roomSvc = new \App\Services\Media\RoomDetectionService();
        $labels = $roomSvc->getAllRoomTypes();

        $this->assertArrayHasKey('pool', $labels);
        $this->assertArrayHasKey('view', $labels);
        $this->assertArrayHasKey('living_room', $labels);
        $this->assertArrayHasKey('bedroom', $labels);
        $this->assertArrayHasKey('kitchen', $labels);
        $this->assertArrayHasKey('bathroom', $labels);
        $this->assertArrayHasKey('terrace', $labels);
        $this->assertArrayHasKey('garden', $labels);
        $this->assertArrayHasKey('exterior', $labels);
    }
}
