<?php

namespace Tests\Feature\ListingLifecycle;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Services\Listing\YalihanLifecycle;
use App\Services\Listing\ListingScoreService;
use Tests\TestCase;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;

/**
 * ListingLifecycleFinalSealTest
 * 🛡️ Phase T4: Sealing Test Packs Standardization
 */
class ListingLifecycleFinalSealTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // 🛡️ Canonical Admin Fixture
        $admin = $this->createAdminUser();
        $this->actingAs($admin);
    }

    /** @test */
    public function direct_state_mutation_is_blocked_by_model_guard()
    {
        $ilan = Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::TASLAK,
            'danisman_id' => auth()->id()
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('İlan durumu (yayin_durumu) doğrudan değiştirilemez');

        $ilan->yayin_durumu = IlanDurumu::YAYINDA;
        $ilan->save();
    }

    /** @test */
    public function publishing_is_blocked_if_completion_score_is_below_100()
    {
        $ilan = $this->createPublishableListing(auth()->user(), [
            'completion_score' => 80
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('completion_score=80');

        app(YalihanLifecycle::class)->transition($ilan, IlanDurumu::YAYINDA);
    }

    /** @test */
    public function publishing_is_blocked_if_quality_score_is_below_40()
    {
        $ilan = $this->createPublishableListing(auth()->user(), [
            'quality_score' => 35
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('minimum kalite skoru %40 olmalıdır');

        app(YalihanLifecycle::class)->transition($ilan, IlanDurumu::YAYINDA);
    }

    /** @test */
    public function arsivlendi_alias_correctly_maps_to_arsiv_state()
    {
        $ilan = $this->createPublishableListing(auth()->user(), [
            'yayin_durumu' => IlanDurumu::YAYINDA
        ]);

        $response = $this->patchJson(route('admin.ilanlar.yayin.update', $ilan), [
            'yayin_durumu' => 'Arşivlendi'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(IlanDurumu::ARSIV->value, $ilan->fresh()->yayin_durumu->value);
    }

    /** @test */
    public function forbidden_transitions_are_blocked_by_state_machine()
    {
        $ilan = Ilan::factory()->create([
            'yayin_durumu' => IlanDurumu::TASLAK,
            'danisman_id' => auth()->id()
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Geçersiz durum geçişi: Taslak → Yayında');

        app(YalihanLifecycle::class)->transition($ilan, IlanDurumu::YAYINDA);
    }

    /** @test */
    public function valid_transition_flow_is_successful_with_history_and_versioning()
    {
        Cache::forget('app_state_version');
        Carbon::setTestNow(Carbon::create(2026, 4, 18, 10, 0, 0));
        
        $ilan = $this->createPublishableListing(auth()->user(), [
            'yayin_durumu' => IlanDurumu::TASLAK
        ]);

        $lifecycle = app(YalihanLifecycle::class);

        // 1. Taslak -> Beklemede
        Carbon::setTestNow(now()->addMinutes(1));
        $v1 = now()->timestamp;
        
        $lifecycle->transition($ilan, IlanDurumu::BEKLEMEDE);
        
        $this->assertEquals(IlanDurumu::BEKLEMEDE->value, $ilan->fresh()->yayin_durumu->value);
        $this->assertEquals($v1, Cache::get('app_state_version'));

        // 2. Beklemede -> Yayında
        Carbon::setTestNow(now()->addMinutes(1));
        $v2 = now()->timestamp;
        
        $lifecycle->transition($ilan, IlanDurumu::YAYINDA);
        
        $this->assertEquals(IlanDurumu::YAYINDA->value, $ilan->fresh()->yayin_durumu->value);
        $this->assertEquals($v2, Cache::get('app_state_version'));
    }

    /** @test */
    public function publish_gate_controller_smoke_test()
    {
        $ilan = $this->createPublishableListing(auth()->user());

        $mock = Mockery::mock(ListingScoreService::class);
        $mock->shouldReceive('refreshAndPersistScores')->andReturnNull();
        $mock->shouldReceive('computeBreakdown')->andReturn([]);
        $this->app->instance(ListingScoreService::class, $mock);

        $response = $this->postJson(route('admin.ilanlar.publish', $ilan), [
            'override' => false
        ]);

        $response->assertStatus(200);
        $this->assertEquals(IlanDurumu::YAYINDA->value, $ilan->fresh()->yayin_durumu->value);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
