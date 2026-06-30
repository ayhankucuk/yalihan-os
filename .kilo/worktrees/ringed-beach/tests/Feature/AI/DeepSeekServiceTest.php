<?php

namespace Tests\Feature\AI;

use App\Application\AI\DTOs\CortexRequestData;
use App\Domain\AI\Enums\AITaskType;
use App\Services\AI\Providers\DeepSeekCortexProvider;
use Illuminate\Support\Facades\Http;
use Tests\SimpleTestCase;

class DeepSeekServiceTest extends SimpleTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Config::set('services.deepseek.model', 'deepseek-chat');
    }

    public function test_deepseek_provider_executes_successfully()
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'hello from deepseek']
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
                'model' => 'deepseek-chat'
            ], 200),
        ]);

        $provider = app(DeepSeekCortexProvider::class);
        $tenantContext = new \App\Application\Shared\DTOs\TenantContext(1, 1, 'test-request');
        $request = new CortexRequestData(
            AITaskType::ANALYZE_PROPERTY,
            ['messages' => [['role' => 'user', 'content' => 'test']],
            $tenantContext,
            [],
            'deepseek-v4-flash',
            []
        );

        $response = $provider->execute($request);

        $this->assertTrue($response->success);
        $this->assertEquals('deepseek', $response->provider);
        $this->assertEquals('hello from deepseek', $response->rawText);
    }

    public function test_deepseek_provider_throws_model_mismatch_exception()
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'hi']
                ],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
                'model' => 'wrong-model'
            ], 200),
        ]);

        $provider = app(DeepSeekCortexProvider::class);
        $tenantContext = new \App\Application\Shared\DTOs\TenantContext(1, 1, 'test-request');
        $request = new CortexRequestData(
            AITaskType::ANALYZE_PROPERTY,
            ['messages' => [['role' => 'user', 'content' => 'test']],
            $tenantContext,
            [],
            'deepseek-v4-flash',
            []
        );

        $response = $provider->execute($request);

        $this->assertFalse($response->success);
        $this->assertEquals('AI_MODEL_MISMATCH', $response->errorCode);
    }
}
