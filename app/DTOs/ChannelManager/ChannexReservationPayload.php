<?php

namespace App\DTOs\ChannelManager;

final class ChannexReservationPayload
{
    public function __construct(
        public readonly string  $externalReservationId,
        public readonly string  $externalListingId,
        public readonly string  $channel,
        public readonly string  $arrivalDate,
        public readonly string  $departureDate,
        public readonly int     $nights,
        public readonly string  $guestName,
        public readonly ?string $guestPhone,
        public readonly ?string $guestEmail,
        public readonly int     $adultCount,
        public readonly float   $totalPrice,
        public readonly string  $currency,
    ) {}

    public static function fromChannexWebhook(array $payload): self
    {
        $res = $payload['reservation'] ?? null;
        if (!$res) {
            throw new \InvalidArgumentException('Missing reservation key in Channex webhook payload');
        }
        foreach (['id', 'property_id', 'arrival_date', 'departure_date', 'guest_name'] as $field) {
            if (empty($res[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }
        $arrival   = new \DateTimeImmutable($res['arrival_date']);
        $departure = new \DateTimeImmutable($res['departure_date']);
        $nights    = max(1, (int) $departure->diff($arrival)->days);

        return new self(
            externalReservationId: $res['id'],
            externalListingId:     $res['property_id'],
            channel:               strtolower($res['channel_name'] ?? 'channex'),
            arrivalDate:           $res['arrival_date'],
            departureDate:         $res['departure_date'],
            nights:                $nights,
            guestName:             $res['guest_name'],
            guestPhone:            $res['guest_phone'] ?? null,
            guestEmail:            $res['guest_email'] ?? null,
            adultCount:            (int) ($res['adults_count'] ?? 1),
            totalPrice:            (float) ($res['total_price'] ?? 0),
            currency:              strtoupper($res['currency'] ?? 'TRY'),
        );
    }

    public function toArray(): array
    {
        return [
            'external_reservation_id' => $this->externalReservationId,
            'external_listing_id'     => $this->externalListingId,
            'channel'                 => $this->channel,
            'arrival_date'            => $this->arrivalDate,
            'departure_date'          => $this->departureDate,
            'nights'                  => $this->nights,
            'adult_count'             => $this->adultCount,
        ];
    }
}
