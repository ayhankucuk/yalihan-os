<?php

namespace Tests\Feature\Telemetry;

use App\Services\Telemetry\OpenTelemetryService;
use App\Services\Telemetry\OltpMetricsExporter;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Class TelemetrySocketFailSafeTest
 * @package Tests\Feature\Telemetry
 * @description Verifies that when the Unix Domain Socket is down or unreachable, the system executes fail-safe and does not block.
 */
class TelemetrySocketFailSafeTest extends TestCase
{
    /** @test */
    public function it_executes_traces_export_fail_safe_when_socket_is_unreachable()
    {
        // 1. Configure the telemetry budget guards to point to a non-existent socket endpoint
        config(['yalihan.telemetry_budget_guards' => [
            'aktiflik_durumu' => true,
            'read_budget_ms' => 1.0,
            'write_budget_ms' => 10.0,
            'sampling_rate' => 1.0, // Force 100% sampling for test
            'transport' => 'socket',
            'endpoint' => '/var/run/yalihan-telemetry-fake-nonexistent.sock',
            'timeout_seconds' => 0.05, // Ultra fast timeout for test
        ]]);

        // 2. Intercept and mock warnings to verify the fail-safe log execution
        Log::shouldReceive('warning')
            ->once()
            ->with("SAB TELEMETRY DAEMON UNREACHABLE: Traces stream dropped to protect runtime latency.");

        // We also allow log debugs and other log calls to prevent warnings in test execution
        Log::shouldReceive('debug')->byDefault();
        Log::shouldReceive('error')->byDefault();

        // 3. Populate a sample span in the OpenTelemetryService
        /** @var OpenTelemetryService $service */
        $service = app(OpenTelemetryService::class);
        $spanId = $service->startSpan('test_fail_safe_span', ['test_key' => 'test_value']);
        $service->endSpan($spanId, ['status' => 'completed']);

        // 4. Trigger exportTraces and assert it finishes cleanly (does not throw exception)
        $service->exportTraces();

        $this->assertEmpty($service->getSampledSpans());
    }

    /** @test */
    public function it_executes_metrics_export_fail_safe_when_socket_is_unreachable()
    {
        // 1. Configure telemetry
        config(['yalihan.telemetry_budget_guards' => [
            'aktiflik_durumu' => true,
            'read_budget_ms' => 1.0,
            'write_budget_ms' => 10.0,
            'sampling_rate' => 1.0,
            'transport' => 'socket',
            'endpoint' => '/var/run/yalihan-telemetry-fake-nonexistent.sock',
            'timeout_seconds' => 0.05,
        ]]);

        Log::shouldReceive('error')->byDefault();
        Log::shouldReceive('debug')->byDefault();

        // 2. Record metrics and trigger export
        /** @var OltpMetricsExporter $exporter */
        $exporter = app(OltpMetricsExporter::class);
        $exporter->record('test_metric', 12.34, ['label' => 'test']);
        
        // Trigger export explicitly (which uses socket under budget guards)
        $exporter->export();

        // 3. Assert buffer is successfully cleared and no exception is thrown
        $this->assertEmpty($exporter->getBufferedMetrics());
    }
}
