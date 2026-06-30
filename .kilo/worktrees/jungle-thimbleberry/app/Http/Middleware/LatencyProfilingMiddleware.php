<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Telemetry\OpenTelemetryService;

/**
 * Class LatencyProfilingMiddleware
 *
 * Enforces strict SAB Core p99 latency barrier budgets:
 * - Read path: <1ms
 * - Write path: <10ms
 *
 * Wires all HTTP request flows into OpenTelemetry adaptive distributed tracing spans.
 * Logs emergency alerts on breaches and sets latency performance headers.
 *
 * @package App\Http\Middleware
 */
class LatencyProfilingMiddleware
{
    /**
     * @var OpenTelemetryService
     */
    protected OpenTelemetryService $telemetry;

    /**
     * LatencyProfilingMiddleware constructor.
     *
     * @param OpenTelemetryService $telemetry
     */
    public function __construct(OpenTelemetryService $telemetry)
    {
        $this->telemetry = $telemetry;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Start OpenTelemetry span
        $spanId = $this->telemetry->startSpan('http.request', [
            'http.method' => $request->method(),
            'http.target' => $request->getRequestUri(),
            'http.scheme' => $request->getScheme(),
        ]);

        $hasError = false;
        try {
            $response = $next($request);
            return $response;
        } catch (\Throwable $exception) {
            $hasError = true;
            throw $exception;
        } finally {
            $durationMs = (microtime(true) - $startTime) * 1000;
            $isWrite = in_array($request->method(), ['POST', 'PUT', 'DELETE', 'PATCH']);

            // End OpenTelemetry span with measured results
            $this->telemetry->endSpan($spanId, [
                'http.status_code' => isset($response) ? $response->getStatusCode() : 500,
                'http.latency_budget_ms' => round($durationMs, 4),
            ], $hasError);

            if (isset($response)) {
                $response->headers->set('X-Latency-Budget-MS', round($durationMs, 4));
            }

            if ($isWrite) {
                if ($durationMs > 10.0) {
                    Log::emergency("🚨 [SAB LATENCY BREACH] Write path exceeded latency budget of 10ms!", [
                        'method' => $request->method(),
                        'uri' => $request->getRequestUri(),
                        'duration_ms' => round($durationMs, 4),
                        'limit_ms' => 10.0,
                    ]);
                }
            } else {
                if ($durationMs > 1.0) {
                    Log::emergency("🚨 [SAB LATENCY BREACH] Read path exceeded latency budget of 1ms!", [
                        'method' => $request->method(),
                        'uri' => $request->getRequestUri(),
                        'duration_ms' => round($durationMs, 4),
                        'limit_ms' => 1.0,
                    ]);
                }
            }
        }
    }
}
