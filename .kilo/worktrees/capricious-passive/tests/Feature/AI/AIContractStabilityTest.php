<?php

namespace Tests\Feature\AI;

use App\Services\AI\AIOrchestrator;
use App\Services\AI\DTO\ListingAIResultData;
use App\Application\AI\DTOs\CortexResponseData;
use App\Application\AI\DTOs\CortexUsage;
use App\Services\AI\Providers\DeepSeekCortexProvider;
use App\Services\AI\Providers\OpenAICortexProvider;
use Tests\SimpleTestCase;
use Mockery;

class AIContractStabilityTest extends SimpleTestCase
{
    public function test_contract_remains_stable_regardless_of_model()
    {
        // 🛡️ Mock Tenant Context
        $resolver = Mockery::mock(\App\Application\Shared\Services\TenantContextResolver::class);
        $resolver->shouldReceive('resolve')->andReturn(new \App\Application\Shared\DTOs\TenantContext(1, 1, 'test-trace'));
        $this->app->instance(\App\Application\Shared\Services\TenantContextResolver::class, $resolver);

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
            Mockery::mock(OpenAICortexProvider::class)
        );

        $result = $orchestrator->generateListing(['data' => 'test']);

        $this->assertInstanceOf(ListingAIResultData::class, $result);
        $this->assertEquals('Satılık', $result->tip);
    }

    public function test_contract_remains_stable_on_fallback()
    {
        // 🛡️ Mock Tenant Context
        $resolver = Mockery::mock(\App\Application\Shared\Services\TenantContextResolver::class);
        $resolver->shouldReceive('resolve')->andReturn(new \App\Application\Shared\DTOs\TenantContext(1, 1, 'test-trace'));
        $this->app->instance(\App\Application\Shared\Services\TenantContextResolver::class, $resolver);

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
            $openAIMock
        );

        $result = $orchestrator->generateListing(['data' => 'test']);

        $this->assertInstanceOf(ListingAIResultData::class, $result);
        $this->assertEquals('Satılık', $result->tip);
    }
}
