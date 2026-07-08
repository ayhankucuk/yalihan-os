<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\HeroImageSelector;
use PHPUnit\Framework\TestCase;

/**
 * Hero Image Selector Unit Tests — Sprint 6.3
 */
class HeroImageSelectorTest extends TestCase
{
    private HeroImageSelector $selector;

    protected function setUp(): void
    {
        $this->selector = new HeroImageSelector();
    }

    public function test_select_returns_pool_as_hero_when_highest_quality(): void
    {
        $photos = [
            100 => [
                'fotograf_id' => 100,
                'oda_turu' => 'pool',
                'oda_guven_skoru' => 90,
                'kalite_ayrinti' => ['sharpness' => 95, 'brightness' => 80, 'exposure' => 85],
            ],
            101 => [
                'fotograf_id' => 101,
                'oda_turu' => 'kitchen',
                'oda_guven_skoru' => 80,
                'kalite_ayrinti' => ['sharpness' => 60, 'brightness' => 70, 'exposure' => 65],
            ],
        ];

        $result = $this->selector->select($photos);

        $this->assertEquals(100, $result['hero_fotograf_id']);
        $this->assertGreaterThan(0, $result['hero_score']);
    }

    public function test_select_returns_view_when_pool_not_present(): void
    {
        $photos = [
            [
                'fotograf_id' => 200,
                'oda_turu' => 'view',
                'oda_guven_skoru' => 85,
                'kalite_ayrinti' => ['sharpness' => 90, 'brightness' => 85, 'exposure' => 88],
            ],
            [
                'fotograf_id' => 201,
                'oda_turu' => 'bedroom',
                'oda_guven_skoru' => 75,
                'kalite_ayrinti' => ['sharpness' => 50, 'brightness' => 60, 'exposure' => 55],
            ],
        ];

        $result = $this->selector->select($photos);

        $this->assertEquals(200, $result['hero_fotograf_id']);
    }

    public function test_select_returns_null_when_empty(): void
    {
        $result = $this->selector->select([]);

        $this->assertNull($result['hero_fotograf_id']);
        $this->assertEquals(0.0, $result['hero_score']);
    }

    public function test_select_returns_highest_quality_when_same_room(): void
    {
        $photos = [
            [
                'fotograf_id' => 300,
                'oda_turu' => 'living_room',
                'oda_guven_skoru' => 80,
                'kalite_ayrinti' => ['sharpness' => 40, 'brightness' => 40, 'exposure' => 40],
            ],
            [
                'fotograf_id' => 301,
                'oda_turu' => 'living_room',
                'oda_guven_skoru' => 90,
                'kalite_ayrinti' => ['sharpness' => 95, 'brightness' => 90, 'exposure' => 92],
            ],
        ];

        $result = $this->selector->select($photos);

        $this->assertEquals(301, $result['hero_fotograf_id']);
    }

    public function test_scoreAll_assigns_score_to_every_photo(): void
    {
        $photos = [
            [
                'fotograf_id' => 400,
                'oda_turu' => 'bedroom',
                'oda_guven_skoru' => 80,
                'kalite_ayrinti' => ['sharpness' => 80, 'brightness' => 80, 'exposure' => 80],
            ],
            [
                'fotograf_id' => 401,
                'oda_turu' => 'other',
                'oda_guven_skoru' => 30,
                'kalite_ayrinti' => ['sharpness' => 30, 'brightness' => 30, 'exposure' => 30],
            ],
        ];

        $scores = $this->selector->scoreAll($photos);

        $this->assertCount(2, $scores);
        $this->assertGreaterThan($scores[401], $scores[400]);
    }
}
