<?php

namespace Tests\Feature\AI;

use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\UpsTemplate;
use App\Models\User;
use App\Services\AI\PriceService;
use App\Services\AI\IlanStorytellingService;
use App\Services\AIService;
use App\Services\AI\YalihanCortex;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class TemplateDrivenAITest extends TestCase
{

    protected User $admin;
    protected Ilan $ilan;
    protected UpsTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        // Manual Schema Setup for SQLite to bypass migration issues
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');
            $this->createTables();
        }

        $this->admin = User::firstOrCreate([
            'email' => 'admin@yalihan.com'
        ], [
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'role_id' => 1
        ]);
        $this->actingAs($this->admin);

        // Setup kategori & yayin tipi
        $kategori = IlanKategori::factory()->create(['slug' => 'tarla']);
        $yayinTipiJunction = YayinTipiSablonu::firstOrCreate([
            'slug' => 'satilik'
        ], [
            'ad' => 'Satılık'
        ]);

        // Create Template with AI multipliers and hints
        $this->template = UpsTemplate::create([
            'yayin_tipi_sablonu_id' => $yayinTipiJunction->id,
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipiJunction->id,
            'template_json' => [
                'ai_fiyat_onerisi' => [
                    'yol_cephe_carpani' => 1.2,
                    'manzara_carpani' => 1.5
                ],
                'storytelling_hints' => [
                    'Toprak kalitesine vurgu yap',
                    'Yatırım potansiyelini belirt'
                ]
            ],
            'aktiflik_durumu' => 1,
            'template_version' => 1,
            'template_hash' => 'test_hash'
        ]);

        // Associated junction with template
        $yayinTipiJunction->update(['ups_template_id' => $this->template->id]);

        $this->ilan = Ilan::create([
            'baslik' => 'Test İlan',
            'slug' => 'test-ilan',
            'ana_kategori_id' => $kategori->id,
            'kategori_id' => $kategori->id,
            'yayin_tipi_id' => $yayinTipiJunction->id,
            'fiyat' => 900000,
            'para_birimi' => 'TRY'
        ]);
    }

    public function test_price_service_uses_template_multipliers()
    {
        $this->mock(AIService::class, function (MockInterface $mock) {
            $mock->shouldReceive('analyze')
                ->once()
                ->with(
                    Mockery::on(function ($context) {
                        return isset($context['business_rules']['price_multipliers']) &&
                               $context['business_rules']['price_multipliers']['manzara_carpani'] === 1.5;
                    }),
                    ['type' => 'price']
                )
                ->andReturn(['price' => 1000000, 'meta' => []]);
        });

        $priceService = app(PriceService::class);
        $result = $priceService->predict([
            'kategori_id' => $this->ilan->ana_kategori_id,
            'yayin_tipi_id' => $this->ilan->yayin_tipi_id,
            'fiyat' => 900000
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['data']['meta']['using_template_rules']);
    }

    public function test_storytelling_service_uses_template_hints()
    {
        $this->mock(YalihanCortex::class, function (MockInterface $mock) {
            $mock->shouldReceive('generateIlanDescription')
                ->once()
                ->with(
                    Mockery::any(),
                    Mockery::on(function ($options) {
                        return isset($options['prompt_override']) &&
                               str_contains($options['prompt_override'], 'Toprak kalitesine vurgu yap') &&
                               str_contains($options['prompt_override'], 'Yatırım potansiyelini belirt');
                    })
                )
                ->andReturnUsing(fn() => [
                    'success' => true,
                    'description' => 'Test description',
                    'text' => 'Test description',
                    'provider' => 'ollama',
                    'duration' => 0.1
                ]);
        });

        $storyService = app(IlanStorytellingService::class);
        $result = $storyService->olustur($this->ilan->id);

        $this->assertEquals('Test description', $result->aciklama);
        $this->assertTrue($result->yapay_zeka_durumu);
    }

    private function createTables()
    {
        $schema = \Illuminate\Support\Facades\Schema::class;

        if (!$schema::hasTable('users')) {
            $schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->integer('role_id')->default(1);
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!$schema::hasTable('ilan_kategorileri')) {
            $schema::create('ilan_kategorileri', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('seviye')->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!$schema::hasTable('yayin_tipi_sablonlari')) {
            $schema::create('yayin_tipi_sablonlari', function ($table) {
                $table->id();
                $table->string('ad')->nullable();
                $table->string('slug')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->unsignedBigInteger('ups_template_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!$schema::hasTable('ups_templates')) {
             $schema::create('ups_templates', function ($table) {
                $table->id();
                $table->unsignedBigInteger('yayin_tipi_sablonu_id')->nullable();
                $table->unsignedBigInteger('kategori_id');
                $table->unsignedBigInteger('yayin_tipi_id')->default(0);
                $table->json('template_json');
                $table->integer('template_version')->default(1);
                $table->string('template_hash')->nullable();
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!$schema::hasTable('ilanlar')) {
            $schema::create('ilanlar', function ($table) {
                $table->id();
                $table->string('baslik')->nullable();
                $table->unsignedBigInteger('ana_kategori_id')->nullable();
                $table->unsignedBigInteger('kategori_id')->nullable();
                $table->unsignedBigInteger('yayin_tipi_id')->nullable();
                $table->decimal('fiyat', 15, 2)->nullable();
                $table->string('para_birimi')->default('TRY');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!$schema::hasTable('ilan_metinleri')) {
            $schema::create('ilan_metinleri', function ($table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id');
                $table->string('baslik')->nullable();
                $table->text('aciklama')->nullable();
                $table->string('ton')->nullable();
                $table->boolean('taslak_durumu')->default(true);
                $table->boolean('aktiflik_durumu')->default(false);
                $table->boolean('yapay_zeka_durumu')->default(true);
                $table->json('kaynak_veriler')->nullable();
                $table->timestamps();
            });
        }
    }
}
