<?php

namespace App\Domain\CRM\DTOs;

use App\Models\User;

/**
 * TalepUpdateCommand — DTO for updating a Talep.
 */
readonly class TalepUpdateCommand
{
    public function __construct(
        public ?string $baslik = null,
        public ?string $aciklama = null,
        public ?string $talepTipi = null,
        public ?int $altKategoriId = null,
        public ?string $talepDurumu = null,
        public ?int $ilId = null,
        public ?int $ilceId = null,
        public ?int $mahalleId = null,
        public ?int $kisiId = null,
        public ?int $danismanId = null,
        public ?float $minFiyat = null,
        public ?float $maxFiyat = null,
        public ?int $minMetrekare = null,
        public ?int $maxMetrekare = null,
        public ?int $minOdaSayisi = null,
        public ?int $maxOdaSayisi = null,
        public ?string $notlar = null,
        public ?User $actor = null,
    ) {}

    public static function fromRequest(array $data, ?User $actor = null): self
    {
        return new self(
            baslik: $data['baslik'] ?? null,
            aciklama: $data['aciklama'] ?? null,
            talepTipi: $data['talep_tipi'] ?? $data['tip'] ?? null,
            altKategoriId: isset($data['alt_kategori_id']) ? (int) $data['alt_kategori_id'] : (isset($data['kategori_id']) ? (int) $data['kategori_id'] : null),
            talepDurumu: $data['talep_durumu'] ?? $data['status'] ?? null,
            ilId: isset($data['il_id']) ? (int) $data['il_id'] : null,
            ilceId: isset($data['ilce_id']) ? (int) $data['ilce_id'] : null,
            mahalleId: isset($data['mahalle_id']) ? (int) $data['mahalle_id'] : null,
            kisiId: isset($data['kisi_id']) ? (int) $data['kisi_id'] : null,
            danismanId: isset($data['danisman_id']) ? (int) $data['danisman_id'] : null,
            minFiyat: isset($data['min_fiyat']) ? (float) $data['min_fiyat'] : null,
            maxFiyat: isset($data['max_fiyat']) ? (float) $data['max_fiyat'] : null,
            minMetrekare: isset($data['min_metrekare']) && $data['min_metrekare'] !== '' ? (int) $data['min_metrekare'] : null,
            maxMetrekare: isset($data['max_metrekare']) && $data['max_metrekare'] !== '' ? (int) $data['max_metrekare'] : null,
            minOdaSayisi: isset($data['min_oda_sayisi']) && $data['min_oda_sayisi'] !== '' ? (int) $data['min_oda_sayisi'] : null,
            maxOdaSayisi: isset($data['max_oda_sayisi']) && $data['max_oda_sayisi'] !== '' ? (int) $data['max_oda_sayisi'] : null,
            notlar: $data['notlar'] ?? null,
            actor: $actor,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'baslik' => $this->baslik,
            'aciklama' => $this->aciklama,
            'talep_tipi' => $this->talepTipi,
            'alt_kategori_id' => $this->altKategoriId,
            'talep_durumu' => $this->talepDurumu,
            'il_id' => $this->ilId,
            'ilce_id' => $this->ilceId,
            'mahalle_id' => $this->mahalleId,
            'kisi_id' => $this->kisiId,
            'danisman_id' => $this->danismanId,
            'min_fiyat' => $this->minFiyat,
            'max_fiyat' => $this->maxFiyat,
            'min_metrekare' => $this->minMetrekare,
            'max_metrekare' => $this->maxMetrekare,
            'min_oda_sayisi' => $this->minOdaSayisi,
            'max_oda_sayisi' => $this->maxOdaSayisi,
            'notlar' => $this->notlar,
        ], fn($val) => $val !== null);
    }
}
