<?php

namespace Tests\Feature\Rental;

use App\Models\Ilan;
use App\Models\RentalEvKarti;
use App\Models\RentalGelirKalemi;
use App\Models\RentalGiderKalemi;
use App\Models\User;
use App\Services\Rental\RentalFinanceService;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 */
class RentalDomainTest extends TestCase
{

    private RentalFinanceService $financeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->financeService = new RentalFinanceService();
    }

    /** @test */
    public function ev_karti_olusturulabilir(): void
    {
        $kart = RentalEvKarti::create([
            'baslik' => 'Test Villa',
            'adres'  => 'Muğla, Bodrum',
        ]);

        $this->assertNotNull($kart->id);
        $this->assertEquals('Test Villa', $kart->baslik);
    }

    /** @test */
    public function gelir_eklenir_ve_summary_yansir(): void
    {
        $kart = RentalEvKarti::create(['baslik' => 'Villa A']);

        RentalGelirKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGelirKalemi::KALEM_KIRA,
            'tutar'       => 5000.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 6,
            'gelir_tarihi'=> '2026-06-01',
        ]);

        RentalGelirKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGelirKalemi::KALEM_EK_GELIR,
            'tutar'       => 500.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 6,
            'gelir_tarihi'=> '2026-06-15',
        ]);

        $ozet = $this->financeService->calculateMonthlySummary($kart, 2026, 6);

        $this->assertEquals(5500.00, $ozet['toplam_gelir']);
        $this->assertEquals(0.00, $ozet['toplam_gider']);
        $this->assertEquals(5500.00, $ozet['net']);
    }

    /** @test */
    public function gider_eklenir_ve_net_duser(): void
    {
        $kart = RentalEvKarti::create(['baslik' => 'Villa B']);

        RentalGelirKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGelirKalemi::KALEM_KIRA,
            'tutar'       => 8000.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 7,
            'gelir_tarihi'=> '2026-07-01',
        ]);

        RentalGiderKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGiderKalemi::KALEM_ELEKTRIK,
            'tutar'       => 1200.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 7,
            'gider_tarihi'=> '2026-07-10',
        ]);

        RentalGiderKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGiderKalemi::KALEM_TEMIZLIK,
            'tutar'       => 800.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 7,
            'gider_tarihi'=> '2026-07-15',
        ]);

        $ozet = $this->financeService->calculateMonthlySummary($kart, 2026, 7);

        $this->assertEquals(8000.00, $ozet['toplam_gelir']);
        $this->assertEquals(2000.00, $ozet['toplam_gider']);
        $this->assertEquals(6000.00, $ozet['net']);
    }

    /** @test */
    public function depozito_ayri_raporlanir(): void
    {
        $kart = RentalEvKarti::create(['baslik' => 'Villa C']);

        RentalGelirKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGelirKalemi::KALEM_KIRA,
            'tutar'       => 6000.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 8,
            'gelir_tarihi'=> '2026-08-01',
        ]);

        RentalGelirKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGelirKalemi::KALEM_DEPOZITO,
            'tutar'       => 3000.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 8,
            'gelir_tarihi'=> '2026-08-01',
        ]);

        $ozet = $this->financeService->calculateMonthlySummary($kart, 2026, 8);

        // Depozito gelirden ayrı
        $this->assertEquals(6000.00, $ozet['toplam_gelir']);
        $this->assertEquals(3000.00, $ozet['depozito_toplam']);
        // Net = gelir - gider (depozito dahil değil)
        $this->assertEquals(6000.00, $ozet['net']);
    }

    /** @test */
    public function bos_donem_sifir_doner(): void
    {
        $kart = RentalEvKarti::create(['baslik' => 'Villa D']);

        $ozet = $this->financeService->calculateMonthlySummary($kart, 2026, 1);

        $this->assertEquals(0.00, $ozet['toplam_gelir']);
        $this->assertEquals(0.00, $ozet['toplam_gider']);
        $this->assertEquals(0.00, $ozet['net']);
        $this->assertEquals(0.00, $ozet['depozito_toplam']);
    }

    /** @test */
    public function yillik_ozet_12_ay_kapsar(): void
    {
        $kart = RentalEvKarti::create(['baslik' => 'Villa E']);

        // Haziran ve Temmuz'a gelir ekle
        RentalGelirKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGelirKalemi::KALEM_KIRA,
            'tutar'       => 5000.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 6,
            'gelir_tarihi'=> '2026-06-01',
        ]);

        RentalGelirKalemi::create([
            'ev_karti_id' => $kart->id,
            'kalem_turu'  => RentalGelirKalemi::KALEM_KIRA,
            'tutar'       => 7000.00,
            'donem_yil'   => 2026,
            'donem_ay'    => 7,
            'gelir_tarihi'=> '2026-07-01',
        ]);

        $yillik = $this->financeService->calculateYearSummary($kart, 2026);

        $this->assertEquals(12000.00, $yillik['toplam_gelir']);
        $this->assertCount(12, $yillik['aylik']);
    }
}
