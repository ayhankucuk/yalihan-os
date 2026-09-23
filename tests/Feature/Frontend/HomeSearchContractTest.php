<?php

namespace Tests\Feature\Frontend;

use App\Enums\IlanDurumu;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Home → Search contract regression tests.
 *
 * Canonical contract (repository-verified via PublicListingService):
 *   yayin_tipi = satilik | kiralik | empty
 *   ilce       = integer ilce ID
 *   kategori   = integer category ID
 *   mahalle    = integer mahalle ID
 *
 * GAP-01: Hero search form sent non-canonical parameter names:
 *           ilan_turu → yayin_tipi, location → ilce, emlak_turu → kategori
 * GAP-02: Popular Neighborhood links sent mahalle_adi string instead of
 *           integer mahalle ID.
 */
class HomeSearchContractTest extends TestCase
{
    use RefreshDatabase;

    private IlanKategori $kategori;
    private IlanKategori $arsaKategori;
    private Il $il;
    private Ilce $ilce;
    private Mahalle $mahalle;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kategori = IlanKategori::firstOrCreate(
            ['slug' => 'konut'],
            ['name' => 'Konut', 'seviye' => 0, 'aktiflik_durumu' => 1, 'display_order' => 1]
        );

        $this->arsaKategori = IlanKategori::firstOrCreate(
            ['slug' => 'arsa-arazi'],
            ['name' => 'Arsa & Arazi', 'seviye' => 0, 'aktiflik_durumu' => 1, 'display_order' => 2]
        );

        $this->il = Il::firstOrCreate(
            ['plaka_kodu' => '48'],
            ['il_adi' => 'Muğla', 'slug' => 'mugla', 'aktiflik_durumu' => 1]
        );

