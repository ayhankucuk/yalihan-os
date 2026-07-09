<?php

namespace App\Services\Publishing\Adapters;

use App\Contracts\Publishing\ChannelAdapterContract;
use App\DTOs\Publishing\AirbnbFormatDTO;
use App\DTOs\Publishing\ChannelPayloadDTO;
use App\DTOs\Publishing\PublishingDecisionDTO;
use App\Models\Ilan;
use App\Services\Publishing\Transformers\AmenityMapper;
use App\Services\Publishing\Transformers\DescriptionTransformer;
use App\Services\Publishing\Transformers\RoomTypeMapper;
use App\Services\Publishing\Transformers\TitleTransformer;
use App\DTOs\Vision\PublishingMediaDTO;

/**
 * Airbnb Adapter — Sprint 6.5
 *
 * Airbnb channel formatına dönüştürür.
 *
 * @rule Sadece transform yapar — iş mantığı PublishingIntelligenceOrchestrator'da.
 * @rule Real API çağrısı YAZILMAZ (Sprint 6.6+).
 * @rule withoutGlobalScopes() KULLANILMAZ — TenantScope korunur.
 * @rule Hata fırlatmaz — ChannelPayloadDTO->errors'a yazar.
 */
class AirbnbAdapter implements ChannelAdapterContract
{
    private const CHANNEL = 'airbnb';

    public function __construct(
        private readonly TitleTransformer $titleTransformer,
        private readonly DescriptionTransformer $descriptionTransformer,
        private readonly AmenityMapper $amenityMapper,
        private readonly RoomTypeMapper $roomTypeMapper,
    ) {}

    public function name(): string
    {
        return self::CHANNEL;
    }

    /**
     * Bu ilan Airbnb için uygun mu?
     * Kiralık + Bodrum + minimum oda bilgisi varsa uygundur.
     */
    public function supports(Ilan $ilan): bool
    {
        // Airbnb: sadece kiralık ilanlar (şimdilik)
        $isRental = $ilan->islem_tipi === 'kiralama'
            || mb_stripos($ilan->anaKategori?->adi ?? '', 'kiralık') !== false
            || mb_stripos($ilan->altKategori?->adi ?? '', 'kiralık') !== false;

        if (!$isRental) {
            return false;
        }

        // Bodrum bölgesi (şimdilik tek lokasyon)
        $location = $ilan->il?->il_adi ?? $ilan->il ?? '';
        if (mb_stripos($location, 'Muğla') === false && mb_stripos($location, 'Bodrum') === false) {
            return false;
        }

        return true;
    }

    /**
     * Zorunlu alanlar: Airbnb için minimum gereksinimler.
     *
     * @return string[]
     */
    public function requiredFields(): array
    {
        return [
            'baslik',
            'fiyat',
            'aciklama',
            'il',
            'min_nights',
        ];
    }

    /**
     * Validate — buildPayload çağrısından önce kontrol.
     *
     * @return string[]  Eksik alan etiketleri
     */
    public function validate(Ilan $ilan): array
    {
        $missing = [];

        if (empty($ilan->baslik)) {
            $missing[] = 'baslik';
        }
        if (empty($ilan->fiyat) || $ilan->fiyat <= 0) {
            $missing[] = 'fiyat';
        }
        if (empty($ilan->aciklama) && empty($ilan->vision_media['media'])) {
            $missing[] = 'aciklama';
        }
        if (empty($ilan->il_id) && empty($ilan->il)) {
            $missing[] = 'il';
        }
        if (empty($ilan->minimum_stay)) {
            $missing[] = 'min_nights';
        }

        return $missing;
    }

