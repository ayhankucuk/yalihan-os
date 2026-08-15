<?php

namespace App\Services\Notification;

use App\Events\Reservation\CheckinWindowOpenedEvent;
use App\Models\Kisi;
use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Log;

/**
 * CredentialCommunicationPolicy — Consent + contact validation for credential delivery.
 *
 * CHECKIN_CHECKOUT Wave 3
 *
 * Extends GuestCommunicationPolicy with credential-specific channel selection
 * and idempotency for checkin_credential template.
 *
 * This policy does NOT handle credential plaintext — only channel eligibility
 * and idempotency decisions.
 *
 * Canonical authority for:
 *   - Channel priority (WhatsApp > Email)
 *   - Consent validation
 *   - Idempotency checks (checkin_credential template)
 */
class CredentialCommunicationPolicy
{
    private GuestCommunicationPolicy $basePolicy;

    public function __construct()
    {
        $this->basePolicy = new GuestCommunicationPolicy();
    }

    /**
     * Determine which channels are eligible for credential delivery.
     *
     * Priority: WhatsApp (primary) → Email (fallback)
     *
     * @return array<string, string>  [channel => recipient]  — empty if none eligible
     */
    public function getEligibleChannelsForCredential(CheckinWindowOpenedEvent $event): array
    {
        $eligible = [];

        // ── WhatsApp (primary) ────────────────────────────────────────────────
        $phone = $this->resolveGuestPhone($event);
        if ($phone !== null && $this->channelConsentIsAllowed($event->tenantId, 'whatsapp', $phone)) {
            $eligible['whatsapp'] = $phone;
        }

        // ── Email (fallback) ────────────────────────────────────────────────
        $email = $this->resolveGuestEmail($event);
        if ($email !== null && $this->isValidEmail($email)) {
            $eligible['email'] = strtolower(trim($email));
        }

        return $eligible;
    }

    /**
     * Check if a checkin_credential notification was already sent or is pending.
     *
     * Idempotency key: template_key='checkin_credential' + reservation_id + channel
     *
     * STATE_CANCELLED is excluded — a cancelled notification can be replayed.
     * STATE_PENDING and STATE_PROCESSING are included — no double-send.
     *
     * @return bool  true if a non-cancellable notification already exists
     */
    public function isCheckinCredentialAlreadySent(int $reservationId, string $channel): bool
    {
        return OutboundNotification::query()
            ->where('template_key', 'checkin_credential')
            ->whereJsonContains('payload_data', ['reservation_id' => $reservationId])
            ->where('channel', $channel)
            ->whereIn('gonderim_durumu', [
                OutboundNotification::STATE_SENT,
                OutboundNotification::STATE_PENDING,
                OutboundNotification::STATE_PROCESSING,
                OutboundNotification::STATE_RETRY_SCHEDULED,
            ])
            ->orderBy('id', 'desc')
            ->exists();
    }

    /**
     * Get the latest checkin_credential notification for a reservation + channel.
     * Used for replay/resend to determine if a new notification should be created
     * or the existing one should be retried.
     *
     * @return OutboundNotification|null
     */
    public function getLatestCredentialNotification(int $reservationId, string $channel): ?OutboundNotification
    {
        return OutboundNotification::query()
            ->where('template_key', 'checkin_credential')
            ->whereJsonContains('payload_data', ['reservation_id' => $reservationId])
            ->where('channel', $channel)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Resolve guest phone from CheckinWindowOpenedEvent.
     */
    protected function resolveGuestPhone(CheckinWindowOpenedEvent $event): ?string
    {
        if (!empty($event->guestPhone)) {
            return $this->basePolicy->normalizePhone($event->guestPhone);
        }

        return null;
    }

    /**
     * Resolve guest email from CheckinWindowOpenedEvent.
     */
    protected function resolveGuestEmail(CheckinWindowOpenedEvent $event): ?string
    {
        return $this->isValidEmail($event->guestEmail ?? null)
            ? strtolower(trim($event->guestEmail))
            : null;
    }

    /**
     * Check if guest has given consent for a specific channel.
     * Returns true if no consent model exists (fail-open for availability).
     */
    protected function channelConsentIsAllowed(int $tenantId, string $channel, string $recipient): bool
    {
        return $this->basePolicy->channelConsentIsAllowed($tenantId, $channel, $recipient);
    }

    protected function isValidEmail(?string $email): bool
    {
        return $this->basePolicy->isValidEmail($email);
    }
}
