<?php

namespace Tests\Feature\Listing;

use App\Domain\Listing\ListingCrudService;
use App\Models\Ilan;
use App\Models\ListingStateTransition;
use App\Models\Property;
use App\Models\SaaS\Tenant;
use App\Models\PropertyWorkspace;
use App\Services\Listing\YalihanLifecycle;
use App\Services\Property\PropertyCrudService;
use App\Services\Property\PropertyStateMachine;
use App\Services\SaaS\TenantContextService;
use App\Repositories\EloquentPropertyRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

/**
 * Sprint 12B Phase 3: Replay Determinism Tests
 *
 * Validates that listing state transitions can be reliably replayed from history.
 *
 * Key invariants:
 * 1. Replay produces the same final state
 * 2. Replay creates new transitions (not duplicates)
 * 3. Original audit trail is preserved (immutable)
 * 4. Replay is idempotent
 */
class ReplayDeterminismTest extends TestCase
{
    protected Tenant $tenant;
    protected PropertyCrudService $propertyService;
    protected ListingCrudService $listingService;
    protected YalihanLifecycle $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupTestTables();

        $this->tenant = Tenant::create(['name' => 'Test Tenant', 'domain' => 'test.test']);
        app(TenantContextService::class)->setTenant($this->tenant);

        $propertyRepo = new EloquentPropertyRepository(new Property());
        $this->propertyService = new PropertyCrudService($propertyRepo, new PropertyStateMachine());
        $this->listingService = new ListingCrudService();
        $this->lifecycle = app(YalihanLifecycle::class);

