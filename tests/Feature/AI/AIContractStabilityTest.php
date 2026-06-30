<?php

namespace Tests\Feature\AI;

use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\DTOs\CortexUsage;
use App\Application\Shared\DTOs\TenantContext;
use App\Application\Shared\Services\TenantContextResolver;
use App\Models\SaaS\Tenant;
use App\Services\AI\AIOrchestrator;
use App\Services\AI\AudioService;
use App\Services\AI\BriefingService;
use App\Services\AI\DTO\ListingAIResultData;
use App\Services\AI\Monetization\AiBudgetGuard;
use App\Services\AI\Prompts\AiPromptRegistry;
use App\Services\AI\Providers\DeepSeekCortexProvider;
use App\Services\AI\Providers\OpenAICortexProvider;
use App\Services\AI\Validation\ListingAIResponseValidator;
use Mockery;
use Tests\SimpleTestCase;

class AIContractStabilityTest extends SimpleTestCase
{
    public function test_contract_remains_stable_regardless_of_model()
    {
        // 🛡️ Mock Tenant Context
        $resolver = Mockery::mock(TenantContextResolver::class);
        $resolver->shouldReceive('resolve')->andReturn(new TenantContext(1, 1, 'test-trace'));
        $resolver->shouldReceive('getTenant')->andReturn(new Tenant(['id' => 1]));
        $this->app->instance(TenantContextResolver::class, $resolver);

        $deepSeekMock = Mockery::mock(DeepSeekCortexProvider::class);
        $deepSeekMock->shouldReceive('execute')->andReturn(new CortexResponseData(
            success: true,
            output: ['test'],
            rawText: file_get_contents(base_path('tests/Fixtures/AI/listing_generation_valid.json')),
            usage: new CortexUsage(1, 1, 2),
            provider: 'deepseek'
        ));

        $budgetMock = Mockery::mock(AiBudgetGuard::class);
        $budgetMock->shouldReceive('canExecute')->andReturn(true);
        $budgetMock->shouldReceive('deductCredits')->andReturn(null);

        $orchestrator = new AIOrchestrator(
            app(AudioService::class),
            app(BriefingService::class),
            $resolver,
            app(AiPromptRegistry::class),
            app(ListingAIResponseValidator::class),
            $deepSeekMock,
            Mockery::mock(OpenAICortexProvider::class),
            $budgetMock
        );

        $result = $orchestrator->generateListing(['data' => 'test']);

        $this->assertInstanceOf(ListingAIResultData::class, $result);
        $this->assertEquals('Satılık', $result->tip);
    }

    public function test_contract_remains_stable_on_fallback()
    {
        // 🛡️ Mock Tenant Context
        $resolver = Mockery::mock(TenantContextResolver::class);
        $resolver->shouldReceive('resolve')->andReturn(new TenantContext(1, 1, 'test-trace'));
        $resolver->shouldReceive('getTenant')->andReturn(new Tenant(['id' => 1]));
        $this->app->instance(TenantContextResolver::class, $resolver);

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

        $budgetMock = Mockery::mock(AiBudgetGuard::class);
        $budgetMock->shouldReceive('canExecute')->andReturn(true);
        $budgetMock->shouldReceive('deductCredits')->andReturn(null);

        $orchestrator = new AIOrchestrator(
            app(AudioService::class),
            app(BriefingService::class),
            $resolver,
            app(AiPromptRegistry::class),
            app(ListingAIResponseValidator::class),
            $deepSeekMock,
            $openAIMock,
            $budgetMock
        );

        $result = $orchestrator->generateListing(['data' => 'test']);

        $this->assertInstanceOf(ListingAIResultData::class, $result);
        $this->assertEquals('Satılık', $result->tip);
    }
}
