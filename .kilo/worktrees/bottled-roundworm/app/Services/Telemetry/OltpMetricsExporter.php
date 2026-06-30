<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Class OltpMetricsExporter
 *
 * High-performance asynchronous metrics exporter utilizing OTLP protocol.
 * Agreggates performance, isolation, and AI metrics and pushes them to the Sentinel Collector.
 *
 * @package App\Services\Telemetry
 */
class OltpMetricsExporter
{
    /**
     * @var array
     */
    protected array $metricsBuffer = [];

    /**
     * Record a metric measurement
     *
     * @param string $name
     * @param float $value
     * @param array $labels
     * @return void
     */
    public function record(string $name, float $value, array $labels = []): void
    {
        $this->metricsBuffer[] = [
            'name' => $name,
            'value' => $value,
            'labels' => $labels,
            'timestamp' => microtime(true),
        ];

        // Automatically push when buffer accumulates
        if (count($this->metricsBuffer) >= 5) {
            $this->export();
        }
    }

    /**
     * Export metric payload over non-blocking Unix Domain Socket pipeline.
     * Eliminates sync HTTP bottleneck completely.
     *
     * @return void
     */
    public function export(): void
    {
        if (empty($this->metricsBuffer)) {
            return;
        }

        $config = config('yalihan.telemetry_budget_guards', []);
        if (!($config['aktiflik_durumu'] ?? false)) {
            $this->metricsBuffer = [];
            return;
        }

        $payload = [
            'resourceMetrics' => [
                [
                    'resource' => [
                        'attributes' => [
                            ['key' => 'service.name', 'value' => ['stringValue' => 'yalihan-ai-os']],
                            ['key' => 'exporter.protocol', 'value' => ['stringValue' => 'otlp/asynchronous-socket']],
                        ]
                    ],
                    'scopeMetrics' => [
                        [
                            'scope' => ['name' => 'yalihan.telemetry.metrics'],
                            'metrics' => array_map(function ($metric) {
                                return [
                                    'name' => $metric['name'],
                                    'description' => 'Yalıhan telemetry performance metric',
                                    'unit' => 'ms',
                                    'gauge' => [
                                        'dataPoints' => [
                                            [
                                                'asDouble' => (float) $metric['value'],
                                                'timeUnixNano' => (int) ($metric['timestamp'] * 1e9),
                                                'attributes' => array_map(function ($k, $v) {
                                                    return [
                                                        'key' => $k,
                                                        'value' => ['stringValue' => (string) $v]
                                                    ];
                                                }, array_keys($metric['labels']), $metric['labels'])
                                            ]
                                        ]
                                    ]
                                ];
                            }, $this->metricsBuffer)
                        ]
                    ]
                ]
            ]
        ];

        try {
            $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
            
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
            }

        } catch (\Throwable $exception) {
            Log::error('OTLP Metric Pipeline Core Failure: ' . $exception->getMessage());
        } finally {
            $this->metricsBuffer = [];
        }
    }

    /**
     * Get buffered metrics (for testing/verification)
     *
     * @return array
     */
    public function getBufferedMetrics(): array
    {
        return $this->metricsBuffer;
    }
}
