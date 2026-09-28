<?php

namespace Tests\Feature\Listing;

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use App\Models\ListingStateTransition;
use App\Services\Listing\ListingScoreService;
use App\Services\Listing\YalihanLifecycle;
use DomainException;
use Mockery;
use Tests\TestCase;

/**
 * ListingLifecycleReentrancyTest
 *
 * ILAN-05: Publish Lifecycle Reentrancy Remediation
 * Verifies that nested transition() calls maintain proper authorization depth
 * and that direct mutation guards on the Ilan model remain strictly enforced.
 */
class ListingLifecycleReentrancyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        YalihanLifecycle::resetAuthorization();
    }

    protected function tearDown(): void
    {
        YalihanLifecycle::resetAuthorization();
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Senaryo 1: Unauthorized direct mutation
     * Model guard must block direct yayin_durumu assignment.
     *
     * @test
     */
    public function unauthorized_direct_mutation_is_blocked_by_model_guard(): void
    {
        $ilan = $this->createPublishableListing(auth()->user(), [
            'yayin_durumu' => IlanDurumu::TASLAK,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('İlan durumu (yayin_durumu) doğrudan değiştirilemez');

        $ilan->yayin_durumu = IlanDurumu::YAYINDA;
        $ilan->save();
    }

    /**
     * Senaryo 2: BEKLEMEDE -> YAYINDA single transition
     * Normal single transition works without issues.
     *
     * @test
     */
    public function beklemede_to_yayinda_transition_succeeds(): void
    {
        $ilan = $this->createPublishableListing(auth()->user(), [
            'yayin_durumu' => IlanDurumu::BEKLEMEDE,
        ]);

        $mock = Mockery::mock(ListingScoreService::class);
        $mock->shouldReceive('computeCompletionScore')->andReturn(100);
        $mock->shouldReceive('computeQualityScore')->andReturn(85);
        $this->app->instance(ListingScoreService::class, $mock);

        $result = app(YalihanLifecycle::class)->transition($ilan, IlanDurumu::YAYINDA, auth()->id(), ['source' => 'test']);

        $this->assertEquals(IlanDurumu::YAYINDA, $result->yayin_durumu);
        $this->assertEquals(IlanDurumu::YAYINDA->value, $ilan->fresh()->yayin_durumu->value);

        $this->assertDatabaseHas('listing_state_transitions', [
            'ilan_id'    => $ilan->id,
            'from_state' => 'beklemede',
            'to_state'   => 'yayinda',
            'aktan_id'   => auth()->id(),
        ]);

        $this->assertFalse(YalihanLifecycle::$isAuthorized);
        $this->assertSame(0, YalihanLifecycle::getAuthDepth());
    }

    /**
     * Senaryo 3: TASLAK -> YAYINDA (Auto-Chain Reentrancy)
     * Directly requesting YAYINDA from TASLAK triggers auto-chain:
     *   TASLAK -> BEKLEMEDE (inner) -> YAYINDA (outer)
     * Reentrancy bug must not reset authorization prematurely for outer call.
     *
     * @test
     */
    public function taslak_to_yayinda_auto_chain_reentrancy_succeeds(): void
    {
        $ilan = $this->createPublishableListing(auth()->user(), [
            'yayin_durumu' => IlanDurumu::TASLAK,
        ]);

        $mock = Mockery::mock(ListingScoreService::class);
        $mock->shouldReceive('computeCompletionScore')->andReturn(100);
        $mock->shouldReceive('computeQualityScore')->andReturn(85);
        $this->app->instance(ListingScoreService::class, $mock);

        $result = app(YalihanLifecycle::class)->transition($ilan, IlanDurumu::YAYINDA, auth()->id(), ['source' => 'auto_chain_test']);

        // Assert final state is YAYINDA
        $this->assertEquals(IlanDurumu::YAYINDA, $result->yayin_durumu);
        $this->assertEquals(IlanDurumu::YAYINDA->value, $ilan->fresh()->yayin_durumu->value);

        // Assert both transitions were logged in order
        $transitions = ListingStateTransition::where('ilan_id', $ilan->id)
            ->orderBy('id', 'asc')
            ->get();

        $this->assertCount(2, $transitions);
        $this->assertEquals('taslak', $transitions[0]->from_state);
        $this->assertEquals('beklemede', $transitions[0]->to_state);
        $this->assertTrue($transitions[0]->meta['chained'] ?? false);

        $this->assertEquals('beklemede', $transitions[1]->from_state);
        $this->assertEquals('yayinda', $transitions[1]->to_state);

        // Assert authorization state cleanly reset to false with depth 0
        $this->assertFalse(YalihanLifecycle::$isAuthorized);
        $this->assertSame(0, YalihanLifecycle::getAuthDepth());
    }

    /**
     * Senaryo 4: Exception cleanup
     * When guards fail during transition, exception is thrown and authorization is cleanly reset.
     *
     * @test
     */
    public function exception_cleanup_ensures_is_authorized_remains_false(): void
    {
        $ilan = $this->createPublishableListing(auth()->user(), [
            'yayin_durumu' => IlanDurumu::BEKLEMEDE,
        ]);

        // Mock score service with failing completion score (80 < 100)
        $mock = Mockery::mock(ListingScoreService::class);
        $mock->shouldReceive('computeCompletionScore')->andReturn(80);
        $mock->shouldReceive('computeQualityScore')->andReturn(85);
        $this->app->instance(ListingScoreService::class, $mock);

        try {
            app(YalihanLifecycle::class)->transition($ilan, IlanDurumu::YAYINDA);
            $this->fail('Expected DomainException was not thrown');
        } catch (DomainException $e) {
            $this->assertStringContainsString('completion_score=80', $e->getMessage());
        }

        // Authorization flag and depth must be safely restored to false and 0
        $this->assertFalse(YalihanLifecycle::$isAuthorized);
        $this->assertSame(0, YalihanLifecycle::getAuthDepth());
    }

    /**
     * Senaryo 5: Auto-chain atomicity and exception behavior analysis
     * When auto-chaining TASLAK -> YAYINDA, if publish guards fail in step 2:
     * Step 1 (TASLAK -> BEKLEMEDE) was committed, step 2 threw DomainException,
     * leaving listing in BEKLEMEDE state, with authorization cleanly reset.
     *
     * @test
     */
    public function auto_chain_partial_failure_leaves_listing_in_beklemede_with_clean_auth(): void
    {
        $ilan = $this->createPublishableListing(auth()->user(), [
            'yayin_durumu' => IlanDurumu::TASLAK,
        ]);

        // Mock score service with failing completion score
        $mock = Mockery::mock(ListingScoreService::class);
        $mock->shouldReceive('computeCompletionScore')->andReturn(75);
        $mock->shouldReceive('computeQualityScore')->andReturn(85);
        $this->app->instance(ListingScoreService::class, $mock);

        try {
            app(YalihanLifecycle::class)->transition($ilan, IlanDurumu::YAYINDA);
            $this->fail('Expected DomainException was not thrown');
        } catch (DomainException $e) {
            $this->assertStringContainsString('completion_score=75', $e->getMessage());
        }

        // Step 1 committed: listing is now in BEKLEMEDE
        $this->assertEquals(IlanDurumu::BEKLEMEDE->value, $ilan->fresh()->yayin_durumu->value);

        // Exactly 1 transition recorded (taslak -> beklemede)
        $transitions = ListingStateTransition::where('ilan_id', $ilan->id)->get();
        $this->assertCount(1, $transitions);
        $this->assertEquals('taslak', $transitions[0]->from_state);
        $this->assertEquals('beklemede', $transitions[0]->to_state);

        // Authorization cleanly reset
        $this->assertFalse(YalihanLifecycle::$isAuthorized);
        $this->assertSame(0, YalihanLifecycle::getAuthDepth());
    }
}
