<?php

namespace App\DTOs\ChannelManager\Booking;

use DateTimeImmutable;

/**
 * BookingReservationPayload — Immutable canonical DTO for Booking.com reservation.
 *
 * Sprint 4.11 — Booking.com Provider Wave 2
 * ADR-009 invariant: retrieve → normalize → canonical commit → ACK
 *
 * Maps Booking.com OTA_HotelResNotif response to Yalıhan canonical format.
 */
readonly final class BookingReservationPayload
{
    public function __construct(
        public string  $externalReservationId,  // Booking.com reservation ID
        public string  $hotelCode,             // BasicPropertyInfo.HotelCode
        public string  $arrivalDate,           // Y-m-d
        public string  $departureDate,         // Y-m-d
        public int     $nights,
        public string  $guestName,
        public ?string $guestPhone,
        public ?string $guestEmail,
        public int     $adultCount,
        public float   $totalPrice,
        public string  $currency,
        public string $roomDescription,
        // 'new' | 'modified' | 'cancelled'
        public string  $status,
    ) {}

    /**
     * Parse from Booking.com API OTA_HotelResNotif response structure.
     */
    public static function fromBookingApiResponse(array $raw): self
    {
        $res = $raw['reservation'] ?? $raw;
        $hotel = $raw['BasicPropertyInfo'] ?? [];

        $arrival   = new DateTimeImmutable($res['arrival_date'] ?? throw new \InvalidArgumentException('Missing arrival_date'));
        $departure = new DateTimeImmutable($res['departure_date'] ?? throw new \InvalidArgumentException('Missing departure_date'));
        $nights    = max(1, (int) $arrival->diff($departure)->days);

        $status = match (strtolower($res['reservation_status'] ?? 'new')) {
            'cancelled', 'cancel' => 'cancelled',
            'modified', 'updated'   => 'modified',
            default                => 'new',
        };

        return new self(
            externalReservationId: $res['id'] ?? throw new \InvalidArgumentException('Missing reservation id'),
            hotelCode: $hotel['HotelCode'] ?? throw new \InvalidArgumentException('Missing HotelCode'),
            arrivalDate: $arrival->format('Y-m-d'),
            departureDate: $departure->format('Y-m-d'),
            nights: $nights,
            guestName: $res['guest_name'] ?? throw new \InvalidArgumentException('Missing guest_name'),
            guestPhone: $res['guest_phone'] ?? null,
            guestEmail: $res['guest_email'] ?? null,
            adultCount: (int) ($res['adults_count'] ?? 1),
            totalPrice: (float) ($res['total_price'] ?? 0),
            currency: strtoupper($res['currency'] ?? 'TRY'),
            roomDescription: $res['room_description'] ?? $res['room_name'] ?? '',
            status: $status,
        );
    }

    /**
     * Build canonical guest data for ReservationService.
     */
    public function toCanonicalGuestData(): array
    {
        return array_filter([
            'guest_name'   => $this->guestName,
            'guest_phone'  => $this->guestPhone,
            'guest_email'  => $this->guestEmail,
            'guest_count'  => $this->adultCount,
        ], fn($v) => $v !== null);
    }

    public function toArray(): array
    {
        return [
            'external_reservation_id' => $this->externalReservationId,
            'hotel_code'            => $this->hotelCode,
            'arrival_date'          => $this->arrivalDate,
            'departure_date'         => $this->departureDate,
            'nights'                => $this->nights,
            'guest_name'             => $this->guestName,
            'adult_count'           => $this->adultCount,
            'total_price'           => $this->totalPrice,
            'currency'              => $this->currency,
            'status'                => $this->status,
        ];
    }
}
