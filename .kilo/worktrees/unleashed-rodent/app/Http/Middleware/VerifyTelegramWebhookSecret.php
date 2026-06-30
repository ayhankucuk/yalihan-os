<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify Telegram Webhook Secret Token
 *
 * Context7: Telegram webhook endpoint'ini yetkisiz erişimlere karşı korur.
 *
 * Telegram, setWebhook() ile tanımlanan secret_token değerini her webhook
 * isteğinde X-Telegram-Bot-Api-Secret-Token header'ı olarak gönderir.
 * Bu middleware gelen header'ı config('services.telegram.webhook_secret')
 * ile karşılaştırır.
 *
 * @see https://core.telegram.org/bots/api#setwebhook
 */
class VerifyTelegramWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedSecret = config('services.telegram.webhook_secret');

        // Güvenlik: Config değeri null/empty ise webhook secret yapılandırılmamış demektir
        if (empty($expectedSecret)) {
            $msg = 'Telegram Webhook Auth: webhook_secret yapılandırılmamış.'
                . ' TELEGRAM_WEBHOOK_SECRET env değişkenini ayarlayıp'
                . ' setWebhook ile Telegram\'a bildirin.';
            Log::channel('security')->critical($msg, [
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'basarili' => false,
                'hata_mesaji' => 'Webhook secret yapılandırılmamış.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if (empty($providedSecret) || ! hash_equals($expectedSecret, $providedSecret)) {
            Log::channel('security')->warning('Telegram Webhook Auth: Yetkisiz erişim girişimi algılandı.', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'basarili' => false,
                'hata_mesaji' => 'Yetkisiz erişim.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
