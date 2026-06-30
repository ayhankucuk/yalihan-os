<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Logging\LogService;

class CheckN8nWebhookToken
{
    /**
     * ✅ SAB Idempotency & Auth Hardening: X-Webhook-Token doğrulaması
     *
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Webhook-Token');
        $expectedToken = config('services.n8n.webhook_token');

        if (empty($expectedToken)) {
            LogService::error('n8n Webhook Auth: Webhook token is not configured in environment!');
            return response()->json(['success' => false, 'message' => 'Internal Configuration Error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$token || !hash_equals($expectedToken, $token)) {
            LogService::warning('n8n Webhook Auth: Unauthorized trigger attempt detected', ['ip' => $request->ip()]);
            return response()->json(['success' => false, 'message' => 'Unauthorized Webhook Trigger'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
