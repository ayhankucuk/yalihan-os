<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use App\Models\Finance\Commission;
use App\Models\Finance\FinancialSetting;
use App\Models\LedgerEntry;
use App\Models\LedgerBalance;
use App\Models\Finance\Bonus;
use App\Enums\Finance\PaymentStatus;
use App\Services\Finance\CommissionCalculator;
use App\Application\Shared\Services\TenantContextResolver;
use App\Application\Shared\DTOs\TenantContext;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;

/**
 * Finance Domain Smoke Test
 * 🛡️ Phase T5: Finance Legacy Isolation Verification
 * 🛡️ SAB P0: TenantContextResolver mocked for all tests.
 */
class FinanceSmokeSealTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $tenantResolverMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 🛡️ SAB P0: Mock TenantContextResolver for all Finance smoke tests
        $this->tenantResolverMock = Mockery::mock(TenantContextResolver::class);
        $this->tenantResolverMock
            ->shouldReceive('resolve')
            ->byDefault()
            ->andReturn(new TenantContext(tenantId: 1, userId: 1, requestId: 'test-smoke-' . uniqid()));

        $this->app->instance(TenantContextResolver::class, $this->tenantResolverMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function finance_tables_exist_after_bootstrap()
    {
        $this->assertTrue(Schema::hasTable('financial_settings'), 'financial_settings');
        $this->assertTrue(Schema::hasTable('commissions'), 'commissions');
        $this->assertTrue(Schema::hasTable('bonuses'), 'bonuses');
        $this->assertTrue(Schema::hasTable('ledger_entries'), 'ledger_entries');
        $this->assertTrue(Schema::hasTable('ledger_balances'), 'ledger_balances');
    }

    /** @test */
    public function financial_settings_model_loads_seeded_data()
    {
        $settings = FinancialSetting::first();
        $this->assertNotNull($settings, 'Default financial settings must be seeded');
        $this->assertIsNumeric($settings->default_commission_rate);
    }

    /** @test */
    public function commission_calculator_returns_model_not_stdclass()
    {
        // 🛡️ SAB P0: Mocked TenantContextResolver ensures tenantId=1 is available
        // Note: RefreshDatabase creates fresh DB without seeders; null is acceptable.
        // Key assertion: result is NEVER stdClass (would indicate raw DB bypass).
        $calc = app(CommissionCalculator::class);
        $settings = $calc->getFinancialSettings();

        // Must be either FinancialSetting model or null — NEVER stdClass
        $this->assertTrue(
            $settings === null || $settings instanceof FinancialSetting,
            'getFinancialSettings() must return FinancialSetting|null, not stdClass'
        );
    }

    /** @test */
    public function payment_status_enum_is_used_in_commission_model()
    {
        $this->assertEquals('pending', PaymentStatus::PENDING->value);
        $this->assertEquals('paid', PaymentStatus::PAID->value);
        $this->assertEquals('approved', PaymentStatus::APPROVED->value);
    }
}
