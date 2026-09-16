<?php

namespace App\Domain\CRM\DTOs;

use App\Enums\TalepDurumu;
use App\Models\User;

/**
 * TalepCreateCommand — Immutable input DTO for Talep creation.
 *
 * Resolves Kişi spillover at construction time so the use case stays pure.
 * Aligned with mapTalepData fields from TalepAuthorityService.
 */
final readonly class TalepCreateCommand
{
    public function __construct(
        public string  $baslik,
        public ?int    $kisiId,
        public ?int    $ilId,
        public ?int    $altKategoriId,
        public ?int    $danismanId,
        public string  $aciklama,
        public ?string $telefon,
        public ?string $email,
        public ?string $adres,
        public ?float  $lat,
        public ?float  $lng,
        public ?float  $minFiyat,
        public ?float  $maxFiyat,
        public ?int    $minMetrekare,
        public ?int    $maxMetrekare,
        public ?string $talepTipi,
        public bool    $oneCikan = false,
        public ?int    $ilceId = null,
        public ?int    $mahalleId = null,
        public ?string $notlar = null,
        // Note: talepler table has no yayin_tipi column — stored in metadata field if needed
        public ?string $yayinTipi = null,
        public string  $talepDurumu = TalepDurumu::AKTIF->value,
        public ?User   $actor = null,
    ) {}

    /**
     * Factory from flat request array.
     */
    public static function fromRequest(array $data, ?User $actor = null): self
    {
        return new self(
            baslik:        $data['baslik'],
            kisiId:        $data['kisi_id'],
            ilId:          $data['il_id'] ?? null,
            altKategoriId: $data['alt_kategori_id'] ?? $data['kategori_id'] ?? null,
            danismanId:    $data['danisman_id'] ?? $actor?->id ?? null,
            aciklama:      $data['aciklama'] ?? '',
            telefon:       $data['telefon'] ?? null,
            email:         $data['email'] ?? null,
            adres:         $data['adres'] ?? null,
            lat:           isset($data['lat']) ? (float) $data['lat'] : null,
            lng:           isset($data['lng']) ? (float) $data['lng'] : null,
            minFiyat:      isset($data['min_fiyat']) ? (float) $data['min_fiyat'] : null,
            maxFiyat:      isset($data['max_fiyat']) ? (float) $data['max_fiyat'] : null,
            minMetrekare:  isset($data['min_metrekare']) ? (int) $data['min_metrekare'] : null,
            maxMetrekare:  isset($data['max_metrekare']) ? (int) $data['max_metrekare'] : null,
            talepTipi:     $data['talep_tipi'] ?? $data['tip'] ?? null,
            oneCikan:      (bool) ($data['one_cikan'] ?? false),
            ilceId:        $data['ilce_id'] ?? null,
            mahalleId:     $data['mahalle_id'] ?? null,
            notlar:        $data['notlar'] ?? null,
            yayinTipi:     $data['yayin_tipi'] ?? null,
            talepDurumu:   $data['talep_durumu'] ?? TalepDurumu::AKTIF->value,
            actor:         $actor,
        );
    }

    /**
     * Factory for creation with automatic Kişi registration spillover.
     */
    public static function fromSpillover(array $data, int $kisiId, ?User $actor = null): self
    {
        $data['kisi_id'] = $kisiId;
        return self::fromRequest($data, $actor);
    }
}
