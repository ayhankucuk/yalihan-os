<?php

namespace Tests\Feature\Api\Mobile;

use App\Models\Il;
use App\Models\Ilce;
use App\Models\Ilan;
use App\Models\Mahalle;
use App\Models\User;
use App\Models\YayinTipi;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class MobileApiTest extends TestCase
{

    protected $user;
    protected $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup locations - Manual creation as factories are missing
        $city = new Il();
        $city->id = 48;
        $city->fill([
            'il_adi' => 'Muğla',
            'plaka_kodu' => 48,
            'telefon_kodu' => 252,
            'lat' => 37.2153,
            'lng' => 28.3636
        ]);
        $city->save();

        $district = new Ilce();
        $district->id = rand(1000, 9999);
        $district->il_id = 48;
        $district->fill([
            'ilce_adi' => 'Bodrum',
            'lat' => 37.0344,
            'lng' => 27.4305,
            'aktiflik_durumu' => true
        ]);
        $district->save();

        $neighborhood = new Mahalle();
        $neighborhood->id = rand(10000, 99999);
        $neighborhood->ilce_id = $district->id;
        $neighborhood->fill([
            'mahalle_adi' => 'Gümüşlük',
            'posta_kodu' => '48970'
        ]);
        $neighborhood->save();

        $this->user = User::factory()->create();

        // Create listing without triggering Observers (Ollama)
        $this->ilan = Ilan::withoutEvents(function () use ($city, $district, $neighborhood) {
            return Ilan::factory()->create([
                'danisman_id' => $this->user->id,
                'il_id' => $city->id,
                'ilce_id' => $district->id,
                'mahalle_id' => $neighborhood->id,
                'yayin_durumu' => 'yayinda',
                'baslik' => 'Test Mobile Villa',
                'fiyat' => 5000000,
                'para_birimi' => 'TRY',
                'oda_sayisi' => '3+1',
            ]);
        });
    }

    /** @test */
    public function v2_listings_index_returns_lightweight_resource()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.ilanlar.index'));

        $response->assertStatus(200);

        // Verify Structure matches IlanListResource
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'price' => [
                        'amount',
                        'currency',
                        'formatted'
                    ],
                    'image',
                    'location' => [
                        'city',
                        'district',
                    ],
                    'features',
                ]
            ],
            'links',
            'meta'
        ]);

        // Assert payload does NOT contain heavy fields
        $data = $response->json('data.0');
        $this->assertArrayNotHasKey('aciklama', $data, 'List view should not have description');
        $this->assertArrayNotHasKey('fotograflar', $data, 'List view should not have full gallery');
    }

    /** @test */
    public function frontend_properties_returns_standardized_resource()
    {
        $response = $this->getJson(route('api.frontend.properties.index'));

        $response->assertStatus(200);

        // Verify it uses the same structure as V2
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'price',
                    'location',
                    'features'
                ]
            ]
        ]);

        // Verify data integrity
        $response->assertJsonFragment(['title' => 'Test Mobile Villa']);
    }

    /** @test */
    public function v2_listing_detail_returns_full_resource()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson(route('api.ilanlar.show', $this->ilan->id));

        $response->assertStatus(200);

        // Verify matches IlanDetailResource
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'description',
                'price',
                'location' => [
                    'coordinates'
                ],
                'gallery',
                'agent',
                'attributes',
                'meta'
            ]
        ]);
    }
}