    /**
     * Ilan + Vision verilerini Airbnb formatına dönüştürür.
     *
     * @param  Ilan  $ilan  TenantScope korunur (query zaten scoped)
     * @param  array  $visionData  vision_media JSON decode — AI Vision çıktısı
     * @param  PublishingDecisionDTO|null  $decision  Publish kararı (nullable)
     */
    public function buildPayload(
        Ilan $ilan,
        array $visionData,
        ?PublishingDecisionDTO $decision = null,
    ): ChannelPayloadDTO {
        $errors = [];

        // 1. Required fields validation
        $missing = $this->validate($ilan);
        if (!empty($missing)) {
            $errors[] = 'Eksik zorunlu alanlar: ' . implode(', ', $missing);
        }

        // 2. Vision media DTO oluştur (var ise)
        $media = $this->buildMediaDto($ilan, $visionData);

        // 3. Title
        $titleHints = $this->extractTitleHints($visionData, $media);
        $listingName = $this->titleTransformer->forAirbnb($ilan, $titleHints);

        // 4. Description
        $descriptionData = $this->descriptionTransformer->forAirbnb($ilan, $media);

        // 5. Room/space mapping
        $roomData = $this->roomTypeMapper->forAirbnb($ilan, $media);

        // 6. Amenities
        $detectedAmenities = $media?->detected_amenities
            ?? ($visionData['detected_amenities'] ?? []);
        $detectedLuxury = $media?->detected_luxury_features
            ?? ($visionData['detected_luxury_features'] ?? []);
        $amenities = $this->amenityMapper->toAirbnbAmenities($detectedAmenities);

        // 7. Photos — IlanFotografi ilişkisinden sıralı
        $photos = $this->buildPhotoPayload($ilan, $media);

        // 8. Pricing
        $pricing = $this->buildPricingPayload($ilan, $decision);

        // 9. Location
        $location = $this->buildLocationPayload($ilan);

        // 10. AirbnbFormatDTO oluştur
        $airbnbDto = new AirbnbFormatDTO(
            ilanId: $ilan->id,
            listingName: $listingName,
            summary: $descriptionData['summary'] ?? '',
            description: $descriptionData['description'] ?? '', // context7-ignore: AI channel content
            space: $descriptionData['space'] ?? null,
            access: $descriptionData['access'] ?? null,
            neighborhood: $descriptionData['neighborhood_overview'] ?? null,
            transit: null,
            interaction: $descriptionData['interaction'] ?? null,
            houseRules: $descriptionData['house_rules'] ?? null,
            price: $pricing['amount'],
            cleaningFee: $pricing['cleaning_fee'],
            securityDeposit: $pricing['security_deposit'],
            minNights: $ilan->minimum_stay ?? 1,
            maxNights: $ilan->max_stay_nights ?? null,
            maxGuests: $ilan->max_guests ?? null,
            street: $ilan->adres ?? null,
            city: $ilan->il?->il_adi ?? $ilan->il ?? 'Muğla',
            state: 'Muğla',
            country: 'Türkiye',
            countryCode: 'TR',
            zip: null,
            photos: $photos,
            amenities: $amenities,
            raw: [
                'space_type' => $roomData['space_type'],
                'property_type' => $roomData['property_type'],
                'bedrooms' => $roomData['bedrooms'],
                'bathrooms' => $roomData['bathrooms'],
                'beds' => $roomData['beds'],
            ],
            errors: $errors,
        );

        // 11. ChannelPayloadDTO üret
        return new ChannelPayloadDTO(
            channel: self::CHANNEL,
            ilanId: $ilan->id,
            mappedFields: $airbnbDto->toArray(),
            photos: $photos,
            seo: [
                'title' => $listingName, // context7-ignore: SEO output key, not DB
                'description' => mb_substr($descriptionData['summary'] ?? '', 0, 150), // context7-ignore: SEO content
            ],
            pricing: $pricing,
            raw: $airbnbDto->toArray(),
            errors: $errors,
        );
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * @return PublishingMediaDTO|null
     */
    private function buildMediaDto(Ilan $ilan, array $visionData): ?PublishingMediaDTO
    {
        if (empty($visionData)) {
            return null;
        }

        // visionData zaten PublishingMediaDTO array ise doğrudan kullan
        if (isset($visionData['ilan_id'])) {
            return PublishingMediaDTO::fromArray($visionData);
        }

        // AI Vision raw çıktısı ise dönüştür
        $mediaData = $visionData['media'] ?? $visionData;
        if (is_array($mediaData) && !empty($mediaData)) {
            return PublishingMediaDTO::fromArray([
                'ilan_id' => $ilan->id,
                'hero_fotograf_id' => $visionData['hero_fotograf_id'] ?? null,
                'photo_order' => $visionData['photo_order'] ?? [],
                'title_hints' => $visionData['title_hints'] ?? [],
                'detected_rooms' => $visionData['detected_rooms'] ?? [],
                'detected_amenities' => $visionData['detected_amenities'] ?? [],
                'detected_luxury_features' => $visionData['detected_luxury_features'] ?? [],
                'vision_score' => $visionData['vision_score'] ?? 0,
                'avg_ai_confidence' => $visionData['avg_ai_confidence'] ?? 0.0,
            ]);
        }

        return null;
    }

    /** @return string[] */
    private function extractTitleHints(array $visionData, ?PublishingMediaDTO $media): array
    {
        $hints = $visionData['title_hints'] ?? [];
        if ($media?->title_hints) {
            $hints = array_merge($hints, $media->title_hints);
        }
        return array_values(array_unique(array_filter($hints)));
    }

    /**
     * @return array<array{caption: string|null, url: string, primary: bool, order: int}>
     */
    private function buildPhotoPayload(Ilan $ilan, ?PublishingMediaDTO $media): array
    {
        $photos = [];
        $order = $media?->photo_order ?? [];

        // TenantScope korunur — fotograflar() zaten scoped
        $fotograflar = $ilan->fotograflar()->get();

        if ($order) {
            // Sıralı photo_order kullan
            $byId = [];
            foreach ($fotograflar as $foto) {
                $byId[$foto->id] = $foto;
            }
            foreach ($order as $idx => $fotoId) {
                if (isset($byId[$fotoId])) {
                    $foto = $byId[$fotoId];
                    $photos[] = [
                        'caption' => $media?->photo_captions[$fotoId]['baslik'] ?? null,
                        'url' => $foto->url ?? $foto->fotograf_url ?? '',
                        'primary' => ($media?->hero_fotograf_id === $fotoId)
                            || ($idx === 0 && empty($media?->hero_fotograf_id)),
                        'sira' => $idx + 1, // context7-ignore: AI channel output field, not DB
                    ];
                }
            }
        } else {
            // Sıralama yoksa varsayılan
            foreach ($fotograflar as $idx => $foto) {
                $photos[] = [
                    'caption' => null,
                    'url' => $foto->url ?? $foto->fotograf_url ?? '',
                    'primary' => $idx === 0,
                    'sira' => $idx + 1, // context7-ignore: AI channel output field, not DB
                ];
            }
        }

        return $photos;
    }

    private function buildPricingPayload(Ilan $ilan, ?PublishingDecisionDTO $decision): array
    {
        $amount = (float) $ilan->fiyat;

        // Decision varsa fiyatı quality tier'a göre ayarla (opsiyonel)
        if ($decision && $decision->qualityTier === 'premium_plus') {
            // Premium+ için küçük bir multiplier uygula (örnek)
            // $amount = $amount * 1.05;
        }

        return [
            'amount' => $amount,
            'currency' => $ilan->para_birimi ?? 'TRY',
            'cleaning_fee' => $ilan->cleaning_fee ?? null,
            'security_deposit' => $ilan->security_deposit ?? null,
        ];
    }

    private function buildLocationPayload(Ilan $ilan): array
    {
        return [
            'street' => $ilan->adres ?? null,
            'city' => $ilan->il?->il_adi ?? $ilan->il ?? 'Muğla',
            'state' => 'Muğla',
            'country' => 'Türkiye',
            'country_code' => 'TR',
            'zip' => null,
        ];
    }
}
