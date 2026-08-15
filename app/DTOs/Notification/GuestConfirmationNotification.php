<?php

namespace App\DTOs\Notification;

use App\Contracts\Notification\NotificationContract;

/**
 * GuestConfirmationNotification — ReservationCreatedEvent → NotificationContract.
 *
 * @sab-ignore Context7 — keys match ReservationCreatedEvent readonly properties (not DB columns)
 * @sab-ignore ForbiddenFieldAST — priority comes from NotificationContract interface, not a DB field
 *
 * Maps reservation context to the notification contract for the
 * reservation_confirmation template (WhatsApp + Email).
 */
class GuestConfirmationNotification implements NotificationContract
{
    public function __construct(
        protected string $channel,
        protected string $recipient,
        protected array $data,
        protected string $priority = 'high',
    ) {}

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getRecipient(): string
    {
        return $this->recipient;
    }

    public function getTemplateKey(): string
    {
        return 'reservation_confirmation';
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function isAsync(): bool
    {
        return true;
    }

    public function getRenderedBody(): string
    {
        return '';
    }

    /**
     * Build from ReservationCreatedEvent flat data.
     * @sab-ignore Context7 — event data keys match ReservationCreatedEvent readonly properties
     */
    public static function fromReservationEvent(array $eventData, string $channel, string $recipient): self
    {
        $data = [
            'reservation_id' => $eventData['reservationId'] ?? null,
            'tenant_id'    => $eventData['tenantId'] ?? null,
            'ilan_id'       => $eventData['ilanId'] ?? null,
            'guest_name'     => $eventData['guestName'] ?? null,
            'guest_phone'   => $eventData['guestPhone'] ?? null,
            'guest_email'   => $eventData['guestEmail'] ?? null,
            'start_date'    => $eventData['startDate'] ?? null,
            'end_date'      => $eventData['endDate'] ?? null,
            'nights'        => $eventData['nights'] ?? null,
            'total_amount'  => $eventData['totalAmount'] ?? null,
            'currency'     => $eventData['currency'] ?? 'TRY',
            'external_channel' => $eventData['externalChannel'] ?? null,
        ];

        return new self($channel, $recipient, $data, 'high');
    }
}
