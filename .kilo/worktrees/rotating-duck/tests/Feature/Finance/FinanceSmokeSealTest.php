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
use Illuminate\Support\Facades\Schema;

/**
 * Finance Domain Smoke Test
 * 🛡️ Phase T5: Finance Legacy Isolation Verification
 */
class FinanceSmokeSealTest extends TestCase
{
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
    public function commission_calculator_uses_model_not_raw_db()
    {
        $calc = app(CommissionCalculator::class);
        $settings = $calc->getFinancialSettings();

        // Must return a FinancialSetting model, not stdClass
        $this->assertInstanceOf(FinancialSetting::class, $settings);
    }

    /** @test */
    public function payment_status_enum_is_used_in_commission_model()
    {
        $this->assertEquals('pending', PaymentStatus::PENDING->value);
        $this->assertEquals('paid', PaymentStatus::PAID->value);
        $this->assertEquals('approved', PaymentStatus::APPROVED->value);
    }
}
