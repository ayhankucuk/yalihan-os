<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureAgentScope — OpenClaw Runtime Enforcement Middleware
 *
 * Enforces:
 * 1. Kill switch (OPENCLAW_ENABLED)
 * 2. Bearer token authentication via X-Agent-Token header
 * 3. API route allowlist
 * 4. Proposal-only mutation guard
 * 5. Rate limiting per token
 * 6. Mandatory audit logging (every request)
 */
class EnsureAgentScope
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Kill switch — reject ALL agent traffic when disabled
        if (!config('openclaw.enabled', false)) {
            Log::channel(config('openclaw.audit.log_channel', 'security'))->warning('openclaw_kill_switch_active', [
                'ip' => $request->ip(),
                'istek_url' => $request->fullUrl(),
            ]);

            return response()->json([
                'hata_mesaji' => 'Agent gateway disabled',
                'durum_kodu' => 503,
            ], 503);
        }

        // 2. Token authentication
        $tokenHeader = config('openclaw.token.header', 'X-Agent-Token');
        $token = $request->header($tokenHeader);

        if (empty($token)) {
            $this->auditReject($request, 'missing_token');
            return response()->json([
                'hata_mesaji' => 'Agent token required',
                'durum_kodu' => 401,
            ], 401);
        }

        // Token validation: in production this would check a database/cache of issued tokens.
        // For now, validate against OPENCLAW_AGENT_TOKEN env variable.
        $validToken = config('services.openclaw.agent_token', '');
        if (empty($validToken) || !hash_equals($validToken, $token)) {
            $this->auditReject($request, 'invalid_token');
            return response()->json([
                'hata_mesaji' => 'Invalid agent token',
                'durum_kodu' => 403,
            ], 403);
        }

        // 3. Route allowlist enforcement
        $routeName = $request->route()?->getName();
        $allowedRoutes = config('openclaw.allowed_routes', []);

        if (!in_array($routeName, $allowedRoutes, true)) {
            $this->auditReject($request, 'route_not_allowed', ['route' => $routeName]);
            return response()->json([
                'hata_mesaji' => 'Route not in agent allowlist',
                'durum_kodu' => 403,
            ], 403);
        }

        // 4. Proposal-only enforcement
        if (config('openclaw.proposal_only', true)) {
            $method = strtoupper($request->method());
            // In proposal-only mode, only GET and POST to proposal endpoints are allowed.
            // PUT, PATCH, DELETE are forbidden (direct mutations).
            if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->auditReject($request, 'mutation_blocked_proposal_only', ['method' => $method]);
                return response()->json([
                    'hata_mesaji' => 'Direct mutations forbidden in proposal-only mode',
                    'durum_kodu' => 403,
                ], 403);
            }
        }

        // 5. Rate limiting
        $rpm = config('openclaw.rate_limits.requests_per_minute', 30);
        $rateLimitKey = 'openclaw:' . sha1($token);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $rpm)) {
            $this->auditReject($request, 'rate_limit_exceeded');
            return response()->json([
                'hata_mesaji' => 'Agent rate limit exceeded',
                'durum_kodu' => 429,
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        // 6. Payload size guard
        $maxPayload = config('openclaw.rate_limits.max_payload_bytes', 65536);
        if (strlen($request->getContent()) > $maxPayload) {
            $this->auditReject($request, 'payload_too_large', [
                'boyut' => strlen($request->getContent()),
                'limit' => $maxPayload,
            ]);
            return response()->json([
                'hata_mesaji' => 'Payload exceeds maximum allowed size',
                'durum_kodu' => 413,
            ], 413);
        }

        // 7. Mandatory audit log (PASS — request proceeding)
        $this->auditPass($request, $routeName);

        return $next($request);
    }

    private function auditReject(Request $request, string $reason, array $extra = []): void
    {
        Log::channel(config('openclaw.audit.log_channel', 'security'))->warning('openclaw_request_rejected', array_merge([
            'sebep' => $reason,
            'ip' => $request->ip(),
            'istek_url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'payload_hash' => hash('sha256', $request->getContent()),
        ], $extra));
    }

    private function auditPass(Request $request, ?string $routeName): void
    {
        $logData = [
            'route' => $routeName,
            'ip' => $request->ip(),
            'istek_url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'payload_hash' => hash('sha256', $request->getContent()),
        ];

        if (config('openclaw.audit.log_payload', false)) {
            $logData['payload'] = $request->getContent();
        }

        Log::channel(config('openclaw.audit.log_channel', 'security'))->info('openclaw_request_passed', $logData);
    }
}
