<?php

namespace App\Domains\GuestCommunication\Models;

use App\Contracts\Notification\NotificationContract;
use App\Models\PropertyReservation;
use App\Domains\GuestCommunication\Services\LanguageResolver;

/**
 * GuestWelcomeNotification
 *
 * GuestCommunication WAVE 1
 *
 * Misafir karşılama mesajı için notification sınıfı.
 * NotificationContract'ı implement eder.
 */
class GuestWelcomeNotification implements NotificationContract
{
    public function __construct(
        private readonly ?PropertyReservation $reservation = null,
        private readonly string $language = 'en',
        private readonly string $channel = 'airbnb',
        private readonly string $priority = 'normal',
    ) {}

    /**
     * Create from reservation
     */
    public static function fromReservation(
        PropertyReservation $reservation,
        ?string $language = null
    ): self {
        $resolver = new LanguageResolver();
        $detectedLanguage = $language ?? $resolver->resolveFromReservation($reservation);

        return new self(
            reservation: $reservation,
            language: $detectedLanguage,
        );
    }

    /**
     * Get notification channel (email, whatsapp, airbnb, telegram).
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Get recipient identifier (phone, email, airbnb guest ID).
     */
    public function getRecipient(): string
    {
        // Airbnb channel: use guest email as identifier
        if ($this->channel === 'airbnb') {
            return $this->reservation->guest_email ?? '';
        }

        // WhatsApp: use phone
        if ($this->channel === 'whatsapp') {
            return $this->reservation->guest_phone ?? '';
        }

        // Default: email
        return $this->reservation->guest_email ?? '';
    }

    /**
     * Get template key for message content.
     */
    public function getTemplateKey(): string
    {
        return "guest.welcome.{$this->language}";
    }

    /**
     * Get payload data for template rendering.
     */
    public function getData(): array
    {
        return [
            'reservation_id' => $this->reservation->id,
            'property_id' => $this->reservation->property_id,
            'tenant_id' => $this->reservation->tenant_id,
            'guest_name' => $this->reservation->guest_name,
            'guest_first_name' => $this->getGuestFirstName(),
            'property_title' => $this->getPropertyTitle(),
            'property_address' => $this->getPropertyAddress(),
            'check_in_date' => $this->reservation->start_date,
            'check_out_date' => $this->reservation->end_date,
            'nights' => $this->reservation->nights,
            'guest_count' => $this->reservation->guest_count,
            'check_in_time' => $this->getCheckInTime(),
            'check_out_time' => $this->getCheckOutTime(),
            'language' => $this->language,
            'channel' => $this->channel,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get notification priority (low, normal, high).
     */
    public function getPriority(): string
    {
        return $this->priority;
    }

    /**
     * Should send asynchronously via queue.
     */
    public function isAsync(): bool
    {
        return true; // Always async for reliability
    }

    /**
     * Get reservation ID
     */
    public function getReservationId(): int
    {
        return $this->reservation->id;
    }

    /**
     * Get property ID
     */
    public function getPropertyId(): int
    {
        return $this->reservation->property_id;
    }

    /**
     * Get tenant ID
     */
    public function getTenantId(): int
    {
        return $this->reservation->tenant_id;
    }

    /**
     * Get language
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Get guest first name for personalization
     */
    private function getGuestFirstName(): string
    {
        $name = $this->reservation->guest_name ?? '';

        // Handle "John Doe" -> "John"
        $parts = explode(' ', trim($name));

        return $parts[0] ?? $name;
    }

    /**
     * Get property title from ilan relation
     */
    private function getPropertyTitle(): string
    {
        if ($this->reservation->ilan) {
            return $this->reservation->ilan->baslik
                ?? $this->reservation->ilan->title
                ?? 'Property';
        }

        return 'Property';
    }

    /**
     * Get property address
     */
    private function getPropertyAddress(): string
    {
        if ($this->reservation->ilan) {
            return $this->reservation->ilan->adres
                ?? $this->reservation->ilan->address
                ?? '';
        }

        return '';
    }

    /**
     * Get standard check-in time
     */
    private function getCheckInTime(): string
    {
        // Standard check-in is 14:00
        return '14:00';
    }

    /**
     * Get standard check-out time
     */
    private function getCheckOutTime(): string
    {
        // Standard check-out is 11:00
        return '11:00';
    }
}
