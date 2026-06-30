<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use App\Services\Logging\LogService;
use App\Services\Telegram\TelegramBrain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Telegram Webhook Controller
 *
 * Context7 Standardı: C7-TELEGRAM-WEBHOOK-2025-11-25
 *
 * Telegram Bot API'den gelen webhook isteklerini işler.
 * Telegram'dan gelen mesajları, komutları ve verileri alır.
 */
class TelegramWebhookController extends Controller
{
    protected TelegramBotService $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Telegram Webhook Endpoint
     *
     * POST /api/telegram/webhook
     *
     * Telegram Bot API'den gelen tüm webhook isteklerini işler.
     * Mesajlar, komutlar, callback query'ler vb. bu endpoint'e gelir.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        try {
            $data = $request->all();

            // Log incoming webhook (debug için)
            Log::info('Telegram webhook received', [
                'update_id' => $data['update_id'] ?? null,
                'message' => isset($data['message']) ? 'present' : 'missing',
                'callback_query' => isset($data['callback_query']) ? 'present' : 'missing',
            ]);

            // Context7: Telegram Cortex Architecture - TelegramBrain kullan
            $telegramBrain = app(TelegramBrain::class);
            $telegramBrain->handle($data);

            // Telegram'a başarılı yanıt döndür
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Hata olsa bile Telegram'a 200 döndür (Telegram retry yapmasın)
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage(),
            ], 200);
        }
    }

    /**
     * Telegram Webhook Test Endpoint
     *
     * GET /api/telegram/webhook/test
     *
     * Webhook endpoint'inin çalışıp çalışmadığını test eder.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'Telegram webhook endpoint is active',
            'endpoint' => '/api/telegram/webhook',
            'method' => 'POST',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
