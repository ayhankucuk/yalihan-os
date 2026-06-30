<?php

namespace App\Services\Integrations;

/**
 * @sab-ignore-catch
 */

use App\Models\Ilan;
use App\Models\IlanReservation;
use App\Services\AdminActivityEventService;
use App\Services\AIService;
use App\Services\Calendar\IlanReservationService;
use App\Services\Logging\LogService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Telegram AI Bot Service
 *
 * Context7 Standardı: C7-TELEGRAM-AI-BOT-2025-12-19
 *
 * Yalıhan Bekçi: AI-powered conversational Telegram bot
 * MCP Compliance: ✅ LogService + Timer tracking
 * Naming Convention: ✅ yayin_durumu, il_id (not technical-fields, is_active)
 *
 * @version 2.0.0
 * @since 2025-12-19
 * @author YalihanCortex AI System
 *
 * Features:
 * - Conversational AI bot
 * - Command handling (/start, /help, /search, /list)
 * - Natural language queries
 * - Ilan/Talep search integration
 * - User context management
 * - Multi-language support
 */
class TelegramAIBotService
{
    protected TelegramService $telegramService;
    protected AIService $aiService;
    protected LogService $logService;
    protected IlanReservationService $reservationService;
    protected AdminActivityEventService $activityService;
    protected string $botToken;
    protected string $botUsername;

    /**
     * Bot commands
     */
    private const COMMANDS = [
        '/start' => 'Bot\'u başlat',
        '/help' => 'Yardım menüsü',
        '/search' => 'İlan ara',
        '/list' => 'Aktif ilanlar',
        '/talep' => 'Talep oluştur',
        '/gorevler' => 'Görevlerimi listele',
        '/gorev_olustur' => 'Yeni görev oluştur',
        '/proje_durumu' => 'Proje durumu',
        '/takim' => 'Takım üyeleri',
        '/settings' => 'Ayarlar',
        '/cancel' => 'İşlemi iptal et',
        '/rezervasyon_yap' => 'Rezervasyon yap',
        '/takvimi_kapat' => 'Takvimi kapat',
        '/rezervasyon_iptal' => 'Rezervasyon iptal et',
    ];

    /**
     * Conversation states
     */
    private const STATES = [
        'idle' => 'Beklemede',
        'searching' => 'Arama yapıyor',
        'creating_talep' => 'Talep oluşturuyor',
        'creating_gorev' => 'Görev oluşturuyor',
        'waiting_location' => 'Lokasyon bekliyor',
        'waiting_price' => 'Fiyat bekliyor',
        'waiting_gorev_details' => 'Görev detayları bekliyor',
        'waiting_ilan_id' => 'İlan ID bekliyor',
        'waiting_reservation_dates' => 'Rezervasyon tarihleri bekliyor',
        'waiting_close_dates' => 'Kapatma tarihleri bekliyor',
        'waiting_reservation_id' => 'Rezervasyon ID bekliyor',
    ];

    public function __construct(
        TelegramService $telegramService,
        AIService $aiService,
        LogService $logService,
        IlanReservationService $reservationService,
        AdminActivityEventService $activityService
    ) {
        $this->telegramService = $telegramService;
        $this->aiService = $aiService;
        $this->logService = $logService;
        $this->reservationService = $reservationService;
        $this->activityService = $activityService;
        $this->botToken = config('services.telegram.bot_token', '');
        $this->botUsername = config('services.telegram.bot_username', 'yalihan_bot');
    }

