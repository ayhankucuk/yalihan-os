<?php

namespace Tests\Feature\SEO;

use App\Models\Ilan;
use App\Services\SEO\SeoEngineService;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class ListingSeoTest extends TestCase
{
    // Using manual schema for SQLite consistency

    protected SeoEngineService $seoService;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure route exists for URL generation services
        \Illuminate\Support\Facades\Route::get('/ilan/{slug}', fn() => '')->name('ilan.detay');

        $this->createTables();
        $this->seoService = new SeoEngineService();
    }

    public function test_generate_title_follows_tr_pattern()
    {
        \Illuminate\Support\Facades\Route::get('/ilan/{slug}', fn() => '')->name('ilan.detay');
        $ilan = Ilan::factory()->create([
            'kategori' => 'Daire',
            'yayin_tipi' => 'Satılık',
            'il' => 'Bodrum',
            'ilce' => 'Yalıkavak',
            'alan_m2' => 200,
            'fiyat' => 5000000,
            'para_birimi' => 'TRY'
        ]);

        $title = $this->seoService->generateTitle($ilan);

        $this->assertStringContainsString('Daire Satılık', $title);
        $this->assertStringContainsString('Bodrum / Yalıkavak', $title);
        $this->assertStringContainsString('200 m²', $title);
        $this->assertStringContainsString('5.000.000 TRY', $title);
    }

    public function test_generate_description_truncates_at_160()
    {
        $ilan = Ilan::factory()->create([
            'aciklama' => str_repeat('A', 500)
        ]);

        $description = $this->seoService->generateDescription($ilan);

        $this->assertLessThanOrEqual(160, strlen($description));
        $this->assertStringContainsString('...', $description);
    }

    public function test_json_ld_structure_validity()
    {
        $this->app['router']->get('/ilan/{slug}', ['as' => 'ilan.detay', 'uses' => fn() => '']);
        $ilan = Ilan::factory()->create([
            'baslik' => 'Lüks Villa',
            'slug' => 'luks-villa-1'
        ]);

        $jsonLd = $this->seoService->generateJsonLd($ilan);

        $this->assertEquals('Offer', $jsonLd['@type']);
        $this->assertArrayHasKey('itemOffered', $jsonLd);
        $this->assertEquals('Lüks Villa', $jsonLd['name']);
        $this->assertArrayHasKey('seller', $jsonLd);
    }

    private function createTables()
    {
        $schema = \Illuminate\Support\Facades\Schema::class;

        if (!$schema::hasTable('ilanlar')) {
            $schema::create('ilanlar', function ($table) {
                $table->id();
                $table->string('baslik')->nullable();
                $table->text('aciklama')->nullable();
                $table->string('slug')->nullable();
                $table->decimal('fiyat', 15, 2)->nullable();
                $table->string('para_birimi')->default('TRY');
                $table->string('kategori')->nullable();
                $table->string('yayin_tipi')->nullable();
                $table->string('il')->nullable();
                $table->string('ilce')->nullable();
                $table->decimal('alan_m2', 12, 2)->nullable();
                $table->integer('visibility_score')->default(0);
                $table->timestamps();
            });
        }
    }
}
