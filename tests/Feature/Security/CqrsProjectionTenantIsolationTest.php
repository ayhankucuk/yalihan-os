<?php

namespace Tests\Feature\Security;

use App\Models\Projections\BuyerIntentProjection;
use App\Models\Projections\BuyerInterestProjection;
use App\Models\Projections\ListingSearchProjection;
use App\Models\Projections\ListingVelocityProjection;
use App\Models\Projections\MarketTrendProjection;
use App\Models\Projections\TalepMatchProjection;
use App\Models\SaaS\Tenant;
use App\Services\SaaS\TenantContextService;
use Tests\TestCase;

class CqrsProjectionTenantIsolationTest extends TestCase
{
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected TenantContextService $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::firstOrCreate(
            ['domain' => 'tenant-a.local'],
            ['name' => 'Tenant A CQRS']
        );

        $this->tenantB = Tenant::firstOrCreate(
            ['domain' => 'tenant-b.local'],
            ['name' => 'Tenant B CQRS']
        );

        $this->tenantContext = app(TenantContextService::class);
    }

    /** @test */
    public function listing_search_projection_respects_tenant_isolation(): void
    {
        // 1. Create projection under Tenant A
        $this->tenantContext->setTenant($this->tenantA);
        $recordA = ListingSearchProjection::create([
            'listing_id' => 9001,
            'title' => 'Tenant A Luxury Villa',
            'city' => 'Mugla',
            'district' => 'Bodrum',
        ]);

        $this->assertEquals($this->tenantA->id, $recordA->tenant_id);
        $this->assertCount(1, ListingSearchProjection::where('listing_id', 9001)->get());

        // 2. Switch to Tenant B -> should see 0 records
        $this->tenantContext->setTenant($this->tenantB);
        $this->assertCount(0, ListingSearchProjection::where('listing_id', 9001)->get());

        // 3. System bypass query via scopeWithoutTenant
        $this->assertCount(1, ListingSearchProjection::withoutTenant()->where('listing_id', 9001)->get());
    }

    /** @test */
    public function listing_velocity_projection_respects_tenant_isolation(): void
    {
        $this->tenantContext->setTenant($this->tenantA);
        $recordA = ListingVelocityProjection::create([
            'listing_id' => 9002,
            'view_count' => 10,
            'activity_score' => 75,
        ]);

        $this->assertEquals($this->tenantA->id, $recordA->tenant_id);
        $this->assertCount(1, ListingVelocityProjection::where('listing_id', 9002)->get());

        $this->tenantContext->setTenant($this->tenantB);
        $this->assertCount(0, ListingVelocityProjection::where('listing_id', 9002)->get());
    }

    /** @test */
    public function market_trend_projection_respects_tenant_isolation(): void
    {
        $this->tenantContext->setTenant($this->tenantA);
        $recordA = MarketTrendProjection::create([
            'city' => 'Mugla',
            'district' => 'Bodrum',
            'demand_index' => 88,
        ]);

        $this->assertEquals($this->tenantA->id, $recordA->tenant_id);
        $this->assertCount(1, MarketTrendProjection::where('id', $recordA->id)->get());

        $this->tenantContext->setTenant($this->tenantB);
        $this->assertCount(0, MarketTrendProjection::where('id', $recordA->id)->get());
    }

    /** @test */
    public function buyer_interest_projection_respects_tenant_isolation(): void
    {
        $this->tenantContext->setTenant($this->tenantA);
        $recordA = BuyerInterestProjection::create([
            'listing_id' => 9003,
            'candidate_count' => 5,
            'avg_match_score' => 82,
        ]);

        $this->assertEquals($this->tenantA->id, $recordA->tenant_id);
        $this->assertCount(1, BuyerInterestProjection::where('listing_id', 9003)->get());

        $this->tenantContext->setTenant($this->tenantB);
        $this->assertCount(0, BuyerInterestProjection::where('listing_id', 9003)->get());
    }

    /** @test */
    public function talep_match_projection_respects_tenant_isolation(): void
    {
        $this->tenantContext->setTenant($this->tenantA);
        $recordA = TalepMatchProjection::create([
            'talep_id' => 8001,
            'buyer_id' => 7001,
            'city' => 'Mugla',
            'district' => 'Yalikavak',
        ]);

        $this->assertEquals($this->tenantA->id, $recordA->tenant_id);
        $this->assertCount(1, TalepMatchProjection::where('talep_id', 8001)->get());

        $this->tenantContext->setTenant($this->tenantB);
        $this->assertCount(0, TalepMatchProjection::where('talep_id', 8001)->get());
    }

    /** @test */
    public function buyer_intent_projection_respects_tenant_isolation(): void
    {
        $this->tenantContext->setTenant($this->tenantA);
        $recordA = BuyerIntentProjection::create([
            'buyer_id' => 7002,
            'preferred_city' => 'Mugla',
            'urgency_level' => 3,
        ]);

        $this->assertEquals($this->tenantA->id, $recordA->tenant_id);
        $this->assertCount(1, BuyerIntentProjection::where('buyer_id', 7002)->get());

        $this->tenantContext->setTenant($this->tenantB);
        $this->assertCount(0, BuyerIntentProjection::where('buyer_id', 7002)->get());
    }
}
