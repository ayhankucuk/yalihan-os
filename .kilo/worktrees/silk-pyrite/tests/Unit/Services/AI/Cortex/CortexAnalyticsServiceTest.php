<?php

namespace Tests\Unit\Services\AI\Cortex;

use App\Models\Ilan;
use App\Models\Kisi;
use App\Services\AI\Cortex\CortexAnalyticsService;
use Tests\TestCase;

/**
 * Context7 TDD: Test First Approach
 * Yalıhan Bekçi: CortexAnalyticsService unit tests
 *
 * @group cortex
 * @group analytics
 * @group skip-until-migration-complete
 */
class CortexAnalyticsServiceTest extends TestCase
{

    private CortexAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CortexAnalyticsService::class);
    }

    /** @test */
    public function it_calculates_market_trends_with_proper_structure()
    {
        // Arrange
        Ilan::factory()->count(5)->create([
            'aktiflik_durumu' => true,
            'fiyat' => 1000000,
        ]);

        // Act
        $result = $this->service->analyzeMarketTrends();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('trends', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertEquals('6_months', $result['period']);
    }

    /** @test */
    public function it_filters_market_trends_by_location()
    {
        // Arrange
        Ilan::factory()->create([
            'il_id' => 1,
            'aktiflik_durumu' => true,
        ]);

        Ilan::factory()->create([
            'il_id' => 2,
            'aktiflik_durumu' => true,
        ]);

        // Act
        $result = $this->service->analyzeMarketTrends(['il_id' => 1]);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(['il_id' => 1], $result['filters_applied']);
    }

    /** @test */
    public function it_analyzes_quality_outcomes()
    {
        // Arrange
        Ilan::factory()->count(3)->create([
            'aktiflik_durumu' => true,
            'yayin_durumu' => 'yayinda',
        ]);

        // Act
        $result = $this->service->analyzeQualityOutcomes();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('outcomes', $result);
        $this->assertArrayHasKey('total_analyzed', $result);
    }

    /** @test */
    public function it_calculates_churn_risk_for_customer()
    {
        // Arrange
        $kisi = Kisi::factory()->create([
            'aktiflik_durumu' => true,
            'last_contacted_at' => now()->subDays(100), // High risk
        ]);

        // Act
        $result = $this->service->calculateChurnRisk($kisi);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('risk_score', $result);
        $this->assertArrayHasKey('risk_level', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertGreaterThan(50, $result['risk_score']); // Should be high risk
    }

    /** @test */
    public function it_returns_low_churn_risk_for_active_customer()
    {
        // Arrange
        $kisi = Kisi::factory()->create([
            'aktiflik_durumu' => true,
            'last_contacted_at' => now()->subDays(5),
        ]);

        Ilan::factory()->count(3)->create([
            'ilan_sahibi_id' => $kisi->id,
            'aktiflik_durumu' => true,
        ]);

        // Act
        $result = $this->service->calculateChurnRisk($kisi);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('low', $result['risk_level']);
        $this->assertLessThan(30, $result['risk_score']);
    }

    /** @test */
    public function it_analyzes_user_listings_performance()
    {
        // Arrange
        $userId = 1;
        Ilan::factory()->count(5)->create([
            'danisman_id' => $userId,
            'aktiflik_durumu' => true,
            'goruntulenme' => 100,
        ]);

        // Act
        $result = $this->service->analyzeMyListings($userId);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals($userId, $result['user_id']);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertEquals(5, $result['metrics']['total_listings']);
        $this->assertArrayHasKey('top_performers', $result);
    }

    /** @test */
    public function it_generates_report_by_type()
    {
        // Arrange
        Ilan::factory()->count(3)->create();

        // Act
        $result = $this->service->generateReport('market_trends');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('trends', $result);
    }

    /** @test */
    public function it_handles_unknown_report_type_gracefully()
    {
        // Act
        $result = $this->service->generateReport('invalid_type');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown report type', $result['error']);
    }

    /** @test */
    public function it_gets_top_churn_risks()
    {
        // Arrange
        Kisi::factory()->count(15)->create([
            'aktiflik_durumu' => true,
            'last_contacted_at' => now()->subDays(random_int(1, 120)),
        ]);

        // Act
        $result = $this->service->getTopChurnRisks(10);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertCount(10, $result['top_risks']);
        $this->assertEquals(15, $result['total_analyzed']);

        // Verify sorted by risk_score descending
        $scores = collect($result['top_risks'])->pluck('risk_score');
        $this->assertEquals($scores->sort()->reverse()->values(), $scores);
    }
}
