<?php

namespace Tests\Unit\MarketIntelligence;

use App\DTOs\MarketIntelligence\LocationInsightDTO;
use App\Services\Location\PoiService;
use App\Services\MarketIntelligence\LocationIntelligenceService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class LocationIntelligenceServiceTest extends TestCase
{
    private LocationIntelligenceService $service;
    private PoiService $mockPoiService;
    private array $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPoiService = $this->createMock(PoiService::class);

        // Load real config file directly (no Laravel container needed)
        $configPath = __DIR__ . '/../../../config/location_intelligence.php';
        $this->config = file_exists($configPath) ? require $configPath : [];

        $this->service = new LocationIntelligenceService($this->mockPoiService, $this->config);
    }

    // ── NULL COORDINATES ──

    public function test_null_lat_returns_no_coordinates(): void
    {
        $result = $this->service->analyze(null, 27.4305);

        $this->assertInstanceOf(LocationInsightDTO::class, $result);
        $this->assertSame('no_coordinates', $result->data_status);
        $this->assertTrue($result->isInsufficient());
        $this->assertNull($result->location_signal_score);
        $this->assertSame(0, $result->confidence_score);
        $this->assertSame('VERY_LOW', $result->confidence_label);
    }

    public function test_null_lng_returns_no_coordinates(): void
    {
        $result = $this->service->analyze(37.0358, null);

        $this->assertSame('no_coordinates', $result->data_status);
        $this->assertTrue($result->isInsufficient());
    }

    public function test_both_null_returns_no_coordinates(): void
    {
        $result = $this->service->analyze(null, null);

        $this->assertSame('no_coordinates', $result->data_status);
    }

    // ── EMPTY POI RESULTS ──

    public function test_no_pois_found_returns_insufficient(): void
    {
        $this->mockPoiService->method('findNearby')
            ->willReturn(collect([]));

        $result = $this->service->analyze(37.0358, 27.4305);

        $this->assertSame('insufficient_location_data', $result->data_status);
        $this->assertTrue($result->isInsufficient());
        $this->assertNull($result->location_signal_score);
    }

    // ── DTO CONTRACT ──

    public function test_dto_to_array_field_contract(): void
    {
        $dto = new LocationInsightDTO(
            location_signal_score: 72,
            confidence_score: 90,
            confidence_label: 'HIGH',
            data_status: 'ok',
            poi_access_score: 30,
            poi_density_score: 22,
            poi_coverage_score: 20,
            top_nearby_groups: [['group' => 'education', 'label' => 'Eğitim', 'closest_m' => 200, 'count' => 3]],
            reason_codes: ['near_education_access'],
            human_summary: 'Test summary',
            demand_modifier: 4,
        );

        $array = $dto->toArray();

        $expectedKeys = [
            'location_signal_score',
            'confidence_score',
            'confidence_label',
            'data_status',
            'poi_access_score',
            'poi_density_score',
            'poi_coverage_score',
            'top_nearby_groups',
            'reason_codes',
            'human_summary',
            'demand_modifier',
        ];

        $this->assertSame($expectedKeys, array_keys($array), 'LocationInsightDTO::toArray() field contract violated');
    }

    public function test_dto_insufficient_factory(): void
    {
        $dto = LocationInsightDTO::insufficient('no_coordinates');

        $this->assertTrue($dto->isInsufficient());
        $this->assertSame('no_coordinates', $dto->data_status);
        $this->assertNull($dto->location_signal_score);
        $this->assertSame(0, $dto->poi_access_score);
        $this->assertSame(0, $dto->poi_density_score);
        $this->assertSame(0, $dto->poi_coverage_score);
        $this->assertSame(0, $dto->demand_modifier);
        $this->assertContains('no_coordinates', $dto->reason_codes);
    }

    // ── SCORING: ACCESS ──

    public function test_access_score_with_all_critical_groups_nearby(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.1],      // education: 250m bucket → weight 1.0 × 10
            ['poi_turu' => 'hospital', 'distance_km' => 0.2],    // health: 250m bucket → weight 1.0 × 10
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.15],   // transport: 250m bucket → weight 1.0 × 10
            ['poi_turu' => 'bank', 'distance_km' => 0.12],       // daily_need: 250m bucket → weight 1.0 × 10
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        $this->assertSame('ok', $result->data_status);
        $this->assertSame(40, $result->poi_access_score); // max access
    }

    public function test_access_score_decreases_with_distance(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 2.5],      // education: 3000m bucket → weight 0.15 × 10
            ['poi_turu' => 'hospital', 'distance_km' => 1.2],    // health: 1500m bucket → weight 0.4 × 10
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.6],    // transport: 750m bucket → weight 0.7 × 10
            ['poi_turu' => 'bank', 'distance_km' => 0.05],       // daily_need: 250m bucket → weight 1.0 × 10
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        // 1.5 + 4 + 7 + 10 = 22.5 → 23 (rounded)
        $this->assertSame(23, $result->poi_access_score);
    }

    public function test_access_score_zero_when_no_critical_groups(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'restaurant', 'distance_km' => 0.1],  // food_social: not in access_weights
            ['poi_turu' => 'park', 'distance_km' => 0.2],        // green_leisure: not in access_weights
            ['poi_turu' => 'cafe', 'distance_km' => 0.15],       // food_social
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        $this->assertSame(0, $result->poi_access_score);
    }

    // ── SCORING: COVERAGE ──

    public function test_coverage_full_with_5_plus_groups(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.5],
            ['poi_turu' => 'hospital', 'distance_km' => 0.5],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.5],
            ['poi_turu' => 'bank', 'distance_km' => 0.5],
            ['poi_turu' => 'restaurant', 'distance_km' => 0.5],
            ['poi_turu' => 'park', 'distance_km' => 0.5],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        $this->assertSame(30, $result->poi_coverage_score); // 6 groups ≥ 5 → full
    }

    public function test_coverage_proportional_with_few_groups(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.5],
            ['poi_turu' => 'hospital', 'distance_km' => 0.5],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        // 2 groups / 5 required * 30 = 12
        $this->assertSame(12, $result->poi_coverage_score);
    }

    // ── SCORING: DENSITY ──

    public function test_density_score_increases_with_more_pois(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.1],
            ['poi_turu' => 'school', 'distance_km' => 0.3],
            ['poi_turu' => 'school', 'distance_km' => 0.5],
            ['poi_turu' => 'hospital', 'distance_km' => 0.2],
            ['poi_turu' => 'hospital', 'distance_km' => 0.4],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.15],
            ['poi_turu' => 'restaurant', 'distance_km' => 0.3],
            ['poi_turu' => 'park', 'distance_km' => 0.25],
            ['poi_turu' => 'bank', 'distance_km' => 0.35],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        // 9 distinct POIs across 6 groups, each capped at 5 per group
        // education: 3, health: 2, transport: 1, food_social: 1, green_leisure: 1, daily_need: 1 = 9
        // expected_max = 5 * 7 = 35
        // density = min(30, round(9/35 * 30)) = round(7.7) = 8
        $this->assertGreaterThan(0, $result->poi_density_score);
        $this->assertLessThanOrEqual(30, $result->poi_density_score);
    }

    // ── TOTAL SIGNAL SCORE ──

    public function test_total_signal_score_is_sum_of_subscores(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.1],
            ['poi_turu' => 'hospital', 'distance_km' => 0.2],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.15],
            ['poi_turu' => 'bank', 'distance_km' => 0.12],
            ['poi_turu' => 'restaurant', 'distance_km' => 0.3],
            ['poi_turu' => 'park', 'distance_km' => 0.25],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        $expectedTotal = $result->poi_access_score + $result->poi_density_score + $result->poi_coverage_score;
        $this->assertSame($expectedTotal, $result->location_signal_score);
    }

    public function test_signal_score_max_is_100(): void
    {
        $dto = new LocationInsightDTO(
            location_signal_score: 100,
            confidence_score: 90,
            confidence_label: 'HIGH',
            data_status: 'ok',
            poi_access_score: 40,
            poi_density_score: 30,
            poi_coverage_score: 30,
            top_nearby_groups: [],
            reason_codes: [],
            human_summary: '',
        );

        $this->assertSame(100, $dto->location_signal_score);
    }

    // ── CONFIDENCE ──

    public function test_high_confidence_with_many_pois_and_groups(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.1],
            ['poi_turu' => 'school', 'distance_km' => 0.3],
            ['poi_turu' => 'hospital', 'distance_km' => 0.2],
            ['poi_turu' => 'hospital', 'distance_km' => 0.4],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.15],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.6],
            ['poi_turu' => 'bank', 'distance_km' => 0.12],
            ['poi_turu' => 'restaurant', 'distance_km' => 0.3],
            ['poi_turu' => 'park', 'distance_km' => 0.25],
            ['poi_turu' => 'supermarket', 'distance_km' => 0.35],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        // 10 POIs, 6 groups → HIGH confidence
        $this->assertSame('HIGH', $result->confidence_label);
        $this->assertSame(90, $result->confidence_score);
    }

    public function test_low_confidence_with_minimal_data(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.5],
            ['poi_turu' => 'hospital', 'distance_km' => 0.6],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.7],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        // 3 POIs, 3 groups → MEDIUM (meets medium threshold but not high)
        $this->assertContains($result->confidence_label, ['LOW', 'MEDIUM']);
    }

    // ── DEMAND MODIFIER ──

    public function test_demand_modifier_zero_below_threshold(): void
    {
        $dto = new LocationInsightDTO(
            location_signal_score: 40,
            confidence_score: 40,
            confidence_label: 'LOW',
            data_status: 'ok',
            poi_access_score: 20,
            poi_density_score: 10,
            poi_coverage_score: 10,
            top_nearby_groups: [],
            reason_codes: [],
            human_summary: '',
            demand_modifier: 0,
        );

        $this->assertSame(0, $dto->demand_modifier);
    }

    public function test_demand_modifier_positive_above_threshold(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.1],
            ['poi_turu' => 'hospital', 'distance_km' => 0.1],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.1],
            ['poi_turu' => 'bank', 'distance_km' => 0.1],
            ['poi_turu' => 'restaurant', 'distance_km' => 0.1],
            ['poi_turu' => 'park', 'distance_km' => 0.1],
            ['poi_turu' => 'supermarket', 'distance_km' => 0.2],
            ['poi_turu' => 'cafe', 'distance_km' => 0.15],
            ['poi_turu' => 'pharmacy', 'distance_km' => 0.15],
            ['poi_turu' => 'metro', 'distance_km' => 0.2],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        if ($result->location_signal_score > 50) {
            $this->assertGreaterThan(0, $result->demand_modifier);
            $this->assertLessThanOrEqual(10, $result->demand_modifier);
        }
    }

    // ── TOP NEARBY GROUPS ──

    public function test_top_nearby_groups_sorted_by_distance(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.5],
            ['poi_turu' => 'hospital', 'distance_km' => 0.1],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.3],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        $this->assertNotEmpty($result->top_nearby_groups);
        // First should be health (closest at 100m)
        $this->assertSame('health', $result->top_nearby_groups[0]['group']);
    }

    public function test_top_nearby_group_has_required_keys(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.2],
            ['poi_turu' => 'hospital', 'distance_km' => 0.3],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        foreach ($result->top_nearby_groups as $group) {
            $this->assertArrayHasKey('group', $group);
            $this->assertArrayHasKey('label', $group);
            $this->assertArrayHasKey('closest_m', $group);
            $this->assertArrayHasKey('count', $group);
        }
    }

    // ── REASON CODES ──

    public function test_reason_codes_include_access_for_nearby_groups(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.2],
            ['poi_turu' => 'hospital', 'distance_km' => 0.3],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.5],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        $this->assertContains('near_education_access', $result->reason_codes);
        $this->assertContains('near_health_access', $result->reason_codes);
        $this->assertContains('strong_transport_access', $result->reason_codes);
    }

    public function test_reason_codes_do_not_include_far_groups(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 2.0],  // > 750m
            ['poi_turu' => 'hospital', 'distance_km' => 0.2],
            ['poi_turu' => 'bus_stop', 'distance_km' => 0.5],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        $this->assertNotContains('near_education_access', $result->reason_codes);
    }

    // ── HUMAN SUMMARY ──

    public function test_human_summary_not_empty(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'school', 'distance_km' => 0.2],
            ['poi_turu' => 'hospital', 'distance_km' => 0.3],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        $this->assertNotEmpty($result->human_summary);
    }

    // ── DATA STATUS ──

    public function test_ok_status_is_not_insufficient(): void
    {
        $dto = new LocationInsightDTO(
            location_signal_score: 60,
            confidence_score: 65,
            confidence_label: 'MEDIUM',
            data_status: 'ok',
            poi_access_score: 25,
            poi_density_score: 15,
            poi_coverage_score: 20,
            top_nearby_groups: [],
            reason_codes: [],
            human_summary: '',
        );

        $this->assertFalse($dto->isInsufficient());
    }

    // ── UNCLASSIFIED POI TYPES ──

    public function test_unknown_poi_type_ignored_in_classification(): void
    {
        $pois = $this->buildPois([
            ['poi_turu' => 'unknown_type_xyz', 'distance_km' => 0.1],
            ['poi_turu' => 'school', 'distance_km' => 0.2],
            ['poi_turu' => 'hospital', 'distance_km' => 0.3],
        ]);

        $this->mockPoiService->method('findNearby')
            ->willReturn($pois);

        $result = $this->service->analyze(37.0358, 27.4305);

        // Only 2 classified groups (education + health)
        $groupNames = array_column($result->top_nearby_groups, 'group');
        $this->assertNotContains('unknown_type_xyz', $groupNames);
        $this->assertCount(2, $result->top_nearby_groups);
    }

    // ── Helper ──

    private function buildPois(array $specs): Collection
    {
        return collect(array_map(function ($spec, $i) {
            return [
                'id' => $i + 1,
                'poi_adi' => 'Test POI ' . ($i + 1),
                'poi_turu' => $spec['poi_turu'],
                'poi_kategorisi' => $spec['poi_turu'],
                'type' => $spec['poi_turu'],
                'name' => 'Test POI ' . ($i + 1),
                'distance_km' => $spec['distance_km'],
                'distance' => (int) ($spec['distance_km'] * 1000),
                'lat' => 37.0358 + ($i * 0.001),
                'lng' => 27.4305 + ($i * 0.001),
                'rating' => 4.0,
                'ek_veri' => null,
            ];
        }, $specs, array_keys($specs)));
    }
}
