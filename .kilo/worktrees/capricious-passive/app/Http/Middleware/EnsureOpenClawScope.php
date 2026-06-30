<?php

namespace App\Http\Middleware;

use App\Models\OpenClawAuditLog;
use App\Services\OpenClaw\OpenClawAuditService;
use App\Support\AgentContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureOpenClawScope — Token & Scope Validation Middleware
 *
 * Layer 2: Validates X-Agent-Token header and X-Agent-Scope claim.
 * Missing token → 401, invalid token/scope → 403.
 */
class EnsureOpenClawScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $tokenHeader = config('openclaw.headers.token', 'X-Agent-Token');
        $token = $request->header($tokenHeader);

        // Token presence check
        if (empty($token)) {
            $this->audit($request, 'missing_token');
            $this->dbAudit($request, OpenClawAuditLog::EVENT_TOKEN_INVALID, 401, false, 'missing_token');
            return response()->json([
                'hata_mesaji' => 'Agent token required',
                'durum_kodu' => 401,
            ], 401);
        }

        // Token validity check (config-based; production may use DB lookup)
        $validToken = config('openclaw.token.value', '');
        if (empty($validToken) || !hash_equals($validToken, $token)) {
            $this->audit($request, 'invalid_token');
            $this->dbAudit($request, OpenClawAuditLog::EVENT_TOKEN_INVALID, 403, false, 'invalid_token');
            return response()->json([
                'hata_mesaji' => 'Invalid agent token',
                'durum_kodu' => 403,
            ], 403);
        }

        // Scope validation
        $scopeHeader = config('openclaw.headers.scope', 'X-Agent-Scope');
        $claimedScope = $request->header($scopeHeader);
        $allowedScopes = config('openclaw.allowed_scopes', []);

        if (!empty($allowedScopes) && !empty($claimedScope)) {
            if (!in_array($claimedScope, $allowedScopes, true)) {
                $this->audit($request, 'invalid_scope', ['claimed_scope' => $claimedScope]);
                $this->dbAudit($request, OpenClawAuditLog::EVENT_SCOPE_REJECTED, 403, false, "invalid_scope: {$claimedScope}");
                return response()->json([
                    'hata_mesaji' => 'Scope not permitted',
                    'durum_kodu' => 403,
                ], 403);
            }
        }

        // Activate agent context for service-layer write guards
        $correlationHeader = config('openclaw.headers.correlation_id', 'X-Correlation-Id');
        AgentContext::activate(
            $claimedScope ?? 'unknown',
            $request->header($correlationHeader),
            substr(hash('sha256', $token), 0, 12),
        );

        return $next($request);
    }

    private function audit(Request $request, string $reason, array $extra = []): void
    {
        Log::channel(config('openclaw.audit.log_channel', 'security'))->warning('openclaw_scope_rejected', array_merge([
            'sebep' => $reason,
            'ip' => $request->ip(),
            'istek_url' => $request->fullUrl(),
            'http_method' => $request->method(),
        ], $extra));
    }

    private function dbAudit(Request $request, string $eventType, int $httpDurumKodu, bool $basarili, ?string $reason = null): void
    {
        try {
            app(OpenClawAuditService::class)->recordRequest($request, $eventType, $httpDurumKodu, $basarili, $reason);
        } catch (\Throwable) {
            // Audit persistence failure — never crash the request
        }
    }
}
