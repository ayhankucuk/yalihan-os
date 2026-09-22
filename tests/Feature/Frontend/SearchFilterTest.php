<?php

namespace Tests\Feature\Frontend;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private IlanKategori $kategori;
    private Il $il;
    private Ilce $ilce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kategori = IlanKategori::firstOrCreate(
            ['slug' => 'konut'],
            [
                'name' => 'Konut',
                'seviye' => 0,
                'aktiflik_durumu' => 1,
                'display_order' => 1,
            ]
        );

        $this->il = Il::firstOrCreate(['plaka_kodu' => '48'], ['il_adi' => 'Muğla', 'slug' => 'mugla', 'aktiflik_durumu' => 1]);
        $this->ilce = Ilce::firstOrCreate(['il_id' => $this->il->id, 'slug' => 'bodrum'], ['ilce_adi' => 'Bodrum', 'aktiflik_durumu' => 1]);

        $user = User::factory()->create();

        // Create an active listing to populate ilan_sayisi > 0 for SQLite subquery
        Ilan::create([
            'baslik' => 'Yalıkavak Lüks Villa',
            'fiyat' => 1500000,
            'para_birimi' => 'EUR',
            'yayin_durumu' => 'yayinda',
            'ana_kategori_id' => $this->kategori->id,
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'danisman_id' => $user->id,
            'tenant_id' => 1,
            'slug' => 'yalikavak-luks-villa',
        ]);
    }

    public function test_search_results_page_renders_with_location_filter_component(): void
    {
        $response = $this->get(route('ilanlar.index'));

        $response->assertStatus(200);
        $response->assertSee('Satılık Konut Portföyü');
        $response->assertSee('Filtrele');
    }

    public function test_search_results_displays_active_filter_chips(): void
    {
        $response = $this->get(route('ilanlar.index', [
            'search' => 'Yalıkavak',
            'min_fiyat' => 500000,
            'max_fiyat' => 2000000,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Aktif Filtreler');
        $response->assertSee('Yalıkavak');
        $response->assertSee('Filtreleri Temizle');
    }

    public function test_empty_state_renders_when_no_listings_match(): void
    {
        $response = $this->get(route('ilanlar.index', [
            'search' => 'NonExistentLocationXYZ99',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Bu kriterlerde aktif portföy bulunamadı');
    }
}