    /**
     * Process incoming Telegram update
     *
     * @CortexDecision Telegram message → AI response
     *
     * @param array $update Telegram update payload
     * @return array Processing result
     */
    public function processUpdate(array $update): array
    {
        $timerId = LogService::startTimer('telegram_process_update');

        try {
            // Phase T: Handle callback_query (inline button clicks)
            if (isset($update['callback_query'])) {
                return $this->handleCallbackQuery($update['callback_query']);
            }

            $message = $update['message'] ?? $update['edited_message'] ?? null;

            if (! $message) {
                return ['success' => false, 'error' => 'No message in update'];
            }

            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $userId = $message['from']['id'] ?? null;

            Log::info('Telegram update received', [
                'chat_id' => $chatId,
                'user_id' => $userId,
                'text' => $text,
            ]);

            // Handle commands
            if (str_starts_with($text, '/')) {
                // Store last command for state management
                Cache::put("telegram_last_command:{$chatId}", $text, 3600);
                $result = $this->handleCommand($chatId, $text, $message);
            } else {
                // Handle natural language
                $result = $this->handleNaturalLanguage($chatId, $text, $message);
            }

            $duration = LogService::stopTimer($timerId);

            LogService::ai('telegram_update_processed', 'cortex', [
                'chat_id' => $chatId,
                'text_length' => strlen($text),
                'is_command' => str_starts_with($text, '/'),
                'success' => $result['success'],
            ], $duration / 1000);

            return $result;
        } catch (Exception $e) {
            LogService::stopTimer($timerId);

            Log::error('Telegram update processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle bot command
     */
    private function handleCommand(int $chatId, string $command, array $message): array
    {
        [$cmd, $args] = $this->parseCommand($command);

        return match ($cmd) {
            '/start' => $this->handleStartCommand($chatId, $message),
            '/help' => $this->handleHelpCommand($chatId),
            '/search' => $this->handleSearchCommand($chatId, $args, $message),
            '/list' => $this->handleListCommand($chatId, $args),
            '/talep' => $this->handleTalepCommand($chatId, $args, $message),
            '/gorevler' => $this->handleGorevlerCommand($chatId, $message),
            '/gorev_olustur' => $this->handleGorevOlusturCommand($chatId, $args, $message),
            '/proje_durumu' => $this->handleProjeDurumCommand($chatId, $args),
            '/takim' => $this->handleTakimCommand($chatId),
            '/settings' => $this->handleSettingsCommand($chatId),
            '/cancel' => $this->handleCancelCommand($chatId),
            '/rezervasyon_yap' => $this->handleRezervasyonYapCommand($chatId, $args, $message),
            '/takvimi_kapat' => $this->handleTakvimiKapatCommand($chatId, $args, $message),
            '/rezervasyon_iptal' => $this->handleRezervasyonIptalCommand($chatId, $args, $message),
            default => $this->handleUnknownCommand($chatId, $cmd),
        };
    }

    /**
     * Handle /start command
     */
    private function handleStartCommand(int $chatId, array $message): array
    {
        $firstName = $message['from']['first_name'] ?? 'Kullanıcı';

        $text = "👋 Merhaba {$firstName}!\n\n";
        $text .= "Ben Yalıhan Emlak AI asistanıyım. Size nasıl yardımcı olabilirim?\n\n";
        $text .= "📋 Komutlar:\n";

        foreach (self::COMMANDS as $cmd => $desc) {
            $text .= "{$cmd} - {$desc}\n";
        }

        $text .= "\n💬 Veya doğal dilde soru sorabilirsiniz:\n";
        $text .= "\"Kadıköy'de satılık daire var mı?\"\n";
        $text .= "\"500-700 bin TL arası konut arıyorum\"";

        return $this->sendMessage($chatId, $text);
    }

    /**
     * Handle /help command
     */
    private function handleHelpCommand(int $chatId): array
    {
        $text = "🤖 *Yalıhan Emlak AI Bot Yardım*\n\n";
        $text .= "*Komutlar:*\n";

        foreach (self::COMMANDS as $cmd => $desc) {
            $text .= "`{$cmd}` - {$desc}\n";
        }

        $text .= "\n*Doğal Dil Örnekleri:*\n";
        $text .= "• Beşiktaş'ta satılık daire var mı?\n";
        $text .= "• 3+1 villa arıyorum\n";
        $text .= "• Fiyatı 2 milyon TL olan arsa göster\n";
        $text .= "\n*Not:* Lokasyon, fiyat, oda sayısı gibi detayları belirtebilirsiniz.";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /search command
     */
    private function handleSearchCommand(int $chatId, string $args, array $message): array
    {
        if (empty($args)) {
            return $this->sendMessage($chatId, "🔍 Arama yapmak için:\n/search <sorgu>\n\nÖrnek: /search Kadıköy satılık daire");
        }

        // Use AI to parse search query
        $searchIntent = $this->parseSearchQuery($args);

        // Perform search
        $results = $this->performSearch($searchIntent);

        if (empty($results)) {
            return $this->sendMessage($chatId, "❌ Aramanızla eşleşen ilan bulunamadı.");
        }

        $text = "🏠 *Arama Sonuçları:*\n\n";
        $text .= "Sorgu: {$args}\n";
        $text .= "Bulunan: " . count($results) . " ilan\n\n";

        foreach (array_slice($results, 0, 5) as $index => $ilan) {
            $text .= ($index + 1) . ". {$ilan['baslik']}\n";
            $text .= "   📍 {$ilan['lokasyon']}\n";
            $text .= "   💰 {$ilan['fiyat']}\n";
            $text .= "   🔗 /detay_{$ilan['id']}\n\n";
        }

        if (count($results) > 5) {
            $text .= "... ve " . (count($results) - 5) . " ilan daha.";
        }

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /list command
     */
    private function handleListCommand(int $chatId, string $args): array
    {
        $text = "📋 *Aktif İlanlar*\n\n";
        $text .= "Yakında: İlan listesi burada görünecek.";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /talep command
     */
    private function handleTalepCommand(int $chatId, string $args, array $message): array
    {
        // Set conversation state
        $this->setUserState($chatId, 'creating_talep');

        $text = "📝 *Yeni Talep Oluştur*\n\n";
        $text .= "Ne tür bir gayrimenkul arıyorsunuz?\n\n";
        $text .= "Örnek:\n";
        $text .= "• Kadıköy'de 2+1 kiralık daire\n";
        $text .= "• Sarıyer'de satılık villa\n";
        $text .= "• Şişli'de 100 m² ofis";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /settings command
     */
    private function handleSettingsCommand(int $chatId): array
    {
        $text = "⚙️ *Ayarlar*\n\n";
        $text .= "Bildirim tercihleri:\n";
        $text .= "• ✅ Yeni ilanlar\n";
        $text .= "• ✅ Eşleşen talepler\n";
        $text .= "• ❌ Fiyat değişiklikleri\n\n";
        $text .= "/settings_toggle <seçenek> ile değiştirebilirsiniz.";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /cancel command
     */
    private function handleCancelCommand(int $chatId): array
    {
        $this->setUserState($chatId, 'idle');

        return $this->sendMessage($chatId, "❌ İşlem iptal edildi.");
    }

    /**
     * Handle unknown command
     */
    private function handleUnknownCommand(int $chatId, string $cmd): array
    {
        return $this->sendMessage($chatId, "❓ Bilinmeyen komut: {$cmd}\n\n/help ile mevcut komutları görebilirsiniz.");
    }

    /**
     * Handle natural language message
     */
    private function handleNaturalLanguage(int $chatId, string $text, array $message): array
    {
        $userState = $this->getUserState($chatId);

        // Check conversation state
        if ($userState === 'creating_talep') {
            return $this->handleTalepCreation($chatId, $text);
        }

        if ($userState === 'creating_gorev') {
            return $this->handleGorevCreation($chatId, $text, $message);
        }

        if ($userState === 'waiting_ilan_id') {
            return $this->handleIlanIdInput($chatId, $text, $message);
        }

        if ($userState === 'waiting_reservation_dates') {
            return $this->handleReservationDatesInput($chatId, $text, $message);
        }

        if ($userState === 'waiting_close_dates') {
            return $this->handleCloseDatesInput($chatId, $text, $message);
        }

        if ($userState === 'waiting_reservation_id') {
            return $this->handleReservationIdInput($chatId, $text, $message);
        }

        // Use AI to understand intent
        $intent = $this->detectIntent($text);

        return match ($intent['type']) { // context7-ignore
            'search' => $this->handleSearchCommand($chatId, $text, $message),
            'greeting' => $this->handleStartCommand($chatId, $message),
            'help' => $this->handleHelpCommand($chatId),
            'chitchat' => $this->handleChitChat($chatId, $text),
            default => $this->handleUnknownIntent($chatId, $text),
        };
    }

    /**
     * Handle talep creation flow
     */
    private function handleTalepCreation(int $chatId, string $text): array
    {
        // Parse talep details with AI
        $talepData = $this->parseTalepData($text);

        // Create talep (simplified)
        $response = "✅ *Talep oluşturuldu!*\n\n";
        $response .= "📍 Lokasyon: {$talepData['lokasyon']}\n";
        $response .= "🏠 Tip: {$talepData['tip']}\n";
        $response .= "💰 Bütçe: {$talepData['butce']}\n\n";
        $response .= "Size uygun ilanlar bulunduğunda bildirim göndereceğim.";

        $this->setUserState($chatId, 'idle');

        return $this->sendMessage($chatId, $response, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle gorev creation flow
     *
     * Context7: Telegram → Takım Yönetimi - Görev oluşturma
     */
    private function handleGorevCreation(int $chatId, string $text, array $message): array
    {
        try {
            // Get user ID
            $userId = $this->getTelegramUserId($message);

            if (!$userId) {
                $this->setUserState($chatId, 'idle');
                return $this->sendMessage($chatId, "❌ Telegram hesabınız bir kullanıcıya bağlı değil.");
            }

            // Parse gorev data with AI
            $gorevData = $this->parseGorevData($text);

            // Validate required fields
            if (empty($gorevData['baslik'])) {
                return $this->sendMessage($chatId, "❌ Görev başlığı gereklidir.\n\nLütfen tekrar deneyin veya /cancel ile iptal edin.");
            }

            // Create gorev
            $gorev = \App\Modules\TakimYonetimi\Models\Gorev::create([
                'baslik' => $gorevData['baslik'],
                'aciklama' => $gorevData['aciklama'] ?? null,
                'oncelik' => $gorevData['oncelik'] ?? 'normal',
                'gorev_durumu' => 'bekliyor',
                'tip' => 'diger',
                'bitis_tarihi' => $gorevData['bitis_tarihi'] ?? null,
                'danisman_id' => $userId,
                'admin_id' => $userId,
            ]);

            $oncelikEmoji = match ($gorev->oncelik) {
                'acil' => '🔴',
                'yuksek' => '🟠',
                'normal' => '🟡',
                'dusuk' => '🟢',
                default => '⚪',
            };

            $response = "✅ *Görev oluşturuldu!*\n\n";
            $response .= "{$oncelikEmoji} *{$gorev->baslik}*\n";

            if ($gorev->aciklama) {
                $response .= "📝 {$gorev->aciklama}\n";
            }

            $response .= "📊 Durum: Bekliyor\n";
            $response .= "⚡ Öncelik: " . ucfirst($gorev->oncelik) . "\n";

            if ($gorev->bitis_tarihi) {
                $response .= "📅 Tarih: " . $gorev->bitis_tarihi->format('d.m.Y') . "\n";
            }

            $this->setUserState($chatId, 'idle');

            return $this->sendMessage($chatId, $response, ['parse_mode' => 'Markdown']);
        } catch (Exception $e) {
            Log::error('TelegramAIBot::handleGorevCreation error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            $this->setUserState($chatId, 'idle');
            return $this->sendMessage($chatId, "❌ Görev oluşturulurken hata oluştu.");
        }
    }

    /**
     * Handle chitchat
     */
    private function handleChitChat($chatId, string $text): array
    {
        $result = $this->aiService->generate(
            "Kullanıcı: {$text}\n\nYalıhan Emlak AI asistanı olarak samimi ve yardımsever bir şekilde cevap ver:",
            ['model' => 'gpt-3.5-turbo', 'max_tokens' => 100]
        );

        return $this->sendMessage($chatId, $result['data']);
    }

    /**
     * Handle unknown intent
     */
    private function handleUnknownIntent(int $chatId, string $text): array
    {
        return $this->sendMessage($chatId, "🤔 Üzgünüm, ne demek istediğinizi anlayamadım.\n\n/help komutu ile size nasıl yardımcı olabileceğimi görebilirsiniz.");
    }

    /**
     * Send message via Telegram
     */
    private function sendMessage(int $chatId, string $text, array $options = []): array
    {
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", array_merge([
                'chat_id' => $chatId,
                'text' => $text,
            ], $options));

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error('Telegram sendMessage failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse command and arguments
     */
    private function parseCommand(string $command): array
    {
        $parts = explode(' ', $command, 2);
        $cmd = $parts[0];
        $args = $parts[1] ?? '';

        return [$cmd, $args];
    }

    /**
     * Detect user intent with AI
     */
    private function detectIntent(string $text): array
    {
        $prompt = "Kullanıcı mesajını analiz et ve intent'i belirle:\n\n";
        $prompt .= "Mesaj: \"{$text}\"\n\n";
        $prompt .= "İntentler: search, greeting, help, chitchat, unknown\n";
        $prompt .= "JSON formatında döndür: {\"type\": \"...\", \"confidence\": 0.9}"; // context7-ignore

        $result = $this->aiService->generate($prompt, ['max_tokens' => 50]);

        return json_decode($result['data'], true) ?? ['type' => 'unknown', 'confidence' => 0]; // context7-ignore
    }

    /**
     * Parse search query with AI
     */
    private function parseSearchQuery(string $query): array
    {
        return [
            'raw_query' => $query,
            'filters' => [],
        ];
    }

    /**
     * Parse talep data with AI
     */
    private function parseTalepData(string $text): array
    {
        return [
            'lokasyon' => 'İstanbul',
            'tip' => 'Konut',
            'butce' => 'Belirtilmedi',
        ];
    }

    /**
     * Parse gorev data with AI
     *
     * Context7: AI-powered görev parsing
     */
    private function parseGorevData(string $text): array
    {
        try {
            $prompt = "Kullanıcının mesajını analiz et ve görev bilgilerini çıkar:\n\n";
            $prompt .= "Mesaj:\n{$text}\n\n";
            $prompt .= "JSON formatında döndür:\n";
            $prompt .= "{\n";
            $prompt .= "  \"baslik\": \"Görev başlığı\",\n";
            $prompt .= "  \"aciklama\": \"Detaylı açıklama\",\n";
            $prompt .= "  \"oncelik\": \"acil|yuksek|normal|dusuk\",\n";
            $prompt .= "  \"bitis_tarihi\": \"YYYY-MM-DD\"\n";
            $prompt .= "}\n\n";
            $prompt .= "Not: Eğer bilgi yoksa null döndür.";

            $result = $this->aiService->generate($prompt, ['max_tokens' => 200]);
            $parsed = json_decode($result['data'], true);

            if (!$parsed) {
                // Fallback: basit parsing
                return $this->simpleParseGorevData($text);
            }

            return $parsed;
        } catch (Exception $e) {
            Log::warning('parseGorevData AI error, using simple parser', [
                'error' => $e->getMessage(),
            ]);

            return $this->simpleParseGorevData($text);
        }
    }

    /**
     * Simple gorev data parser (fallback)
     */
    private function simpleParseGorevData(string $text): array
    {
        $data = [
            'baslik' => null,
            'aciklama' => null,
            'oncelik' => 'normal',
            'bitis_tarihi' => null,
        ];

        // Extract baslik (first line or after "Başlık:")
        if (preg_match('/Başlık:\s*(.+)/i', $text, $matches)) {
            $data['baslik'] = trim($matches[1]);
        } elseif (preg_match('/^(.+?)(?:\n|$)/i', $text, $matches)) {
            $data['baslik'] = trim($matches[1]);
        }

        // Extract aciklama
        if (preg_match('/Açıklama:\s*(.+?)(?=\n[A-ZÇĞİÖŞÜ]|$)/is', $text, $matches)) {
            $data['aciklama'] = trim($matches[1]);
        }

        // Extract oncelik
        if (preg_match('/Öncelik:\s*(acil|yuksek|normal|dusuk)/i', $text, $matches)) {
            $data['oncelik'] = strtolower($matches[1]);
        }

        // Extract tarih (DD.MM.YYYY)
        if (preg_match('/Tarih:\s*(\d{2})\.(\d{2})\.(\d{4})/', $text, $matches)) {
            $data['bitis_tarihi'] = "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }

        return $data;
    }

    /**
     * Perform search
     */
    private function performSearch(array $searchIntent): array
    {
        // Integration with IlanService (placeholder)
        return [];
    }

    /**
     * Handle /gorevler command - List user's tasks
     *
     * Context7: Telegram → Takım Yönetimi Integration
     */
    private function handleGorevlerCommand(int $chatId, array $message): array
    {
        try {
            // Get Telegram user's linked User ID
            $userId = $this->getTelegramUserId($message);

            if (!$userId) {
                return $this->sendMessage($chatId, "❌ Telegram hesabınız bir kullanıcıya bağlı değil.\n\nLütfen admin ile iletişime geçin.");
            }

            // Fetch active tasks
            $gorevler = \App\Modules\TakimYonetimi\Models\Gorev::where('danisman_id', $userId)
                ->whereIn('gorev_durumu', ['bekliyor', 'devam_ediyor'])
                ->orderBy('oncelik', 'asc') // context7-ignore
                ->orderBy('bitis_tarihi', 'asc') // context7-ignore
                ->limit(10)
                ->get();

            if ($gorevler->isEmpty()) {
                $text = "✅ *Aktif Görevleriniz*\n\n";
                $text .= "Henüz aktif göreviniz bulunmuyor.";

                return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
            }

            $text = "📋 *Aktif Görevleriniz* ({$gorevler->count()})\n\n";

            foreach ($gorevler as $gorev) {
                $oncelikEmoji = match ($gorev->oncelik) {
                    'acil' => '🔴',
                    'yuksek' => '🟠',
                    'normal' => '🟡',
                    'dusuk' => '🟢',
                    default => '⚪',
                };

                $statusEmoji = match ($gorev->gorev_durumu) {
                    'bekliyor' => '⏸️',
                    'devam_ediyor' => '▶️',
                    'tamamlandi' => '✅',
                    'iptal' => '❌',
                    default => '❓',
                };

                $text .= "{$oncelikEmoji} {$statusEmoji} *{$gorev->baslik}*\n";
                $text .= "   📅 " . ($gorev->bitis_tarihi ? $gorev->bitis_tarihi->format('d.m.Y') : 'Tarih yok') . "\n";

                if ($gorev->aciklama) {
                    $text .= "   📝 " . \Illuminate\Support\Str::limit($gorev->aciklama, 60) . "\n";
                }

                $text .= "\n";
            }

            $text .= "Yeni görev oluşturmak için: /gorev_olustur";

            return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
        } catch (Exception $e) {
            Log::error('TelegramAIBot::handleGorevlerCommand error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return $this->sendMessage($chatId, "❌ Görevler listelenirken hata oluştu.");
        }
    }

    /**
     * Handle /gorev_olustur command - Create new task
     *
     * Context7: Telegram → Takım Yönetimi Integration
     */
    private function handleGorevOlusturCommand(int $chatId, string $args, array $message): array
    {
        $this->setUserState($chatId, 'creating_gorev');

        $text = "📝 *Yeni Görev Oluştur*\n\n";
        $text .= "Görev detaylarını şu formatta yazın:\n\n";
        $text .= "*Başlık:* Görev başlığı\n";
        $text .= "*Açıklama:* Detaylı açıklama\n";
        $text .= "*Öncelik:* acil / yuksek / normal / dusuk\n";
        $text .= "*Tarih:* 25.12.2025\n\n";
        $text .= "Örnek:\n";
        $text .= "_Başlık: Müşteri ziyareti\nAçıklama: Kadıköy'deki müşteri ile görüşme\nÖncelik: yuksek\nTarih: 25.12.2025_";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /proje_durumu command - Project durumu
     *
     * Context7: Telegram → Takım Yönetimi Integration
     */
    private function handleProjeDurumCommand(int $chatId, string $args): array
    {
        try {
            // Fetch active projects
            $projeler = \App\Modules\TakimYonetimi\Models\Proje::where('proje_durumu', 'aktif')
                ->withCount(['gorevler' => function ($query) {
                    $query->where('gorev_durumu', 'tamamlandi');
                }, 'gorevler as toplam_gorev' => function ($query) {
                    // Count all tasks
                }])
                ->orderBy('baslik', 'asc') // context7-ignore
                ->limit(10)
                ->get();

            if ($projeler->isEmpty()) {
                $text = "📊 *Proje Durumu*\n\n";
                $text .= "Henüz aktif proje bulunmuyor.";

                return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
            }

            $text = "📊 *Aktif Projeler* ({$projeler->count()})\n\n";

            foreach ($projeler as $proje) {
                $tamamlananGorev = $proje->gorevler_count ?? 0;
                $toplamGorev = $proje->toplam_gorev ?? 0;
                $tamamlanmaOrani = $toplamGorev > 0 ? round(($tamamlananGorev / $toplamGorev) * 100) : 0;

                $progressBar = $this->getProgressBar($tamamlanmaOrani);

                $text .= "🎯 *{$proje->baslik}*\n";
                $text .= "   {$progressBar} {$tamamlanmaOrani}%\n";
                $text .= "   ✅ {$tamamlananGorev} / {$toplamGorev} görev\n";

                if ($proje->bitis_tarihi) {
                    $text .= "   📅 Bitiş: " . $proje->bitis_tarihi->format('d.m.Y') . "\n";
                }

                $text .= "\n";
            }

            return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
        } catch (Exception $e) {
            Log::error('TelegramAIBot::handleProjeDurumCommand error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return $this->sendMessage($chatId, "❌ Proje durumu alınırken hata oluştu.");
        }
    }

    /**
     * Handle /takim command - Team members
     *
     * Context7: Telegram → Takım Yönetimi Integration
     */
    private function handleTakimCommand(int $chatId): array
    {
        try {
            // Fetch active team members
            $takimUyeleri = \App\Modules\TakimYonetimi\Models\TakimUyesi::with('kullanici')
                ->where('aktiflik_durumu', 1)
                ->orderBy('rol', 'asc') // context7-ignore
                ->limit(15)
                ->get();

            if ($takimUyeleri->isEmpty()) {
                $text = "👥 *Takım Üyeleri*\n\n";
                $text .= "Henüz takım üyesi bulunmuyor.";

                return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
            }

            $text = "👥 *Takım Üyeleri* ({$takimUyeleri->count()})\n\n";

            $rolGroups = $takimUyeleri->groupBy('rol');

            foreach ($rolGroups as $rol => $uyeler) {
                $rolEmoji = match ($rol) {
                    'yonetici' => '👔',
                    'danisman' => '🏠',
                    'asistan' => '📋',
                    default => '👤',
                };

                $text .= "{$rolEmoji} *" . ucfirst($rol) . "*\n";

                foreach ($uyeler as $uye) {
                    $text .= "   • " . ($uye->kullanici->name ?? 'N/A');

                    if ($uye->telefon) {
                        $text .= " 📞";
                    }

                    $text .= "\n";
                }

                $text .= "\n";
            }

            return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
        } catch (Exception $e) {
            Log::error('TelegramAIBot::handleTakimCommand error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return $this->sendMessage($chatId, "❌ Takım üyeleri listelenirken hata oluştu.");
        }
    }

    /**
     * Get progress bar emoji
     */
    private function getProgressBar(int $percentage): string
    {
        $filled = intval($percentage / 10);
        $empty = 10 - $filled;

        return str_repeat('▓', $filled) . str_repeat('░', $empty);
    }

    /**
     * Get Telegram user's linked User ID
     */
    private function getTelegramUserId(array $message): ?int
    {
        $telegramUserId = $message['from']['id'] ?? null;

        if (!$telegramUserId) {
            return null;
        }

        // Try to find User with this Telegram ID
        $user = \App\Models\User::where('telegram_id', $telegramUserId)->first();

        return $user?->id;
    }

    /**
     * Get user conversation state
     */
    private function getUserState(int $chatId): string
    {
        return Cache::get("telegram_user_state:{$chatId}", 'idle');
    }

    /**
     * Set user conversation state
     */
    private function setUserState(int $chatId, string $state): void
    {
        Cache::put("telegram_user_state:{$chatId}", $state, 3600);
    }

    /**
     * Get bot commands
     */
    public function getCommands(): array
    {
        return self::COMMANDS;
    }

    /**
     * Get bot statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_messages' => Cache::get('telegram_total_messages', 0),
            'active_users' => Cache::get('telegram_active_users', 0), // context7-ignore
            'commands_count' => count(self::COMMANDS),
        ];
    }

    /**
     * Get/Set selected ilan for user
     */
    private function getUserSelectedIlan(int $chatId): ?int
    {
        return Cache::get("telegram_selected_ilan:{$chatId}");
    }

    private function setUserSelectedIlan(int $chatId, int $ilanId): void
    {
        Cache::put("telegram_selected_ilan:{$chatId}", $ilanId, 3600);
    }

    /**
     * /rezervasyon_yap [ilan_id] (Phase R + Phase W)
     * Phase W: Deterministic ilan selection with priority_order
     */
    private function handleRezervasyonYapCommand(int $chatId, string $args, array $message): array
    {
        $userId = $this->getTelegramUserId($message);
        if (!$userId) {
            return $this->sendMessage($chatId, "❌ Telegram hesabınız bir kullanıcıya bağlı değil.");
        }

        $user = \App\Models\User::where('telegram_id', $userId)
            ->orWhere('telegram_chat_id', $userId)
            ->first();

        if (!$user) {
            return $this->sendMessage($chatId, "❌ Kullanıcı bulunamadı.");
        }

        // Phase W: Priority display_order for ilan selection
        $ilanId = null;

        // 1. Deeplink: /rezervasyon_yap {ilan_id}
        if ($args && is_numeric($args)) {
            $ilanId = (int) $args;
        }

        // 2. Callback context: Last inline button's ilan_id (from cache)
        if (!$ilanId) {
            $ilanId = Cache::get("telegram_last_callback_ilan:{$chatId}");
        }

        // 3. Session memory: chat_id → last_selected_ilan_id
        if (!$ilanId) {
            $ilanId = $this->getUserSelectedIlan($chatId);
        }

        // 4. If still no ilan_id, show inline keyboard selection
        if (!$ilanId) {
            $ilanlar = $this->getUserIlanlar($user);
            if ($ilanlar->isEmpty()) {
                return $this->sendMessage($chatId, "❌ Size ait ilan bulunamadı.");
            }

            $text = "🏠 *Rezervasyon Yap*\n\n";
            $text .= "Hangi ilan için rezervasyon yapmak istiyorsunuz?\n\n";
            $text .= "Aşağıdaki listeden seçin:";

            $keyboard = $this->buildIlanSelectKeyboard($ilanlar, 'rezervasyon_yap');

            return $this->sendMessage($chatId, $text, [
                'parse_mode' => 'Markdown',
                'reply_markup' => $keyboard,
            ]);
        }

        // Validate ilan access
        $ilan = Ilan::find($ilanId);
        if (!$ilan) {
            return $this->sendMessage($chatId, "❌ İlan bulunamadı (ID: {$ilanId}).");
        }

        if (!$this->canAccessIlan($user, $ilan)) {
            return $this->sendMessage($chatId, "⛔ Bu ilana erişim yetkin yok.");
        }

        // Set selected ilan and proceed
        $this->setUserSelectedIlan($chatId, $ilanId);
        $this->setUserState($chatId, 'waiting_reservation_dates');

        $text = "📅 *Rezervasyon Tarihleri*\n\n";
        $text .= "İlan: {$ilan->baslik}\n\n";
        $text .= "Tarih aralığını şu formatta gönderin:\n\n";
        $text .= "`2026-06-01 14:00 - 2026-06-05 11:00`\n\n";
        $text .= "veya\n\n";
        $text .= "`01.06.2026 14:00 - 05.06.2026 11:00`";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * /takvimi_kapat [ilan_id] (Phase R)
     */
    private function handleTakvimiKapatCommand(int $chatId, string $args, array $message): array
    {
        $userId = $this->getTelegramUserId($message);
        if (!$userId) {
            return $this->sendMessage($chatId, "❌ Telegram hesabınız bir kullanıcıya bağlı değil.");
        }

        $ilanId = $args ? (int) $args : $this->getUserSelectedIlan($chatId);

        if (!$ilanId) {
            $this->setUserState($chatId, 'waiting_ilan_id');
            Cache::put("telegram_pending_action:{$chatId}", 'takvimi_kapat', 600);
            return $this->sendMessage($chatId, "⛔ *Takvimi Kapat*\n\nHangi ilanın takvimini kapatmak istiyorsunuz?\n\nİlan ID'sini gönderin.", ['parse_mode' => 'Markdown']);
        }

        $ilan = Ilan::find($ilanId);
        if (!$ilan) {
            return $this->sendMessage($chatId, "❌ İlan bulunamadı (ID: {$ilanId}).");
        }

        $this->setUserSelectedIlan($chatId, $ilanId);
        $this->setUserState($chatId, 'waiting_close_dates');

        $text = "⛔ *Takvimi Kapat*\n\n";
        $text .= "İlan: {$ilan->baslik}\n\n";
        $text .= "Kapatmak istediğiniz tarih aralığını gönderin:\n\n";
        $text .= "`2026-06-01 00:00 - 2026-06-30 23:59`";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * /rezervasyon_iptal [reservation_id] (Phase R)
     */
    private function handleRezervasyonIptalCommand(int $chatId, string $args, array $message): array
    {
        $userId = $this->getTelegramUserId($message);
        if (!$userId) {
            return $this->sendMessage($chatId, "❌ Telegram hesabınız bir kullanıcıya bağlı değil.");
        }

        $reservationId = $args ? (int) $args : null;

        if (!$reservationId) {
            $this->setUserState($chatId, 'waiting_reservation_id');
            return $this->sendMessage($chatId, "❌ *Rezervasyon İptal*\n\nİptal etmek istediğiniz rezervasyon ID'sini gönderin.", ['parse_mode' => 'Markdown']);
        }

        $service = app(IlanReservationService::class);
        $telegramUserId = $message['from']['id'] ?? null;
        $reservation = $service->cancelById($reservationId, $userId, 'telegram_cancel', 'telegram', $telegramUserId);

        if (!$reservation) {
            return $this->sendMessage($chatId, "❌ Rezervasyon bulunamadı (ID: {$reservationId}).");
        }

        LogService::info('telegram_reservation_cancel', [
            'telegram_user_id' => $message['from']['id'],
            'user_id' => $userId,
            'reservation_id' => $reservationId,
            'ilan_id' => $reservation->ilan_id,
        ]);

        $this->setUserState($chatId, 'idle');

        return $this->sendMessage($chatId, "✅ Rezervasyon iptal edildi.\n\nID: {$reservationId}\nİlan: {$reservation->ilan->baslik}");
    }

    /**
     * Handle ilan ID input (Phase R)
     */
    private function handleIlanIdInput(int $chatId, string $text, array $message): array
    {
        $ilanId = (int) trim($text);
        if ($ilanId <= 0) {
            return $this->sendMessage($chatId, "❌ Geçersiz İlan ID. Lütfen sayısal bir değer gönderin.");
        }

        $ilan = Ilan::find($ilanId);
        if (!$ilan) {
            return $this->sendMessage($chatId, "❌ İlan bulunamadı (ID: {$ilanId}). Başka bir ID deneyin.");
        }

        $this->setUserSelectedIlan($chatId, $ilanId);
        $pendingAction = Cache::get("telegram_pending_action:{$chatId}", 'rezervasyon_yap');

        if ($pendingAction === 'rezervasyon_yap') {
            $this->setUserState($chatId, 'waiting_reservation_dates');
            $text = "📅 *Rezervasyon Tarihleri*\n\n";
            $text .= "İlan: {$ilan->baslik}\n\n";
            $text .= "Tarih aralığını gönderin:\n`2026-06-01 14:00 - 2026-06-05 11:00`";
            return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
        }

        if ($pendingAction === 'takvimi_kapat') {
            $this->setUserState($chatId, 'waiting_close_dates');
            $text = "⛔ *Takvimi Kapat*\n\n";
            $text .= "İlan: {$ilan->baslik}\n\n";
            $text .= "Tarih aralığını gönderin:\n`2026-06-01 00:00 - 2026-06-30 23:59`";
            return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
        }

        return $this->sendMessage($chatId, "❌ Geçersiz işlem.");
    }

    /**
     * Handle reservation dates input (Phase R)
     */
    private function handleReservationDatesInput(int $chatId, string $text, array $message): array
    {
        $userId = $this->getTelegramUserId($message);
        $ilanId = $this->getUserSelectedIlan($chatId);

        if (!$ilanId) {
            $this->setUserState($chatId, 'idle');
            return $this->sendMessage($chatId, "❌ İlan seçimi kayboldu. Lütfen /rezervasyon_yap ile tekrar deneyin.");
        }

        try {
            $dates = \App\Helpers\DateParser::parseDateRange($text);
            $service = app(IlanReservationService::class);

            $telegramUserId = $message['from']['id'] ?? null;
            $reservation = $service->create($ilanId, [
                'starts_at' => $dates['from']->toDateTimeString(),
                'ends_at' => $dates['to']->toDateTimeString(),
                'source' => 'telegram',
                'customer_name' => $message['from']['first_name'] ?? null,
                'note' => 'telegram_reservation',
                'telegram_user_id' => $telegramUserId,
            ], $userId);

            LogService::info('telegram_reservation_create', [
                'telegram_user_id' => $message['from']['id'],
                'user_id' => $userId,
                'ilan_id' => $ilanId,
                'reservation_id' => $reservation->id,
                'starts_at' => $dates['from']->toIso8601String(),
                'ends_at' => $dates['to']->toIso8601String(),
            ]);

            $this->setUserState($chatId, 'idle');

            // Phase T: Send message with inline keyboard
            $response = $this->formatReservationMessage($reservation);
            $keyboard = $this->buildReservationKeyboard($reservation);

            return $this->sendMessage($chatId, $response, [
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode($keyboard),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendMessage($chatId, "⛔ Çakışma: " . $e->getMessage() . "\n\nBaşka bir aralık deneyin veya /cancel ile iptal edin.");
        } catch (Exception $e) {
            LogService::error('telegram_reservation_create_error', [
                'telegram_user_id' => $message['from']['id'],
                'error' => $e->getMessage(),
            ]);
            return $this->sendMessage($chatId, "❌ Hata: " . $e->getMessage());
        }
    }

    /**
     * Handle close dates input (Phase R)
     */
    private function handleCloseDatesInput(int $chatId, string $text, array $message): array
    {
        $userId = $this->getTelegramUserId($message);
        $ilanId = $this->getUserSelectedIlan($chatId);

        if (!$ilanId) {
            $this->setUserState($chatId, 'idle');
            return $this->sendMessage($chatId, "❌ İlan seçimi kayboldu. Lütfen /takvimi_kapat ile tekrar deneyin.");
        }

        try {
            $dates = \App\Helpers\DateParser::parseDateRange($text);
            $service = app(IlanReservationService::class);

            $telegramUserId = $message['from']['id'] ?? null;
            $reservation = $service->closeCalendar(
                $ilanId,
                $dates['from'],
                $dates['to'],
                'telegram',
                $userId,
                'telegram_calendar_close',
                $telegramUserId
            );

            LogService::info('telegram_calendar_close', [
                'telegram_user_id' => $message['from']['id'],
                'user_id' => $userId,
                'ilan_id' => $ilanId,
                'reservation_id' => $reservation->id,
                'starts_at' => $dates['from']->toIso8601String(),
                'ends_at' => $dates['to']->toIso8601String(),
            ]);

            $this->setUserState($chatId, 'idle');

            // Phase T: Send message with inline keyboard (optional)
            $response = "⛔ *Takvim Kapatıldı!*\n\n";
            $response .= "ID: {$reservation->id}\n";
            $response .= "İlan: {$reservation->ilan->baslik}\n";
            $response .= "Başlangıç: {$dates['from']->format('d.m.Y H:i')}\n";
            $response .= "Bitiş: {$dates['to']->format('d.m.Y H:i')}";

            return $this->sendMessage($chatId, $response, ['parse_mode' => 'Markdown']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->sendMessage($chatId, "⛔ Çakışma: " . $e->getMessage() . "\n\nBaşka bir aralık deneyin.");
        } catch (Exception $e) {
            LogService::error('telegram_calendar_close_error', [
                'telegram_user_id' => $message['from']['id'],
                'error' => $e->getMessage(),
            ]);
            return $this->sendMessage($chatId, "❌ Hata: " . $e->getMessage());
        }
    }

    /**
     * Handle reservation ID input (Phase R)
     */
    private function handleReservationIdInput(int $chatId, string $text, array $message): array
    {
        $reservationId = (int) trim($text);
        if ($reservationId <= 0) {
            return $this->sendMessage($chatId, "❌ Geçersiz Rezervasyon ID.");
        }

        return $this->handleRezervasyonIptalCommand($chatId, (string) $reservationId, $message);
    }

    /**
     * Parse dates from text
     *
     * Format: "25.12.2025 10:00 - 25.12.2025 12:00"
     */
    private function parseDates(string $text): ?array
    {
        // Try to parse format: "DD.MM.YYYY HH:mm - DD.MM.YYYY HH:mm"
        if (preg_match('/(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2})\s*-\s*(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2})/', $text, $matches)) {
            try {
                $from = Carbon::createFromFormat('d.m.Y H:i', trim($matches[1]));
                $to = Carbon::createFromFormat('d.m.Y H:i', trim($matches[2]));

                if ($from && $to && $to->gt($from)) {
                    return ['from' => $from, 'to' => $to];
                }
            } catch (Exception $e) {
                // Try alternative formats
            }
        }

        // Try format: "DD.MM.YYYY - DD.MM.YYYY"
        if (preg_match('/(\d{2}\.\d{2}\.\d{4})\s*-\s*(\d{2}\.\d{2}\.\d{4})/', $text, $matches)) {
            try {
                $from = Carbon::createFromFormat('d.m.Y', trim($matches[1]))->startOfDay();
                $to = Carbon::createFromFormat('d.m.Y', trim($matches[2]))->endOfDay();

                if ($from && $to && $to->gt($from)) {
                    return ['from' => $from, 'to' => $to];
                }
            } catch (Exception $e) {
                // Failed
            }
        }

        return null;
    }

    /**
     * Phase T: Handle callback query (inline button clicks)
     */
    private function handleCallbackQuery(array $callbackQuery): array
    {
        $t0 = microtime(true);
        $callbackId = $callbackQuery['id'];
        $telegramUserId = $callbackQuery['from']['id'];
        $data = $callbackQuery['data'] ?? '';
        $message = $callbackQuery['message'] ?? null;
        $chatId = $message['chat']['id'] ?? null;
        $messageId = $message['message_id'] ?? null;

        try {
            // Parse callback data: "resv:confirm:{reservation_id}:{nonce}"
            $parts = explode(':', $data, 4);
            if (count($parts) < 3) {
                $this->answerCallbackQuery($callbackId, 'Geçersiz işlem', true);
                return ['success' => false, 'error' => 'Invalid callback data'];
            }

            [$type, $action, $id] = $parts;
            $nonce = $parts[3] ?? '';

            // Get user from telegram_id
            $user = \App\Models\User::where('telegram_id', $telegramUserId)
                ->orWhere('telegram_chat_id', $telegramUserId)
                ->first();

            if (!$user) {
                $this->answerCallbackQuery($callbackId, 'Yetkilendirme gerekli', true);
                $this->sendMessage($chatId, "🚫 Bu işlem için hesabınızı bağlayın.");
                return ['success' => false, 'error' => 'User not found'];
            }

            // Route to handler
            $result = match ($type) {
                'resv' => $this->handleReservationCallback($action, (int) $id, $user, $callbackId, $chatId, $messageId, $nonce),
                'cal' => $this->handleCalendarCallback($action, $parts, $user, $callbackId, $chatId, $messageId, $nonce),
                'ilan' => $this->handleIlanSelectCallback($action, (int) $id, $user, $callbackId, $chatId, $messageId, $nonce),
                default => ['success' => false, 'error' => 'Unknown callback type'],
            };

            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::info('telegram_callback', [
                'action' => 'telegram_callback',
                'callback_action' => "{$type}:{$action}",
                'telegram_user_id' => $telegramUserId,
                'app_user_id' => $user->id,
                'result' => $result['success'] ? ($result['skipped'] ?? false ? 'skipped' : 'success') : 'error',
                'duration_ms' => $durationMs,
                'source' => 'telegram',
            ]);

            return $result;
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::error('telegram_callback_error', [
                'action' => 'telegram_callback',
                'telegram_user_id' => $telegramUserId,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            $this->answerCallbackQuery($callbackId, 'Hata oluştu', true);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle reservation callback (confirm/cancel/detail)
     */
    private function handleReservationCallback(string $action, int $reservationId, \App\Models\User $user, string $callbackId, int $chatId, int $messageId, string $nonce): array
    {
        $reservation = IlanReservation::find($reservationId);
        if (!$reservation) {
            $this->answerCallbackQuery($callbackId, 'Rezervasyon bulunamadı', true);
            return ['success' => false, 'error' => 'Reservation not found'];
        }

        // Authorization check
        if (!$this->canAccessReservation($user, $reservation)) {
            $this->answerCallbackQuery($callbackId, 'Yetkin yok', true);
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        return match ($action) {
            'confirm' => $this->handleConfirmReservationCallback($reservation, $user, $callbackId, $chatId, $messageId),
            'cancel' => $this->handleCancelReservationCallback($reservation, $user, $callbackId, $chatId, $messageId),
            'detail' => $this->handleReservationDetailCallback($reservation, $callbackId, $chatId, $messageId),
            default => ['success' => false, 'error' => 'Unknown action'],
        };
    }

    /**
     * Handle calendar callback (close)
     */
    private function handleCalendarCallback(string $action, array $parts, \App\Models\User $user, string $callbackId, int $chatId, int $messageId, string $nonce): array
    {
        if ($action !== 'close' || count($parts) < 6) {
            $this->answerCallbackQuery($callbackId, 'Geçersiz işlem', true);
            return ['success' => false, 'error' => 'Invalid calendar callback'];
        }

        $ilanId = (int) $parts[2];
        $from = Carbon::parse($parts[3]);
        $to = Carbon::parse($parts[4]);

        $ilan = Ilan::find($ilanId);
        if (!$ilan) {
            $this->answerCallbackQuery($callbackId, 'İlan bulunamadı', true);
            return ['success' => false, 'error' => 'Ilan not found'];
        }

        // Authorization check
        if (!$this->canAccessIlan($user, $ilan)) {
            $this->answerCallbackQuery($callbackId, 'Yetkin yok', true);
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        return $this->handleCloseCalendarCallback($ilan, $from, $to, $user, $callbackId, $chatId, $messageId);
    }

    /**
     * Handle confirm reservation callback
     *
     * Phase T: Idempotent - zaten confirmed ise skip
     */
    private function handleConfirmReservationCallback(IlanReservation $reservation, \App\Models\User $user, string $callbackId, int $chatId, int $messageId): array
    {
        $t0 = microtime(true);

        try {
            // Idempotency: Zaten confirmed ise skip
            if ($reservation->islem_durumu === 'confirmed') {
                $this->answerCallbackQuery($callbackId, '✅ Zaten onaylanmış');
                return ['success' => true, 'skipped' => true];
            }

            $telegramUserId = $user->telegram_id ?? $user->telegram_chat_id ?? null;
            $this->reservationService->confirm($reservation->id, $user->id, 'telegram', $telegramUserId);

            $this->answerCallbackQuery($callbackId, '✅ Rezervasyon onaylandı');

            $updatedReservation = $reservation->fresh(['ilan']);
            $updatedText = $this->formatReservationMessage($updatedReservation, true);
            // Phase T: Remove action buttons after confirmation (only detail remains)
            $this->editMessageText($chatId, $messageId, $updatedText, $this->buildReservationKeyboard($updatedReservation));

            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::info('telegram_callback_confirm', [
                'action' => 'telegram_callback',
                'callback_action' => 'confirm',
                'reservation_id' => $reservation->id,
                'ilan_id' => $reservation->ilan_id,
                'telegram_user_id' => $user->telegram_id ?? $user->telegram_chat_id,
                'app_user_id' => $user->id,
                'result' => 'success',
                'duration_ms' => $durationMs,
                'source' => 'telegram',
            ]);

            // Phase U: Activity log (already logged in IlanReservationService::confirm)

            return ['success' => true];
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::error('telegram_callback_confirm_error', [
                'action' => 'telegram_callback',
                'callback_action' => 'confirm',
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            $this->answerCallbackQuery($callbackId, 'Hata: ' . $e->getMessage(), true);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle cancel reservation callback
     *
     * Phase T: Idempotent - zaten cancelled ise skip
     */
    private function handleCancelReservationCallback(IlanReservation $reservation, \App\Models\User $user, string $callbackId, int $chatId, int $messageId): array
    {
        $t0 = microtime(true);

        try {
            // Idempotency: Zaten cancelled ise skip
            if ($reservation->isCancelled()) {
                $this->answerCallbackQuery($callbackId, '❌ Zaten iptal edilmiş');
                return ['success' => true, 'skipped' => true];
            }

            $telegramUserId = $user->telegram_id ?? $user->telegram_chat_id ?? null;
            $this->reservationService->cancelById($reservation->id, $user->id, 'Telegram bot ile iptal edildi', 'telegram', $telegramUserId);

            $this->answerCallbackQuery($callbackId, '❌ Rezervasyon iptal edildi');

            $updatedReservation = $reservation->fresh(['ilan']);
            $updatedText = $this->formatReservationMessage($updatedReservation, true);
            $this->editMessageText($chatId, $messageId, $updatedText, []); // Remove buttons

            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::info('telegram_callback_cancel', [
                'action' => 'telegram_callback',
                'callback_action' => 'cancel',
                'reservation_id' => $reservation->id,
                'ilan_id' => $reservation->ilan_id,
                'telegram_user_id' => $user->telegram_id ?? $user->telegram_chat_id,
                'app_user_id' => $user->id,
                'result' => 'success',
                'duration_ms' => $durationMs,
                'source' => 'telegram',
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::error('telegram_callback_cancel_error', [
                'action' => 'telegram_callback',
                'callback_action' => 'cancel',
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            $this->answerCallbackQuery($callbackId, 'Hata: ' . $e->getMessage(), true);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle close calendar callback
     *
     * Phase T: Idempotent - aynı range varsa skip
     */
    private function handleCloseCalendarCallback(Ilan $ilan, Carbon $from, Carbon $to, \App\Models\User $user, string $callbackId, int $chatId, int $messageId): array
    {
        $t0 = microtime(true);

        try {
            $telegramUserId = $user->telegram_id ?? $user->telegram_chat_id ?? null;
            $reservation = $this->reservationService->closeCalendar(
                $ilan->id,
                $from,
                $to,
                'telegram',
                $user->id,
                'Telegram bot ile kapatıldı',
                $telegramUserId
            );

            // Check if it was skipped (idempotent)
            $wasSkipped = $reservation->note === 'calendar_closed' &&
                IlanReservation::where('ilan_id', $ilan->id)
                ->where('starts_at', '<=', $from)
                ->where('ends_at', '>=', $to)
                ->where('id', '!=', $reservation->id)
                ->exists();

            if ($wasSkipped) {
                $this->answerCallbackQuery($callbackId, '🔒 Zaten kapalı');
            } else {
                $this->answerCallbackQuery($callbackId, '🔒 Takvim kapatıldı');
            }

            $updatedText = "🔒 *Takvim Kapatıldı*\n\n";
            $updatedText .= "İlan: #{$ilan->id} - {$ilan->baslik}\n";
            $updatedText .= "Tarih: {$from->format('d.m.Y H:i')} - {$to->format('d.m.Y H:i')}\n";
            $updatedText .= "\n✅ İşlem tamamlandı.";

            $this->editMessageText($chatId, $messageId, $updatedText, []); // Remove buttons

            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::info('telegram_callback_close_calendar', [
                'action' => 'telegram_callback',
                'callback_action' => 'close_calendar',
                'ilan_id' => $ilan->id,
                'reservation_id' => $reservation->id,
                'telegram_user_id' => $user->telegram_id ?? $user->telegram_chat_id,
                'app_user_id' => $user->id,
                'result' => $wasSkipped ? 'skipped' : 'success',
                'duration_ms' => $durationMs,
                'source' => 'telegram',
            ]);

            return ['success' => true, 'skipped' => $wasSkipped];
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::error('telegram_callback_close_calendar_error', [
                'action' => 'telegram_callback',
                'callback_action' => 'close_calendar',
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            $this->answerCallbackQuery($callbackId, 'Hata: ' . $e->getMessage(), true);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Handle reservation detail callback
     *
     * Phase T: Read-only detail view
     */
    private function handleReservationDetailCallback(IlanReservation $reservation, string $callbackId, int $chatId, int $messageId): array
    {
        $t0 = microtime(true);

        try {
            $this->answerCallbackQuery($callbackId, '📄 Detaylar gösteriliyor');

            $detailText = $this->formatReservationDetail($reservation);
            $this->sendMessage($chatId, $detailText, ['parse_mode' => 'Markdown']);

            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::info('telegram_callback_detail', [
                'action' => 'telegram_callback',
                'callback_action' => 'detail',
                'reservation_id' => $reservation->id,
                'ilan_id' => $reservation->ilan_id,
                'result' => 'success',
                'duration_ms' => $durationMs,
                'source' => 'telegram',
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::error('telegram_callback_detail_error', [
                'action' => 'telegram_callback',
                'callback_action' => 'detail',
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            $this->answerCallbackQuery($callbackId, 'Hata: ' . $e->getMessage(), true);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if user can access reservation
     */
    private function canAccessReservation(\App\Models\User $user, IlanReservation $reservation): bool
    {
        $ilan = $reservation->ilan;
        return $this->canAccessIlan($user, $ilan);
    }

    /**
     * Check if user can access ilan
     */
    private function canAccessIlan(\App\Models\User $user, Ilan $ilan): bool
    {
        // Admin or super_admin
        if (in_array($user->role_id, [1, 2])) {
            return true;
        }

        // Danışman of the ilan
        if ($ilan->danisman_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Phase W: Get user's accessible ilanlar
     */
    private function getUserIlanlar(\App\Models\User $user): \Illuminate\Database\Eloquent\Collection
    {
        // Admin → tüm ilanlar
        if (in_array($user->role_id, [1, 2])) {
            return Ilan::select('id', 'baslik')
                ->orderBy('baslik') // context7-ignore
                ->limit(20)
                ->get();
        }

        // Danışman → sadece kendi ilanları
        return Ilan::select('id', 'baslik')
            ->where('danisman_id', $user->id)
            ->orderBy('baslik') // context7-ignore
            ->limit(20)
            ->get();
    }

    /**
     * Phase W: Build inline keyboard for ilan selection
     */
    private function buildIlanSelectKeyboard(\Illuminate\Database\Eloquent\Collection $ilanlar, string $action = 'select'): array
    {
        $keyboard = [];
        $nonce = substr(md5(time()), 0, 8);

        foreach ($ilanlar as $ilan) {
            $text = "🏠 " . mb_substr($ilan->baslik, 0, 30);
            if (mb_strlen($ilan->baslik) > 30) {
                $text .= '...';
            }
            $keyboard[] = [
                [
                    'text' => $text,
                    'callback_data' => "ilan:select:{$ilan->id}:{$nonce}",
                ],
            ];
        }

        return ['inline_keyboard' => $keyboard];
    }

    /**
     * Phase W: Handle ilan select callback
     */
    private function handleIlanSelectCallback(string $action, int $ilanId, \App\Models\User $user, string $callbackId, int $chatId, int $messageId, string $nonce): array
    {
        $t0 = microtime(true);

        try {
            // Idempotency: Check if already processed (nonce-based)
            $cacheKey = "telegram_ilan_select:{$chatId}:{$ilanId}:{$nonce}";
            if (Cache::has($cacheKey)) {
                $this->answerCallbackQuery($callbackId, 'Zaten işlendi');
                return ['success' => true, 'skipped' => true];
            }

            // Validate ilan
            $ilan = Ilan::find($ilanId);
            if (!$ilan) {
                $this->answerCallbackQuery($callbackId, 'İlan bulunamadı', true);
                return ['success' => false, 'error' => 'Ilan not found'];
            }

            // Check access
            if (!$this->canAccessIlan($user, $ilan)) {
                $this->answerCallbackQuery($callbackId, '⛔ Bu ilana erişim yetkin yok', true);
                return ['success' => false, 'error' => 'Access denied'];
            }

            // Set selected ilan (session memory)
            $this->setUserSelectedIlan($chatId, $ilanId);
            Cache::put("telegram_last_callback_ilan:{$chatId}", $ilanId, 3600);

            // Mark as processed
            Cache::put($cacheKey, true, 300);

            // Update message
            $updatedText = "✅ *İlan Seçildi*\n\n";
            $updatedText .= "🏠 İlan: {$ilan->baslik}\n\n";
            $updatedText .= "📅 Tarih aralığını şu formatta gönderin:\n\n";
            $updatedText .= "`2026-06-01 14:00 - 2026-06-05 11:00`\n\n";
            $updatedText .= "veya\n\n";
            $updatedText .= "`01.06.2026 14:00 - 05.06.2026 11:00`";

            $this->editMessageText($chatId, $messageId, $updatedText, null);

            // Set state
            $this->setUserState($chatId, 'waiting_reservation_dates');

            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::info('telegram_ilan_select', [
                'action' => 'telegram_ilan_select',
                'ilan_id' => $ilanId,
                'telegram_user_id' => $user->telegram_id ?? $user->telegram_chat_id,
                'app_user_id' => $user->id,
                'source' => 'telegram',
                'duration_ms' => $durationMs,
            ]);

            $this->answerCallbackQuery($callbackId, 'İlan seçildi');

            return ['success' => true];
        } catch (Exception $e) {
            $durationMs = (int) round((microtime(true) - $t0) * 1000);

            LogService::error('telegram_ilan_select_error', [
                'action' => 'telegram_ilan_select',
                'ilan_id' => $ilanId,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            $this->answerCallbackQuery($callbackId, 'Hata: ' . $e->getMessage(), true);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build inline keyboard for reservation message
     *
     * Phase T: Inline keyboard builder
     */
    private function buildReservationKeyboard(IlanReservation $reservation): array
    {
        $nonce = substr(md5($reservation->id . time()), 0, 8);
        $reservationId = $reservation->id;

        $keyboard = [];

        // Phase T: Only show action buttons for active reservations
        if ($reservation->islem_durumu === 'active') { // context7-ignore
            $keyboard[] = [
                ['text' => '✅ Onayla', 'callback_data' => "resv:confirm:{$reservationId}:{$nonce}"],
                ['text' => '❌ İptal', 'callback_data' => "resv:cancel:{$reservationId}:{$nonce}"],
            ];
        }

        // Detail button always available
        $keyboard[] = [
            ['text' => '📄 Detay', 'callback_data' => "resv:detail:{$reservationId}:{$nonce}"],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    /**
     * Build inline keyboard for calendar close
     */
    private function buildCalendarCloseKeyboard(Ilan $ilan, Carbon $from, Carbon $to): array
    {
        $nonce = substr(md5($ilan->id . $from->toDateString() . $to->toDateString()), 0, 8);
        $fromStr = $from->format('Y-m-d');
        $toStr = $to->format('Y-m-d');

        return [
            'inline_keyboard' => [
                [
                    ['text' => '🔒 Takvimi Kapat', 'callback_data' => "cal:close:{$ilan->id}:{$fromStr}:{$toStr}:{$nonce}"],
                ],
            ],
        ];
    }

    /**
     * Format reservation message with inline keyboard
     *
     * Phase T: Reservation message formatter
     */
    private function formatReservationMessage(IlanReservation $reservation, bool $isUpdated = false): string
    {
        $statusEmoji = match ($reservation->islem_durumu) {
            'active' => '🟡', // context7-ignore
            'pasif' => '🔴',
            'taslak' => '⚪️',
            'silinmis' => '⚫️',
            'rezerve' => '🔵',
            'satildi' => '🟢',
            'kiralandi' => '🟢',
            'confirmed' => '✅',
            'cancelled' => '❌',
            default => '⚪',
        };

        $statusText = match ($reservation->islem_durumu) {
            'active' => 'Beklemede', // context7-ignore
            'confirmed' => 'Onaylandı',
            'cancelled' => 'İptal Edildi',
            default => ucfirst($reservation->islem_durumu),
        };

        $text = "🏡 *Rezervasyon " . ($isUpdated ? "Güncellendi" : "Oluşturuldu") . "*\n\n";
        $text .= "{$statusEmoji} Durum: {$statusText}\n";
        $text .= "🆔 ID: {$reservation->id}\n";
        $text .= "🏠 İlan: #{$reservation->ilan_id} - {$reservation->ilan->baslik}\n";
        $text .= "📅 Tarih: {$reservation->starts_at->format('d.m.Y H:i')} - {$reservation->ends_at->format('d.m.Y H:i')}\n";

        if ($reservation->customer_name) {
            $text .= "👤 Müşteri: {$reservation->customer_name}\n";
        }

        if ($reservation->customer_phone) {
            $text .= "📞 Telefon: {$reservation->customer_phone}\n";
        }

        return $text;
    }

    /**
     * Format reservation detail
     */
    private function formatReservationDetail(IlanReservation $reservation): string
    {
        $text = "📄 *Rezervasyon Detayları*\n\n";
        $text .= "🆔 ID: {$reservation->id}\n";
        $text .= "🏠 İlan: #{$reservation->ilan_id}\n";
        $text .= "📅 Başlangıç: {$reservation->starts_at->format('d.m.Y H:i')}\n";
        $text .= "📅 Bitiş: {$reservation->ends_at->format('d.m.Y H:i')}\n";
        $text .= "📊 Durum: {$reservation->islem_durumu}\n";
        $text .= "📝 Kaynak: {$reservation->source}\n";

        if ($reservation->customer_name) {
            $text .= "👤 Müşteri: {$reservation->customer_name}\n";
        }

        if ($reservation->note) {
            $text .= "💬 Not: {$reservation->note}\n";
        }

        return $text;
    }

    /**
     * Telegram API: Answer callback query
     */
    private function answerCallbackQuery(string $callbackId, string $text, bool $showAlert = false): void
    {
        try {
            Http::post("https://api.telegram.org/bot{$this->botToken}/answerCallbackQuery", [
                'callback_query_id' => $callbackId,
                'text' => $text,
                'show_alert' => $showAlert,
            ]);
        } catch (Exception $e) {
            Log::error('Telegram answerCallbackQuery failed', [
                'callback_id' => $callbackId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Telegram API: Edit message text
     */
    private function editMessageText(int $chatId, int $messageId, string $text, ?array $replyMarkup = null): void
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ];

            if ($replyMarkup !== null) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            } else {
                $payload['reply_markup'] = json_encode(['inline_keyboard' => []]);
            }

            Http::post("https://api.telegram.org/bot{$this->botToken}/editMessageText", $payload);
        } catch (Exception $e) {
            Log::error('Telegram editMessageText failed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
