<?php

namespace Tests\Unit\Services\MarketIntelligence;

use App\DTOs\MarketIntelligence\PricingInsightDTO;
use App\Enums\IlanDurumu;
use App\Enums\MarketIntelligence\PricingPosition;
use App\Jobs\AITranslation\TranslateListingJob;
use App\Models\Ilan;
use App\Services\MarketIntelligence\BenchmarkService;
use App\Services\MarketIntelligence\PricingPositionService;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PricingPositionServiceTest extends TestCase
{

    private PricingPositionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent IlanObserver from dispatching TranslateListingJob synchronously
        Bus::fake(TranslateListingJob::class);

        // Seed required FK parent records for Ilan factory
        \Illuminate\Support\Facades\DB::table('ilan_kategorileri')->insertOrIgnore([
            ['id' => 1, 'name' => 'Konut', 'slug' => 'konut', 'parent_id' => null, 'aktiflik_durumu' => 1, 'display_order' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'name' => 'Daire', 'slug' => 'daire', 'parent_id' => 1, 'aktiflik_durumu' => 1, 'display_order' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->service = app(PricingPositionService::class);
    }

    /**
     * Helper: create N comparable listings in same location/category.
     */
    private function seedComps(int $count, array $base, float $m2Price = 10000.0): void
    {
        for ($i = 0; $i < $count; $i++) {
            $brut = 100; // fixed m2
            Ilan::factory()->create(array_merge($base, [
                'fiyat' => $m2Price * $brut,
                'brut_m2' => $brut,
                'net_m2' => 90,
                'yayin_durumu' => IlanDurumu::YAYINDA->value,
            ]));
        }
    }

    // ─── Test 1: Enough data + close price → FAIR ───

    public function test_fair_when_price_close_to_benchmark(): void
    {
        $base = [
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ana_kategori_id' => 7,
        ];

        // Seed 10 comps at ~10,000 TL/m2
        $this->seedComps(10, $base, 10000.0);

        // Subject listing at exactly median (sapma ~0%)
        $ilan = Ilan::factory()->create(array_merge($base, [
            'fiyat' => 10000.0 * 100, // 1,000,000 TL for 100 m2
            'brut_m2' => 100,
            'net_m2' => 90,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]));

        $result = $this->service->analyze($ilan);

        $this->assertInstanceOf(PricingInsightDTO::class, $result);
        $this->assertFalse($result->insufficient_data);
        $this->assertEquals(PricingPosition::FAIR, $result->pricing_position);
        $this->assertGreaterThanOrEqual(80, $result->pricing_score);
        $this->assertNotNull($result->benchmark_price);
    }

    // ─── Test 2: Enough data + high price → OVERPRICED ───

    public function test_overpriced_when_price_above_benchmark(): void
    {
        $base = [
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ana_kategori_id' => 7,
        ];

        // Seed 10 comps at ~10,000 TL/m2
        $this->seedComps(10, $base, 10000.0);

        // Subject listing at 15% above median → OVERPRICED
        $ilan = Ilan::factory()->create(array_merge($base, [
            'fiyat' => 11500.0 * 100, // 1,150,000 TL = +15%
            'brut_m2' => 100,
            'net_m2' => 90,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]));

        $result = $this->service->analyze($ilan);

        $this->assertFalse($result->insufficient_data);
        $this->assertEquals(PricingPosition::OVERPRICED, $result->pricing_position);
        $this->assertGreaterThan(10, $result->price_delta_percent);
    }

    // ─── Test 3: Enough data + low price → UNDERPRICED ───

    public function test_underpriced_when_price_below_benchmark(): void
    {
        $base = [
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ana_kategori_id' => 7,
        ];

        // Seed 10 comps at ~10,000 TL/m2
        $this->seedComps(10, $base, 10000.0);

        // Subject listing at 20% below median → UNDERPRICED
        $ilan = Ilan::factory()->create(array_merge($base, [
            'fiyat' => 8000.0 * 100, // 800,000 TL = -20%
            'brut_m2' => 100,
            'net_m2' => 90,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]));

        $result = $this->service->analyze($ilan);

        $this->assertFalse($result->insufficient_data);
        $this->assertEquals(PricingPosition::UNDERPRICED, $result->pricing_position);
        $this->assertLessThan(-10, $result->price_delta_percent);
    }

    // ─── Test 4: Too few rows → INSUFFICIENT_DATA ───

    public function test_insufficient_data_when_too_few_comps(): void
    {
        $base = [
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ana_kategori_id' => 7,
        ];

        // Seed only 2 comps (below MIN_COMP_COUNT=5)
        $this->seedComps(2, $base, 10000.0);

        $ilan = Ilan::factory()->create(array_merge($base, [
            'fiyat' => 1000000,
            'brut_m2' => 100,
            'net_m2' => 90,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]));

        $result = $this->service->analyze($ilan);

        $this->assertTrue($result->insufficient_data);
        $this->assertEquals(PricingPosition::INSUFFICIENT_DATA, $result->pricing_position);
        $this->assertEquals(0, $result->pricing_score);
        $this->assertNull($result->benchmark_price);
    }

    // ─── Test 5: Determinism — same input → identical output ───

    public function test_deterministic_same_input_same_output(): void
    {
        $base = [
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ana_kategori_id' => 7,
        ];

        $this->seedComps(10, $base, 10000.0);

        $ilan = Ilan::factory()->create(array_merge($base, [
            'fiyat' => 1200000,
            'brut_m2' => 100,
            'net_m2' => 90,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]));

        // Clear cache to ensure fresh computation both times
        cache()->flush();
        $result1 = $this->service->analyze($ilan);

        cache()->flush();
        $result2 = $this->service->analyze($ilan);

        // Exact equality on all fields
        $this->assertEquals($result1->pricing_position, $result2->pricing_position);
        $this->assertEquals($result1->pricing_score, $result2->pricing_score);
        $this->assertEquals($result1->price_delta_percent, $result2->price_delta_percent);
        $this->assertEquals($result1->benchmark_price, $result2->benchmark_price);
        $this->assertEquals($result1->confidence, $result2->confidence);
        $this->assertEquals($result1->reason, $result2->reason);
        $this->assertEquals($result1->toArray(), $result2->toArray());
    }

    // ─── Test 6: Aggressively overpriced (+25%+) ───

    public function test_aggressively_overpriced_when_price_far_above(): void
    {
        $base = [
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ana_kategori_id' => 7,
        ];

        $this->seedComps(10, $base, 10000.0);

        // Subject at +30% above median
        $ilan = Ilan::factory()->create(array_merge($base, [
            'fiyat' => 13000.0 * 100,
            'brut_m2' => 100,
            'net_m2' => 90,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]));

        $result = $this->service->analyze($ilan);

        $this->assertFalse($result->insufficient_data);
        $this->assertEquals(PricingPosition::AGGRESSIVELY_OVERPRICED, $result->pricing_position);
        $this->assertGreaterThan(25, $result->price_delta_percent);
    }

    // ─── Test 7: Missing m2 → insufficient data ───

    public function test_insufficient_data_when_no_m2(): void
    {
        $ilan = Ilan::factory()->create([
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ana_kategori_id' => 7,
            'fiyat' => 1000000,
            'brut_m2' => null,
            'net_m2' => null,
            'alan_m2' => null,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]);

        $result = $this->service->analyze($ilan);

        $this->assertTrue($result->insufficient_data);
        $this->assertEquals(PricingPosition::INSUFFICIENT_DATA, $result->pricing_position);
    }

    // ─── Test 8: DTO toArray structure ───

    public function test_dto_to_array_has_expected_keys(): void
    {
        $base = [
            'il_id' => 1,
            'ilce_id' => 1,
            'mahalle_id' => 1,
            'ana_kategori_id' => 7,
        ];

        $this->seedComps(10, $base, 10000.0);

        $ilan = Ilan::factory()->create(array_merge($base, [
            'fiyat' => 1000000,
            'brut_m2' => 100,
            'net_m2' => 90,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
        ]));

        $result = $this->service->analyze($ilan);
        $array = $result->toArray();

        $expectedKeys = [
            'ilan_id',
            'current_price',
            'benchmark_price',
            'benchmark_min',
            'benchmark_max',
            'sample_size',
            'price_delta_percent',
            'pricing_position',
            'pricing_position_label',
            'pricing_score',
            'confidence',
            'insufficient_data',
            'reason',
            'confidence_score',
            'confidence_label',
            'confidence_reason',
            'demand_score',
            'demand_label',
            'demand_reason',
            'opportunity_score',
            'opportunity_action',
            'opportunity_reason',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $array, "Missing key: {$key}");
        }
    }
}
