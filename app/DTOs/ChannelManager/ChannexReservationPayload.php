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
        public readonly ?string $revisionId = null,
        public readonly string  $action = 'new',
    ) {}

    public static function fromChannexWebhook(array $payload): self
    {
        // Support JSON:API format (data -> attributes) or flat webhook payload (reservation -> ...)
        if (isset($payload['data']['attributes'])) {
            return self::fromChannexRevision($payload['data']);
        }

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
        $revisionId = $payload['revision_id'] ?? $payload['booking_revision_id'] ?? $res['revision_id'] ?? null;
        $action = $payload['action'] ?? $res['status'] ?? 'new';

        return new self(
            externalReservationId: $res['id'],
            externalListingId:     $res['property_id'],
            channel:               strtolower($res['channel_name'] ?? $res['ota_name'] ?? 'channex'),
            arrivalDate:           $res['arrival_date'],
            departureDate:         $res['departure_date'],
            nights:                $nights,
            guestName:             $res['guest_name'],
            guestPhone:            $res['guest_phone'] ?? null,
            guestEmail:            $res['guest_email'] ?? null,
            adultCount:            (int) ($res['adults_count'] ?? 1),
            totalPrice:            (float) ($res['total_price'] ?? 0),
            currency:              strtoupper($res['currency'] ?? 'TRY'),
            revisionId:            $revisionId,
            action:                $action,
        );
    }

    public static function fromChannexRevision(array $revisionData): self
    {
        $revisionId = $revisionData['id'] ?? null;
        $attr = $revisionData['attributes'] ?? $revisionData;

        $resId = $attr['booking_id'] ?? $attr['id'] ?? null;
        $propId = $attr['property_id'] ?? null;

        if (!$resId || !$propId) {
            throw new \InvalidArgumentException('Missing booking_id or property_id in revision data');
        }

        $arrivalDate = $attr['arrival_date'] ?? $attr['checkin_date'] ?? date('Y-m-d');
        $departureDate = $attr['departure_date'] ?? $attr['checkout_date'] ?? date('Y-m-d', strtotime('+1 day'));

        $arrival   = new \DateTimeImmutable($arrivalDate);
        $departure = new \DateTimeImmutable($departureDate);
        $nights    = max(1, (int) $departure->diff($arrival)->days);
        $action    = $attr['status'] ?? $attr['action'] ?? 'new';

        return new self(
            externalReservationId: $resId,
            externalListingId:     $propId,
            channel:               strtolower($attr['ota_name'] ?? $attr['channel_name'] ?? 'channex'),
            arrivalDate:           $arrivalDate,
            departureDate:         $departureDate,
            nights:                $nights,
            guestName:             $attr['guest_name'] ?? $attr['customer_name'] ?? 'Guest',
            guestPhone:            $attr['guest_phone'] ?? $attr['customer_phone'] ?? null,
            guestEmail:            $attr['guest_email'] ?? $attr['customer_email'] ?? null,
            adultCount:            (int) ($attr['adults_count'] ?? $attr['occupancy'] ?? 1),
            totalPrice:            (float) ($attr['total_price'] ?? $attr['amount'] ?? 0),
            currency:              strtoupper($attr['currency'] ?? 'EUR'),
            revisionId:            $revisionId,
            action:                $action,
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
            'revision_id'             => $this->revisionId,
            'action'                  => $this->action,
        ];
    }
}
