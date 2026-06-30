<?php

namespace Tests\Feature\AI;

use App\Application\AI\DTOs\CortexRequestData;
use App\Domain\AI\Enums\AITaskType;
use App\Services\AI\Providers\DeepSeekCortexProvider;
use Illuminate\Support\Facades\Http;
use Tests\SimpleTestCase;

/**
 * 🧪 LIVE TEST: DeepSeek Integration
 * This test only runs when explicitly requested.
 * Usage: RUN_REAL_AI_TESTS=true php artisan test --filter=DeepSeekLiveTest
 */
class DeepSeekLiveTest extends SimpleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!env('RUN_REAL_AI_TESTS')) {
            $this->markTestSkipped('RUN_REAL_AI_TESTS is not enabled. Skipping live API test.');
        }

        if (!config('services.deepseek.api_key')) {
            $this->markTestSkipped('DEEPSEEK_API_KEY is not configured.');
        }
    }

    public function test_deepseek_live_api_call()
    {
        // 🛡️ Ensure no mocks are active
        $provider = app(DeepSeekCortexProvider::class);
        $tenantContext = new \App\Application\Shared\DTOs\TenantContext(1, 1, 'live-test-request');
        $request = new CortexRequestData(
            taskType: AITaskType::ANALYZE_PROPERTY,
            input: ['messages' => [['role' => 'user', 'content' => 'Say "DeepSeek Live Test OK"']]],
            tenantContext: $tenantContext,
            model: config('services.deepseek.model', 'deepseek-v4-flash'),
            meta: [
                'temperature' => 0,
                'max_tokens' => 32
            ]
        );

        $response = $provider->execute($request);

        if (!$response->success) {
            $this->fail("DeepSeek Live API Failed: " . $response->errorMessage . " (Code: " . $response->errorCode . ")");
        }

        $this->assertTrue($response->success);
        $this->assertNotEmpty($response->rawText);
        $this->assertEquals('deepseek', $response->provider);
        
        \Illuminate\Support\Facades\Log::info('DEEPSEEK_LIVE_TEST_SUCCESS', [
            'latency' => $response->meta['latency_ms'] ?? 0,
            'model' => $response->model,
            'text' => $response->rawText
        ]);
    }
}
