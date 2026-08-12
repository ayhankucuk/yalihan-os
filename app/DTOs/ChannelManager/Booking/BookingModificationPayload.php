<?php

namespace App\DTOs\ChannelManager\Booking;

use DateTimeImmutable;

/**
 * BookingModificationPayload — Immutable DTO for modification and cancellation events.
 *
 * Sprint 4.12 — Booking.com Provider Wave 3
 * ADR-009 invariant: modification/cancellation use same canonical pipeline as new reservations.
 *
 * Booking.com sends both modification and cancellation via OTA_HotelResNotif
 * with reservation_status = 'modified' | 'cancelled'.
 *
 * Maps to the same structure as BookingReservationPayload but is a separate
 * type to make processors explicit (not routed through IngestService).
 */
readonly final class BookingModificationPayload
{
    public function __construct(
        public string  $externalReservationId,
        public string  $hotelCode,
        public string  $arrivalDate,       // Y-m-d
        public string  $departureDate,     // Y-m-d
        public int     $nights,
        public string  $guestName,
        public ?string $guestPhone,
        public ?string $guestEmail,
        public int     $adultCount,
        public float   $totalPrice,
        public string  $currency,
        // 'modified' | 'cancelled'
        public string  $status,
    ) {}

    /**
     * Parse from Booking.com API response (OTA_HotelResNotif structure).
     */
    public static function fromApiResponse(array $raw): self
    {
        $res = $raw['reservation'] ?? $raw;
        $hotel = $raw['BasicPropertyInfo'] ?? [];

        $arrival   = new DateTimeImmutable($res['arrival_date'] ?? throw new \InvalidArgumentException('Missing arrival_date'));
        $departure = new DateTimeImmutable($res['departure_date'] ?? throw new \InvalidArgumentException('Missing departure_date'));
        $nights    = max(1, (int) $arrival->diff($departure)->days);

        $status = match (strtolower($res['reservation_status'] ?? 'modified')) {
            'cancelled', 'cancel' => 'cancelled',
            'modified', 'updated'  => 'modified',
            default               => 'modified',
        };

        return new self(
            externalReservationId: $res['id'] ?? throw new \InvalidArgumentException('Missing reservation id'),
            hotelCode: $hotel['HotelCode'] ?? throw new \InvalidArgumentException('Missing HotelCode'),
            arrivalDate: $arrival->format('Y-m-d'),
            departureDate: $departure->format('Y-m-d'),
            nights: $nights,
            guestName: $res['guest_name'] ?? '',
            guestPhone: $res['guest_phone'] ?? null,
            guestEmail: $res['guest_email'] ?? null,
            adultCount: (int) ($res['adults_count'] ?? 1),
            totalPrice: (float) ($res['total_price'] ?? 0),
            currency: strtoupper($res['currency'] ?? 'TRY'),
            status: $status,
        );
    }

    public function toCanonicalGuestData(): array
    {
        return array_filter([
            'guest_name'  => $this->guestName,
            'guest_phone' => $this->guestPhone,
            'guest_email' => $this->guestEmail,
            'guest_count' => $this->adultCount,
        ], fn($v) => $v !== null);
    }

    public function toArray(): array
    {
        return [
            'external_reservation_id' => $this->externalReservationId,
            'hotel_code'             => $this->hotelCode,
            'arrival_date'           => $this->arrivalDate,
            'departure_date'         => $this->departureDate,
            'nights'                => $this->nights,
            'guest_name'            => $this->guestName,
            'adult_count'           => $this->adultCount,
            'total_price'           => $this->totalPrice,
            'currency'              => $this->currency,
            'status'                => $this->status,
        ];
    }
}
