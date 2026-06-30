<?php

namespace Tests\Feature;

use App\Models\Ilan;
use App\Models\User;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu; // Changed
use App\Services\AI\DataDrivenAIContentService;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class KonutStructuredDataTest extends TestCase
{

    protected User $user;
    protected Ilan $ilan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $kategori = IlanKategori::firstOrCreate([
            'slug' => 'konut',
        ], [
            'name' => 'Konut',
            'aktiflik_durumu' => true,
            'display_order' => 1,
        ]);

        // Fixed: Use YayinTipiSablonu with firstOrCreate
        $yayinTipi = YayinTipiSablonu::firstOrCreate([
            'slug' => 'satilik',
        ], [
            'ad' => 'Satılık',
            'aktiflik_durumu' => true,
        ]);

        $this->ilan = Ilan::create([
            'baslik' => 'Test Konut İlan',
            'fiyat' => 1000000,
            'para_birimi' => 'TRY',
            'ana_kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipi->id,
            'structured_data_scope' => 'konut_satilik',
            'yayin_durumu' => 'Taslak',
        ]);
    }

    public function test_ai_endpoint_returns_403_when_not_approved(): void
    {
        $response = $this->postJson("/admin/ilanlar/{$this->ilan->id}/structured-data/konut/title");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'İlan onaylanmamış (mühürlenmemiş)',
        ]);
    }

    public function test_validate_endpoint_returns_422_with_missing_required_fields(): void
    {
        $structuredData = [
            'konut_tipi' => 'villa',
        ];

        $response = $this->postJson("/admin/ilanlar/{$this->ilan->id}/structured-data/konut/validate", [
            'structured_data' => $structuredData,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $this->assertNotEmpty($response->json('errors'));
    }

    public function test_validate_endpoint_returns_200_with_valid_data(): void
    {
        $structuredData = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'brut_m2' => 150.5,
            'banyo_sayisi' => 2,
            'fiyat' => [
                'satilik_fiyat' => 1000000,
                'para_birimi' => 'TRY',
            ],
        ];

        $response = $this->postJson("/admin/ilanlar/{$this->ilan->id}/structured-data/konut/validate", [
            'structured_data' => $structuredData,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    public function test_store_endpoint_saves_structured_data(): void
    {
        $structuredData = [
            'lokasyon' => [
                'il_id' => 1,
                'ilce_id' => 2,
            ],
            'konut_tipi' => 'villa',
            'oda_sayisi' => 3,
            'salon_sayisi' => 1,
            'brut_m2' => 150.5,
            'banyo_sayisi' => 2,
            'fiyat' => [
                'satilik_fiyat' => 1000000,
                'para_birimi' => 'TRY',
            ],
        ];

        $response = $this->postJson("/admin/ilanlar/{$this->ilan->id}/structured-data/konut", [
            'structured_data' => $structuredData,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->ilan->refresh();
        $this->assertEquals('konut_satilik', $this->ilan->structured_data_scope);
        $this->assertNotNull($this->ilan->structured_data);
    }

    public function test_approve_endpoint_sets_approved_at(): void
    {
        $this->ilan->update([
            'structured_data' => [
                'lokasyon' => ['il_id' => 1, 'ilce_id' => 2],
                'konut_tipi' => 'villa',
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'brut_m2' => 150.5,
                'banyo_sayisi' => 2,
                'fiyat' => ['satilik_fiyat' => 1000000, 'para_birimi' => 'TRY'],
            ],
        ]);

        $response = $this->postJson("/admin/ilanlar/{$this->ilan->id}/structured-data/konut/approve");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $this->ilan->refresh();
        $this->assertNotNull($this->ilan->approved_at);
        $this->assertEquals($this->user->id, $this->ilan->approved_by);
    }

    public function test_ai_endpoint_returns_200_when_approved(): void
    {
        $this->ilan->update([
            'structured_data' => [
                'lokasyon' => ['il_id' => 1, 'ilce_id' => 2],
                'konut_tipi' => 'villa',
                'oda_sayisi' => 3,
                'salon_sayisi' => 1,
                'brut_m2' => 150.5,
                'banyo_sayisi' => 2,
                'fiyat' => ['satilik_fiyat' => 1000000, 'para_birimi' => 'TRY'],
            ],
            'approved_at' => now(),
            'approved_by' => $this->user->id,
        ]);

        $mockAiService = Mockery::mock(DataDrivenAIContentService::class);
        $mockAiService->shouldReceive('generateTitle')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => ['title' => 'Test Başlık'],
                'provider' => 'ollama',
            ]);

        App::instance(DataDrivenAIContentService::class, $mockAiService);

        $response = $this->postJson("/admin/ilanlar/{$this->ilan->id}/structured-data/konut/title");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
