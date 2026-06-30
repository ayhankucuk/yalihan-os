<?php

namespace Tests\Feature\Wizard;

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\UpsTemplate;
use App\Models\YayinTipi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 */
class ListingQualityScoreTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        // Manual Schema Setup for SQLite to bypass migration issues
        if (DB::getDriverName() === 'sqlite') {
            $this->createTables();
        }
    }

    private function createTables()
    {
        if (!Schema::hasTable('ilan_kategorileri')) {
            Schema::create('ilan_kategorileri', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('yayin_tipleri')) {
             Schema::create('yayin_tipleri', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('yayin_tipi_sablonlari')) {
            Schema::create('yayin_tipi_sablonlari', function ($table) {
                $table->id();
                $table->string('ad')->nullable();
                $table->string('slug')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Ensure YayinTipiSablonu does NOT have ups_template_id column (V2)
        // UpsTemplate points to YayinTipiSablonu, NOT vice versa.
        if (Schema::hasColumn('yayin_tipi_sablonlari', 'ups_template_id')) {
            Schema::table('yayin_tipi_sablonlari', function ($table) {
                $table->dropColumn('ups_template_id');
            });
        }

        if (!Schema::hasTable('ups_templates')) {
             Schema::create('ups_templates', function ($table) {
                $table->id();
                $table->unsignedBigInteger('yayin_tipi_sablonu_id'); // non-nullable in migration
                $table->unsignedBigInteger('kategori_id');
                $table->unsignedBigInteger('yayin_tipi_id');
                $table->json('template_json');
                $table->integer('template_version')->default(1);
                $table->string('template_hash')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function test_calculate_score_returns_expected_logic()
    {
        // 1. Setup Data
        $kategori = IlanKategori::factory()->create(['name' => 'Tarla', 'slug' => 'tarla']);

        // Ensure YayinTipi exists or create
        $yayinTipi = YayinTipi::firstOrCreate(
            ['slug' => 'satilik'],
            ['name' => 'Satılık']
        );

        // Circular Dependency Resolution:
        // A. Create/Get Pivot (without UpsTemplate)
        $pivot = YayinTipiSablonu::firstOrCreate(
             ['slug' => 'satilik'],
             ['ad' => 'Satılık', 'aktiflik_durumu' => true]
        );

        // B. Create UpsTemplate (with Pivot ID)
        $upsTemplate = UpsTemplate::create([
             'yayin_tipi_sablonu_id' => $pivot->id, // REQUIRED FK
             'kategori_id' => $kategori->id,
             'yayin_tipi_id' => $yayinTipi->id,
             'template_version' => 1,
             'template_hash' => 'hash123',
             'aktiflik_durumu' => true,
             'template_json' => [
                 'zorunlu_alanlar' => ['metrekare', 'tapu_durumu'],
                 'opsiyonel_alanlar' => ['imar_durumu', 'ada', 'parsel'],
                 'gizli_alanlar' => [],
                 'validasyon_kurallari' => [],
                 'ui_ipuclari' => []
             ]
        ]);

        // C. V2 logic: No update needed on pivot
        // $pivot->update(['ups_template_id' => $upsTemplate->id]); // REMOVED FOR V2 COMPLIANCE

        // 2. Scenario: Full Data
        // Base: 50
        // Required (2): +20 = 70
        // Recommended (3): +15 = 85
        $formDataFull = [
             'metrekare' => 5000,
             'tapu_durumu' => 'Müstakil',
             'imar_durumu' => 'Konut',
             'ada' => 123,
             'parsel' => 456
        ];

        $responseFull = $this->postJson(route('wizard.score'), [
             'category_id' => $kategori->id,
             'yayin_tipi_id' => $pivot->id,
             'form_data' => $formDataFull
        ]);

        $responseFull->assertOk()
             ->assertJsonPath('data.score', 85)
             ->assertJsonPath('data.level', 'good')
             ->assertJsonPath('data.completed_ratio.required', 1); // Relaxed float check

        // 3. Scenario: Missing Required & Recommended
        // Base: 50
        // Required (Filled: 1 [+10], Missing: 1 [-20]): 50 + 10 - 20 = 40
        // Recommended (Filled: 1 [+5], Missing: 2 [-10]): 40 + 5 - 10 = 35
        $formDataPartial = [
             'metrekare' => 5000,
             // missing tapu_durumu
             'imar_durumu' => 'Konut',
             // missing ada, parsel
        ];

        $responsePartial = $this->postJson(route('wizard.score'), [
             'category_id' => $kategori->id,
             'yayin_tipi_id' => $pivot->id,
             'form_data' => $formDataPartial
        ]);

        $responsePartial->assertOk()
             ->assertJsonPath('data.score', 35)
             ->assertJsonPath('data.level', 'poor')
             ->assertJsonFragment(['missing_required' => ['tapu_durumu']])
             ->assertJsonFragment(['missing_recommended' => ['ada', 'parsel']]);

        // Check Hints exist
        $this->assertNotEmpty($responsePartial->json('data.hints'));
    }

    /**
     * [SAB ENFORCEMENT] Soft fallback kaldırıldı (legacy average score = 50 devre dışı).
     * Aktif UpsTemplate yoksa TemplateResolutionException → 500 dönmesi beklenir.
     * Deterministic: aynı DB state her zaman aynı hata üretir.
     */
    public function test_inactive_template_returns_legacy_average_score()
    {
        $kategori = IlanKategori::factory()->create();
        $pivot = YayinTipiSablonu::factory()->create();

        $response = $this->postJson(route('wizard.score'), [
            'category_id' => $kategori->id,
            'yayin_tipi_id' => $pivot->id,
            'form_data' => ['dummy' => 'data']
        ]);

        // SAB ENFORCEMENT: UpsTemplate yoksa legacy fallback yok → hata döner
        $response->assertStatus(500)
            ->assertJsonPath('message', 'Skor hesaplanamadı');
    }
}
