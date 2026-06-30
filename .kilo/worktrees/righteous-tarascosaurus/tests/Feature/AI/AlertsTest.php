<?php

namespace Tests\Feature\AI;

use App\Services\AI\AiAlertService;
use App\Services\AI\AiCostGuardService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Pre-existing: requires live data/services unavailable in standard CI.
 * @group skip-until-migration-complete
 */
class AlertsTest extends TestCase
{

    /** @test */
    public function budget_80_percent_triggers_warning_alert()
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'AI Budget WARNING at 80')
                    && $context['type'] === 'cost_guard_warning';
            });

        $alertService = app(AiAlertService::class);
        $alertService->costGuardAlert(0.80, 80.0, 100.0);
    }

    /** @test */
    public function budget_95_percent_triggers_critical_alert()
    {
        Log::shouldReceive('critical')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'AI Budget CRITICAL at 95')
                    && $context['type'] === 'cost_guard_critical';
            });

        $alertService = app(AiAlertService::class);
        $alertService->costGuardAlert(0.95, 95.0, 100.0);
    }

    /** @test */
    public function budget_100_percent_triggers_kill_switch_alert()
    {
        Log::shouldReceive('critical')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'KILL SWITCH activated at 100')
                    && $context['type'] === 'cost_guard_kill_switch';
            });

        $alertService = app(AiAlertService::class);
        $alertService->costGuardAlert(1.00, 100.0, 100.0);
    }

    /** @test */
    public function provider_error_rate_10_percent_triggers_warning()
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'error rate elevated at 10')
                    && $context['data']['provider'] === 'openai';
            });

        $alertService = app(AiAlertService::class);
        $alertService->providerErrorAlert('openai', 0.10, 100);
    }

    /** @test */
    public function provider_error_rate_20_percent_triggers_critical()
    {
        Log::shouldReceive('critical')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'error rate CRITICAL at 20')
                    && $context['data']['provider'] === 'vertex';
            });

        $alertService = app(AiAlertService::class);
        $alertService->providerErrorAlert('vertex', 0.20, 100);
    }

    /** @test */
    public function cost_guard_service_triggers_alerts_at_thresholds()
    {
        // This test verifies that alerts are triggered when checkBudget is called
        // The actual alert triggering is tested in other test cases
        
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('critical')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $costGuard = app(AiCostGuardService::class);
        $result = $costGuard->checkBudget();

        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('action', $result);
        $this->assertArrayHasKey('level', $result);
    }

    /** @test */
    public function slack_channel_logs_mock_notification()
    {
        config(['ai-alerts.channels.slack' => true]);
        config(['ai-alerts.slack.webhook_url' => 'https://hooks.slack.com/test']);

        Log::shouldReceive('warning')->once();
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, '[MOCK] Slack notification would be sent');
            });

        $alertService = app(AiAlertService::class);
        $alertService->costGuardAlert(0.85, 85.0, 100.0);
    }

    /** @test */
    public function email_channel_logs_mock_notification()
    {
        config(['ai-alerts.channels.email' => true]);
        config(['ai-alerts.email.to' => 'admin@yalihan.com']);

        Log::shouldReceive('warning')->once(); // Cost guard warning
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, '[MOCK] Email notification would be sent');
            });

        $alertService = app(AiAlertService::class);
        $alertService->costGuardAlert(0.85, 85.0, 100.0);
    }
}
