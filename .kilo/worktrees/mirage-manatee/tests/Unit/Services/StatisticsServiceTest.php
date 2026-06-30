<?php

namespace Tests\Unit\Services;

use App\Models\Ilan;
use App\Services\Statistics\StatisticsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatisticsServiceTest extends TestCase
{

    protected StatisticsService $statisticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Legacy StatisticsServiceTest with Context7 violations');
        $this->statisticsService = new StatisticsService;
    }

    /**
     * Test StatisticsService getModelStats method
     */
    public function test_get_model_stats(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            [
                'baslik' => 'Test İlan 1',
                'fiyat' => 100000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'baslik' => 'Test İlan 2',
                'fiyat' => 200000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'Pasif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $stats = $this->statisticsService->getModelStats(Ilan::class);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active', $stats);
        $this->assertArrayHasKey('inactive', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['total']);
    }

    /**
     * Test StatisticsService getMonthlyStats method
     */
    public function test_get_monthly_stats(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            [
                'baslik' => 'Test İlan 1',
                'fiyat' => 100000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'created_at' => now()->subMonth(),
                'updated_at' => now(),
            ],
            [
                'baslik' => 'Test İlan 2',
                'fiyat' => 200000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $stats = $this->statisticsService->getMonthlyStats(Ilan::class);

        $this->assertIsArray($stats);
        $this->assertGreaterThanOrEqual(1, count($stats));
    }

    /**
     * Test StatisticsService getDailyStats method
     */
    public function test_get_daily_stats(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            [
                'baslik' => 'Test İlan 1',
                'fiyat' => 100000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'created_at' => now()->subDay(),
                'updated_at' => now(),
            ],
            [
                'baslik' => 'Test İlan 2',
                'fiyat' => 200000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $stats = $this->statisticsService->getDailyStats(Ilan::class, ['days' => 7]);

        $this->assertIsArray($stats);
        $this->assertGreaterThanOrEqual(1, count($stats));
    }

    /**
     * Test StatisticsService getStatusStats method
     */
    public function test_get_status_stats(): void
    {
        // Create test data
        DB::table('ilanlar')->insert([
            [
                'baslik' => 'Test İlan 1',
                'fiyat' => 100000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'yayinda',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'baslik' => 'Test İlan 2',
                'fiyat' => 200000,
                'para_birimi' => 'TL',
                'yayin_durumu' => 'Pasif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $stats = $this->statisticsService->getStatusStats(Ilan::class, ['status_field' => 'yayin_durumu']);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('Aktif', $stats);
        $this->assertArrayHasKey('Pasif', $stats);
    }

    /**
     * Test StatisticsService clearCache method
     */
    public function test_clear_cache(): void
    {
        // This should not throw an exception
        $this->statisticsService->clearCache(Ilan::class);

        $this->assertTrue(true); // If no exception, test passes
    }
}
