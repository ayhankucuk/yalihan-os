<?php

namespace App\Http\Middleware;

use App\Services\Response\ResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check N8N Webhook Secret Middleware
 *
 * Context7: n8n webhook endpoint'lerini yetkisiz erişimlere karşı korur
 *
 * X-N8N-SECRET header'ını kontrol eder ve config('services.n8n.webhook_secret')
 * ile karşılaştırır. Eşleşmezse 403 Unauthorized döner.
 */
class CheckN8nSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedSecret = $request->header('X-N8N-SECRET');
        $expectedSecret = config('services.n8n.webhook_secret');

        // Güvenlik: Config değeri null/empty ise sistem yapılandırılmamış demektir
        // Bu statusda güvenlik açığı olmaması için işlemi reddet
        if (empty($expectedSecret) || is_null($expectedSecret)) {
            return ResponseService::error(
                'Webhook secret yapılandırılmamış. Lütfen N8N_WEBHOOK_SECRET environment variable\'ını ayarlayın.',
                500,
                [],
                'WEBHOOK_SECRET_NOT_CONFIGURED'
            );
        }

        // Secret header'ı yoksa veya eşleşmiyorsa 403 döndür
        if (empty($providedSecret) || ! hash_equals($expectedSecret, $providedSecret)) {
            return ResponseService::error(
                'Yetkisiz erişim. Geçerli X-N8N-SECRET header\'ı gerekli.',
                403,
                [],
                'UNAUTHORIZED_WEBHOOK'
            );
        }

        return $next($request);
    }
}
