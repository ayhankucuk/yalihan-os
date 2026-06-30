<?php

namespace Tests\Feature\Rental;

use App\Models\CountryFinancialRule;
use App\Models\FinancialTransaction;
use App\Models\Ilan;
use App\Models\PropertyGrowthProjection;
use App\Models\User;
use App\Services\InvestorAnalyticsService;
use App\Services\InvestorDashboardService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Investor Intelligence — Test Matrix
 *
 * 1. ROI doğru hesaplandı mı?
 * 2. Net yield brüt yield’den küçük mü?
 * 3. Growth simülasyonu matematiksel doğru mu?
 * 4. Country comparison oranları doğru mu?
 * 5. Dashboard hesapları cache’li mi?
 */
class InvestorIntelligenceTest extends TestCase
{

    protected InvestorAnalyticsService $investorService;
    protected InvestorDashboardService $dashboardService;
    protected Ilan $ilan;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->investorService  = app(InvestorAnalyticsService::class);
        $this->dashboardService = app(InvestorDashboardService::class);

        // Seed country rules (required for services)
        CountryFinancialRule::create([
            'country_code'           => 'TR',
            'country_name'           => 'Türkiye',
            'rental_commission_rate' => 0.08,
            'sales_commission_rate'  => 0.03,
            'advisory_fee_rate'      => 0.00,
            'tax_rate'               => 0.00,
            'aktiflik_durumu'        => true,
        ]);
        CountryFinancialRule::create([
            'country_code'           => 'GR',
            'country_name'           => 'Yunanistan',
            'rental_commission_rate' => 0.05,
            'sales_commission_rate'  => 0.03,
            'advisory_fee_rate'      => 0.02,
            'tax_rate'               => 0.00,
            'aktiflik_durumu'        => true,
        ]);
        CountryFinancialRule::create([
            'country_code'           => 'UK',
            'country_name'           => 'İngiltere',
            'rental_commission_rate' => 0.06,
            'sales_commission_rate'  => 0.025,
            'advisory_fee_rate'      => 0.00,
            'tax_rate'               => 0.00,
            'aktiflik_durumu'        => true,
        ]);

        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'user_id'                   => $this->user->id,
            'purchase_price'            => 1000000, // 1M TRY
            'operating_expenses_annual' => 10000,   // 10K TRY
            'fiyat'                     => 5000,    // 5K gecelik fiyat (estimate için)
            'rental_enabled'            => true,
            'country_code'              => 'TR',
        ]);
    }

    /**
     * Test 1: ROI calculation (Actuals vs Estimates)
     */
    public function test_1_roi_calculation_is_accurate(): void
    {
        // 1. Estimate ROI (no transactions yet)
        // 5000 * 0.5 * 30 * 12 = 900,000 estimate annual rent
        // Net = 900,000 - 10,000 (exp) = 890,000
        // ROI = 890,000 / 1,000,000 = 89%
        $roiData = $this->investorService->calculateROI($this->ilan->id);

        $this->assertEquals(89.0, $roiData['roi']);

        // 2. Clear cache and add actual transactions
        Cache::flush();
        FinancialTransaction::create([
            'property_id'    => $this->ilan->id,
            'base_amount'    => 100000, // 100K actual rent
            'islem_tipi'     => 'kira',
            'islem_durumu'   => 'settled',
            'created_at'     => now()->subMonths(2),
        ]);

        // Net = 100,000 - 10,000 = 90,000
        // ROI = 90,000 / 1,000,000 = 9%
        $roiData = $this->investorService->calculateROI($this->ilan->id);
        $this->assertEquals(9.0, $roiData['roi']);
    }

    /**
     * Test 2: Net yield must be less than gross yield
     */
    public function test_2_net_yield_is_less_than_gross(): void
    {
        $yields = $this->investorService->calculateYield($this->ilan->id);

        $this->assertGreaterThan($yields['net_yield'], $yields['gross_yield']);
        $this->assertEquals(90.0, $yields['gross_yield']); // 900K / 1M
        $this->assertEquals(89.0, $yields['net_yield']);   // 890K / 1M
    }

    /**
     * Test 3: Growth simulation math
     */
    public function test_3_growth_simulation_math(): void
    {
        // 1M * (1 + 0.10)^2 = 1,210,000
        PropertyGrowthProjection::create([
            'property_id'        => $this->ilan->id,
            'yearly_growth_rate' => 0.10,
            'projection_years'   => 2,
        ]);

        $simulation = $this->investorService->simulateCapitalGain($this->ilan->id);

        $this->assertEquals(1210000.0, $simulation['future_value']);
        $this->assertEquals(210000.0, $simulation['total_gain']);
    }

    /**
     * Test 4: Dashboard aggregation and caching
     */
    public function test_4_dashboard_aggregation_and_cache(): void
    {
        $dashboard = $this->dashboardService->getDashboardKPIs();

        $this->assertEquals(1000000.0, $dashboard['total_portfolio_value']);
        $this->assertArrayHasKey('average_roi', $dashboard);
        $this->assertArrayHasKey('country_comparison', $dashboard);

        // Test Cache
        Cache::shouldReceive('remember')
            ->once()
            ->with('investor_dashboard_kpis', 600, \Mockery::type('Closure'))
            ->andReturn($dashboard);

        $this->dashboardService->getDashboardKPIs();
    }

    /**
     * Test 5: Cash flow projection structure
     */
    public function test_5_cash_flow_projection_structure(): void
    {
        $projection = $this->investorService->getCashFlowProjection($this->ilan->id);

        $this->assertCount(12, $projection);
        $this->assertEquals(1, $projection[0]['month']);
        $this->assertArrayHasKey('income', $projection[0]);
        $this->assertArrayHasKey('expense', $projection[0]);
    }
}
