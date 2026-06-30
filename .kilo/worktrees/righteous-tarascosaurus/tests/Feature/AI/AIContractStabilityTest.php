<?php

namespace Tests\Feature\AI;

use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\ListingAIResultData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\DTOs\CortexUsage;
use App\Services\AI\Providers\DeepSeekCortexProvider;
use App\Services\AI\Providers\OpenAICortexProvider;
use App\Services\AI\Monetization\AiBudgetGuard;
use App\Models\User;
use App\Models\SaaS\Tenant;
use Tests\SimpleTestCase;
use Mockery;

class AIContractStabilityTest extends SimpleTestCase
{
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

    public function test_contract_remains_stable_regardless_of_model()
    {
        // Mock AiBudgetGuard to allow execution
        $budgetGuardMock = Mockery::mock(AiBudgetGuard::class);
        $budgetGuardMock->shouldReceive('canExecute')->andReturn(true);
        $budgetGuardMock->shouldReceive('deductCredits')->andReturn(null);

        $deepSeekMock = Mockery::mock(DeepSeekCortexProvider::class);
        $deepSeekMock->shouldReceive('execute')->andReturn(new CortexResponseData(
            success: true,
            output: ['test'],
            rawText: file_get_contents(base_path('tests/Fixtures/AI/listing_generation_valid.json')),
            usage: new CortexUsage(1, 1, 2),
            provider: 'deepseek'
        ));

        $orchestrator = new AIOrchestrator(
            app(\App\Services\AI\AudioService::class),
            app(\App\Services\AI\BriefingService::class),
            app(\App\Application\Shared\Services\TenantContextResolver::class),
            app(\App\Services\AI\Prompts\AiPromptRegistry::class),
            app(\App\Services\AI\Validation\ListingAIResponseValidator::class),
            $deepSeekMock,
            Mockery::mock(OpenAICortexProvider::class),
            $budgetGuardMock
        );

        $result = $orchestrator->generateListing(['data' => 'test']);

        $this->assertInstanceOf(ListingAIResultData::class, $result);
        $this->assertEquals('Satılık', $result->tip);
    }

    public function test_contract_remains_stable_on_fallback()
    {
        // Mock AiBudgetGuard to allow execution
        $budgetGuardMock = Mockery::mock(AiBudgetGuard::class);
        $budgetGuardMock->shouldReceive('canExecute')->andReturn(true);
        $budgetGuardMock->shouldReceive('deductCredits')->andReturn(null);

        $deepSeekMock = Mockery::mock(DeepSeekCortexProvider::class);
        $deepSeekMock->shouldReceive('execute')->andReturn(new CortexResponseData(
            success: false,
            errorMessage: 'Network Error',
            errorCode: 'PROVIDER_TIMEOUT',
            usage: new CortexUsage(0, 0, 0),
            provider: 'deepseek'
        ));

        $openAIMock = Mockery::mock(OpenAICortexProvider::class);
        $openAIMock->shouldReceive('execute')->andReturn(new CortexResponseData(
            success: true,
            output: ['test'],
            rawText: file_get_contents(base_path('tests/Fixtures/AI/listing_generation_valid.json')),
            usage: new CortexUsage(1, 1, 2),
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

        $result = $orchestrator->generateListing(['data' => 'test']);

        $this->assertInstanceOf(ListingAIResultData::class, $result);
        $this->assertEquals('Satılık', $result->tip);
    }
}
