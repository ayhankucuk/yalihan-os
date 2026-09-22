<?php

namespace Tests\Feature\AI;

use App\Application\AI\DTOs\CortexRequestData;
use App\Application\Shared\DTOs\TenantContext;
use App\Domain\AI\Enums\AITaskType;
use App\Services\AI\Providers\DeepSeekCortexProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\SimpleTestCase;

class DeepSeekServiceTest extends SimpleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.deepseek.model', 'deepseek-v4-flash');
    }

    public function test_deepseek_provider_executes_successfully()
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response(
                [
                    'choices' => [
                        0 => [
                            'message' => [
                                'content' => 'hello from deepseek',
                            ],
                        ],
                    ],
                    'usage' => [
                        'prompt_tokens' => 10,
                        'completion_tokens' => 5,
                    ],
                    'model' => 'deepseek-v4-flash',
                ],
                200
            ),
        ]);

        $provider = app(DeepSeekCortexProvider::class);
        $tenantContext = new TenantContext(1, 1, 'test-request');
        $messages = [
            0 => [
                'role' => 'user',
                'content' => 'test',
            ],
        ];
        $request = new CortexRequestData(
            AITaskType::ANALYZE_PROPERTY,
            ['messages' => $messages],
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
            'api.deepseek.com/*' => Http::response(
                [
                    'choices' => [
                        0 => [
                            'message' => ['content' => 'hi'],
                        ],
                    ],
                    'usage' => [
                        'prompt_tokens' => 1,
                        'completion_tokens' => 1,
                    ],
                    'model' => 'wrong-model',
                ],
                200
            ),
        ]);

        $provider = app(DeepSeekCortexProvider::class);
        $tenantContext = new TenantContext(1, 1, 'test-request');
        $messages = [
            0 => [
                'role' => 'user',
                'content' => 'test',
            ],
        ];
        $request = new CortexRequestData(
            AITaskType::ANALYZE_PROPERTY,
            ['messages' => $messages],
            $tenantContext,
            [],
            'deepseek-v4-flash',
            []
        );

        $response = $provider->execute($request);

        $this->assertFalse($response->success);
        $this->assertEquals('AI_MODEL_MISMATCH', $response->errorCode);
    }

    public function test_deepseek_provider_records_telemetry_on_http_500_exception()
    {
        Http::fake([
            'api.deepseek.com/*' => Http::response(
                [
                    'error' => [
                        'message' => 'Internal server error',
                        'type' => 'server_error',
                    ],
                ],
                500
            ),
        ]);

        $telemetryMock = \Mockery::mock(\App\Services\AI\Monitoring\AiTelemetryService::class);
        $telemetryMock->shouldReceive('logFailure')
            ->once()
            ->with(
                'deepseek',
                'text_generation',
                \Mockery::type('string'),
                500,
                \Mockery::type('array'),
                1
            );
        $this->app->instance(\App\Services\AI\Monitoring\AiTelemetryService::class, $telemetryMock);


        $tenantContext = new TenantContext(1, 1, 'test-request');
        $messages = [
            0 => [
                'role' => 'user',
                'content' => 'test',
            ],
        ];
        $request = new CortexRequestData(
            AITaskType::ANALYZE_PROPERTY,
            ['messages' => $messages],
            $tenantContext,
            [],
            config('services.deepseek.model', 'deepseek-v4-flash'),
            []
        );

        $provider = app(DeepSeekCortexProvider::class);

        $response = $provider->execute($request);

        // Controlled failure response
        $this->assertFalse($response->success);
        $this->assertEquals('AI_EXCEPTION', $response->errorCode);
    }

    public function test_deepseek_provider_records_telemetry_on_connection_exception()
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Connection timed out');
        });

        $telemetryMock = \Mockery::mock(\App\Services\AI\Monitoring\AiTelemetryService::class);
        $telemetryMock->shouldReceive('logFailure')
            ->once()
            ->with(
                'deepseek',
                'text_generation',
                \Mockery::type('string'),
                500,
                \Mockery::type('array'),
                1
            );
        $this->app->instance(\App\Services\AI\Monitoring\AiTelemetryService::class, $telemetryMock);

        $tenantContext = new TenantContext(1, 1, 'test-request');
        $messages = [
            0 => [
                'role' => 'user',
                'content' => 'test',
            ],
        ];
        $request = new CortexRequestData(
            AITaskType::ANALYZE_PROPERTY,
            ['messages' => $messages],
            $tenantContext,
            [],
            config('services.deepseek.model', 'deepseek-v4-flash'),
            []
        );

        $provider = app(DeepSeekCortexProvider::class);

        $response = $provider->execute($request);

        $this->assertFalse($response->success);
        $this->assertEquals('AI_EXCEPTION', $response->errorCode);
    }
}
