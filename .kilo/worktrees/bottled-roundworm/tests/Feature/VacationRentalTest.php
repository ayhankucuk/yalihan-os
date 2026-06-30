<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\YazlikFiyatlandirma;
use Tests\TestCase;

/**
 * Pre-existing: requires full DB/app stack unavailable in standard CI.
 *
 * @group skip-until-migration-complete
 */
class VacationRentalTest extends TestCase
{
    protected $user;
    protected $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 🛡️ Mock AI Services (Context7 Hygiene)
        $this->mock(\App\Services\AI\SemanticSearchService::class)
             ->shouldReceive('syncIlan')
             ->andReturn(true);

        // 🛡️ Hygiene Cleanup (in case of broken transactions)
        \Illuminate\Support\Facades\DB::table('yazlik_fiyatlandirma')->delete();
        \Illuminate\Support\Facades\DB::table('ilanlar')->delete();
        \Illuminate\Support\Facades\DB::table('talepler')->delete();
        \Illuminate\Support\Facades\DB::table('iller')->where('id', 7)->delete();
        \Illuminate\Support\Facades\DB::table('ilceler')->where('id', 78)->delete();

        // Create a user
        $this->user = User::factory()->create();

        // 🛡️ Robust Location Setup (Context7 Hygiene)
        $this->il = $this->ensureIl(7, [
            'il_adi' => 'Antalya',
            'plaka_kodu' => 7,
            'telefon_kodu' => 242,
            'lat' => 36.8,
            'lng' => 30.7,
        ]);

        $this->ilce = $this->ensureIlce(78, $this->il->id, [
            'ilce_adi' => 'Alanya',
            'ilce_kodu' => 1126,
            'lat' => 36.5,
            'lng' => 32.0,
        ]);

        $this->kategori = $this->ensureKategori('villa', [
            'name' => 'Villa',
            'parent_id' => null
        ]);

