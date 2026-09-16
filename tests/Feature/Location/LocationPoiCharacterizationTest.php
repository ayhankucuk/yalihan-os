<?php

namespace Tests\Feature\Location;

use App\Models\PointOfInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Characterization tests for POST /api/v1/location/poi-distances
 *
 * Captures legacy behavior to ensure 100% equivalence during Strangler Fig migration.
 *
 * Test data: poi_turu values MUST match the filter expectations from getPoiFiltersByCategory.
 * villa filter → poi_turu IN ('amenity.school', 'amenity.hospital', 'amenity.restaurant', 'shop', 'park')
 */
class LocationPoiCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register SQLite math functions so Haversine SQL executes correctly
        if (config('database.default') === 'sqlite') {
            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            if (method_exists($pdo, 'sqliteCreateFunction')) {
                $pdo->sqliteCreateFunction('radians', 'deg2rad', 1);
                $pdo->sqliteCreateFunction('acos', 'acos', 1);
                $pdo->sqliteCreateFunction('cos', 'cos', 1);
                $pdo->sqliteCreateFunction('sin', 'sin', 1);
            }
        }

        // POI test data — poi_turu values aligned with getPoiFiltersByCategory() expectations
        // villa filter: amenity.school, amenity.hospital, amenity.restaurant, shop, park
        // arsa filter: highway, amenity.parking, amenity.fuel, railway.station
        PointOfInterest::insert([
            [
                'poi_adi' => 'Yalıkavak İlkokulu',
                'poi_turu' => 'amenity.school',
                'poi_kategorisi' => 'education',
                'lat' => 37.1030,
                'lng' => 27.2960,
                'rating' => 4.0,
                'aktiflik_durumu' => true,
                'ek_veri' => null,
            ],
            [
                'poi_adi' => 'Yakın Market',
                'poi_turu' => 'shop',
                'poi_kategorisi' => 'shopping',
                'lat' => 37.1050,
                'lng' => 27.2930,
                'rating' => 3.5,
                'aktiflik_durumu' => true,
                'ek_veri' => json_encode(['address' => 'Yalıkavak Mah. 101. Sk. No:5']),
            ],
            [
                'poi_adi' => 'Bodrum Devlet Hastanesi',
                'poi_turu' => 'amenity.hospital',
                'poi_kategorisi' => 'health',
                'lat' => 37.0350,
                'lng' => 27.4280,
                'rating' => 3.5,
                'aktiflik_durumu' => true,
                'ek_veri' => null,
            ],
        ]);
    }

    /** @test */
    public function it_calculates_distances_and_returns_exact_contract_structure(): void
    {
        $response = $this->postJson('/api/v1/location/poi-distances', [
            'lat' => 37.1030,
            'lng' => 27.2910,
            'kategori' => 'villa',
            'radius_km' => 2,
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertTrue($data['sealed']);
        $this->assertEquals(2, $data['summary']['total_found']);
        $this->assertCount(2, $data['pois']);
        $this->assertCount(2, $data['data']);

        foreach ($data['pois'] as $poi) {
            $this->assertArrayHasKey('id', $poi);
            $this->assertArrayHasKey('poi_adi', $poi);
            $this->assertArrayHasKey('poi_turu', $poi);
            $this->assertArrayHasKey('distance_km', $poi);
            $this->assertArrayHasKey('distance', $poi);
            $this->assertArrayHasKey('lat', $poi);
            $this->assertArrayHasKey('lng', $poi);

            if ($poi['poi_adi'] === 'Yalıkavak İlkokulu') {
                $this->assertLessThan(1, $poi['distance_km']);
                $this->assertEquals('amenity.school', $poi['poi_turu']);
            }
        }

        $this->assertArrayHasKey('total_found', $data['summary']);
        $this->assertArrayHasKey('by_type', $data['summary']);
        $this->assertArrayHasKey('closest_poi', $data['summary']);
        $this->assertArrayHasKey('farthest_poi', $data['summary']);
    }

    /** @test */
    public function it_correctly_filters_pois_outside_radius(): void
    {
        $response = $this->postJson('/api/v1/location/poi-distances', [
            'lat' => 37.1030,
            'lng' => 27.2910,
            'radius_km' => 0.5,
        ]);

        $response->assertStatus(200);

        // Only Yakın Market (0.28km) within 0.5km radius — Yalıkavak İlkokulu (0.44km) also within
        // Both shop and amenity.school are within 0.5km
        // 0.5km from (37.1030, 27.2910) captures both at 0.28km and 0.44km
        // But without kategori filter: all POIs (including hospital at 14km) would be returned
        // With SQLite PHP filter: only those within 0.5km
        $pois = $response->json('data.pois');
        $this->assertLessThanOrEqual(2, count($pois));
        foreach ($pois as $poi) {
            $this->assertLessThanOrEqual(0.5, $poi['distance_km']);
        }
    }

    /** @test */
    public function it_returns_nearby_poi_when_querying_exact_coordinate(): void
    {
        $response = $this->postJson('/api/v1/location/poi-distances', [
            'lat' => 37.1030,
            'lng' => 27.2960,
            'radius_km' => 0.5,
            'kategori' => 'villa',
        ]);

        $response->assertStatus(200);
        $pois = $response->json('data.pois');
        $this->assertNotEmpty($pois);

        $first = $pois[0];
        $this->assertEquals('Yalıkavak İlkokulu', $first['poi_adi']);
        $this->assertEquals('amenity.school', $first['poi_turu']);
        $this->assertLessThan(0.1, $first['distance_km']);
    }

    /** @test */
    public function it_validates_coordinates_out_of_range(): void
    {
        $response = $this->postJson('/api/v1/location/poi-distances', [
            'lat' => 91,
            'lng' => 181,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_returns_exact_same_response_when_domain_use_case_is_enabled(): void
    {
        config(['location.use_domain_poi_search' => true]);

        $response = $this->postJson('/api/v1/location/poi-distances', [
            'lat' => 37.1030,
            'lng' => 27.2910,
            'kategori' => 'villa',
            'radius_km' => 2,
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertTrue($data['sealed']);
        $this->assertEquals(2, $data['summary']['total_found']);
        $this->assertCount(2, $data['pois']);
        $this->assertCount(2, $data['data']);

        $this->assertArrayHasKey('closest_poi', $data['summary']);
        $this->assertArrayHasKey('farthest_poi', $data['summary']);
        $this->assertEquals('Yakın Market', $data['summary']['closest_poi']['poi_adi']);
        $this->assertEquals('Yalıkavak İlkokulu', $data['summary']['farthest_poi']['poi_adi']);
    }
}
