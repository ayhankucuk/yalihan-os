<?php

namespace App\Services\Hermes\Handlers;

use App\Services\Hermes\Contracts\HandlerInterface;
use Illuminate\Support\Facades\Log;

/**
 * Sprint 3.6: Hermes Async Queue Foundation
 *
 * STUB ONLY - Real Telegram API disabled for Sprint 3.6
 * This handler remains disabled until external integrations are enabled.
 *
 * When real Telegram integration is needed, implement:
 * 1. Enable via config('hermes.handlers.telegram.enabled')
 * 2. Implement actual HTTP call to Telegram Bot API
 * 3. Add proper error handling and rate limiting
 */
class TelegramNotificationHandler implements HandlerInterface
{
    /**
     * Event names this handler subscribes to
     */
    public static function handles(): array
    {
        return [
            'ilan.created',
            'ilan.updated',
            'ilan.deleted',
            'lead.assigned',
            'lead.status_changed',
        ];
    }

    /**
     * Handle event - STUB IMPLEMENTATION
     *
     * @throws \RuntimeException Always - STUB only
     */
    public function handle(string $eventName, array $payload): void
    {
        // STUB: Real implementation would send Telegram notification
        // For now, just log that this would have been called
        Log::info('Hermes [STUB]: TelegramNotificationHandler would process', [
            'event' => $eventName,
            'tenant_id' => $payload['tenant_id'] ?? null,
        ]);

        // STUB: Always throws - should never be called in production
        throw new \RuntimeException(
            'TelegramNotificationHandler is STUB only. ' .
            'Real Telegram API integration not enabled in Sprint 3.6.'
        );
    }

    /**
     * Handler is disabled by default - STUB only
     */
    public function isEnabled(): bool
    {
        return false; // Always disabled - STUB only
    }
}