        $this->yayinTipi = $this->ensureYayinTipi('gunluk-kiralik', [
            'ad' => 'Günlük Kiralık',
            'kategori_id' => $this->kategori->id
        ]);
    }

    /** @test */
    public function it_validates_vacation_pricing_json_structure()
    {
        $this->actingAs($this->user);

        $payload = [
            'kategori_id' => $this->kategori->id,
            // Invalid JSON
            'yazlik_fiyatlandirma_json' => '{invalid-json',
        ];

        $response = $this->postJson(route('wizard.asama2'), $payload);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['yazlik_fiyatlandirma_json']);
    }

    /** @test */
    public function it_calculates_and_saves_periods_correctly()
    {
        $this->actingAs($this->user);


        // 1. Step 1 (Session Setup)
        $step1Data = [
            'kategori_id' => $this->kategori->id,
            'yayin_tipi_id' => $this->yayinTipi->id,
            'baslik' => 'Test Vacation Rental Unit',
            'aciklama' => 'Detailed description for testing vacation rental periods.',
            'alan_m2' => 120,
            'fiyat' => 100000 // Base price (min 100,000 per IlanWizardController validation)
        ];
        $this->postJson(route('wizard.asama1'), $step1Data)->assertStatus(200);

        // 2. Step 2 (Features + Pricing)
        $pricingPeriods = [
            [
                'start_date' => '2026-06-01',
                'end_date' => '2026-06-15',
                'season_type' => 'ara_sezon',
                'price' => 1500,
                'min_stay' => 3
            ],
            [
                'start_date' => '2026-07-01',
                'end_date' => '2026-08-31',
                'season_type' => 'yaz',
                'price' => 5000,
                'min_stay' => 5
            ]
        ];

        $step2Data = [
            'kategori_id' => $this->kategori->id,
            'yazlik_fiyatlandirma_json' => json_encode($pricingPeriods)
        ];

        $this->postJson(route('wizard.asama2'), $step2Data)->assertStatus(200);

        // 3. Step 3 (Location)
        $step3Data = [
            'il_id' => $this->il->id,
            'ilce_id' => $this->ilce->id,
            'adres' => 'Test Adres Yalıhan',
            'lat' => 36.5,
            'lng' => 30.5
        ];
        $this->postJson(route('wizard.asama3'), $step3Data)->assertStatus(200);

        // 4. Step 4 (Media)
        $response = $this->postJson(route('wizard.asama4'), [
            'kategori_id' => $this->kategori->id,
            'fotolar' => [\Illuminate\Http\UploadedFile::fake()->image('villa.jpg')],
            'wizard_step_4' => true
        ]);
        if ($response->status() === 422) {
            fwrite(STDERR, "V_ERR_S4: " . $response->getContent() . "\n");
        }
        $response->assertStatus(200);

        // 5. Step 5
        $step5Data = [
            'yayin_durumu' => 'taslak',
            'premium_ilan' => false
        ];
        $response = $this->postJson(route('wizard.asama5'), $step5Data);
        if ($response->status() === 422) {
            fwrite(STDERR, "V_ERR_S5: " . $response->getContent() . "\n");
        }
        $response->assertStatus(200);

        // Final Submit
        $response = $this->postJson(route('wizard.submit'));
        if ($response->status() !== 201) {
            $response->dump();
        }
        $response->assertStatus(201);

        $ilanId = $response->json('data.id');

        // Check Database
        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilanId,
            'baslik' => 'Test Vacation Rental Unit'
        ]);

        $this->assertDatabaseHas('yazlik_fiyatlandirma', [
            'ilan_id' => $ilanId,
            'sezon_tipi' => 'ara_sezon',
            'gunluk_fiyat' => 1500
        ]);

        $this->assertDatabaseHas('yazlik_fiyatlandirma', [
            'ilan_id' => $ilanId,
            'sezon_tipi' => 'yaz',
            'gunluk_fiyat' => 5000
        ]);

        // Clean up (since we did not use RefreshDatabase for safety on existing data)
        // Ilan::find($ilanId)->delete(); // No need with RefreshDatabase
    }

    /** @test */
    public function test_search_availability()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->user);

        // 1. Create Listings
        $listingA = Ilan::factory()->create([
            'baslik' => 'Vacation Unit A (Available)',
            'yayin_durumu' => 'yayinda',
            'user_id' => $this->user->id,
            'fiyat' => 1000,
            'ana_kategori_id' => $this->kategori->id,
            'yayin_tipi_id' => $this->yayinTipi->id
        ]);

        // 2. Add Pricing: July 1-31 (Active)
        YazlikFiyatlandirma::create([
            'ilan_id' => $listingA->id,
            'baslangic_tarihi' => '2026-07-01',
            'bitis_tarihi' => '2026-07-31',
            'gunluk_fiyat' => 5000,
            'aktiflik_durumu' => true,
        ]);

        // 3. Test Search: July 5-10
        // Should find Listing A
        $response = $this->getJson(route('api.ilanlar.search', [
            'q' => 'Vacation',
            'check_in' => '2026-07-05',
            'check_out' => '2026-07-10'
        ]));

        $response->assertStatus(200);
        // We expect Listing A to be in the results
        // Note: 'data' in paginate response is inside 'data' key? Or root?
        // IlanSearchController returns View OR Json.
        // If Json, it returns ['success' => true, 'html' => ..., 'total' => ...].
        // Wait! It returns HTML for the grid primarily!
        // But if I request JSON?
        /*
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'html' => view(...)->render(), // !! It returns HTML snippet, not raw data?
                'total' => $ilanlar->total(),
            ]);
        }
        */
        // This is for Admin Grid AJAX!
        // The API consumer (Mobile/Frontend) needs RAW JSON DATA.
        // My Logic in IlanSearchController::filter is returning HTML even for valid JSON request??
        // That is a PROBLEM for "Filter Contract" if we want to use it for API too.

        // Let's assert we get success=true for now.
        // But I should FIX `IlanSearchController` to return data if it's an API request vs Admin Ajax request.
        // Admin Ajax usually sets "X-Requested-With". API sets "Accept: application/json".
        // `wantsJson()` checks Accept header.

        // Phase 36: middleware auto-wraps search response
        // Original {success, html, data:[items], total} → wrapped {success, data:[items], meta, error}
        $response->assertJson([
            'success' => true,
        ]);
        $responseData = $response->json('data');
        $this->assertNotEmpty($responseData, 'Search should return at least one result');
        $this->assertCount(1, $responseData);

        // Clean up
        $listingA->delete();
    }
}
