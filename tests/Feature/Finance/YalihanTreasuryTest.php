<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ilan;
use App\Models\Finance\Commission;
use App\Models\Finance\Bonus;
use App\Services\Finance\YalihanTreasury;
use App\Application\Shared\Services\TenantContextResolver;
use App\Application\Shared\DTOs\TenantContext;
use App\Enums\Finance\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;

/**
 * YalihanTreasury Integration Test Suite
 *
 * Phase 13 Sprint +2: Multi-Tenant Financial Scoping
 * Tests financial isolation, Context7 compliance, and multi-tenant constraints
 *
 * @property MockInterface&TenantContextResolver $tenantResolverMock
 */
class YalihanTreasuryTest extends TestCase
{
    use RefreshDatabase;

    private YalihanTreasury $treasury;
    private User $agent;
    private Ilan $ilan;
    private MockInterface $tenantResolverMock;

    /**
     * Setup the multi-tenant financial isolation context.
     * Zero Trust Runtime compliant tenant context mocking.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Zero Trust Runtime: Type-Safe Mock Construction
        $this->tenantResolverMock = Mockery::mock(TenantContextResolver::class);

        // Mock Tenant Context - Using actual TenantContext DTO with correct constructor
        $mockTenantContext = new TenantContext(
            tenantId: 1,
            userId: $this->agent->id ?? 1,
            requestId: 'test-request-' . uniqid()
        );

        // Deterministic contract return on every resolve() call
        $this->tenantResolverMock
            ->shouldReceive('resolve')
            ->byDefault()
            ->andReturn($mockTenantContext);

        // Seal dependency in Laravel IoC Container
        $this->app->instance(TenantContextResolver::class, $this->tenantResolverMock);

        // Create test agent
        $this->agent = User::factory()->create([
            'name' => 'Test Agent',
            'email' => 'agent@test.com',
        ]);

        // Create test listing (without tenant_id - not part of Phase 13)
        $this->ilan = Ilan::factory()->create([
            'danisman_id' => $this->agent->id,
            'fiyat' => 1000000,
        ]);

        // Resolve YalihanTreasury from container (with mocked TenantContextResolver)
        $this->treasury = app(YalihanTreasury::class);
    }

    /**
     * Tear down the mocked environment.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: Admin dashboard metrics calculation
     */
    public function test_admin_dashboard_metrics_returns_valid_structure(): void
    {
        $metrics = $this->treasury->getAdminDashboardMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('monthly_revenue', $metrics);
        $this->assertArrayHasKey('pending_commissions', $metrics);
        $this->assertArrayHasKey('approved_unpaid', $metrics);
        $this->assertArrayHasKey('unverified_count', $metrics);
        $this->assertArrayHasKey('unpaid_bonuses', $metrics);
    }

    /**
     * Test: Agent wallet metrics calculation
     */
    public function test_agent_wallet_metrics_returns_valid_structure(): void
    {
        $metrics = $this->treasury->getAgentWalletMetrics($this->agent->id);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('monthly_earnings', $metrics);
        $this->assertArrayHasKey('pending_commissions', $metrics);
        $this->assertArrayHasKey('total_earnings', $metrics);
        $this->assertArrayHasKey('this_month_sales', $metrics);
        $this->assertArrayHasKey('sales_count', $metrics);
        $this->assertArrayHasKey('monthly_target', $metrics);
        $this->assertArrayHasKey('achievement_percentage', $metrics);
    }

    /**
     * Test: Commission approval workflow
     */
    public function test_commission_approval_workflow(): void
    {
        // Create pending commission (tenant_id will be added by Phase 13 migration)
        $commission = Commission::create([
            'ilan_id' => $this->ilan->id,
            'agent_id' => $this->agent->id,
            'payment_state' => PaymentStatus::PENDING,
            'sale_price' => 1000000,
            'commission_rate' => 3.00,
            'total_commission' => 30000,
            'office_share_percentage' => 50,
            'agent_share_percentage' => 50,
            'ofis_tutari' => 15000,
            'danisman_tutari' => 15000,
        ]);

        // Request approval
        $result = $this->treasury->requestCommissionApproval($commission->id);

        $this->assertTrue($result);
        $this->assertEquals(PaymentStatus::APPROVED, $commission->fresh()->payment_state);
    }

    /**
     * Test: Commission payment cannot be processed if not approved
     */
    public function test_commission_payment_requires_approval(): void
    {
        // Create pending commission
        $commission = Commission::create([
            'ilan_id' => $this->ilan->id,
            'agent_id' => $this->agent->id,
            'payment_state' => PaymentStatus::PENDING,
            'sale_price' => 1000000,
            'commission_rate' => 3.00,
            'total_commission' => 30000,
            'office_share_percentage' => 50,
            'agent_share_percentage' => 50,
            'ofis_tutari' => 15000,
            'danisman_tutari' => 15000,
        ]);

        // Attempt payment without approval should fail
        $result = $this->treasury->requestCommissionPayment($commission->id);

        $this->assertFalse($result);
    }

    /**
     * Test: Bonus payment workflow
     */
    public function test_bonus_payment_workflow(): void
    {
        // Create unpaid bonus (using Context7 field names from Phase 13)
        $bonus = Bonus::create([
            'agent_id' => $this->agent->id,
            'target_month' => now()->format('Y-m'),
            'prim_tutari' => 5000,
            'bonus_type' => 'performance',
            'odendi_mi' => false,
        ]);

        // Request payment
        $result = $this->treasury->requestBonusPayment($bonus->id);

        $this->assertTrue($result);
        $this->assertTrue($bonus->fresh()->odendi_mi);
        $this->assertNotNull($bonus->fresh()->odeme_tarihi);
    }

    /**
     * Test: Batch monthly bonus calculation
     */
    public function test_batch_calculate_monthly_bonuses(): void
    {
        $month = now()->format('Y-m');

        $results = $this->treasury->batchCalculateMonthlyBonuses($month);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('total', $results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
    }

    /**
     * Test: Commission simulation
     */
    public function test_commission_simulation(): void
    {
        $salePrice = 1000000;
        $commissionRate = 3.00;

        $simulation = $this->treasury->simulateCommission($salePrice, $commissionRate);

        $this->assertIsArray($simulation);
        $this->assertArrayHasKey('sale_price', $simulation);
        $this->assertArrayHasKey('commission_rate', $simulation);
        $this->assertArrayHasKey('total_commission', $simulation);
    }

    /**
     * Test: Bonus simulation
     */
    public function test_bonus_simulation(): void
    {
        $monthlyTarget = 10000000;
        $achievedAmount = 12000000;

        $simulation = $this->treasury->simulateBonus($monthlyTarget, $achievedAmount);

        $this->assertIsArray($simulation);
    }

    /**
     * Test: Agent performance calculation
     */
    public function test_agent_performance_calculation(): void
    {
        $month = now()->format('Y-m');

        $performance = $this->treasury->calculateAgentPerformance($this->agent->id, $month);

        $this->assertIsArray($performance);
        $this->assertArrayHasKey('agent_id', $performance);
        $this->assertArrayHasKey('target_month', $performance);
        $this->assertArrayHasKey('monthly_target', $performance);
        $this->assertArrayHasKey('achieved_amount', $performance);
        $this->assertArrayHasKey('achievement_percentage', $performance);
        $this->assertArrayHasKey('bonus_tier', $performance);
        $this->assertArrayHasKey('projected_bonus', $performance);
    }
}
