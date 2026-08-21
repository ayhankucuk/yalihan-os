<?php

namespace App\Domain\Hermes\Events;

use App\Contracts\Hermes\HermesEventContract;
use DateTimeImmutable;

/**
 * EmailCommunicationReceivedEvent
 *
 * Gmail'den yeni bir email alındığında Hermes event bus'a publish edilen event.
 *
 * Handler'lar:
 *   - CommunicationEmailHandler: Ayhan'a bildirim gönderir (P0/P1)
 *   - CommunicationDashboardHandler: Cockpit verisini günceller
 *
 * Payload: Tenant + Communication ID + severity + intent + AI extraction
 */
readonly class EmailCommunicationReceivedEvent implements HermesEventContract
{
    public function __construct(
        private int $tenantId,
        private int $communicationId,
        private string $severity,
        private string $intent,
        private string $platform,
        private bool $hasReservation,
        private array $aiExtractedData,
        private DateTimeImmutable $occurredAt = new DateTimeImmutable(),
    ) {}

    public function eventName(): string
    {
        return 'email.communication.received';
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function toPayload(): array
    {
        return [
            'communication_id'  => $this->communicationId,
            'severity'          => $this->severity,
            'intent'            => $this->intent,
            'platform'          => $this->platform,
            'has_reservation'   => $this->hasReservation,
            'ai_extracted_data' => $this->aiExtractedData,
        ];
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
