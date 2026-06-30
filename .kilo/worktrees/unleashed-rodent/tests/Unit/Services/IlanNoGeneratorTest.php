<?php

namespace Tests\Unit\Services;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Services\Listing\IlanNoGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * İlan Numarası Üretim Testi
 * 
 * @group ilan
 * @group ilan-no
 */
class IlanNoGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private IlanNoGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = app(IlanNoGenerator::class);
    }

    /** @test */
    public function it_generates_unique_ilan_number()
    {
        $ilan = Ilan::factory()->create([
            'ilan_no' => null,
        ]);

        $ilanNo = $this->generator->generate($ilan);

        $this->assertNotEmpty($ilanNo);
        $this->assertMatchesRegularExpression('/^[A-Z]{3}-[A-Z]{3}-\d{4}-\d{3}$/', $ilanNo);
    }

    /** @test */
    public function it_generates_sequential_numbers()
    {
        $ilan1 = Ilan::factory()->create(['ilan_no' => null]);
        $ilan2 = Ilan::factory()->create(['ilan_no' => null]);

        $no1 = $this->generator->generate($ilan1);
        $no2 = $this->generator->generate($ilan2);

        // Aynı tip ve kategorideyse sıra numarası artmalı
        $parsed1 = $this->generator->parse($no1);
        $parsed2 = $this->generator->parse($no2);

        if ($parsed1['tip'] === $parsed2['tip'] && $parsed1['kategori'] === $parsed2['kategori']) {
            $this->assertEquals($parsed1['sira'] + 1, $parsed2['sira']);
        }
    }

    /** @test */
    public function it_does_not_regenerate_existing_number()
    {
        $existingNo = 'STL-DRE-2024-999';
        $ilan = Ilan::factory()->create([
            'ilan_no' => $existingNo,
        ]);

        $ilanNo = $this->generator->generate($ilan);

        $this->assertEquals($existingNo, $ilanNo);
    }

    /** @test */
    public function it_parses_ilan_number_correctly()
    {
        $ilanNo = 'STL-DRE-2024-001';
        $parsed = $this->generator->parse($ilanNo);

        $this->assertEquals('STL', $parsed['tip']);
        $this->assertEquals('DRE', $parsed['kategori']);
        $this->assertEquals(2024, $parsed['yil']);
        $this->assertEquals(1, $parsed['sira']);
    }

    /** @test */
    public function it_validates_ilan_number_format()
    {
        $this->assertTrue($this->generator->validate('STL-DRE-2024-001'));
        $this->assertTrue($this->generator->validate('KRL-VLA-2025-999'));
        
        $this->assertFalse($this->generator->validate('INVALID'));
        $this->assertFalse($this->generator->validate('STL-DRE-2024'));
        $this->assertFalse($this->generator->validate('XXX-YYY-2024-001'));
    }

    /** @test */
    public function it_gets_tip_aciklama()
    {
        $this->assertEquals('Satılık', $this->generator->getTipAciklama('STL'));
        $this->assertEquals('Kiralık', $this->generator->getTipAciklama('KRL'));
        $this->assertEquals('Yazlık', $this->generator->getTipAciklama('YZL'));
    }

    /** @test */
    public function it_gets_kategori_aciklama()
    {
        $this->assertEquals('Daire', $this->generator->getKategoriAciklama('DRE'));
        $this->assertEquals('Villa', $this->generator->getKategoriAciklama('VLA'));
        $this->assertEquals('Arsa', $this->generator->getKategoriAciklama('ARS'));
    }

    /** @test */
    public function it_handles_concurrent_generation()
    {
        // Aynı anda 10 ilan oluştur
        $ilanlar = Ilan::factory()->count(10)->create(['ilan_no' => null]);
        
        $numaralar = [];
        foreach ($ilanlar as $ilan) {
            $numaralar[] = $this->generator->generate($ilan);
        }

        // Tüm numaralar benzersiz olmalı
        $this->assertEquals(count($numaralar), count(array_unique($numaralar)));
    }

    /** @test */
    public function it_generates_different_numbers_for_different_categories()
    {
        $daire = Ilan::factory()->create([
            'ilan_no' => null,
            'ana_kategori_id' => IlanKategori::factory()->create(['slug' => 'daire'])->id,
        ]);

        $villa = Ilan::factory()->create([
            'ilan_no' => null,
            'ana_kategori_id' => IlanKategori::factory()->create(['slug' => 'villa'])->id,
        ]);

        $daireNo = $this->generator->generate($daire);
        $villaNo = $this->generator->generate($villa);

        $parsedDaire = $this->generator->parse($daireNo);
        $parsedVilla = $this->generator->parse($villaNo);

        // Farklı kategoriler farklı kod almalı
        $this->assertNotEquals($parsedDaire['kategori'], $parsedVilla['kategori']);
    }
}
