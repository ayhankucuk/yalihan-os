<?php

namespace Tests\Unit\AI;

use Tests\TestCase;
use App\Services\AI\Monitoring\AiTelemetryService;
use App\Models\AiLog;

class AiTelemetryTest extends TestCase
{

    protected AiTelemetryService $telemetry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->telemetry = new AiTelemetryService();
    }

    public function test_it_logs_successful_transaction()
    {
        $log = $this->telemetry->logTransaction(
            'ollama',
            'test_endpoint',
            0.5,
            100,
            0,   // outputTokens
            200, // aktiflikKodu
            ['request' => ['foo' => 'bar']]
        );

        $this->assertInstanceOf(AiLog::class, $log);
        $this->assertEquals('ollama', $log->provider);
        $this->assertEquals('test_endpoint', $log->endpoint);
        $this->assertEquals(500, $log->duration_ms); // 0.5s * 1000
        $this->assertEquals('success', $log->calisma_durumu); // 200 is success

        $this->assertDatabaseHas('ai_logs', [
            'id' => $log->id,
            'provider' => 'ollama',
            'calisma_durumu' => 'success',
        ]);
    }

    public function test_it_logs_failed_transaction()
    {
        $this->telemetry->logFailure(
            'openai',
            'failed_endpoint',
            'API Timeout',
            500, // aktiflikKodu
            ['request' => ['baz' => 'qux']]
        );

        $this->assertDatabaseHas('ai_logs', [
            'provider' => 'openai',
            'endpoint' => 'failed_endpoint',
            'aktiflik_kodu' => 500,
            'hata_mesaji' => 'API Timeout',
        ]);
    }
}
