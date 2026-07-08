<?php

namespace Tests\Unit\Services\Location;

use App\DTOs\Location\GeocodingResultDTO;
use App\DTOs\Location\LocationAnalysisResultDTO;
use PHPUnit\Framework\TestCase;

/**
 * Location DTO Unit Tests — Sprint 6.2
 */
class LocationDTOResultTest extends TestCase
{
    public function test_geocoding_result_dto_success(): void
    {
        $dto = new GeocodingResultDTO(
            success: true,
            lat: 37.0634,
            lng: 27.4374,
            source: 'nominatim',
            displayName: 'Yalıkavak, Bodrum, Muğla, Türkiye',
            rawData: '{"lat":"37.0634","lon":"27.4374"}',
            error: null,
        );

        $this->assertTrue($dto->success);
        $this->assertEquals(37.0634, $dto->lat);
        $this->assertEquals(27.4374, $dto->lng);
        $this->assertEquals('nominatim', $dto->source);
        $this->assertFalse($dto->fromCache);
    }

    public function test_geocoding_result_dto_failure(): void
    {
        $dto = GeocodingResultDTO::failure('Adres çözülemedi');

        $this->assertFalse($dto->success);
        $this->assertNull($dto->lat);
        $this->assertNull($dto->lng);
        $this->assertEquals('none', $dto->source);
        $this->assertEquals('Adres çözülemedi', $dto->error);
    }

    public function test_geocoding_result_dto_to_array(): void
    {
        $dto = new GeocodingResultDTO(
            success: true,
            lat: 37.0634,
            lng: 27.4374,
            source: 'adres_db',
            displayName: 'Bodrum, Muğla',
            rawData: null,
            error: null,
            fromCache: true,
        );

        $arr = $dto->toArray();

        $this->assertTrue($arr['success']);
        $this->assertEquals(37.0634, $arr['lat']);
        $this->assertEquals('adres_db', $arr['source']);
        $this->assertTrue($arr['from_cache']);
    }

    public function test_location_analysis_result_dto_ok(): void
    {
        $dto = new LocationAnalysisResultDTO(
            pipeline_durumu: 'ok',
            score: 72,
            confidence: 'HIGH',
            poi_access_score: 28,
            poi_density_score: 22,
            poi_coverage_score: 22,
            top_groups: [
                ['group' => 'beach', 'label' => 'Plaj', 'closest_m' => 320, 'count' => 3],
            ],
            lat: 37.0634,
            lng: 27.4374,
            geocode_source: 'nominatim',
            ai_summary: 'Bodrum Yalıkavak denize 320m.',
            reason_codes: ['near_beach_access'],
            demand_modifier: 4,
        );

        $this->assertTrue($dto->isOk());
        $this->assertEquals('ok', $dto->getStatus());
        $this->assertEquals(72, $dto->score);
        $this->assertEquals('HIGH', $dto->confidence);
        $this->assertEquals(28, $dto->poi_access_score);
        $this->assertEquals(22, $dto->poi_density_score);
        $this->assertEquals(22, $dto->poi_coverage_score);
        $this->assertEquals('nominatim', $dto->geocode_source);
        $this->assertEquals(4, $dto->demand_modifier);
        $this->assertCount(1, $dto->top_groups);
    }

    public function test_location_analysis_result_dto_insufficient(): void
    {
        $dto = LocationAnalysisResultDTO::insufficient('no_coordinates');

        $this->assertFalse($dto->isOk());
        $this->assertEquals('no_coordinates', $dto->getStatus());
        $this->assertNull($dto->score);
        $this->assertEquals('VERY_LOW', $dto->confidence);
        $this->assertEquals(0, $dto->poi_access_score);
        $this->assertEquals(0, $dto->poi_density_score);
        $this->assertEquals(0, $dto->poi_coverage_score);
        $this->assertEmpty($dto->top_groups);
        $this->assertEquals('none', $dto->geocode_source);
    }

    public function test_location_analysis_result_dto_to_api_response_ok(): void
    {
        $dto = new LocationAnalysisResultDTO(
            pipeline_durumu: 'ok',
            score: 72,
            confidence: 'HIGH',
            poi_access_score: 28,
            poi_density_score: 22,
            poi_coverage_score: 22,
            top_groups: [],
            lat: 37.0634,
            lng: 27.4374,
            geocode_source: 'nominatim',
            ai_summary: 'Test summary',
            reason_codes: [],
            demand_modifier: 4,
        );

        $response = $dto->toApiResponse();

        $this->assertEquals('ok', $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals(72, $response['data']['score']);
        $this->assertEquals('HIGH', $response['data']['confidence']);
        $this->assertEquals(28, $response['data']['sub_scores']['poi_access_score']);
    }

    public function test_location_analysis_result_dto_to_api_response_insufficient(): void
    {
        $dto = LocationAnalysisResultDTO::insufficient('insufficient_data');
        $response = $dto->toApiResponse();

        $this->assertEquals('insufficient_data', $response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertNull($response['data']['score']);
        $this->assertEquals('VERY_LOW', $response['data']['confidence']);
    }

    public function test_location_analysis_result_dto_to_array(): void
    {
        $dto = new LocationAnalysisResultDTO(
            pipeline_durumu: 'ok',
            score: 50,
            confidence: 'MEDIUM',
            poi_access_score: 10,
            poi_density_score: 20,
            poi_coverage_score: 20,
            top_groups: [['group' => 'education', 'label' => 'Eğitim', 'closest_m' => 500, 'count' => 5]],
            lat: 37.0,
            lng: 27.0,
            geocode_source: 'adres_db',
            ai_summary: 'Test',
            reason_codes: ['strong_poi_coverage'],
            demand_modifier: 0,
        );

        $arr = $dto->toArray();

        $this->assertEquals('ok', $arr['status']);
        $this->assertEquals(50, $arr['score']);
        $this->assertEquals('MEDIUM', $arr['confidence']);
        $this->assertEquals(10, $arr['sub_scores']['poi_access_score']);
        $this->assertEquals(20, $arr['sub_scores']['poi_density_score']);
        $this->assertEquals(20, $arr['sub_scores']['poi_coverage_score']);
        $this->assertEquals(['lat' => 37.0, 'lng' => 27.0], $arr['coordinates']);
        $this->assertEquals('adres_db', $arr['geocode_source']);
        $this->assertEquals(0, $arr['demand_modifier']);
    }
}
