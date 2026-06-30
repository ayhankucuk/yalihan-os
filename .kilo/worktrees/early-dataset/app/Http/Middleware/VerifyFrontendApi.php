<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Logging\LogService;
use Symfony\Component\HttpFoundation\Response;

/**
 * VerifyFrontendApi Middleware
 *
 * Context7 Standard: C7-FRONTEND-API-AUTH-2025-12-01
 *
 * Vitrin (www.yalihanemlak.com.tr) ile Panel (panel.yalihanemlak.com.tr)
 * arasındaki internal API iletişimini güvence altına alır.
 *
 * Docker network içinde olsa bile güvenlik için API key kontrolü yapar.
 */
class VerifyFrontendApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. API Key kontrolü
        $apiKey = $request->header('X-Internal-API-Key');
        $expectedKey = config('services.frontend_api.internal_key');

        if (empty($expectedKey)) {
            LogService::error('Frontend API: Internal key tanımlı değil');
            return response()->json([
                'success' => false,
                'error' => 'API configuration error',
            ], 500);
        }

        if ($apiKey !== $expectedKey) {
            LogService::warning('Frontend API: Geçersiz API key', [
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
            ], 401);
        }

        // 2. IP Whitelist kontrolü (Docker network)
        $allowedIps = config('services.frontend_api.allowed_ips', []);
        if (!empty($allowedIps) && !$this->isIpAllowed($request->ip(), $allowedIps)) {
            LogService::warning('Frontend API: IP whitelist dışında', [
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Forbidden',
            ], 403);
        }

        // 3. Request logging (optional, production'da kapatılabilir)
        if (config('services.frontend_api.log_requests', false)) {
            LogService::info('Frontend API Request', [
                'endpoint' => $request->path(),
                'ip' => $request->ip(),
                'method' => $request->method(),
            ]);
        }

        return $next($request);
    }

    /**
     * IP adresinin whitelist'te olup olmadığını kontrol et
     *
     * @param string $ip
     * @param array $allowedIps
     * @return bool
     */
    private function isIpAllowed(string $ip, array $allowedIps): bool
    {
        foreach ($allowedIps as $allowedIp) {
            // CIDR notation desteği (örn: 172.17.0.0/16)
            if (str_contains($allowedIp, '/')) {
                if ($this->ipInRange($ip, $allowedIp)) {
                    return true;
                }
            } else {
                // Direkt IP eşleşmesi
                if ($ip === $allowedIp) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * IP adresinin CIDR range içinde olup olmadığını kontrol et
     *
     * @param string $ip
     * @param string $range (CIDR notation, örn: 172.17.0.0/16)
     * @return bool
     */
    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $mask] = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int) $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