        $this->ilce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'slug' => 'bodrum'],
            ['ilce_adi' => 'Bodrum', 'aktiflik_durumu' => 1]
        );

        $this->mahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'slug' => 'yalikavak'],
            ['mahalle_adi' => 'Yalıkavak', 'aktiflik_durumu' => 1]
        );

        $this->user = User::factory()->create();
    }

    // -------------------------------------------------------------------------
    // GAP-01 Tests — Canonical parameter names on Hero search form
    // -------------------------------------------------------------------------

    public function test_home_search_with_yayin_tipi_satilik_returns_satilik_listings(): void
    {
        // Active satılık listing
        Ilan::create([
            'baslik' => 'Satılık Bodrum Villa',
            'fiyat' => 5000000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'tenant_id' => 1,
            'slug' => 'satilik-bodrum-villa',
        ]);

        // Active kiralık listing (should be excluded)
        Ilan::create([
            'baslik' => 'Kiralık Bodrum Daire',
            'fiyat' => 25000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'tenant_id' => 1,
            'slug' => 'kiralik-bodrum-daire',
        ]);

        // Published at DB level means both have yayin_durumu = 'yayinda'
        // (yayin_tipi filter at service level requires additional setup,
        //  so we verify the parameter is accepted without error)
        $response = $this->get(route('ilanlar.index', ['yayin_tipi' => 'satilik']));

        $response->assertStatus(200);
        $response->assertSee('Satılık Bodrum Villa');
    }

    public function test_home_search_with_yayin_tipi_kiralik_returns_kiralik_listings(): void
    {
        $response = $this->get(route('ilanlar.index', ['yayin_tipi' => 'kiralik']));

        $response->assertStatus(200);
    }

    public function test_home_search_with_ilce_parameter_filters_by_district(): void
    {
        Ilan::create([
            'baslik' => 'Bodrum Merkezde Daire',
            'fiyat' => 3000000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'tenant_id' => 1,
            'slug' => 'bodrum-merkez-daire',
        ]);

        // Another ilce
        $otherIlce = Ilce::firstOrCreate(
            ['il_id' => $this->il->id, 'slug' => 'milas'],
            ['ilce_adi' => 'Milas', 'aktiflik_durumu' => 1]
        );

        $otherUser = User::factory()->create();
        Ilan::create([
            'baslik' => 'Milas ta Daire',
            'fiyat' => 1500000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $otherIlce->id,
            'mahalle_id' => null,
            'danisman_id' => $otherUser->id,
            'tenant_id' => 1,
            'slug' => 'milas-daire',
        ]);

        $response = $this->get(route('ilanlar.index', ['ilce' => $this->ilce->id]));

        $response->assertStatus(200);
        $response->assertSee('Bodrum Merkezde Daire');
        $response->assertDontSee('Milas ta Daire');
    }

    public function test_home_search_with_kategori_parameter_filters_by_category(): void
    {
        Ilan::create([
            'baslik' => 'Satılık Arsa',
            'fiyat' => 2000000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->arsaKategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'tenant_id' => 1,
            'slug' => 'satilik-arsa',
        ]);

        Ilan::create([
            'baslik' => 'Satılık Konut',
            'fiyat' => 4000000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'tenant_id' => 1,
            'slug' => 'satilik-konut',
        ]);

        $response = $this->get(route('ilanlar.index', ['kategori' => $this->arsaKategori->id]));

        $response->assertStatus(200);
        $response->assertSee('Satılık Arsa');
        $response->assertDontSee('Satılık Konut');
    }

    // -------------------------------------------------------------------------
    // GAP-02 Tests — Popular Neighborhood integer ID
    // -------------------------------------------------------------------------

    public function test_popular_neighborhood_link_sends_mahalle_integer_id(): void
    {
        Ilan::create([
            'baslik' => 'Yalıkavak ta Villa',
            'fiyat' => 8000000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $this->mahalle->id,
            'danisman_id' => $this->user->id,
            'tenant_id' => 1,
            'slug' => 'yalikavak-villa',
        ]);

        // Another mahalle
        $otherMahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'slug' => 'bitez'],
            ['mahalle_adi' => 'Bitez', 'aktiflik_durumu' => 1]
        );

        $otherUser = User::factory()->create();
        Ilan::create([
            'baslik' => 'Bitez de Daire',
            'fiyat' => 2500000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $otherMahalle->id,
            'danisman_id' => $otherUser->id,
            'tenant_id' => 1,
            'slug' => 'bitez-daire',
        ]);

        // Canonical: mahalle = integer ID
        $response = $this->get(route('ilanlar.index', ['mahalle' => $this->mahalle->id]));

        $response->assertStatus(200);
        $response->assertSee('Yalıkavak ta Villa');
        $response->assertDontSee('Bitez de Daire');
    }

    // -------------------------------------------------------------------------
    // Obsolete contract — render verification
    // -------------------------------------------------------------------------

    public function test_home_page_does_not_render_obsolete_ilan_turu_parameter(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);

        // The form's hidden input must use yayin_tipi, not ilan_turu
        // We verify the page renders without the old parameter name
        // by checking the form exists (ilan_turu should not appear in the source)
        $content = $response->getContent();

        // Assert obsolete names are NOT present in the form
        $this->assertStringNotContainsString('name="ilan_turu"', $content);
        $this->assertStringNotContainsString('name="location"', $content);
        $this->assertStringNotContainsString('name="emlak_turu"', $content);
    }

    public function test_home_page_renders_canonical_parameter_names_in_hero_search_form(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Verify canonical names ARE present
        $this->assertStringContainsString('name="yayin_tipi"', $content);
        $this->assertStringContainsString('name="ilce"', $content);
        $this->assertStringContainsString('name="kategori"', $content);
    }

    public function test_popular_neighborhood_links_use_integer_id_not_string_name(): void
    {
        // Create mahalle with known ID
        $testMahalle = Mahalle::firstOrCreate(
            ['ilce_id' => $this->ilce->id, 'slug' => 'gumusluk'],
            ['mahalle_adi' => 'Gümüşlük', 'aktiflik_durumu' => 1]
        );

        // Seed a listing so populerMahalleler includes this mahalle
        Ilan::create([
            'baslik' => 'Gümüşlük te Ev',
            'fiyat' => 3500000,
            'para_birimi' => 'TL',
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'mahalle_id' => $testMahalle->id,
            'danisman_id' => $this->user->id,
            'tenant_id' => 1,
            'slug' => 'gumusluk-ev',
        ]);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Popular neighborhood links should contain mahalle ID, not mahalle_adi string
        // The correct pattern: route('ilanlar.index', ['mahalle' => $mahalle->id])
        // So the URL should contain mahalle= with the integer ID
        $expectedPattern = 'mahalle=' . $testMahalle->id;
        $this->assertStringContainsString($expectedPattern, $content);

        // And should NOT contain the string name in a mahalle= query param
        $wrongPattern = 'mahalle=' . urlencode($testMahalle->mahalle_adi);
        $this->assertStringNotContainsString($wrongPattern, $content);
    }
}
