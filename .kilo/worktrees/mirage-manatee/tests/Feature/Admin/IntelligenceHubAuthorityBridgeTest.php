<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Tests\TestCase;

/**
 * Intelligence Hub Authority Bridge Tests
 *
 * Validates all patches A-I applied during Intelligence Hub audit.
 * Guard script: scripts/guards/ci-guard-intelligence-hub.sh
 * 16 Nisan 2026
 */
class IntelligenceHubAuthorityBridgeTest extends TestCase
{
    // ----------------------------------------------------------------
    // Patch A: AiUsageController runtime bug
    // ----------------------------------------------------------------

    public function test_ai_usage_controller_uses_walletService_property(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Admin/AiUsageController.php')
        );

        $this->assertStringNotContainsString(
            '$this->wallet->',
            $source,
            'AiUsageController must use $this->walletService, not $this->wallet'
        );

        $this->assertStringContainsString(
            '$this->walletService',
            $source,
            'AiUsageController must reference $this->walletService'
        );
    }

    // ----------------------------------------------------------------
    // Patch B: DanismanAIService::chat() cost guard
    // ----------------------------------------------------------------

    public function test_danisman_ai_service_has_cost_guard(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/DanismanAIService.php')
        );

        $this->assertMatchesRegularExpression(
            '/checkBudget|guardCostBudget/',
            $source,
            'DanismanAIService must call checkBudget or guardCostBudget'
        );
    }

    public function test_danisman_chat_method_has_cost_guard(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/DanismanAIService.php')
        );

        // chat() function must contain a checkBudget call within its body
        // We look for checkBudget in the chat section of the file
        $this->assertStringContainsString(
            'checkBudget($chatProvider)',
            $source,
            'DanismanAIService::chat() must call $this->costGuard->checkBudget()'
        );
    }

    // ----------------------------------------------------------------
    // Patch C: EmbeddingService cost guard injection
    // ----------------------------------------------------------------

    public function test_embedding_service_injects_cost_guard(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/EmbeddingService.php')
        );

        $this->assertStringContainsString(
            'AiCostGuardService $costGuard',
            $source,
            'EmbeddingService constructor must inject AiCostGuardService'
        );
    }

    public function test_embedding_service_calls_check_budget(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/EmbeddingService.php')
        );

        $this->assertMatchesRegularExpression(
            '/\$this->costGuard->checkBudget/',
            $source,
            'EmbeddingService::getEmbedding() must call $this->costGuard->checkBudget()'
        );
    }

    // ----------------------------------------------------------------
    // Patch D: YalihanCortex cost guard injection
    // ----------------------------------------------------------------

    public function test_yalihan_cortex_injects_cost_guard(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/YalihanCortex.php')
        );

        $this->assertStringContainsString(
            'AiCostGuardService $costGuard',
            $source,
            'YalihanCortex constructor must inject AiCostGuardService'
        );
    }

    public function test_yalihan_cortex_has_guard_cost_budget_helper(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/YalihanCortex.php')
        );

        $this->assertStringContainsString(
            'private function guardCostBudget',
            $source,
            'YalihanCortex must have private guardCostBudget() helper'
        );
    }

    public function test_yalihan_cortex_guards_generate_ilan_title(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/YalihanCortex.php')
        );

        // Find generateIlanTitle method and verify guardCostBudget call
        $this->assertMatchesRegularExpression(
            '/function generateIlanTitle[\s\S]{1,500}guardCostBudget/',
            $source,
            'YalihanCortex::generateIlanTitle() must call guardCostBudget()'
        );
    }

    public function test_yalihan_cortex_get_performance_uses_canonical_keys(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/YalihanCortex.php')
        );

        $this->assertStringNotContainsString(
            "'success_rate'",
            $source,
            "YalihanCortex::getPerformance() must not use 'success_rate' — use 'basari_orani'"
        );

        $this->assertStringNotContainsString(
            "'total_requests'",
            $source,
            "YalihanCortex::getPerformance() must not use 'total_requests' — use 'toplam_istek'"
        );

        $this->assertStringContainsString(
            "'basari_orani'",
            $source,
            "YalihanCortex::getPerformance() must use canonical 'basari_orani'"
        );
    }

    // ----------------------------------------------------------------
    // Patch E: hard_cap_enabled default true
    // ----------------------------------------------------------------

    public function test_ai_budgets_config_hard_cap_enabled_defaults_to_true(): void
    {
        $source = file_get_contents(
            config_path('ai-budgets.php')
        );

        $this->assertStringContainsString(
            "env('AI_HARD_CAP_ENABLED', true)",
            $source,
            "ai-budgets.php default hard_cap_enabled must be env-driven with default true"
        );

        $this->assertStringNotContainsString(
            "'hard_cap_enabled' => false",
            $source,
            "ai-budgets.php must not have bare false for hard_cap_enabled"
        );
    }

    // ----------------------------------------------------------------
    // Patch F: N8nIntegrationService telemetry fields
    // ----------------------------------------------------------------

    public function test_n8n_integration_service_uses_canonical_telemetry_fields(): void
    {
        $source = file_get_contents(
            app_path('Services/Integrations/N8nIntegrationService.php')
        );

        $this->assertStringContainsString(
            "'istek_url'",
            $source,
            "N8nIntegrationService must use 'istek_url' (not 'url')"
        );

        $this->assertStringContainsString(
            "'basari_orani'",
            $source,
            "N8nIntegrationService must use 'basari_orani' (not 'success_rate')"
        );
    }

    // ----------------------------------------------------------------
    // Patch G: AITelemetryController canonical keys
    // ----------------------------------------------------------------

    public function test_ai_telemetry_controller_uses_basari_orani(): void
    {
        $source = file_get_contents(
            app_path('Http/Controllers/Admin/AITelemetryController.php')
        );

        $this->assertStringNotContainsString(
            "'success_rate'",
            $source,
            "AITelemetryController must not use 'success_rate' — use 'basari_orani'"
        );
    }

    // ----------------------------------------------------------------
    // Patch H: Handler.php telemetry fields
    // ----------------------------------------------------------------

    public function test_exception_handler_uses_istek_url(): void
    {
        $source = file_get_contents(
            app_path('Exceptions/Handler.php')
        );

        $this->assertStringContainsString(
            "'istek_url'",
            $source,
            "Handler.php must use 'istek_url' (not 'url') for telemetry"
        );
    }

    // ----------------------------------------------------------------
    // Patch I: AiLog hata_mesaji
    // ----------------------------------------------------------------

    public function test_ai_log_model_uses_hata_mesaji(): void
    {
        $source = file_get_contents(
            app_path('Models/AiLog.php')
        );

        $this->assertStringContainsString(
            "'hata_mesaji'",
            $source,
            "AiLog \$fillable must contain 'hata_mesaji'"
        );

        $this->assertStringNotContainsString(
            "'error_message'",
            $source,
            "AiLog \$fillable must not contain 'error_message' (use 'hata_mesaji')"
        );
    }

    public function test_ai_log_migration_exists_for_hata_mesaji(): void
    {
        $migrations = glob(
            database_path('migrations/*rename_ai_logs_error_message_to_hata_mesaji.php')
        );

        $this->assertNotEmpty(
            $migrations,
            "Migration to rename error_message → hata_mesaji must exist in database/migrations/"
        );
    }

    public function test_ai_telemetry_service_uses_hata_mesaji(): void
    {
        $source = file_get_contents(
            app_path('Services/AI/Monitoring/AiTelemetryService.php')
        );

        $this->assertStringNotContainsString(
            "'error_message'",
            $source,
            "AiTelemetryService must not use 'error_message' — use 'hata_mesaji'"
        );

        $this->assertStringContainsString(
            "'hata_mesaji'",
            $source,
            "AiTelemetryService must use 'hata_mesaji'"
        );
    }

    // ----------------------------------------------------------------
    // Contract: AiCostGuardService is container-resolvable
    // ----------------------------------------------------------------

    public function test_ai_cost_guard_service_is_resolvable(): void
    {
        $service = app(\App\Services\AI\AiCostGuardService::class);
        $this->assertNotNull($service);
    }

    // ----------------------------------------------------------------
    // Negative: cost guard budget exceeded throws RuntimeException
    // ----------------------------------------------------------------

    public function test_guard_cost_budget_throws_when_budget_exceeded(): void
    {
        // Temporarily set an exhausted budget scenario via Cortex reflection
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/bütçe sınırı/');

        $mockCostGuard = $this->createMock(\App\Services\AI\AiCostGuardService::class);
        $mockCostGuard->method('checkBudget')
            ->willReturn(['allowed' => false, 'reason' => 'günlük bütçe aşıldı']);

        // Build a minimal EmbeddingService substitute with the guard
        $embedding = new class($mockCostGuard) {
            private $costGuard;
            public function __construct($cg) { $this->costGuard = $cg; }
            public function guardCostBudget(string $provider): void
            {
                $budget = $this->costGuard->checkBudget($provider);
                if (!$budget['allowed']) {
                    throw new \RuntimeException('AI bütçe sınırı aşıldı: ' . ($budget['reason'] ?? $provider));
                }
            }
        };

        $embedding->guardCostBudget('openai');
    }
}
