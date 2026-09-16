<?php

namespace Tests\Feature\CRM;

use App\Domain\CRM\Services\MatchDemandsForListingUseCase;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Kisi;
use App\Models\Mahalle;
use App\Models\Talep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * DemandMatchingSagaTest
 *
 * Verifies MatchDemandsForListingUseCase evaluation rules:
 * 1. Location scoring (İl, İlçe, Mahalle)
 * 2. Type & Category scoring
 * 3. Budget tolerance scoring
 * 4. Urgency classification
 * 5. Minimum score filtering
 * 6. Tenant isolation
 */
class DemandMatchingSagaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Il $ilMugla;
    private Ilce $ilceBodrum;
    private Mahalle $mahalleYalikavak;
    private IlanKategori $kategoriVilla;
    private Kisi $kisi;
    private MatchDemandsForListingUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'matching-admin-' . uniqid() . '@yalihan.local',
        ]);

        $this->ilMugla = Il::firstOrCreate(
            ['plaka_kodu' => '48'],
            ['id' => 48, 'il_adi' => 'Muğla', 'aktiflik_durumu' => 1]
        );

        $this->ilceBodrum = Ilce::firstOrCreate(
            ['il_id' => $this->ilMugla->id, 'ilce_adi' => 'Bodrum'],
            ['id' => 4801, 'aktiflik_durumu' => 1]
        );

        $this->mahalleYalikavak = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilceBodrum->id, 'mahalle_adi' => 'Yalıkavak'],
            ['id' => 480101, 'aktiflik_durumu' => 1]
        );

        $this->kategoriVilla = IlanKategori::firstOrCreate(
            ['slug' => 'luks-villa'],
            ['name' => 'Lüks Villa', 'aktiflik_durumu' => 1]
        );

        $this->kisi = Kisi::factory()->create([
            'ad' => 'Selin',
            'soyad' => 'Demir',
        ]);

        $this->useCase = app(MatchDemandsForListingUseCase::class);
    }

    /** @test */
    public function perfect_match_yields_high_score_and_reasons(): void
    {
        $talep = Talep::factory()->create([
            'tenant_id'       => 1,
            'baslik'          => 'Yalıkavak 4+1 Villa Arayışı',
            'talep_durumu'    => 'yayinda',
            'tip'             => 'Satılık',
            'talep_tipi'      => 'Satılık',
            'alt_kategori_id' => $this->kategoriVilla->id,
            'il_id'           => $this->ilMugla->id,
            'ilce_id'         => $this->ilceBodrum->id,
            'mahalle_id'      => $this->mahalleYalikavak->id,
            'kisi_id'         => $this->kisi->id,
            'min_fiyat'       => 20000000,
            'max_fiyat'       => 35000000,
        ]);

        $ilan = Ilan::factory()->create([
            'tenant_id'       => 1,
            'baslik'          => 'Yalıkavak Deniz Manzaralı Müstakil Villa',
            'yayin_tipi_id'   => 1,
            'alt_kategori_id' => $this->kategoriVilla->id,
            'il_id'           => $this->ilMugla->id,
            'ilce_id'         => $this->ilceBodrum->id,
            'mahalle_id'      => $this->mahalleYalikavak->id,
            'fiyat'           => 28000000,
            'yayin_durumu'    => 'yayinda',
        ]);

        $matches = $this->useCase->execute($ilan, 50.0);

        $this->assertCount(1, $matches);
        $match = $matches->first();

        $this->assertEquals($talep->id, $match->talepId);
        $this->assertEquals(100.0, $match->score);
        $this->assertContains('il_match', $match->matchReasons);
        $this->assertContains('ilce_match', $match->matchReasons);
        $this->assertContains('mahalle_match', $match->matchReasons);
        $this->assertContains('type_match', $match->matchReasons);
        $this->assertContains('category_match', $match->matchReasons);
        $this->assertContains('budget_match', $match->matchReasons);
        $this->assertEquals('CRITICAL', $match->urgencyLevel);
    }

    /** @test */
    public function out_of_budget_and_location_below_threshold_is_filtered(): void
    {
        $ilIstanbul = Il::firstOrCreate(
            ['plaka_kodu' => '34'],
            ['id' => 34, 'il_adi' => 'İstanbul', 'aktiflik_durumu' => 1]
        );

        Talep::factory()->create([
            'tenant_id'    => 1,
            'baslik'       => 'İstanbul Daire Talebi',
            'talep_durumu' => 'yayinda',
            'tip'          => 'Kiralık',
            'il_id'        => $ilIstanbul->id,
            'min_fiyat'    => 50000,
            'max_fiyat'    => 80000,
        ]);

        $ilan = Ilan::factory()->create([
            'tenant_id'       => 1,
            'baslik'          => 'Bodrum Satılık Villa',
            'yayin_tipi_id'   => 1,
            'il_id'           => $this->ilMugla->id,
            'fiyat'           => 30000000,
            'yayin_durumu'    => 'yayinda',
        ]);

        $matches = $this->useCase->execute($ilan, 60.0);

        $this->assertCount(0, $matches, 'Unmatched listing must not produce matches above 60% threshold.');
    }

    /** @test */
    public function tenant_isolation_is_strictly_enforced(): void
    {
        Talep::factory()->create([
            'tenant_id'    => 2, // Other tenant
            'baslik'       => 'Tenant 2 Talebi',
            'talep_durumu' => 'yayinda',
            'tip'          => 'Satılık',
            'il_id'        => $this->ilMugla->id,
            'min_fiyat'    => 20000000,
            'max_fiyat'    => 40000000,
        ]);

        $ilan = Ilan::factory()->create([
            'tenant_id'       => 1, // Current tenant
            'baslik'          => 'Tenant 1 İlanı',
            'yayin_tipi_id'   => 1,
            'il_id'           => $this->ilMugla->id,
            'fiyat'           => 30000000,
            'yayin_durumu'    => 'yayinda',
        ]);

        $matches = $this->useCase->execute($ilan, 0.0);

        $this->assertCount(0, $matches, 'Demands belonging to other tenants must never be matched.');
    }
}
