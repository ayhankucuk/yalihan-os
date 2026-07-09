<?php

namespace App\Services\Publishing\Adapters;

use App\Contracts\Publishing\ChannelAdapterContract;
use App\DTOs\Publishing\ChannelPayloadDTO;
use App\DTOs\Publishing\PublishingDecisionDTO;
use App\DTOs\Publishing\SahibindenFormatDTO;
use App\Models\Ilan;
use App\Services\Publishing\Transformers\AmenityMapper;
use App\Services\Publishing\Transformers\DescriptionTransformer;
use App\Services\Publishing\Transformers\RoomTypeMapper;
use App\Services\Publishing\Transformers\TitleTransformer;
use App\DTOs\Vision\PublishingMediaDTO;

/**
 * Sahibinden Adapter — Sprint 6.5
 *
 * Sahibinden.com formatına dönüştürür.
 *
 * @rule Sadece transform yapar — iş mantığı PublishingIntelligenceOrchestrator'da.
 * @rule Real API çağrısı YAZILMAZ (Sprint 6.6+).
 * @rule withoutGlobalScopes() KULLANILMAZ — TenantScope korunur.
 * @rule Hata fırlatmaz — ChannelPayloadDTO->errors'a yazar.
 */
class SahibindenAdapter implements ChannelAdapterContract
{
    private const CHANNEL = 'sahibinden';

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
     * Bu ilan Sahibinden için uygun mu?
     * Tüm emlak tiplerini destekler.
     */
    public function supports(Ilan $ilan): bool
    {
        // Sahibinden: bodrum/muğla bölgesindeki tüm emlak tipleri
        $location = $ilan->il?->il_adi ?? $ilan->il ?? '';
        if (mb_stripos($location, 'Muğla') === false && mb_stripos($location, 'Bodrum') === false) {
            return false;
        }

        // Boş fiyat = desteklenmez
        if (empty($ilan->fiyat) || $ilan->fiyat <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Zorunlu alanlar: Sahibinden için minimum gereksinimler.
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
            'ilce',
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
        if (empty($ilan->aciklama)) {
            $missing[] = 'aciklama';
        }
        if (empty($ilan->il_id) && empty($ilan->il)) {
            $missing[] = 'il';
        }
        if (empty($ilan->ilce_id) && empty($ilan->ilce)) {
            $missing[] = 'ilce';
        }

        return $missing;
    }

    /**
     * Ilan + Vision verilerini Sahibinden formatına dönüştürür.
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

        // 2. Vision media DTO
        $media = $this->buildMediaDto($ilan, $visionData);

        // 3. Title
        $titleHints = $this->extractTitleHints($visionData, $media);
        $baslik = $this->titleTransformer->forSahibinden($ilan, $titleHints);

        // 4. Description
        $descriptionData = $this->descriptionTransformer->forSahibinden($ilan, $media);

        // 5. Room/space mapping
        $roomData = $this->roomTypeMapper->forSahibinden($ilan, $media);

        // 6. Amenities
        $detectedAmenities = $media?->detected_amenities
            ?? ($visionData['detected_amenities'] ?? []);
        $detectedLuxury = $media?->detected_luxury_features
            ?? ($visionData['detected_luxury_features'] ?? []);
        $features = $this->amenityMapper->toSahibindenFeatures($detectedAmenities, $detectedLuxury);

        // 7. Photos
        $photos = $this->buildPhotoPayload($ilan, $media);

        // 8. SahibindenFormatDTO oluştur
        $sahibindenDto = new SahibindenFormatDTO(
            ilanId: $ilan->id,
            baslik: $baslik ?: $ilan->baslik,
            aciklama: $descriptionData['aciklama'] ?? $ilan->aciklama ?? '',
            fiyat: (float) $ilan->fiyat,
            paraBirimi: $ilan->para_birimi ?? 'TL',
            il: $ilan->il?->il_adi ?? $ilan->il ?? null,
            ilce: $ilan->ilce?->ilce_adi ?? $ilan->ilce ?? null,
            mahalle: $ilan->mahalle?->mahalle_adi ?? null,
            netM2: $ilan->net_m2 ?? null,
            odaSayisi: $this->parseRoomCount($ilan->altKategori?->adi ?? ''),
            banyoSayisi: $ilan->banyo_sayisi ?? null,
            binaYasi: $ilan->bina_yasi ?? null,
            isitma: $ilan->isitma ?? $ilan->isinma_tipi ?? null,
            fotograflar: $photos,
            ozellikler: $features,
            visionScore: $media?->vision_score ?? ($visionData['vision_score'] ?? 0),
            raw: [
                'kategori' => $roomData['kategori'],
                'oda' => $roomData['oda'],
                'tip' => $roomData['tip'],
            ],
            errors: $errors,
        );

        // 9. ChannelPayloadDTO üret
        return new ChannelPayloadDTO(
            channel: self::CHANNEL,
            ilanId: $ilan->id,
            mappedFields: $sahibindenDto->toArray(),
            photos: $photos,
            seo: [
                'title' => $baslik ?: $ilan->baslik,
                'description' => mb_substr($descriptionData['aciklama'] ?? '', 0, 150),
            ],
            pricing: [
                'fiyat' => (float) $ilan->fiyat,
                'para_birimi' => $ilan->para_birimi ?? 'TL',
            ],
            raw: $sahibindenDto->toArray(),
            errors: $errors,
        );
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function buildMediaDto(Ilan $ilan, array $visionData): ?PublishingMediaDTO
    {
        if (empty($visionData)) {
            return null;
        }

        if (isset($visionData['ilan_id'])) {
            return PublishingMediaDTO::fromArray($visionData);
        }

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

    /** @return array<array{caption: string|null, url: string, primary: bool, sira: int}> */
    private function buildPhotoPayload(Ilan $ilan, ?PublishingMediaDTO $media): array
    {
        $photos = [];
        $order = $media?->photo_order ?? []; // context7-ignore: AI pipeline key

        $fotograflar = $ilan->fotograflar()->get();

        if ($order) {
            $byId = [];
            foreach ($fotograflar as $foto) {
                $byId[$foto->id] = $foto;
            }
            foreach ($order as $idx => $fotoId) {
                if (isset($byId[$fotoId])) {
                    $foto = $byId[$fotoId];
                    $photos[] = [
                        'baslik' => $media?->photo_captions[$fotoId]['baslik'] ?? null,
                        'url' => $foto->url ?? $foto->fotograf_url ?? '',
                        'birincil' => ($media?->hero_fotograf_id === $fotoId)
                            || ($idx === 0 && empty($media?->hero_fotograf_id)),
                        'sira' => $idx + 1,
                    ];
                }
            }
        } else {
            foreach ($fotograflar as $idx => $foto) {
                $photos[] = [
                    'baslik' => null,
                    'url' => $foto->url ?? $foto->fotograf_url ?? '',
                    'birincil' => $idx === 0,
                    'sira' => $idx + 1,
                ];
            }
        }

        return $photos;
    }

    private function parseRoomCount(string $kategori): ?int
    {
        if (preg_match('/(\d+)\s*\+/', $kategori, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/(\d+)/', $kategori, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}
