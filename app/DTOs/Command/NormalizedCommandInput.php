<?php

declare(strict_types=1);

namespace App\DTOs\Command;

/**
 * NormalizedCommandInput DTO
 *
 * Context7 Standard: C7-COMMAND-CENTER-DTO-2026-09-19
 *
 * Kanal bağımsız (Telegram, WhatsApp, Web, Voice) ortak girdi zarfı.
 */
readonly class NormalizedCommandInput
{
    public function __construct(
        public string $channel,
        public string $externalActorId,
        public string $chatId,
        public string $rawText,
        public array $payload = [],
        public ?\DateTimeImmutable $receivedAt = null
    ) {}

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt ?? new \DateTimeImmutable;
    }
}
