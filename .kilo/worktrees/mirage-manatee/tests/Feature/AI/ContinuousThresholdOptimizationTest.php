<?php

namespace Tests\Feature\AI;

use App\Models\AiThresholdOverride;
use App\Models\AiOptimizationRun;
use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContinuousThresholdOptimizationTest extends TestCase
{

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role_id' => 1]);

        // Seed category context
        DB::table('ilan_kategorileri')->insert([
            'id' => 1, 'name' => 'Konut', 'slug' => 'konut', 'aktiflik_durumu' => 1, 'display_order' => 1
        ]);
        DB::table('yayin_tipi_sablonlari')->insertOrIgnore([
            'id' => 1, 'ad' => 'Satılık', 'slug' => 'satilik', 'aktiflik_durumu' => 1, 'display_order' => 1, 'created_at' => now(), 'updated_at' => now()
        ]);

        Config::set('ai-governance.global.auto_apply_min_confidence', 0.80);
        Config::set('ai-governance.global.suggest_min_confidence', 0.50);
    }

    /** @test */
    public function it_does_not_change_thresholds_with_insufficient_sample_size()
    {
        // 49 samples (limit is 50)
        $this->seedUsages(1, 1, 49, 10, 39); // 10 dismissed, 39 accepted

        $this->artisan('ai:optimize-thresholds --apply')
             ->expectsOutputToContain('No optimization needed');

        $this->assertDatabaseEmpty('ai_threshold_overrides');
    }

    /** @test */
    public function it_increases_auto_apply_threshold_when_false_positive_rate_is_high()
    {
        // 100 samples, 40 dismissed (40% FP rate > 30% rule)
        $this->seedUsages(1, 1, 100, 40, 60);

        $this->artisan('ai:optimize-thresholds --apply');

        $this->assertDatabaseHas('ai_threshold_overrides', [
            'kategori_id' => 1,
            'auto_apply_threshold' => 0.83 // 0.80 + 0.03
        ]);
    }

    /** @test */
    public function it_decreases_auto_apply_threshold_when_performance_is_excellent()
    {
        // 100 samples, 5 dismissed (5% FP rate < 10% rule, 95% accept rate > 75% rule)
        $this->seedUsages(1, 1, 100, 5, 95);

        $this->artisan('ai:optimize-thresholds --apply');

        $this->assertDatabaseHas('ai_threshold_overrides', [
            'kategori_id' => 1,
            'auto_apply_threshold' => 0.78 // 0.80 - 0.02
        ]);
    }

    /** @test */
    public function it_enforces_invariants_auto_at_least_0_20_above_suggest()
    {
        // Force high suggest threshold via accept_rate < 0.45 rule
        // 100 samples, 60 dismissed (60% FP -> auto UP, 40% accept -> suggest UP)
        $this->seedUsages(1, 1, 100, 60, 40);

        $this->artisan('ai:optimize-thresholds --apply');

        $override = AiThresholdOverride::first();
        // auto = 0.80 + 0.03 = 0.83
        // suggest = 0.50 + 0.02 = 0.52
        // invariant: 0.83 >= 0.52 + 0.20 (0.72) -> TRUE
        $this->assertGreaterThanOrEqual($override->suggest_threshold + 0.20, $override->auto_apply_threshold);
    }

    /** @test */
    public function it_respects_yazlik_minimum_auto_apply_threshold()
    {
        // Yazlık (ID 5)
        DB::table('ilan_kategorileri')->insert([
            'id' => 5, 'name' => 'Yazlık', 'slug' => 'yazlik', 'aktiflik_durumu' => 1, 'display_order' => 5
        ]);
        DB::table('yayin_tipi_sablonlari')->insertOrIgnore([
            'id' => 5, 'ad' => 'Kiralık', 'slug' => 'kiralik', 'aktiflik_durumu' => 1, 'display_order' => 5, 'created_at' => now(), 'updated_at' => now()
        ]);

        // Force Yazlık config to 0.80 (looser than allowed by rule)
        Config::set('ai-governance.category_overrides.yazlik.auto_apply_min_confidence', 0.80);

        // Excellent performance which would normally drop it to 0.78
        // But the 0.90 minimum rule should kick in and SET it to 0.90
        $this->seedUsages(5, 5, 100, 5, 95);

        $this->artisan('ai:optimize-thresholds --apply --category=5');

        $this->assertDatabaseHas('ai_threshold_overrides', [
            'kategori_id' => 5,
            'auto_apply_threshold' => 0.90
        ]);
    }

    /** @test */
    public function it_supports_rollback()
    {
        $this->seedUsages(1, 1, 100, 40, 60);
        $this->artisan('ai:optimize-thresholds --apply');

        $run = AiOptimizationRun::first();
        $this->assertCount(1, AiThresholdOverride::all());

        $this->artisan("ai:rollback-thresholds {$run->id}")
             ->expectsConfirmation("Are you sure you want to rollback optimization run #{$run->id}?", 'yes');

        $this->assertDatabaseEmpty('ai_threshold_overrides');
    }

    /** @test */
    public function it_does_not_save_changes_in_dry_run()
    {
        $this->seedUsages(1, 1, 100, 40, 60);

        $this->artisan('ai:optimize-thresholds --dry-run')
             ->expectsOutputToContain('Dry-run complete');

        $this->assertDatabaseEmpty('ai_threshold_overrides');
    }

    private function seedUsages(int $catId, int $pubId, int $count, int $dismissed, int $accepted)
    {
        for ($i = 0; $i < $dismissed; $i++) {
            $this->createUsage($catId, $pubId, 'dismissed');
        }
        for ($i = 0; $i < $accepted; $i++) {
            $this->createUsage($catId, $pubId, 'user_applied');
        }
        // Fill remaining with 'auto_applied' which counts towards accepted logic or ignored based on specific query
        $remaining = $count - ($dismissed + $accepted);
        for ($i = 0; $i < $remaining; $i++) {
            $this->createUsage($catId, $pubId, 'auto_applied');
        }
    }

    private function createUsage($catId, $pubId, $aksiyon)
    {
        DB::table('ai_feature_usages')->insert([
            'kategori_id' => $catId,
            'yayin_tipi_id' => $pubId,
            'feature_slug' => 'test-feature',
            'confidence' => 0.85,
            'source_tipi' => 'image',
            'aksiyon' => $aksiyon,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
