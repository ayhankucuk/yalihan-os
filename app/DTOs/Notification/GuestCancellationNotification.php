<?php

namespace App\DTOs\Notification;

use App\Contracts\Notification\NotificationContract;

/**
 * GuestCancellationNotification — ReservationCancelledEvent → NotificationContract.
 *
 * @sab-ignore Context7 — keys match ReservationCancelledEvent readonly properties (not DB columns)
 *
 * Maps cancellation context to the notification contract for the
 * reservation_cancellation template (WhatsApp + Email).
 *
 * Design rules (A2 scope):
 * - Only canonical known facts: guest name, property, dates, reservation reference
 * - Does NOT include: refund amount, penalty, payment promise, platform policy
 * - Those fields require explicit domain data provision before inclusion
 *
 * A2 — Cancellation Communication Wave
 */
class GuestCancellationNotification implements NotificationContract
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
        return 'reservation_cancellation';
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
     * Build from ReservationCancelledEvent flat data.
     *
     * @sab-ignore Context7 — event data keys match ReservationCancelledEvent readonly properties
     */
    public static function fromCancelledEvent(array $eventData, string $channel, string $recipient): self
    {
        $data = [
            'reservation_id'      => $eventData['reservationId'] ?? null,
            'tenant_id'           => $eventData['tenantId'] ?? null,
            'ilan_id'             => $eventData['ilanId'] ?? null,
            'guest_name'          => $eventData['guestName'] ?? null,
            'guest_phone'         => $eventData['guestPhone'] ?? null,
            'guest_email'         => $eventData['guestEmail'] ?? null,
            'start_date'          => $eventData['startDate'] ?? null,
            'end_date'            => $eventData['endDate'] ?? null,
            'nights'              => $eventData['nights'] ?? null,
            'cancelled_at'        => $eventData['cancelledAt'] ?? null,
            'cancelled_by'        => $eventData['cancelledBy'] ?? 'system',
            'cancellation_reason' => $eventData['reason'] ?? null,
            'external_channel'    => $eventData['externalChannel'] ?? null,
            'external_reservation_id' => $eventData['externalReservationId'] ?? null,
        ];

        return new self($channel, $recipient, $data, 'high');
    }
}
