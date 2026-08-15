<?php

namespace App\Services\Notification;

use App\DTOs\Notification\GuestConfirmationNotification;
use App\Events\Reservation\ReservationCreatedEvent;
use App\Models\Kisi;
use App\Models\Notification\OutboundNotification;
use Illuminate\Support\Facades\Log;

/**
 * GuestCommunicationPolicy — Consent + contact validation before sending.
 *
 * Validates:
 * 1. Guest has a reachable contact (phone or email)
 * 2. If Kisi model has iletisim_tercihleri JSON, check channel preference
 * 3. If no contact → skip silently (data quality issue, not system failure)
 */
class GuestCommunicationPolicy
{
    /**
     * Determine which channels are eligible to receive a confirmation for this reservation.
     *
     * @return array<string, string>  [channel => recipient]  — empty if none eligible
     */
    public function getEligibleChannels(ReservationCreatedEvent $event): array
    {
        $eligible = [];

        // ── WhatsApp ──────────────────────────────────────────────
        $phone = $this->resolveGuestPhone($event);
        if ($phone !== null && $this->channelConsentIsAllowed($event->tenantId, 'whatsapp', $phone)) {
            $eligible['whatsapp'] = $phone;
        }

        // ── Email ────────────────────────────────────────────────
        $email = $this->resolveGuestEmail($event);
        if ($email !== null && $this->isValidEmail($email)) {
            $eligible['email'] = $email;
        }

        return $eligible;
    }

    /**
     * Check if a notification for this reservation was already sent or is pending.
     * Idempotency: deduplicate by reservationId + templateKey + channel.
     */
    public function isAlreadySent(int $reservationId, string $channel): bool
    {
        // Idempotency: deduplicate per reservationId + templateKey + channel.
        // STATE_CANCELLED is included — even a blocked notification counts as "already processed."
        $existing = OutboundNotification::query()
            ->where('template_key', 'reservation_confirmation')
            ->whereJsonContains('payload_data', ['reservation_id' => $reservationId])
            ->where('channel', $channel)
            ->orderBy('id', 'desc')
            ->first();

        return $existing !== null;
    }

    /**
     * Normalize phone number to E.164 format starting with +90.
     */
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', trim($phone));

        // Already E.164 (+90...)
        if (str_starts_with($digits, '90') && strlen($digits) > 10) {
            return '+' . $digits;
        }

        // Starts with 0 — strip leading 0 and prepend country code
        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Turkish mobile: 500-599, 530-599, etc.
        if (strlen($digits) === 10) {
            return '+90' . $digits;
        }

        // International format without +
        if (strlen($digits) > 10 && !str_starts_with($digits, '90')) {
            return '+' . $digits;
        }

        return null;
    }

    /**
     * Resolve guest phone from event — first party guest or danisman fallback.
     */
    protected function resolveGuestPhone(ReservationCreatedEvent $event): ?string
    {
        // Primary: guest's own phone on the reservation
        if (!empty($event->guestPhone)) {
            return $this->normalizePhone($event->guestPhone);
        }

        return null;
    }

    /**
     * Resolve guest email from event.
     */
    protected function resolveGuestEmail(ReservationCreatedEvent $event): ?string
    {
        return $this->isValidEmail($event->guestEmail ?? null)
            ? strtolower(trim($event->guestEmail))
            : null;
    }

    /**
     * Check if guest has given consent for a specific channel.
     * Returns true if no consent model exists (fail-open for availability).
     */
    public function channelConsentIsAllowed(int $tenantId, string $channel, string $recipient): bool
    {
        // Find guest Kisi by phone or email
        $normalizedPhone = $this->normalizePhone($recipient);
        $kisi = null;

        if ($normalizedPhone !== null) {
            $kisi = Kisi::query()
                ->where('tenant_id', $tenantId)
                ->where(function ($q) use ($normalizedPhone, $recipient) {
                    $q->where('telefon', $normalizedPhone)
                      ->orWhere('telefon', '+' . $normalizedPhone)
                      ->orWhere('telefon', $recipient);
                })
                ->orderBy('id')
                ->first();
        }

        // No consent model found — fail open (availability)
        if ($kisi === null) {
            return true;
        }

        // Check iletisim_tercihleri JSON field
        $preferences = $kisi->iletisim_tercihleri ?? [];
        if (is_array($preferences) && array_key_exists($channel, $preferences)) {
            $allowed = filter_var($preferences[$channel], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($allowed === false) {
                Log::debug('[GuestCommunicationPolicy] Channel disabled by consent', [
                    'kisi_id' => $kisi->id,
                    'channel' => $channel,
                ]);
                return false;
            }
        }

        return true;
    }

    public function isValidEmail(?string $email): bool
    {
        if ($email === null || trim($email) === '') {
            return false;
        }

        return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
    }
}
