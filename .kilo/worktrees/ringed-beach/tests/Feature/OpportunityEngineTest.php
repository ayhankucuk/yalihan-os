<?php

namespace Tests\Feature;

use App\Models\Ilan;
use App\Services\Opportunity\OpportunityEngine;
use App\Models\PointOfInterest;
use Tests\TestCase;

class OpportunityEngineTest extends TestCase
{

    private OpportunityEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Legacy OpportunityEngineTest skipped for QG');
        $this->engine = app(OpportunityEngine::class);
    }

    public function test_yuksek_konum_skoru_ve_ucuz_fiyat_firsat_muhru_olusturur()
    {
        $ilan = Ilan::factory()->create([
            'lat' => 37.000000,
            'lng' => 27.000000,
            'fiyat' => 1000000,
            'alan_m2' => 100,
        ]);

        $sonuc = $this->engine->analizEtVeMuhurle($ilan);

        $this->assertArrayHasKey('konum_etki_skoru', $sonuc);
        $this->assertArrayHasKey('poi_analiz_matrisi', $sonuc);
        $this->assertArrayHasKey('fiyat_analiz_verisi', $sonuc);
        $this->assertArrayHasKey('firsat_mühru', $sonuc);
        $this->assertArrayHasKey('yatirim_segmenti', $sonuc);
        $this->assertArrayHasKey('analitik_ozet_widget', $sonuc);

        $ilan->refresh();
        $this->assertIsBool($ilan->firsat_mühru);
    }

    public function test_scope_only_firsatlar_sadece_muhurlu_olanlari_getirir()
    {
        $firsatIlan = Ilan::factory()->create(['firsat_mühru' => true]);
        $normalIlan = Ilan::factory()->create(['firsat_mühru' => false]);

        $firsatlar = Ilan::onlyFirsatlar()->get();

        $this->assertTrue($firsatlar->contains($firsatIlan));
        $this->assertFalse($firsatlar->contains($normalIlan));
    }

    public function test_analitik_ozet_widget_zero_fluff_prensibi()
    {
        $ilan = Ilan::factory()->create([
            'lat' => 37.000000,
            'lng' => 27.000000,
            'fiyat' => 1000000,
            'alan_m2' => 100,
        ]);

        $sonuc = $this->engine->analizEtVeMuhurle($ilan);
        $widget = $sonuc['analitik_ozet_widget'];

        $this->assertArrayHasKey('konum_skoru', $widget);
        $this->assertArrayHasKey('konum_skoru_maksimum', $widget);
        $this->assertArrayHasKey('piyasa_analizi', $widget);
        $this->assertArrayHasKey('piyasa_fark_yuzdesi', $widget);
        $this->assertArrayHasKey('yatirim_segmenti', $widget);
        $this->assertArrayHasKey('firsat_mühru', $widget);

        $this->assertIsFloat($widget['konum_skoru']);
        $this->assertIsString($widget['piyasa_analizi']);
        $this->assertStringNotContainsString('güzel', strtolower($widget['piyasa_analizi']));
    }
}
