<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\CoverageAnalyzer;
use App\DTOs\Media\MediaRoomDTO;
use PHPUnit\Framework\TestCase;

/**
 * Coverage Analyzer Unit Tests — Sprint 6.3
 */
class CoverageAnalyzerTest extends TestCase
{
    private CoverageAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new CoverageAnalyzer();
    }

    public function test_analyze_with_no_rooms(): void
    {
        $result = $this->analyzer->analyze([]);

        $this->assertEquals(0.0, $result['coverage']);
        $this->assertCount(9, $result['missing_rooms']);
        $this->assertFalse($result['all_required']);
    }

    public function test_analyze_with_pool_and_view(): void
    {
        $rooms = [
            new MediaRoomDTO('pool', 'Havuz', 80, 1, [1]),
            new MediaRoomDTO('view', 'Manzara', 85, 1, [2]),
        ];

        $result = $this->analyzer->analyze($rooms);

        $this->assertEquals(2 / 9, $result['coverage'], '', 0.01);
        // pool ve view MEVCUT — missing_rooms'da OLMAMALI
        $this->assertNotContains('pool', $result['missing_rooms']);
        $this->assertNotContains('view', $result['missing_rooms']);
        // bedroom OLMADIĞI için missing_rooms'da olmalı
        $this->assertContains('bedroom', $result['missing_rooms']);
        $this->assertFalse($result['all_required']);
    }

    public function test_analyze_all_required_rooms(): void
    {
        $required = $this->analyzer->getRequiredRooms();
        $rooms = array_map(fn($t) => new MediaRoomDTO($t, $t, 80, 1, [1]), $required);

        $result = $this->analyzer->analyze($rooms);

        $this->assertEquals(1.0, $result['coverage']);
        $this->assertEmpty($result['missing_rooms']);
        $this->assertTrue($result['all_required']);
    }

    public function test_getCoverageScore_returns_0_for_empty(): void
    {
        $score = $this->analyzer->getCoverageScore([]);
        $this->assertEquals(0, $score);
    }

    public function test_getCoverageScore_returns_100_for_full_coverage(): void
    {
        $required = $this->analyzer->getRequiredRooms();
        $rooms = array_map(fn($t) => new MediaRoomDTO($t, $t, 80, 1, [1]), $required);

        $score = $this->analyzer->getCoverageScore($rooms);

        $this->assertEquals(100, $score);
    }

    public function test_getRequiredRooms_returns_9_types(): void
    {
        $rooms = $this->analyzer->getRequiredRooms();
        $this->assertCount(9, $rooms);
        $this->assertContains('living_room', $rooms);
        $this->assertContains('pool', $rooms);
        $this->assertContains('view', $rooms);
    }
}
