<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Class OpenTelemetryService
 *
 * Central Observability Gateway for Yalıhan AI OS.
 * Manages adaptive distributed tracing spans and exports them over high-performance OTLP.
 *
 * @package App\Services\Telemetry
 */
class OpenTelemetryService
{
    /**
     * @var array
     */
    protected array $activeSpans = [];

    /**
     * @var array
     */
    protected array $sampledSpans = [];

    /**
     * Start a distributed tracing span
     *
     * @param string $name
     * @param array $attributes
     * @return string Span ID
     */
    public function startSpan(string $name, array $attributes = []): string
    {
        $spanId = bin2hex(random_bytes(8));
        
        $this->activeSpans[$spanId] = [
            'span_id' => $spanId,
            'name' => $name,
            'start_time' => microtime(true),
            'attributes' => $attributes,
        ];

        return $spanId;
    }

    /**
     * End a span and process adaptive sampling
     *
     * @param string $spanId
     * @param array $attributes
     * @param bool $hasError
     * @return void
     */
    public function endSpan(string $spanId, array $attributes = [], bool $hasError = false): void
    {
        if (! isset($this->activeSpans[$spanId])) {
            return;
        }

        $span = $this->activeSpans[$spanId];
        unset($this->activeSpans[$spanId]);

        $span['end_time'] = microtime(true);
        $span['duration_ms'] = ($span['end_time'] - $span['start_time']) * 1000;
        $span['attributes'] = array_merge($span['attributes'], $attributes);
        $span['has_error'] = $hasError;

        // Extract Request parameters
        $method = $span['attributes']['http.method'] ?? 'GET';
        $uri = $span['attributes']['http.target'] ?? '/';

        // Enforce SAB Adaptive Sampling:
        // - 10% base rate for healthy requests.
        // - 100% rate for slow requests (GET > 1ms, POST > 10ms) or errors.
        if ($this->shouldSample($method, $uri, $span['duration_ms'], $hasError)) {
            $span['sampled'] = true;
            $this->sampledSpans[] = $span;
            
            // Auto export if buffer grows
            if (count($this->sampledSpans) >= 10) {
                $this->exportTraces();
            }
        }
    }

    /**
     * SAB Adaptive Sampling Decision Engine
     *
     * @param string $method
     * @param string $uri
     * @param float $durationMs
     * @param bool $hasError
     * @return bool
     */
    public function shouldSample(string $method, string $uri, float $durationMs, bool $hasError): bool
    {
        // 1. Errors are ALWAYS 100% sampled
        if ($hasError) {
            return true;
        }

        // 2. Slow Requests exceeding p99 budget are ALWAYS 100% sampled
        $isWrite = in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH']);
        $limit = $isWrite ? 10.0 : 1.0;

        if ($durationMs > $limit) {
            return true;
        }

        // 3. Healthy/Fast requests are sampled based on config (default 10%)
        $samplingRate = config('yalihan.telemetry_budget_guards.sampling_rate', 0.1);
        $threshold = (int) ($samplingRate * 100);
        return mt_rand(1, 100) <= $threshold;
    }

    /**
     * Export accumulated spans using high-performance OTLP push protocol
     *
     * @return void
     */
    /**
     * Export accumulated spans using non-blocking high-performance Unix Domain Socket.
     * SAB v24.2.0 Standard Compliant - Zero Network/Disk Blockage.
     *
     * @return void
     */
    public function exportTraces(): void
    {
        if (empty($this->sampledSpans)) {
            return;
        }

        $config = config('yalihan.telemetry_budget_guards', []);
        if (!($config['aktiflik_durumu'] ?? false)) {
            $this->sampledSpans = [];
            return;
        }

        // distributed tracing için kalıcı anayasal correlation ID enjeksiyonu
        $globalTraceId = request()->header('X-Correlation-ID') ?: bin2hex(random_bytes(16));

        $payload = [
            'resourceSpans' => [
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => 'yalihan-ai-os']],
                            ['key' => 'environment', 'value' => ['stringValue' => config('app.env', 'production')]],
                        ]
                    ],
                    'scopeSpans' => [
                        [
                            'scope' => ['name' => 'yalihan.telemetry.spans'],
                            'spans' => array_map(function ($span) use ($globalTraceId) {
                                return [
                                    'traceId' => $globalTraceId,
                                    'spanId' => $span['span_id'],
                                    'name' => $span['name'],
                                    'startTimeUnixNano' => (int) ($span['start_time'] * 1e9),
                                    'endTimeUnixNano' => (int) ($span['end_time'] * 1e9),
                                    'attributes' => array_map(function ($k, $v) {
                                        return [
                                            'key' => $k,
                                            'value' => is_numeric($v) 
                                                ? ['doubleValue' => (float) $v]
                                                : ['stringValue' => (string) $v]
                                        ];
                                    }, array_keys($span['attributes']), $span['attributes']),
                                    // context7-ignore
                                    'status' => $span['has_error'] // context7-ignore
                                        ? ['code' => 2, 'message' => 'Span Error'] 
                                        : ['code' => 1]
                                ];
                            }, $this->sampledSpans)
                        ]
                    ]
                ]
            ]
        ];

        try {
            $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
            
            // OltpPipelineAgentInjector Mekanizması: Non-blocking socket stream write
            $endpoint = $config['endpoint'] ?? '/var/run/yalihan-telemetry.sock';
            $socket = @stream_socket_client(
                "unix://{$endpoint}",
                $errno,
                $errstr,
                (float)($config['timeout_seconds'] ?? 0.2),
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );

            if ($socket) {
                stream_set_blocking($socket, false);
                @fwrite($socket, $jsonPayload . "\n");
                @fclose($socket);
            } else {
                // Fail-Loud: Altyapı kaybı log diski kirletilmeden Sentinel linter'a deklare edilir
                Log::warning("SAB TELEMETRY DAEMON UNREACHABLE: Traces stream dropped to protect runtime latency.");
            }

        } catch (\Throwable $exception) {
            Log::error('OTLP Trace Pipeline Core Failure: ' . $exception->getMessage());
        } finally {
            $this->sampledSpans = [];
        }
    }

    /**
     * Get sampled spans (for verification & testing)
     *
     * @return array
     */
    public function getSampledSpans(): array
    {
        return $this->sampledSpans;
    }
}
