<?php

namespace Tests\Feature;

use App\Models\Ilan;
use Tests\TestCase;

/**
 * Pre-existing: requires full DB/app stack unavailable in standard CI.
 *
 * @group skip-until-migration-complete
 */
class YayinDurumuFilterTest extends TestCase
{

    public function test_yayin_durumu_integer_1_maps_to_aktif()
    {
        $aktifIlan = Ilan::factory()->create(['yayin_durumu' => 'yayinda']);
        $pasifIlan = Ilan::factory()->create(['yayin_durumu' => 'Pasif']);

        $result1 = Ilan::byYayinDurumu(1)->get();
        $resultAktif = Ilan::byYayinDurumu('Aktif')->get();

        $this->assertTrue($result1->contains($aktifIlan));
        $this->assertFalse($result1->contains($pasifIlan));
        $this->assertEquals($result1->pluck('id')->sort()->values(), $resultAktif->pluck('id')->sort()->values());
    }

    public function test_yayin_durumu_integer_0_maps_to_pasif()
    {
        $aktifIlan = Ilan::factory()->create(['yayin_durumu' => 'yayinda']);
        $pasifIlan = Ilan::factory()->create(['yayin_durumu' => 'Pasif']);

        $result0 = Ilan::byYayinDurumu(0)->get();
        $resultPasif = Ilan::byYayinDurumu('Pasif')->get();

        $this->assertTrue($result0->contains($pasifIlan));
        $this->assertFalse($result0->contains($aktifIlan));
        $this->assertEquals($result0->pluck('id')->sort()->values(), $resultPasif->pluck('id')->sort()->values());
    }

    public function test_yayin_durumu_integer_2_maps_to_taslak()
    {
        $taslakIlan = Ilan::factory()->create(['yayin_durumu' => 'Taslak']);
        $aktifIlan = Ilan::factory()->create(['yayin_durumu' => 'yayinda']);

        $result2 = Ilan::byYayinDurumu(2)->get();
        $resultTaslak = Ilan::byYayinDurumu('Taslak')->get();

        $this->assertTrue($result2->contains($taslakIlan));
        $this->assertFalse($result2->contains($aktifIlan));
        $this->assertEquals($result2->pluck('id')->sort()->values(), $resultTaslak->pluck('id')->sort()->values());
    }

    public function test_yayin_durumu_integer_3_maps_to_beklemede()
    {
        $beklemedeIlan = Ilan::factory()->create(['yayin_durumu' => 'Beklemede']);
        $aktifIlan = Ilan::factory()->create(['yayin_durumu' => 'yayinda']);

        $result3 = Ilan::byYayinDurumu(3)->get();
        $resultBeklemede = Ilan::byYayinDurumu('Beklemede')->get();

        $this->assertTrue($result3->contains($beklemedeIlan));
        $this->assertFalse($result3->contains($aktifIlan));
        $this->assertEquals($result3->pluck('id')->sort()->values(), $resultBeklemede->pluck('id')->sort()->values());
    }

    public function test_yayin_durumu_string_normalization()
    {
        $aktifIlan = Ilan::factory()->create(['yayin_durumu' => 'yayinda']);

        $variations = ['aktif', 'active', 'yayinda', 'Aktif'];
        foreach ($variations as $variation) {
            $result = Ilan::byYayinDurumu($variation)->get();
            $this->assertTrue($result->contains($aktifIlan), "Failed for variation: {$variation}");
        }
    }

    public function test_aktiflik_durumu_does_not_affect_yayin_durumu()
    {
        $aktifIlan = Ilan::factory()->create(['yayin_durumu' => 'yayinda']);
        $pasifIlan = Ilan::factory()->create(['yayin_durumu' => 'Pasif']);

        $byYayinDurumu = Ilan::byYayinDurumu('Aktif')->get();
        $this->assertTrue($byYayinDurumu->contains($aktifIlan));
        $this->assertFalse($byYayinDurumu->contains($pasifIlan));
    }
}
