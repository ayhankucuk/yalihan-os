<?php

namespace App\Http\Middleware;

use App\Models\OpenClawAuditLog;
use App\Services\OpenClaw\OpenClawAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureOpenClawEnabled — Kill Switch Middleware
 *
 * Layer 1: Checks OPENCLAW_ENABLED config. When false, rejects with 503.
 * Logs kill switch activation to security channel.
 */
class EnsureOpenClawEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('openclaw.enabled', false)) {
            Log::channel(config('openclaw.audit.log_channel', 'security'))->warning('openclaw_kill_switch_active', [
                'ip' => $request->ip(),
                'istek_url' => $request->fullUrl(),
                'http_method' => $request->method(),
                'agent_source' => $request->header('X-Agent-Source'),
                'correlation_id' => $request->header('X-Correlation-Id'),
                'zaman_damgasi' => now()->toIso8601String(),
            ]);

            $this->audit($request, OpenClawAuditLog::EVENT_GATEWAY_BLOCKED, 503, false, 'kill_switch_active');

            return response()->json([
                'hata_mesaji' => 'Agent gateway disabled',
                'durum_kodu' => 503,
            ], 503);
        }

        // Log that kill switch is enabled and request is proceeding
        Log::channel(config('openclaw.audit.log_channel', 'security'))->info('openclaw_gateway_open', [
            'ip' => $request->ip(),
            'istek_url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'agent_source' => $request->header('X-Agent-Source'),
            'correlation_id' => $request->header('X-Correlation-Id'),
        ]);

        $this->audit($request, OpenClawAuditLog::EVENT_GATEWAY_OPEN, 200, true);

        return $next($request);
    }

    private function audit(Request $request, string $eventType, int $httpDurumKodu, bool $basarili, ?string $reason = null): void
    {
        try {
            app(OpenClawAuditService::class)->recordRequest($request, $eventType, $httpDurumKodu, $basarili, $reason);
        } catch (\Throwable) {
            // Audit persistence failure — never crash the request
        }
    }
}
