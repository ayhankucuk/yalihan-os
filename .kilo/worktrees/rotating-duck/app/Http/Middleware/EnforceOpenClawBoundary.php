<?php

namespace App\Http\Middleware;

use App\Models\OpenClawAuditLog;
use App\Services\OpenClaw\OpenClawAuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnforceOpenClawBoundary — Route Allowlist, Forbidden Patterns, Proposal-Only, Source Tagging
 *
 * Layer 3: Enforces route boundaries, proposal-only mode, source header,
 * correlation ID, and mandatory audit logging.
 */
class EnforceOpenClawBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName() ?? '';
        $correlationId = $request->header(config('openclaw.headers.correlation_id', 'X-Correlation-Id'));
        $startTime = microtime(true);

        // 1. Source header validation: X-Agent-Source must be 'openclaw'
        $sourceHeader = config('openclaw.headers.source', 'X-Agent-Source');
        $source = $request->header($sourceHeader);

        if ($source !== 'openclaw') {
            $this->auditReject($request, $routeName, $correlationId, 'invalid_source', ['source' => $source]);
            $this->dbAudit($request, OpenClawAuditLog::EVENT_BOUNDARY_REJECTED, 403, false, "invalid_source: {$source}");
            return response()->json([
                'hata_mesaji' => 'Invalid agent source',
                'durum_kodu' => 403,
            ], 403);
        }

        // 2. Forbidden route patterns (deny takes precedence)
        $forbiddenPatterns = config('openclaw.forbidden_route_patterns', []);
        foreach ($forbiddenPatterns as $pattern) {
            if (Str::is($pattern, $routeName)) {
                $this->auditReject($request, $routeName, $correlationId, 'forbidden_route', ['pattern' => $pattern]);
                $this->dbAudit($request, OpenClawAuditLog::EVENT_BOUNDARY_REJECTED, 403, false, "forbidden_route: {$pattern}");
                return response()->json([
                    'hata_mesaji' => 'Route forbidden for agents',
                    'durum_kodu' => 403,
                ], 403);
            }
        }

        // 3. Route allowlist (wildcard matching)
        $allowedRoutes = config('openclaw.allowed_routes', []);
        $routeAllowed = false;
        foreach ($allowedRoutes as $pattern) {
            if (Str::is($pattern, $routeName)) {
                $routeAllowed = true;
                break;
            }
        }

        if (!$routeAllowed) {
            $this->auditReject($request, $routeName, $correlationId, 'route_not_allowed');
            $this->dbAudit($request, OpenClawAuditLog::EVENT_BOUNDARY_REJECTED, 403, false, "route_not_allowed: {$routeName}");
            return response()->json([
                'hata_mesaji' => 'Route not in agent allowlist',
                'durum_kodu' => 403,
            ], 403);
        }

        // 4. Proposal-only enforcement
        if (config('openclaw.proposal_only', true)) {
            $method = strtoupper($request->method());

            // Block direct mutation HTTP methods
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->auditReject($request, $routeName, $correlationId, 'mutation_blocked_proposal_only', ['method' => $method]);
                $this->dbAudit($request, OpenClawAuditLog::EVENT_BOUNDARY_REJECTED, 403, false, "mutation_blocked: {$method}");
                return response()->json([
                    'hata_mesaji' => 'Direct mutations forbidden in proposal-only mode',
                    'durum_kodu' => 403,
                ], 403);
            }

            // Block execute flag in POST payloads
            $modeHeader = config('openclaw.headers.mode', 'X-Agent-Mode');
            $mode = $request->header($modeHeader);

            if ($mode === 'execute') {
                $this->auditReject($request, $routeName, $correlationId, 'execute_mode_blocked');
                $this->dbAudit($request, OpenClawAuditLog::EVENT_BOUNDARY_REJECTED, 403, false, 'execute_mode_blocked');
                return response()->json([
                    'hata_mesaji' => 'Execute mode forbidden in proposal-only mode',
                    'durum_kodu' => 403,
                ], 403);
            }

            // Reject payload with execute=true
            if ($request->input('execute') === true || $request->input('execute') === 'true') {
                $this->auditReject($request, $routeName, $correlationId, 'execute_payload_blocked');
                $this->dbAudit($request, OpenClawAuditLog::EVENT_BOUNDARY_REJECTED, 403, false, 'execute_payload_blocked');
                return response()->json([
                    'hata_mesaji' => 'Execute flag forbidden in proposal-only mode',
                    'durum_kodu' => 403,
                ], 403);
            }
        }

        // 5. Payload size guard
        $maxPayload = config('openclaw.rate_limits.max_payload_bytes', 65536);
        if (strlen($request->getContent()) > $maxPayload) {
            $this->auditReject($request, $routeName, $correlationId, 'payload_too_large', [
                'boyut' => strlen($request->getContent()),
                'limit' => $maxPayload,
            ]);
            $this->dbAudit($request, OpenClawAuditLog::EVENT_BOUNDARY_REJECTED, 413, false, 'payload_too_large');
            return response()->json([
                'hata_mesaji' => 'Payload exceeds maximum allowed size',
                'durum_kodu' => 413,
            ], 413);
        }

        // 6. Mandatory audit (PASS)
        $durationMs = (microtime(true) - $startTime) * 1000;
        $this->auditPass($request, $routeName, $correlationId);
        $this->dbAudit($request, OpenClawAuditLog::EVENT_REQUEST_PASSED, 200, true, null, $durationMs);

        return $next($request);
    }

    private function auditReject(Request $request, string $routeName, ?string $correlationId, string $reason, array $extra = []): void
    {
        Log::channel(config('openclaw.audit.log_channel', 'security'))->warning('openclaw_boundary_rejected', array_merge([
            'sebep' => $reason,
            'route' => $routeName,
            'correlation_id' => $correlationId,
            'ip' => $request->ip(),
            'istek_url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'agent_source' => $request->header(config('openclaw.headers.source', 'X-Agent-Source')),
            'payload_hash' => hash('sha256', $request->getContent()),
        ], $extra));
    }

    private function auditPass(Request $request, string $routeName, ?string $correlationId): void
    {
        $logData = [
            'route' => $routeName,
            'correlation_id' => $correlationId,
            'ip' => $request->ip(),
            'istek_url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'agent_source' => $request->header(config('openclaw.headers.source', 'X-Agent-Source')),
            'payload_hash' => hash('sha256', $request->getContent()),
        ];

        if (config('openclaw.audit.log_payload', false)) {
            $logData['payload'] = $request->getContent();
        }

        Log::channel(config('openclaw.audit.log_channel', 'security'))->info('openclaw_request_passed', $logData);
    }

    private function dbAudit(Request $request, string $eventType, int $httpDurumKodu, bool $basarili, ?string $reason = null, ?float $durationMs = null): void
    {
        try {
            app(OpenClawAuditService::class)->recordRequest($request, $eventType, $httpDurumKodu, $basarili, $reason, $durationMs);
        } catch (\Throwable) {
            // Audit persistence failure — never crash the request
        }
    }
}
