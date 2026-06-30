<?php

namespace Tests\Unit\Services;

use App\Enums\ImarDurumu;
use App\Models\Ilan;
use App\Services\ROI\NeuralRoiEngine;
use Tests\TestCase;

/**
 * NeuralRoiEngineTest — Requires seeded ilan_kategorileri data (FK constraints).
 * Excluded from standard CI quality gate.
 *
 * @group skip-until-migration-complete
 */
class NeuralRoiEngineTest extends TestCase
{

    private NeuralRoiEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(NeuralRoiEngine::class);
    }

    public function test_konut_icin_yatirim_karti_uretilir()
    {
        $ilan = Ilan::factory()->create([
            'fiyat' => 3000000,
            'alan_m2' => 120,
            'imar_statusu' => ImarDurumu::KONUT_IMARLI->value,
        ]);

        $konumAnalizi = [
            'konum_etki_skoru' => 85.0,
        ];

        $fiyatAnalizi = [
            'has_data' => true,
            'avg_unit_price' => 25000.0,
            'market_pulse' => 'high',
        ];

        $kart = $this->engine->analizEt($ilan, $konumAnalizi, $fiyatAnalizi);

        $this->assertArrayHasKey('amortisman_suresi_yil', $kart);
        $this->assertArrayHasKey('yillik_verim_orani', $kart);
        $this->assertArrayHasKey('projeksiyon_matrisi', $kart);
        $this->assertArrayHasKey('bes_yillik_tahmin', $kart);
        $this->assertArrayHasKey('yatirim_skoru', $kart);

        $this->assertNotNull($kart['amortisman_suresi_yil']);
        $this->assertNotNull($kart['yillik_verim_orani']);
        $this->assertNotEmpty($kart['projeksiyon_matrisi']);
        $this->assertIsString($kart['bes_yillik_tahmin']);
        $this->assertGreaterThan(0, $kart['yatirim_skoru']);
        $this->assertLessThanOrEqual(100, $kart['yatirim_skoru']);
    }

    public function test_arsa_icin_yatirim_karti_uretilir()
    {
        $ilan = Ilan::factory()->create([
            'fiyat' => 1500000,
            'alan_m2' => 500,
            'imar_statusu' => ImarDurumu::IMARLI->value,
        ]);

        $konumAnalizi = [
            'konum_etki_skoru' => 70.0,
        ];

        $fiyatAnalizi = [
            'has_data' => true,
            'avg_unit_price' => 5000.0,
            'market_pulse' => 'medium',
        ];

        $kart = $this->engine->analizEt($ilan, $konumAnalizi, $fiyatAnalizi);

        $this->assertNotNull($kart['amortisman_suresi_yil']);
        $this->assertNotNull($kart['yillik_verim_orani']);
        $this->assertNotEmpty($kart['projeksiyon_matrisi']);
    }

    public function test_villa_imarli_icin_nadirik_carpani_uygulanir()
    {
        $ilan = Ilan::factory()->create([
            'fiyat' => 5000000,
            'alan_m2' => 300,
            'imar_statusu' => ImarDurumu::VILLA_IMARLI->value,
        ]);

        $konumAnalizi = [
            'konum_etki_skoru' => 80.0,
        ];

        $fiyatAnalizi = [
            'has_data' => true,
            'avg_unit_price' => 35000.0,
            'market_pulse' => 'high',
        ];

        $kartVilla = $this->engine->analizEt($ilan, $konumAnalizi, $fiyatAnalizi);

        $ilanKonut = Ilan::factory()->create([
            'fiyat' => 5000000,
            'alan_m2' => 300,
            'imar_statusu' => ImarDurumu::KONUT_IMARLI->value,
        ]);

        $kartKonut = $this->engine->analizEt($ilanKonut, $konumAnalizi, $fiyatAnalizi);

        $this->assertGreaterThan(
            $kartKonut['projeksiyon_matrisi'][4]['tahmini_deger'],
            $kartVilla['projeksiyon_matrisi'][4]['tahmini_deger']
        );
    }
}

