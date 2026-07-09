<?php

namespace App\DTOs\Publishing;

/**
 * Airbnb Format DTO — Sprint 6.5
 *
 * Airbnb'ye özgü alan eşleşmeleri.
 * Bu DTO üretildikten sonra değiştirilemez (readonly).
 */
final class AirbnbFormatDTO
{
    public function __construct(
        public readonly int $ilanId,
        public readonly string $listingName,
        public readonly string $summary,
        public readonly string $description,
        public readonly ?string $space,
        public readonly ?string $access,
        public readonly ?string $neighborhood,
        public readonly ?string $transit,
        public readonly ?string $interaction,
        public readonly ?string $houseRules,
        // Pricing
        public readonly float $price,
        public readonly ?float $cleaningFee,
        public readonly ?float $securityDeposit,
        public readonly ?int $minNights,
        public readonly ?int $maxNights,
        public readonly ?int $maxGuests,
        // Location
        public readonly ?string $street,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $country,
        public readonly ?string $countryCode,
        public readonly ?string $zip,
        // Photos
        public readonly array $photos = [],    // [{caption, url, primary}]
        // Amenities (Airbnb amenity IDs)
        public readonly array $amenities = [],
        // Metadata
        public readonly array $raw = [],
        public readonly array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'channel'       => 'airbnb',
            'ilan_id'       => $this->ilanId,
            'listing_name'  => $this->listingName,
            'summary'      => $this->summary,
            'description'  => $this->description, // context7-ignore: Airbnb API field name
            'space'       => $this->space,
            'access'      => $this->access,
            'neighborhood_overview' => $this->neighborhood,
            'transit'     => $this->transit,
            'interaction' => $this->interaction,
            'house_rules' => $this->houseRules,
            'price' => [
                'amount'          => $this->price,
                'currency'         => 'TRY', // Airbnb'nin otomatik kur dönüşümü
            ],
            'cleaning_fee'      => $this->cleaningFee,
            'security_deposit'   => $this->securityDeposit,
            'bedrooms'    => null,
            'bathrooms'   => null,
            'beds'        => null,
            'guests'       => $this->maxGuests,
            'min_nights'  => $this->minNights,
            'max_nights'  => $this->maxNights,
            'location' => [
                'street'    => $this->street,
                'city'      => $this->city,
                'state'     => $this->state,
                'country'   => $this->country,
                'country_code' => $this->countryCode,
                'zip'       => $this->zip,
            ],
            'photos'   => $this->photos,
            'amenities' => $this->amenities,
            'raw'      => $this->raw,
            'errors'    => $this->errors,
            'is_valid' => empty($this->errors),
        ];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
