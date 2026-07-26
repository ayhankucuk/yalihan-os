<?php

namespace Tests\Feature\AI;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\DTOs\CortexUsage;
use App\Domain\AI\Enums\AITaskType;
use App\Models\User;
use App\Models\SaaS\Tenant;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\Providers\DeepSeekCortexProvider;
use App\Services\AI\Providers\OpenAICortexProvider;
use App\Services\AI\Monetization\AiBudgetGuard;
use App\Contracts\Resilience\CircuitBreakerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SimpleTestCase;
use Mockery;

class AIResilienceTest extends SimpleTestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant and authenticated user for tenant context
        $this->tenant = Tenant::create(['name' => 'Test Tenant', 'status' => 'active']);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->actingAs($this->user);
    }

    /**
     * 🛡️ PROOF 1: AI_MODEL_MISMATCH must result in HARD FAIL (No Fallback)
     */
    public function test_model_mismatch_results_in_hard_fail_no_fallback()
    {
        // Mock AiBudgetGuard to allow execution
        $budgetGuardMock = Mockery::mock(AiBudgetGuard::class);
        $budgetGuardMock->shouldReceive('canExecute')->andReturn(true);
        $budgetGuardMock->shouldReceive('deductCredits')->andReturn(null);

        $deepSeekMock = Mockery::mock(DeepSeekCortexProvider::class);
        $deepSeekMock->shouldReceive('execute')->andReturn(new CortexResponseData(
            success: false,
            errorMessage: 'Model Mismatch',
            errorCode: 'AI_MODEL_MISMATCH',
            usage: new CortexUsage(0, 0, 0),
            provider: 'deepseek'
        ));

        $openAIMock = Mockery::mock(OpenAICortexProvider::class);
        $openAIMock->shouldReceive('execute')->never();

        $orchestrator = new AIOrchestrator(
            app(\App\Services\AI\AudioService::class),
            app(\App\Services\AI\BriefingService::class),
            app(\App\Application\Shared\Services\TenantContextResolver::class),
            app(\App\Services\AI\Prompts\AiPromptRegistry::class),
            app(\App\Services\AI\Validation\ListingAIResponseValidator::class),
            $deepSeekMock,
            $openAIMock,
            $budgetGuardMock
        );

        $tenantContext = new \App\Application\Shared\DTOs\TenantContext($this->tenant->id, $this->user->id, 'test-trace');

        $request = new CortexRequestData(
            taskType: AITaskType::ANALYZE_PROPERTY,
            input: ['messages' => [['role' => 'user', 'content' => 'test']]],
            tenantContext: $tenantContext,
            model: 'deepseek-v4-pro'
        );

        $response = $orchestrator->orchestrateAI($request);

        $this->assertFalse($response->success);
        $this->assertEquals('AI_MODEL_MISMATCH', $response->errorCode);
    }

    /**
     * 🛡️ PROOF 2: Circuit Breaker OPEN must trigger Fallback to OpenAI
     */
    public function test_circuit_breaker_open_triggers_fallback_to_openai()
    {
        // Mock AiBudgetGuard to allow execution
        $budgetGuardMock = Mockery::mock(AiBudgetGuard::class);
        $budgetGuardMock->shouldReceive('canExecute')->andReturn(true);
        $budgetGuardMock->shouldReceive('deductCredits')->andReturn(null);

        $deepSeekMock = Mockery::mock(DeepSeekCortexProvider::class);
        $deepSeekMock->shouldReceive('execute')->andReturn(new CortexResponseData(
            success: false,
            errorMessage: 'Circuit is open',
            errorCode: 'CIRCUIT_OPEN',
            usage: new CortexUsage(0, 0, 0),
            provider: 'deepseek'
        ));

        $openAIMock = Mockery::mock(OpenAICortexProvider::class);
        $openAIMock->shouldReceive('execute')->once()->andReturn(new CortexResponseData(
            success: true,
            output: ['content' => 'fallback success'],
            rawText: 'fallback success',
            usage: new CortexUsage(10, 5, 15),
            provider: 'openai'
        ));

        $orchestrator = new AIOrchestrator(
            app(\App\Services\AI\AudioService::class),
            app(\App\Services\AI\BriefingService::class),
            app(\App\Application\Shared\Services\TenantContextResolver::class),
            app(\App\Services\AI\Prompts\AiPromptRegistry::class),
            app(\App\Services\AI\Validation\ListingAIResponseValidator::class),
            $deepSeekMock,
            $openAIMock,
            $budgetGuardMock
        );

        $tenantContext = new \App\Application\Shared\DTOs\TenantContext($this->tenant->id, $this->user->id, 'test-trace');

        $request = new CortexRequestData(
            taskType: AITaskType::ANALYZE_PROPERTY,
            input: ['messages' => [['role' => 'user', 'content' => 'test']]],
            tenantContext: $tenantContext
        );

        $response = $orchestrator->orchestrateAI($request);

        $this->assertTrue($response->success);
        $this->assertEquals('openai', $response->provider);
        $this->assertEquals('fallback success', $response->rawText);
    }

    /**
     * 🛡️ PROOF 3: Budget Exceeded must result in HARD FAIL (No Fallback)
     */
    public function test_budget_exceeded_results_in_hard_fail_no_fallback()
    {
        // Mock AiBudgetGuard to REJECT execution (budget exceeded)
        $budgetGuardMock = Mockery::mock(AiBudgetGuard::class);
        $budgetGuardMock->shouldReceive('canExecute')->andReturn(false);
        $budgetGuardMock->shouldReceive('deductCredits')->andReturn(null);

        $deepSeekMock = Mockery::mock(DeepSeekCortexProvider::class);
        $deepSeekMock->shouldReceive('execute')->andReturn(new CortexResponseData(
            success: false,
            errorMessage: 'Budget limit exceeded',
            errorCode: 'AI_BUDGET_EXCEEDED',
            usage: new CortexUsage(0, 0, 0),
            provider: 'deepseek'
        ));

        $openAIMock = Mockery::mock(OpenAICortexProvider::class);
        $openAIMock->shouldReceive('execute')->never();

        $orchestrator = new AIOrchestrator(
            app(\App\Services\AI\AudioService::class),
            app(\App\Services\AI\BriefingService::class),
            app(\App\Application\Shared\Services\TenantContextResolver::class),
            app(\App\Services\AI\Prompts\AiPromptRegistry::class),
            app(\App\Services\AI\Validation\ListingAIResponseValidator::class),
            $deepSeekMock,
            $openAIMock,
            $budgetGuardMock
        );

        $tenantContext = new \App\Application\Shared\DTOs\TenantContext($this->tenant->id, $this->user->id, 'test-trace');

        $request = new CortexRequestData(
            taskType: AITaskType::ANALYZE_PROPERTY,
            input: ['messages' => [['role' => 'user', 'content' => 'test']]],
            tenantContext: $tenantContext
        );

        $response = $orchestrator->orchestrateAI($request);

        $this->assertFalse($response->success);
        $this->assertEquals('AI_BUDGET_EXCEEDED', $response->errorCode);
    }
}