        YalihanLifecycle::$skipGuards = true;
    }

    protected function tearDown(): void
    {
        YalihanLifecycle::$skipGuards = false;
        app(TenantContextService::class)->clear();
        parent::tearDown();
    }

    protected function setupTestTables(): void
    {
        if (!Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('workspace_id')->nullable();
                $table->string('uuid')->unique();
                $table->string('idempotency_key', 64)->nullable()->unique();
                $table->string('tkgm_id')->nullable();
                $table->string('ada')->nullable();
                $table->string('parsel')->nullable();
                $table->unsignedInteger('il_id')->nullable();
                $table->unsignedInteger('ilce_id')->nullable();
                $table->unsignedInteger('mahalle_id')->nullable();
                $table->decimal('lat', 10, 8)->nullable();
                $table->decimal('lng', 11, 8)->nullable();
                $table->decimal('alan_m2', 10, 2)->nullable();
                $table->string('bina_yasi')->nullable();
                $table->unsignedInteger('kat_sayisi')->nullable();
                $table->unsignedInteger('bulundugu_kat')->nullable();
                $table->string('oda_sayisi')->nullable();
                $table->unsignedInteger('banyo_sayisi')->nullable();
                $table->string('aktiflik_durumu');
                $table->string('kapak_resmi')->nullable();
                $table->string('nitelik')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::dropIfExists('ilanlar');
        Schema::dropIfExists('listing_state_transitions');
        Schema::dropIfExists('property_workspaces');

        Schema::create('property_workspaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('ilan_id')->nullable();
            $table->string('workspace_uuid', 36)->unique();
            $table->string('state', 32)->default('draft');
            $table->string('intent', 64)->nullable();
            $table->string('template_id', 64)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('listing_state_transitions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('ilan_id');
            $t->string('from_state', 32);
            $t->string('to_state', 32);
            $t->unsignedBigInteger('aktan_id')->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index('ilan_id');
            $t->index('created_at');
        });

        Schema::create('ilanlar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('uuid')->unique();
            $table->string('baslik')->nullable();
            $table->text('aciklama')->nullable();
            $table->decimal('fiyat', 15, 2)->nullable();
            $table->string('para_birimi', 10)->default('TRY');
            $table->string('yayin_durumu')->default('taslak');
            $table->string('kanal', 32)->nullable();
            $table->unsignedInteger('il_id')->nullable();
            $table->unsignedInteger('ilce_id')->nullable();
            $table->unsignedInteger('mahalle_id')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->string('ada_no')->nullable();
            $table->string('parsel_no')->nullable();
            $table->string('slug')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->integer('completion_score')->nullable();
            $table->float('quality_score')->nullable();
            $table->unsignedBigInteger('yayin_tipi_id')->nullable();
            $table->string('ilan_tarihi')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REPLAY PRODUCES SAME FINAL STATE
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function replay_through_full_lifecycle_produces_same_final_state(): void
    {
        // Create listing and transition through full lifecycle
        $listing = $this->createListingInState('taslak');

        // Record original transitions
        $originalTransitionCount = $this->countTransitions($listing->id);

        // Transition through full lifecycle
        $this->transitionThroughFullLifecycle($listing);

        // Get final state
        $finalState = $this->getStateValue($listing->fresh());
        $this->assertEquals('yayinda', $finalState);

        // Count transitions after full lifecycle
        $transitionCountAfterLifecycle = $this->countTransitions($listing->id);

        // Create a new listing and replay the same transitions
        $replayListing = $this->createListingInState('taslak');

        // Replay transitions (simulate replay by calling each transition)
        $this->replayTransitions($replayListing, $listing->fresh());

        // Verify final state is the same
        $replayFinalState = $this->getStateValue($replayListing->fresh());
        $this->assertEquals($finalState, $replayFinalState, 'Replay should produce the same final state');

        // Verify both have the same number of transitions
        $replayTransitionCount = $this->countTransitions($replayListing->id);
        $this->assertEquals($transitionCountAfterLifecycle, $replayTransitionCount);
    }

    /** @test */
    public function replay_idempotent_behavior(): void
    {
        $listing = $this->createListingInState('taslak');

        // Transition through lifecycle
        $this->transitionThroughFullLifecycle($listing);
        $originalTransitionCount = $this->countTransitions($listing->id);

        // Attempt to transition to same state (idempotent behavior)
        $ilan = $listing->fresh();
        $currentState = $this->getStateValue($ilan);

        // Transition to same state should be handled by state machine
        // (state machine should reject invalid transitions)
        try {
            $this->lifecycle->transition($ilan, \App\Enums\IlanDurumu::from($currentState));
            // If no exception, state machine allowed it
        } catch (\DomainException $e) {
            // Expected - state machine rejects same → same
        }

        // Transition count should not increase for same state
        $transitionCountAfterIdempotent = $this->countTransitions($listing->id);
        $this->assertEquals($originalTransitionCount, $transitionCountAfterIdempotent,
            'Idempotent transition should not create new transition');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REPLAY CREATES NEW TRANSITIONS (NOT DUPLICATES)
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function replay_creates_new_transitions(): void
    {
        $listing = $this->createListingInState('taslak');
        $originalTransitionCount = $this->countTransitions($listing->id);

        // Transition through lifecycle
        $this->transitionThroughFullLifecycle($listing);

        // Count transitions after lifecycle
        $afterLifecycleCount = $this->countTransitions($listing->id);
        $newTransitions = $afterLifecycleCount - $originalTransitionCount;

        $this->assertGreaterThan(0, $newTransitions, 'Lifecycle should create new transitions');

        // Record original transition IDs
        $originalTransitionIds = ListingStateTransition::where('ilan_id', $listing->id)
            ->pluck('id')
            ->toArray();

        // Create replay listing
        $replayListing = $this->createListingInState('taslak');

        // Replay transitions
        $this->replayTransitions($replayListing, $listing->fresh());

        // Get replay transition IDs
        $replayTransitionIds = ListingStateTransition::where('ilan_id', $replayListing->id)
            ->pluck('id')
            ->toArray();

        // Verify no overlap (replay created NEW transitions)
        $overlap = array_intersect($originalTransitionIds, $replayTransitionIds);
        $this->assertEmpty($overlap, 'Replay should create new transitions, not reuse IDs');
    }

    /** @test */
    public function replay_transitions_have_different_records(): void
    {
        $listing = $this->createListingInState('taslak');

        // Transition through lifecycle
        $this->transitionThroughFullLifecycle($listing);

        // Get original transition
        $originalTransitions = ListingStateTransition::where('ilan_id', $listing->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Create replay listing
        $replayListing = $this->createListingInState('taslak');

        // Replay transitions
        $this->replayTransitions($replayListing, $listing->fresh());

        // Get replay transitions
        $replayTransitions = ListingStateTransition::where('ilan_id', $replayListing->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Verify replay has its own distinct records (different IDs)
        $originalIds = $originalTransitions->pluck('id')->toArray();
        $replayIds = $replayTransitions->pluck('id')->toArray();

        $this->assertEmpty(array_intersect($originalIds, $replayIds),
            'Replay should create new records with different IDs');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ORIGINAL AUDIT TRAIL PRESERVED (IMMUTABLE)
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function original_transitions_are_immutable(): void
    {
        $listing = $this->createListingInState('taslak');

        // Transition through lifecycle
        $this->transitionThroughFullLifecycle($listing);

        // Get original transition data
        $originalTransitions = ListingStateTransition::where('ilan_id', $listing->id)
            ->orderBy('created_at')
            ->get();

        $originalCount = $originalTransitions->count();
        $originalFirst = $originalTransitions->first()->toArray();

        // Attempt to modify (should be blocked)
        $firstTransition = $originalTransitions->first();

        try {
            $firstTransition->to_state = 'modified';
            $firstTransition->save();
            $this->fail('Should have thrown exception');
        } catch (\LogicException $e) {
            // Expected - immutable
        }

        // Verify original data unchanged
        $afterAttemptTransitions = ListingStateTransition::where('ilan_id', $listing->id)
            ->orderBy('created_at')
            ->get();

        $this->assertEquals($originalCount, $afterAttemptTransitions->count());
        $this->assertEquals($originalFirst['to_state'], $afterAttemptTransitions->first()->to_state);
    }

    /** @test */
    public function replay_does_not_modify_original_transition_chain(): void
    {
        $listing = $this->createListingInState('taslak');

        // Transition through lifecycle
        $this->transitionThroughFullLifecycle($listing);

        // Record original transition chain
        $originalChain = ListingStateTransition::where('ilan_id', $listing->id)
            ->orderBy('created_at')
            ->pluck('to_state')
            ->toArray();

        // Create replay listing
        $replayListing = $this->createListingInState('taslak');

        // Replay transitions
        $this->replayTransitions($replayListing, $listing->fresh());

        // Verify original chain unchanged
        $currentChain = ListingStateTransition::where('ilan_id', $listing->id)
            ->orderBy('created_at')
            ->pluck('to_state')
            ->toArray();

        $this->assertEquals($originalChain, $currentChain, 'Original chain should be unchanged after replay');

        // Verify replay listing has its own chain
        $replayChain = ListingStateTransition::where('ilan_id', $replayListing->id)
            ->orderBy('created_at')
            ->pluck('to_state')
            ->toArray();

        $this->assertEquals($originalChain, $replayChain, 'Replay should produce same sequence');
    }

    /** @test */
    public function replay_does_not_delete_original_transitions(): void
    {
        $listing = $this->createListingInState('taslak');

        // Transition through lifecycle
        $this->transitionThroughFullLifecycle($listing);

        $originalCount = $this->countTransitions($listing->id);

        // Create replay listing
        $replayListing = $this->createListingInState('taslak');

        // Replay transitions
        $this->replayTransitions($replayListing, $listing->fresh());

        // Verify original count unchanged
        $currentCount = $this->countTransitions($listing->id);
        $this->assertEquals($originalCount, $currentCount, 'Original transitions should not be deleted');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // REPLAY EVENT/AUDIT INTEGRITY
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function replay_transitions_have_replay_metadata(): void
    {
        $listing = $this->createListingInState('taslak');

        // Transition through lifecycle
        $this->transitionThroughFullLifecycle($listing);

        // Create replay listing
        $replayListing = $this->createListingInState('taslak');

        // Replay transitions
        $this->replayTransitions($replayListing, $listing->fresh());

        // Check that replay transitions have source metadata
        $replayTransitions = ListingStateTransition::where('ilan_id', $replayListing->id)
            ->orderBy('created_at')
            ->get();

        foreach ($replayTransitions as $transition) {
            $meta = $transition->meta;
            $this->assertIsArray($meta);
            $this->assertArrayHasKey('source', $meta);
        }
    }

    /** @test */
    public function replay_preserves_transition_sequence(): void
    {
        $listing = $this->createListingInState('taslak');

        // Transition through lifecycle
        $this->transitionThroughFullLifecycle($listing);

        // Record original sequence
        $originalSequence = ListingStateTransition::where('ilan_id', $listing->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($t) => $t->to_state)
            ->toArray();

        // Create replay listing
        $replayListing = $this->createListingInState('taslak');

        // Replay transitions
        $this->replayTransitions($replayListing, $listing->fresh());

        // Verify sequence preserved
        $replaySequence = ListingStateTransition::where('ilan_id', $replayListing->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($t) => $t->to_state)
            ->toArray();

        $this->assertEquals($originalSequence, $replaySequence, 'Transition sequence should be preserved');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CROSS-TENANT REPLAY BLOCKED
    // ═══════════════════════════════════════════════════════════════════════

    /** @test */
    public function replay_blocked_for_different_tenant(): void
    {
        // Create listing under Tenant A
        app(TenantContextService::class)->setTenant($this->tenant);
        $listingA = $this->createListingInState('taslak');
        $this->transitionThroughFullLifecycle($listingA);

        // Create Tenant B
        $tenantB = Tenant::create(['name' => 'Tenant B', 'domain' => 'tenant-b.test']);
        app(TenantContextService::class)->setTenant($tenantB);

        // Attempt to create replay (should be blocked by tenant isolation)
        $listingB = $this->createListingInState('taslak');

        // Try to replay Tenant A's transitions on Tenant B's listing
        // This should fail because tenant_id validation should block it
        try {
            // Simulate replay - this would need workspace/tenant context
            // The listing service should block cross-tenant access
            $this->listingService->publish($listingB);
            $this->fail('Cross-tenant replay should be blocked');
        } catch (\DomainException $e) {
            // Expected - cross-tenant blocked
            $this->assertStringContainsString('tenant', strtolower($e->getMessage()));
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════════════

    protected function createListingInState(string $state): Ilan
    {
        $property = $this->propertyService->create([
            'workspace_id' => 1,
            'il_id' => 48,
            'ilce_id' => 1,
            'mahalle_id' => 2,
            'lat' => '37.1042',
            'lng' => '27.2900',
            'ada' => '102',
            'parsel' => '4',
        ]);
        $this->propertyService->verify($property);
        $this->propertyService->activate($property);

        $listing = $this->listingService->createFromProperty($property, [
            'baslik' => "Test Listing",
            'kanal' => 'yalihan',
        ]);

        DB::table('ilanlar')->where('id', $listing->id)->update([
            'tenant_id' => $this->tenant->id,
        ]);

        // Set initial state if needed
        if ($state !== 'taslak') {
            $this->lifecycle->transition($listing, \App\Enums\IlanDurumu::from($state));
        }

        return $listing->fresh();
    }

    protected function transitionThroughFullLifecycle(Ilan $ilan): Ilan
    {
        // taslak → beklemede
        $ilan = $this->listingService->submitForReview($ilan);

        // beklemede → yayinda
        $ilan = $this->listingService->publish($ilan);

        return $ilan;
    }

    protected function replayTransitions(Ilan $replayListing, Ilan $sourceListing): void
    {
        // Get source transitions
        $sourceTransitions = ListingStateTransition::where('ilan_id', $sourceListing->id)
            ->orderBy('created_at')
            ->get();

        // Replay each transition
        foreach ($sourceTransitions as $transition) {
            $targetState = \App\Enums\IlanDurumu::from($transition->to_state);
            $this->lifecycle->transition($replayListing, $targetState, $transition->aktan_id, [
                'source' => 'replay',
                'original_ilan_id' => $sourceListing->id,
                'original_transition_id' => $transition->id,
            ]);
        }
    }

    protected function countTransitions(int $ilanId): int
    {
        return ListingStateTransition::where('ilan_id', $ilanId)->count();
    }

    protected function getStateValue(Ilan $ilan): string
    {
        return $ilan->yayin_durumu instanceof \App\Enums\IlanDurumu
            ? $ilan->yayin_durumu->value
            : (string) $ilan->yayin_durumu;
    }
}
