<?php

namespace Tests\Feature\LocationIntelligence;

use App\Models\Ilan;
use App\Models\PointOfInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Location Intelligence API Feature Tests — Sprint 6.2
 */
class LocationIntelligenceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed POI data for tests
        PointOfInterest::insert([
            [
                'poi_adi' => 'Bodrum Merkez Plaj',
                'poi_turu' => 'beach',
                'poi_kategorisi' => 'leisure',
                'lat' => 37.0344,
                'lng' => 27.4305,
                'rating' => 4.5,
                'aktiflik_durumu' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'poi_adi' => 'Bodrum Marina',
                'poi_turu' => 'marina',
                'poi_kategorisi' => 'transport',
                'lat' => 37.0344,
                'lng' => 27.4305,
                'rating' => 4.8,
                'aktiflik_durumu' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'poi_adi' => 'Bodrum Devlet Hastanesi',
                'poi_turu' => 'hospital',
                'poi_kategorisi' => 'health',
                'lat' => 37.0400,
                'lng' => 27.4294,
                'rating' => 3.9,
                'aktiflik_durumu' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'poi_adi' => 'Bodrum Süpermarket',
                'poi_turu' => 'supermarket',
                'poi_kategorisi' => 'shopping',
                'lat' => 37.0350,
                'lng' => 27.4350,
                'rating' => 4.2,
                'aktiflik_durumu' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'poi_adi' => 'Bodrum İlkokulu',
                'poi_turu' => 'school',
                'poi_kategorisi' => 'education',
                'lat' => 37.0360,
                'lng' => 27.4360,
                'rating' => 4.3,
                'aktiflik_durumu' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_analyze_endpoint_returns_200_with_valid_ilan(): void
    {
        $ilan = Ilan::create([
            'baslik' => 'Test Ilan',
            'aciklama' => 'Test description',
            'fiyat' => 5000000,
            'yayin_durumu' => 1,
            'lat' => 37.0344,
            'lng' => 27.4305,
            'ilan_no' => 'TEST-' . uniqid(),
            'aktiflik_durumu' => 1,
        ]);

        $response = $this->postJson('/api/location-intelligence/analyze', [
            'ilan_id' => $ilan->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'score',
                    'confidence',
                    'sub_scores' => [
                        'poi_access_score',
                        'poi_density_score',
                        'poi_coverage_score',
                    ],
                    'coordinates' => ['lat', 'lng'],
                    'geocode_source',
                ],
            ]);
    }

    public function test_analyze_endpoint_returns_422_for_missing_ilan_id(): void
    {
        $response = $this->postJson('/api/location-intelligence/analyze', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ilan_id']);
    }

    public function test_analyze_endpoint_returns_422_for_invalid_ilan_id(): void
    {
        $response = $this->postJson('/api/location-intelligence/analyze', [
            'ilan_id' => 999999,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'ilan_not_found',
            ]);
    }

    public function test_analyze_endpoint_returns_200_for_ilan_without_coordinates(): void
    {
        $ilan = Ilan::create([
            'baslik' => 'Test Ilan No Coords',
            'aciklama' => 'Test',
            'fiyat' => 5000000,
            'yayin_durumu' => 1,
            'lat' => null,
            'lng' => null,
            'ilan_no' => 'TEST-' . uniqid(),
            'aktiflik_durumu' => 1,
        ]);

        $response = $this->postJson('/api/location-intelligence/analyze', [
            'ilan_id' => $ilan->id,
        ]);

        // Returns 200 with insufficient_data status (not 422)
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'no_coordinates',
            ]);
    }

    public function test_analyze_endpoint_persists_location_data_to_ilan(): void
    {
        $ilan = Ilan::create([
            'baslik' => 'Test Persist',
            'aciklama' => 'Test',
            'fiyat' => 5000000,
            'yayin_durumu' => 1,
            'lat' => 37.0344,
            'lng' => 27.4305,
            'ilan_no' => 'TEST-' . uniqid(),
            'aktiflik_durumu' => 1,
            'location_score' => null,
        ]);

        $this->postJson('/api/location-intelligence/analyze', [
            'ilan_id' => $ilan->id,
        ]);

        $ilan->refresh();

        $this->assertNotNull($ilan->location_score);
        $this->assertNotNull($ilan->location_score_confidence);
        $this->assertNotNull($ilan->location_analyzed_at);
        $this->assertNotNull($ilan->location_data);
        $this->assertIsArray($ilan->location_data);
    }

    public function test_score_endpoint_returns_cached_score(): void
    {
        $ilan = Ilan::create([
            'baslik' => 'Test Score',
            'aciklama' => 'Test',
            'fiyat' => 5000000,
            'yayin_durumu' => 1,
            'lat' => 37.0344,
            'lng' => 27.4305,
            'ilan_no' => 'TEST-' . uniqid(),
            'aktiflik_durumu' => 1,
            'location_score' => 65,
            'location_score_confidence' => 'HIGH',
        ]);

        $response = $this->getJson("/api/location-intelligence/score/{$ilan->id}");

        $response->assertStatus(200)
            ->assertJson([
                'ilan_id' => $ilan->id,
                'score' => 65,
                'confidence' => 'HIGH',
            ]);
    }

    public function test_score_endpoint_returns_404_for_missing_ilan(): void
    {
        $response = $this->getJson('/api/location-intelligence/score/999999');

        $response->assertStatus(404);
    }

    public function test_batch_endpoint_queues_multiple_ilans(): void
    {
        $ilanIds = [];
        for ($i = 0; $i < 3; $i++) {
            $ilan = Ilan::create([
                'baslik' => "Test Batch {$i}",
                'aciklama' => 'Test',
                'fiyat' => 5000000,
                'yayin_durumu' => 1,
                'lat' => 37.0344,
                'lng' => 27.4305,
                'ilan_no' => 'TEST-' . uniqid(),
                'aktiflik_durumu' => 1,
            ]);
            $ilanIds[] = $ilan->id;
        }

        $response = $this->postJson('/api/location-intelligence/batch', [
            'ilan_ids' => $ilanIds,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'queued',
                'total',
                'message',
            ])
            ->assertJson([
                'queued' => 3,
                'total' => 3,
            ]);
    }

    public function test_batch_endpoint_validates_empty_ilan_ids(): void
    {
        $response = $this->postJson('/api/location-intelligence/batch', [
            'ilan_ids' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_batch_endpoint_validates_max_100_ilans(): void
    {
        $ilanIds = range(1, 101);

        $response = $this->postJson('/api/location-intelligence/batch', [
            'ilan_ids' => $ilanIds,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'En fazla 100 ilan eklenebilir.',
            ]);
    }
}
