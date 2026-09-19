<?php

declare(strict_types=1);

namespace App\Services\CommandCenter\Adapters;

use App\DTOs\Command\NormalizedCommandInput;

/**
 * TelegramChannelAdapter
 *
 * Context7 Standard: C7-TELEGRAM-CHANNEL-ADAPTER-2026-09-19
 *
 * Telegram webhook payload paketini kanal-bağımsız NormalizedCommandInput DTO'ya dönüştürür.
 */
class TelegramChannelAdapter
{
    public function normalize(array $update): ?NormalizedCommandInput
    {
        // 1. Message paketinden oku
        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = (string) ($message['chat']['id'] ?? '');
            $fromId = (string) ($message['from']['id'] ?? $chatId);
            $text = trim((string) ($message['text'] ?? ''));

            if (empty($chatId)) {
                return null;
            }

            return new NormalizedCommandInput(
                channel: 'telegram',
                externalActorId: $fromId,
                chatId: $chatId,
                rawText: $text,
                payload: $update,
                receivedAt: new \DateTimeImmutable
            );
        }

        // 2. Callback query paketinden oku
        if (isset($update['callback_query'])) {
            $cq = $update['callback_query'];
            $chatId = (string) ($cq['message']['chat']['id'] ?? '');
            $fromId = (string) ($cq['from']['id'] ?? $chatId);
            $data = trim((string) ($cq['data'] ?? ''));

            if (empty($chatId)) {
                return null;
            }

            return new NormalizedCommandInput(
                channel: 'telegram',
                externalActorId: $fromId,
                chatId: $chatId,
                rawText: $data,
                payload: $update,
                receivedAt: new \DateTimeImmutable
            );
        }

        return null;
    }
}
