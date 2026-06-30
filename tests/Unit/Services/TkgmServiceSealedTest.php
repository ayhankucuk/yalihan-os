<?php

namespace Tests\Unit\Services;

use App\Enums\ImarDurumu;
use App\Enums\IlanDurumu;
use App\Exceptions\RealityCheckException;
use App\Models\Ilan;
use App\Services\Integrations\TKGMService;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class TkgmServiceSealedTest extends TestCase
{

    protected TKGMService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TKGMService::class);
    }

    public function test_normalize_tkgm_data_maps_yasakli_kelimeler()
    {
        $rawData = [
            'status' => 'zoned', // context7-ignore: intentional forbidden field usage for negative test
            'type' => 'residential',
            'island' => '1234',
            'parcel' => '5',
            'area' => 500.5,
        ];

        $normalized = $this->service->normalizeTkgmData($rawData);

        $this->assertEquals('1234', $normalized['ada_no']);
        $this->assertEquals('5', $normalized['parsel_no']);
        $this->assertEquals('1234-5', $normalized['ada_parsel_bilgisi']);
        $this->assertEquals(500.5, $normalized['toplam_yuzolcumu']);
        $this->assertEquals(ImarDurumu::KONUT_IMARLI->value, $normalized['imar_durumu']);
    }

    public function test_normalize_tkgm_data_uses_muhurlu_karsiliklar()
    {
        $rawData = [
            'imar_durumu' => 'imarlı',
            'alan_m2' => 750.0,
        ];

        $normalized = $this->service->normalizeTkgmData($rawData);

        $this->assertEquals(ImarDurumu::IMARLI->value, $normalized['imar_durumu']);
        $this->assertEquals(750.0, $normalized['toplam_yuzolcumu']);
    }

    public function test_normalize_tkgm_data_type_safety_emsal_orani()
    {
        $rawData = [
            'kaks' => 2.5,
        ];

        $normalized = $this->service->normalizeTkgmData($rawData);

        $this->assertEquals(2.5, $normalized['emsal_orani']);
        $this->assertIsFloat($normalized['emsal_orani']);
    }

    public function test_normalize_tkgm_data_type_safety_gabari_yuksekligi()
    {
        $rawData = [
            'gabari' => 12.5,
        ];

        $normalized = $this->service->normalizeTkgmData($rawData);

        $this->assertEquals(12.5, $normalized['gabari_yuksekligi']);
        $this->assertIsFloat($normalized['gabari_yuksekligi']);
    }

    public function test_normalize_tkgm_data_pasif_veri_muhurlu_taslak()
    {
        $rawData = [
            'active' => false, // context7-ignore: intentional forbidden field usage for negative test
        ];

        $normalized = $this->service->normalizeTkgmData($rawData);

        $this->assertEquals(IlanDurumu::TASLAK->value, $normalized['yayin_durumu']);
    }

    public function test_neural_handshake_no_conflict_when_no_existing_record()
    {
        $normalizedData = [
            'ada_no' => '1234',
            'parsel_no' => '5',
            'imar_durumu' => 'imarlı',
        ];

        $this->expectNotToPerformAssertions();
        $this->service->neuralHandshake($normalizedData);
    }

    public function test_neural_handshake_throws_exception_on_imar_conflict()
    {
        $existingIlan = Ilan::factory()->create([
            'ada_no' => '1234',
            'parsel_no' => '5',
            'imar_statusu' => 'imarsiz',
        ]);

        $normalizedData = [
            'ada_no' => '1234',
            'parsel_no' => '5',
            'imar_durumu' => 'imarlı',
        ];

        $this->expectException(RealityCheckException::class);
        $this->expectExceptionMessage('veri tutarsızlığı tespit edildi');

        $this->service->neuralHandshake($normalizedData);
    }

    public function test_neural_handshake_throws_exception_on_alan_conflict()
    {
        $existingIlan = Ilan::factory()->create([
            'ada_no' => '1234',
            'parsel_no' => '5',
            'alan_m2' => 500.0,
        ]);

        $normalizedData = [
            'ada_no' => '1234',
            'parsel_no' => '5',
            'toplam_yuzolcumu' => 750.0,
        ];

        $this->expectException(RealityCheckException::class);
        $this->expectExceptionMessage('veri tutarsızlığı tespit edildi');

        $this->service->neuralHandshake($normalizedData);
    }

    public function test_neural_handshake_excludes_current_ilan_on_update()
    {
        $existingIlan = Ilan::factory()->create([
            'ada_no' => '1234',
            'parsel_no' => '5',
            'imar_statusu' => 'imarlı',
        ]);

        $normalizedData = [
            'ada_no' => '1234',
            'parsel_no' => '5',
            'imar_durumu' => 'imarlı',
        ];

        $this->expectNotToPerformAssertions();
        $this->service->neuralHandshake($normalizedData, $existingIlan->id);
    }

    public function test_map_to_ups_entity_converts_normalized_to_ups_format()
    {
        $normalizedData = [
            'ada_no' => '1234',
            'parsel_no' => '5',
            'ada_parsel_bilgisi' => '1234-5',
            'imar_durumu' => 'imarlı',
            'toplam_yuzolcumu' => 500.0,
            'emsal_orani' => 2.0,
            'taks' => 0.5,
            'gabari_yuksekligi' => 12.5,
            'yayin_durumu' => 'yayinda',
        ];

        $upsData = $this->service->mapToUpsEntity($normalizedData);

        $this->assertEquals('1234', $upsData['ada_no']);
        $this->assertEquals('5', $upsData['parsel_no']);
        $this->assertEquals('1234-5', $upsData['ada_parsel']);
        $this->assertEquals('imarlı', $upsData['imar_statusu']);
        $this->assertEquals(500.0, $upsData['alan_m2']);
        $this->assertEquals(2.0, $upsData['kaks']);
        $this->assertEquals(0.5, $upsData['taks']);
        $this->assertEquals(12.5, $upsData['gabari']);
        $this->assertEquals('Aktif', $upsData['yayin_durumu']);
    }
}
