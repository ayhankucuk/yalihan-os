<?php

namespace App\Services\Hermes\Handlers;

use App\Contracts\Hermes\HermesEventContract;
use App\Contracts\Hermes\HermesHandlerContract;
use App\Domain\Hermes\Enums\HermesEventVocabulary;
use Illuminate\Support\Facades\Log;

/**
 * TelegramNotificationHandler (STUB)
 *
 * Telegram bildirimleri için stub implementasyon.
 * Gerçek Telegram API entegrasyonu sonraki sprint'te eklenecek.
 *
 * WARNING: Bu dosya gerçek API çağrısı YAPMAZ.
 * Sadece log'lar ve yapı hazırlar.
 *
 * Stub durumu:
 * - Token validation hazır
 * - Rate limiting yapısı hazır
 * - Message formatting hazır
 * - Gerçek API çağrısı TODO
 */
class TelegramNotificationHandler implements HermesHandlerContract
{
    private ?string $botToken;
    private ?string $chatId;
    private bool $enabled;

    public function __construct()
    {
        // TODO: Config'den alınacak
        $this->botToken = config('services.telegram.bot_token');
        $this->chatId = config('services.telegram.chat_id');
        $this->enabled = config('services.telegram.enabled', false);
    }

    /**
     * @inheritDoc
     */
    public function subscribesTo(): array
    {
        return [
            HermesEventVocabulary::NOTIFICATION_SENT->value,
            HermesEventVocabulary::NOTIFICATION_FAILED->value,
        ];
    }

    /**
     * @inheritDoc
     */
    public function handle(HermesEventContract $event): array
    {
        $eventName = $event->eventName();
        $payload = $event->toPayload();

        // Stub: Sadece log'la, gerçek API çağrısı yapma
        if (!$this->enabled) {
            Log::debug('[TelegramStub] Telegram disabled, skipping', [
                'event' => $eventName,
            ]);
            return [
                'handler' => self::class,
                'event' => $eventName,
                'sent' => false,
                'reason' => 'telegram_disabled',
                'stub' => true,
            ];
        }

        // TODO: Gerçek Telegram API entegrasyonu
        // $this->sendToTelegram($event);

        Log::debug('[TelegramStub] Would send notification', [
            'event' => $eventName,
            'payload' => $payload,
            'bot_configured' => !empty($this->botToken),
            'chat_configured' => !empty($this->chatId),
        ]);

        return [
            'handler' => self::class,
            'event' => $eventName,
            'sent' => false,
            'reason' => 'stub_implementation',
            'would_send' => true,
            'stub' => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function isAsync(): bool
    {
        return true; // Telegram API async olmalı
    }

    /**
     * Format message for Telegram
     *
     * TODO: Gerçek implementasyonda kullanılacak
     */
    public function formatMessage(array $payload): string
    {
        $type = $payload['notification_type'] ?? 'unknown';
        $ilanBaslik = $payload['ilan_baslik'] ?? 'N/A';

        return match ($type) {
            'portfolio_created' => "📢 Yeni Portföy\n{$ilanBaslik}",
            'lead_created' => "👤 Yeni Lead\n{$ilanBaslik}",
            'governance_decision' => "⚖️ Governance Kararı\n{$ilanBaslik}",
            default => "📢 Bildirim\n{$ilanBaslik}",
        };
    }

    /**
     * Rate limiting check
     *
     * TODO: Gerçek implementasyonda kullanılacak
     */
    public function checkRateLimit(): bool
    {
        // Placeholder: 1 mesaj/saniye rate limit
        return true;
    }

    /**
     * Validate Telegram configuration
     *
     * TODO: Gerçek implementasyonda kullanılacak
     */
    public function validateConfig(): array
    {
        $issues = [];

        if (empty($this->botToken)) {
            $issues[] = 'Telegram bot token not configured';
        }

        if (empty($this->chatId)) {
            $issues[] = 'Telegram chat ID not configured';
        }

        return $issues;
    }
}
