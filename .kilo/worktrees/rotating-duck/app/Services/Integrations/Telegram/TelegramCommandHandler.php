<?php

namespace App\Services\Integrations\Telegram;

use App\Services\TelegramService;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Cache;

/**
 * Telegram Command Handler
 *
 * Handles all /command requests from Telegram Bot
 * Extracted from TelegramAIBotService (God Class refactoring)
 *
 * Context7: ✅ yayin_durumu, aktiflik_durumu
 * Yalıhan Bekçi: Command Pattern implementation
 *
 * @version 1.0.0
 * @package App\Services\Integrations\Telegram
 */
class TelegramCommandHandler
{
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

    public function __construct(
        private TelegramService $telegramService
    ) {}

    /**
     * Handle bot command
     */
    public function handleCommand(int $chatId, string $command, array $message): array
    {
        [$cmd, $args] = $this->parseCommand($command);

        // Store last command for state management
        Cache::put("telegram_last_command:{$chatId}", $cmd, 3600);

        return match ($cmd) {
            '/start' => $this->handleStartCommand($chatId, $message),
            '/help' => $this->handleHelpCommand($chatId),
            '/search' => $this->handleUnknownCommand($chatId, $cmd), // $this->searchHandler->handleSearchCommand($chatId, $args, $message),
            '/list' => $this->handleListCommand($chatId, $args),
            '/talep' => $this->handleTalepCommand($chatId, $args, $message),
            '/gorevler' => $this->handleGorevlerCommand($chatId, $message),
            '/gorev_olustur' => $this->handleGorevOlusturCommand($chatId, $args, $message),
            '/proje_durumu' => $this->handleProjeDurumCommand($chatId, $args),
            '/takim' => $this->handleTakimCommand($chatId),
            '/settings' => $this->handleSettingsCommand($chatId),
            '/cancel' => $this->handleCancelCommand($chatId),
            '/rezervasyon_yap' => $this->handleUnknownCommand($chatId, $cmd), // $this->reservationHandler->handleRezervasyonYapCommand($chatId, $args, $message),
            '/takvimi_kapat' => $this->handleUnknownCommand($chatId, $cmd), // $this->reservationHandler->handleTakvimiKapatCommand($chatId, $args, $message),
            '/rezervasyon_iptal' => $this->handleUnknownCommand($chatId, $cmd), // $this->reservationHandler->handleRezervasyonIptalCommand($chatId, $args, $message),
            default => $this->handleUnknownCommand($chatId, $cmd),
        };
    }

    /**
     * Parse command into cmd and args
     */
    private function parseCommand(string $command): array
    {
        $parts = explode(' ', $command, 2);
        $cmd = $parts[0];
        $args = $parts[1] ?? '';

        return [$cmd, $args];
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
     * Handle /gorevler command
     */
    private function handleGorevlerCommand(int $chatId, array $message): array
    {
        $text = "📋 *Görevleriniz*\n\n";
        $text .= "Yakında: Görev listesi burada görünecek.";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /gorev_olustur command
     */
    private function handleGorevOlusturCommand(int $chatId, string $args, array $message): array
    {
        $this->setUserState($chatId, 'creating_gorev');

        $text = "📝 *Yeni Görev Oluştur*\n\n";
        $text .= "Görev detaylarını yazın:\n";
        $text .= "Örnek: Müşteri ile 15:00'da görüşme yap";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /proje_durumu command
     */
    private function handleProjeDurumCommand(int $chatId, string $args): array
    {
        $text = "📊 *Proje Durumu*\n\n";
        $text .= "Yakında: Proje metrikleri burada görünecek.";

        return $this->sendMessage($chatId, $text, ['parse_mode' => 'Markdown']);
    }

    /**
     * Handle /takim command
     */
    private function handleTakimCommand(int $chatId): array
    {
        $text = "👥 *Takım Üyeleri*\n\n";
        $text .= "Yakında: Takım listesi burada görünecek.";

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
     * Set user conversation state
     */
    private function setUserState(int $chatId, string $state): void
    {
        Cache::put("telegram_user_state:{$chatId}", $state, 3600);
    }

    /**
     * Send message via Telegram
     */
    private function sendMessage(int $chatId, string $text, array $options = []): array
    {
        return $this->telegramService->sendMessage($chatId, $text, $options);
    }
}
