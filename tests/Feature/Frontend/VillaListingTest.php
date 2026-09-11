<?php

namespace Tests\Feature\Frontend;

use App\Enums\IlanDurumu;
use App\Models\Event;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Mahalle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillaListingTest extends TestCase
{
    use RefreshDatabase;

    private IlanKategori $kategori;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kategori = IlanKategori::firstOrCreate(
            ['slug' => 'yazlik-kiralama'],
            [
                'name' => 'Yazlık Kiralama',
                'seviye' => 0,
                'aktiflik_durumu' => 1,
                'display_order' => 1,
            ]
        );
    }

    public function test_yazliklar_index_returns_404_when_category_missing(): void
    {
        $this->kategori->delete();

        $response = $this->get('/yazliklar');
        $response->assertStatus(404);
    }

    public function test_yazliklar_index_renders_successfully_when_category_exists(): void
    {
        $response = $this->get('/yazliklar');
        $response->assertStatus(200);
        $response->assertViewIs('villas.index');
        $response->assertViewHas(['villas', 'locations', 'popularAmenities', 'stats']);
    }

    public function test_yazliklar_index_with_listings_and_location_filtering(): void
    {
        $il = Il::firstOrCreate(['plaka_kodu' => 48], ['il_adi' => 'Muğla', 'slug' => 'mugla', 'aktiflik_durumu' => 1]);
        $ilce = Ilce::firstOrCreate(['il_id' => $il->id, 'slug' => 'bodrum'], ['ilce_adi' => 'Bodrum', 'aktiflik_durumu' => 1]);
        $mahalle = Mahalle::firstOrCreate(['ilce_id' => $ilce->id, 'slug' => 'yalikavak'], ['mahalle_adi' => 'Yalıkavak', 'aktiflik_durumu' => 1]);

        $user = User::factory()->create();

        $villa = Ilan::create([
            'tenant_id' => 1,
            'baslik' => 'Lüks Yalıkavak Villası',
            'slug' => 'luks-yalikavak-villasi',
            'aciklama' => 'Muhteşem deniz manzaralı villa',
            'fiyat' => 15000,
            'gunluk_fiyat' => 15000,
            'para_birimi' => 'TRY',
            'ana_kategori_id' => $this->kategori->id,
            'kategori_id' => $this->kategori->id,
            'il_id' => $il->id,
            'ilce_id' => $ilce->id,
            'mahalle_id' => $mahalle->id,
            'danisman_id' => $user->id,
            'ilan_sahibi_id' => $user->id,
            'yayin_durumu' => IlanDurumu::YAYINDA->value,
            'maksimum_misafir' => 6,
        ]);

        $response = $this->get('/yazliklar?location[]=Yalıkavak&guests=4');
        $response->assertStatus(200);
        $response->assertSee('Lüks Yalıkavak Villası');
    }

    public function test_yazliklar_show_renders_detail_page_for_published_villa(): void
    {
        $il = Il::firstOrCreate(['plaka_kodu' => 48], ['il_adi' => 'Muğla', 'slug' => 'mugla', 'aktiflik_durumu' => 1]);
        $ilce = Ilce::firstOrCreate(['il_id' => $il->id, 'slug' => 'bodrum'], ['ilce_adi' => 'Bodrum', 'aktiflik_durumu' => 1]);
        $mahalle = Mahalle::firstOrCreate(['ilce_id' => $ilce->id, 'slug' => 'gumusluk'], ['mahalle_adi' => 'Gümüşlük', 'aktiflik_durumu' => 1]);

        $user = User::factory()->create();

        $villa = Ilan::create([
            'tenant_id' => 1,
            'baslik' => 'Gümüşlük Gün Batımı Villası',
            'slug' => 'gumusluk-gun-batimi-villasi',
            'aciklama' => 'Özel havuzlu lüks villa',
            'fiyat' => 20000,
            'gunluk_fiyat' => 20000,
            'para_birimi' => 'TRY',
            'ana_kategori_id' => $this->kategori->id,
            'kategori_id' => $this->kategori->id,
            'il_id' => $il->id,
            'ilce_id' => $ilce->id,
            'mahalle_id' => $mahalle->id,
            'danisman_id' => $user->id,
            'ilan_sahibi_id' => $user->id,
            'yayin_durumu' => 'yayinda',
            'maksimum_misafir' => 8,
        ]);

        \App\Models\Photo::create([
            'tenant_id' => 1,
            'ilan_id' => $villa->id,
            'dosya_adi' => 'villa_featured.jpg',
            'dosya_yolu' => 'photos/villa_featured.jpg',
            'kapak_fotografi' => true,
            'display_order' => 1,
        ]);

        $response = $this->get('/yazliklar/' . $villa->id);
        $response->assertStatus(200);
        $response->assertViewIs('villas.show');
        $response->assertViewHas(['villa', 'availabilityCalendar', 'pricing', 'similarVillas']);
        $response->assertSee('Gümüşlük Gün Batımı Villası');
    }

    public function test_yazliklar_show_returns_404_for_non_published_or_missing_villa(): void
    {
        $response = $this->get('/yazliklar/999999');
        $response->assertStatus(404);
    }

    public function test_yazliklar_ajax_availability_check(): void
    {
        $user = User::factory()->create();

        $villa = Ilan::create([
            'tenant_id' => 1,
            'baslik' => 'Müsaitlik Test Villası',
            'slug' => 'musaitlik-test-villasi',
            'aciklama' => 'Test açıklama',
            'fiyat' => 10000,
            'gunluk_fiyat' => 10000,
            'para_birimi' => 'TRY',
            'ana_kategori_id' => $this->kategori->id,
            'kategori_id' => $this->kategori->id,
            'danisman_id' => $user->id,
            'ilan_sahibi_id' => $user->id,
            'yayin_durumu' => 'yayinda',
        ]);

        $checkIn = Carbon::tomorrow()->format('Y-m-d');
        $checkOut = Carbon::tomorrow()->addDays(5)->format('Y-m-d');

        $response = $this->postJson('/yazliklar/check-availability', [
            'ilan_id' => $villa->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => 2,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('available', true);
    }
}
