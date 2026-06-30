<?php

namespace Tests\Feature\AI;

use App\Services\AI\TenantQuotaService;
use App\Services\AI\TenantSettingsService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group skip-until-migration-complete
 * Ghost class: App\Services\AI\TenantQuotaService henüz implement edilmedi.
 */
class AiMultiTenancyTest extends TestCase
{

    /** @test */
    public function tenant_quota_service_initializes()
    {
        $quotaService = app(TenantQuotaService::class);

        $this->assertInstanceOf(TenantQuotaService::class, $quotaService);
    }

    /** @test */
    public function tenant_without_quota_has_unlimited_access()
    {
        $quotaService = app(TenantQuotaService::class);

        $result = $quotaService->checkQuota(999, 'vision');

        $this->assertTrue($result['allowed']);
        $this->assertEquals('No quota configured', $result['reason']);
    }

    /** @test */
    public function tenant_quota_blocks_when_exceeded()
    {
        // Create quota
        DB::table('ai_tenant_quotas')->insert([
            'tenant_id' => 1,
            'monthly_budget_usd' => 10.00,
            'max_calls_per_month' => 100,
            'current_month_spend' => 15.00, // Exceeded
            'current_month_calls' => 50,
            'overflow_policy' => 'block',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaService = app(TenantQuotaService::class);
        $result = $quotaService->checkQuota(1, 'vision');

        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('blocked', $result['reason']);
    }

    /** @test */
    public function tenant_quota_allows_with_overage_policy()
    {
        // Create quota with allow policy
        DB::table('ai_tenant_quotas')->insert([
            'tenant_id' => 1,
            'monthly_budget_usd' => 10.00,
            'max_calls_per_month' => 100,
            'current_month_spend' => 15.00, // Exceeded
            'current_month_calls' => 50,
            'overflow_policy' => 'allow',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaService = app(TenantQuotaService::class);
        $result = $quotaService->checkQuota(1, 'vision');

        $this->assertTrue($result['allowed']);
        $this->assertStringContainsString('overage allowed', $result['reason']);
    }

    /** @test */
    public function tenant_settings_service_initializes()
    {
        $settingsService = app(TenantSettingsService::class);

        $this->assertInstanceOf(TenantSettingsService::class, $settingsService);
    }

    /** @test */
    public function tenant_without_settings_has_defaults_enabled()
    {
        $settingsService = app(TenantSettingsService::class);

        $enabled = $settingsService->isFeatureEnabled(999, 'vision');

        $this->assertTrue($enabled);
    }

    /** @test */
    public function tenant_settings_can_disable_features()
    {
        // Create settings with vision disabled
        DB::table('ai_tenant_settings')->insert([
            'tenant_id' => 1,
            'vision_enabled' => false,
            'title_generation_enabled' => true,
            'description_generation_enabled' => true,
            'auto_apply_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settingsService = app(TenantSettingsService::class);

        $this->assertFalse($settingsService->isFeatureEnabled(1, 'vision'));
        $this->assertTrue($settingsService->isFeatureEnabled(1, 'title_generation'));
    }

    /** @test */
    public function tenant_settings_returns_custom_thresholds()
    {
        // Create settings with custom threshold
        DB::table('ai_tenant_settings')->insert([
            'tenant_id' => 1,
            'vision_enabled' => true,
            'confidence_threshold_vision' => 0.85,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settingsService = app(TenantSettingsService::class);
        $threshold = $settingsService->getConfidenceThreshold(1, 'vision');

        $this->assertEquals(0.85, $threshold);
    }
}
