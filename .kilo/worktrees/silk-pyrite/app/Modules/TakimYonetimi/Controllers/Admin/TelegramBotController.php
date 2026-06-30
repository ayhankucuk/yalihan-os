<?php

namespace App\Modules\TakimYonetimi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Modules\TakimYonetimi\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function index()
    {
        $botInfo = $this->telegramService->getBotInfo();
        $webhookInfo = $this->telegramService->getWebhookInfo();
        $settings = $this->telegramService->getSettings();

        $teamId = 1;
        $teamSettingKey = 'team:' . $teamId . ':telegram_channel_id';
        // ✅ STANDARDIZED: Using Setting model instead of SiteSetting (merged)
        $teamChannelId = Setting::get($teamSettingKey);

        return view('admin.telegram-bot.index', compact('botInfo', 'webhookInfo', 'settings', 'teamId', 'teamChannelId'));
    }

    public function setWebhook(Request $request)
    {
        try {
            $webhookUrl = config('services.telegram.webhook_url') ?: url('/api/telegram/webhook');
            $result = $this->telegramService->setWebhook($webhookUrl);

            if (is_array($result) && isset($result['success']) && $result['success']) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Webhook başarıyla ayarlandı!',
                        'data' => $result['data'] ?? null,
                    ]);
                }
                return redirect()->back()->with('success', 'Webhook başarıyla ayarlandı!');
            }

            $message = (is_array($result) && isset($result['message'])) ? $result['message'] : 'Bilinmeyen hata';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Webhook ayarlanamadı: ' . $message,
                ], 400);
            }

            return redirect()->back()->with('error', 'Webhook ayarlanamadı: ' . $message);
        } catch (\Exception $e) {
            Log::error('Telegram webhook ayarlama hatası: ' . $e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bir hata oluştu: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function sendTestMessage(Request $request)
    {
        $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            $message = $request->filled('message') ? $request->message : 'Context7 Telegram test mesajı';
            $result = $this->telegramService->sendTestMessage($message);

            if ($request->wantsJson() || $request->ajax()) {
                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Test mesajı başarıyla gönderildi!',
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Mesaj gönderilemedi: ' . $result['message'],
                ], 400);
            }

            if ($result['success']) {
                return redirect()->back()->with('success', 'Test mesajı gönderildi.');
            }

            return redirect()->back()->with('error', 'Mesaj gönderilemedi: ' . $result['message']);
        } catch (\Exception $e) {
            Log::error('Telegram test mesajı hatası: ' . $e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bir hata oluştu: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function getWebhookInfo()
    {
        try {
            $webhookInfo = $this->telegramService->getWebhookInfo();

            return response()->json([
                'success' => true,
                'data' => $webhookInfo,
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook bilgisi alma hatası: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Webhook bilgisi alınamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'bot_token' => 'sometimes|string',
            'chat_id' => 'sometimes|string',
            'auto_notifications' => 'sometimes|boolean',
            'task_assignments' => 'sometimes|boolean',
            'performance_reports' => 'sometimes|boolean',
            'team_id' => 'sometimes|integer',
            'telegram_channel_id' => 'sometimes|string',
        ]);

        try {
            // ✅ STANDARDIZED: Using Setting model instead of SiteSetting (merged)
            // Takım-Kanal eşlemesi DB'de saklanır (settings tablosu)
            if ($request->filled('team_id') && $request->filled('telegram_channel_id')) {
                $key = 'team:' . $request->integer('team_id') . ':telegram_channel_id';
                Setting::set(
                    $key,
                    $request->string('telegram_channel_id')->toString(),
                    'telegram',
                    'string',
                    'Takım Telegram Kanal ID'
                );
            }

            $result = $this->telegramService->updateSettings($request->all());

            if ($request->wantsJson() || $request->ajax()) {
                if ($result['success']) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Ayarlar başarıyla güncellendi!',
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Ayarlar güncellenemedi: ' . $result['message'],
                ], 400);
            }

            if ($result['success']) {
                return redirect()->back()->with('success', 'Ayarlar güncellendi.');
            }

            return redirect()->back()->with('error', 'Ayarlar güncellenemedi: ' . $result['message']);
        } catch (\Exception $e) {
            Log::error('Telegram ayarları güncelleme hatası: ' . $e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bir hata oluştu: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function getAktiflikDurumu()
    {
        try {
            $aktiflik_durumu = $this->telegramService->getStatus();

            return response()->json([
                'success' => true,
                'connected' => $aktiflik_durumu['connected'] ?? false,
                'webhook_set' => $aktiflik_durumu['webhook_set'] ?? false,
                'pending_messages' => $aktiflik_durumu['pending_messages'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram bot aktiflik durumu alma hatası: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'connected' => false,
                'webhook_set' => false,
                'pending_messages' => 0,
                'message' => 'Bot aktiflik durumu alınamadı: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function testBot()
    {
        try {
            $result = $this->telegramService->testBot();

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bot testi başarılı!',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Bot testi başarısız: ' . $result['message'],
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Telegram bot testi hatası: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Bot testi sırasında hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eşleştirme kodu oluştur
     *
     * Context7 Standard: C7-TELEGRAM-PAIRING-2025-12-01
     */
    public function generatePairingCode(Request $request)
    {
        try {
            /** @var User|null $user */
            $user = auth()->user();

            if (!$user) {
                return redirect()->back()->with('error', 'Oturum açmanız gerekiyor.');
            }

            // 6 haneli rastgele kod oluştur
            $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            $user->telegram_pairing_code = $code;
            $user->save();

            Log::info('Telegram pairing code generated', [
                'user_id' => $user->id,
                'code' => $code,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Eşleştirme kodu oluşturuldu!',
                    'code' => $code,
                ]);
            }

            return redirect()->back()->with('success', "Eşleştirme kodu oluşturuldu: {$code}");
        } catch (\Exception $e) {
            Log::error('Telegram pairing code generation error: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kod oluşturulamadı: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Kod oluşturulamadı: ' . $e->getMessage());
        }
    }
}
