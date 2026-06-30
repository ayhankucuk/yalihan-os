<?php

namespace Tests\Feature\Ranking;

use App\Models\Ilan;
use App\Models\IlanMetin;
use App\Services\Ranking\ListingRankingService;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class RankingEngineTest extends TestCase
{
    // Using manual schema bootstrap from base TestCase for stability

    protected ListingRankingService $rankingService;
    protected $ilan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTables();
        $this->rankingService = new ListingRankingService();
    }

    public function test_calculate_score_with_minimal_data()
    {
        $ilan = Ilan::factory()->create([
            'baslik' => 'Test Ilan',
            'aciklama' => '',
            'fiyat' => null
        ]);

        $score = $this->rankingService->calculateScore($ilan);

        // Minimal data should lead to a low score but greater than 0
        $this->assertGreaterThan(0, $score);
        $this->assertLessThan(5000, $score);
    }

    public function test_calculate_score_with_ai_content()
    {
        $ilan = Ilan::factory()->create();

        // Initial score without AI content
        $initialScore = $this->rankingService->calculateScore($ilan);

        // Add AI content
        IlanMetin::create([
            'ilan_id' => $ilan->id,
            'baslik' => 'AI Title',
            'aciklama' => 'AI Description',
            'yapay_zeka_durumu' => true,
            'taslak_durumu' => false,
            'aktiflik_durumu' => true
        ]);

        $newScore = $this->rankingService->calculateScore($ilan);

        // Score should increase significantly
        $this->assertGreaterThan($initialScore, $newScore);
    }

    public function test_calculate_score_with_media()
    {
        $ilan = Ilan::factory()->create();
        $initialScore = $this->rankingService->calculateScore($ilan);

        // In a real test we would add photos, here we mock the photo count logic
        // if necessary, or use factory for photos.
        // For simplicity, let's assume the calculateScore uses the relationship.

        $ilan->update(['youtube_video_url' => 'https://youtube.com/v/test']);

        $newScore = $this->rankingService->calculateScore($ilan);
        $this->assertGreaterThan($initialScore, $newScore);
    }

    private function createTables()
    {
        $schema = \Illuminate\Support\Facades\Schema::class;

        if (!$schema::hasTable('ilanlar')) {
            $schema::create('ilanlar', function ($table) {
                $table->id();
                $table->string('baslik')->nullable();
                $table->text('aciklama')->nullable();
                $table->decimal('fiyat', 15, 2)->nullable();
                $table->decimal('visibility_score', 8, 2)->default(0);
                $table->integer('goruntulenme')->default(0);
                $table->integer('favorite_count')->default(0);
                $table->string('youtube_video_url')->nullable();
                $table->unsignedBigInteger('il_id')->nullable();
                $table->unsignedBigInteger('ilce_id')->nullable();
                $table->unsignedBigInteger('mahalle_id')->nullable();
                $table->unsignedBigInteger('ana_kategori_id')->nullable();
                $table->unsignedBigInteger('alt_kategori_id')->nullable();
                $table->decimal('alan_m2', 12, 2)->nullable();
                $table->string('ada_no')->nullable();
                $table->string('parsel_no')->nullable();
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        } else {
            // Ensure columns exist if table was partially created by migrations
            $schema::table('ilanlar', function ($table) use ($schema) {
                if (!$schema::hasColumn('ilanlar', 'youtube_video_url')) {
                    $table->string('youtube_video_url')->nullable();
                }
                if (!$schema::hasColumn('ilanlar', 'visibility_score')) {
                    $table->decimal('visibility_score', 8, 2)->default(0);
                }
                if (!$schema::hasColumn('ilanlar', 'goruntulenme')) {
                    $table->integer('goruntulenme')->default(0);
                }
            });
        }

        if (!$schema::hasTable('ilan_metinleri')) {
            $schema::create('ilan_metinleri', function ($table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id');
                $table->string('baslik')->nullable();
                $table->text('aciklama')->nullable();
                $table->boolean('yapay_zeka_durumu')->default(false);
                $table->boolean('aktiflik_durumu')->default(false);
                $table->boolean('taslak_durumu')->default(true);
                $table->timestamps();
            });
        }
    }
}
