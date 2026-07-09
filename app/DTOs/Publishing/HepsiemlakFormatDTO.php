<?php

namespace App\DTOs\Publishing;

/**
 * Hepsi Emlak Format DTO — Sprint 6.5
 *
 * Hepsiemlak'a özgü alan eşleşmeleri.
 * Bu DTO üretildikten sonra değiştirilemez (readonly).
 */
final class HepsiemlakFormatDTO
{
    public function __construct(
        public readonly int $ilanId,
        public readonly string $baslik,
        public readonly string $aciklama,
        public readonly float $fiyat,
        public readonly string $paraBirimi,
        public readonly ?string $il = null,
        public readonly ?string $ilce = null,
        public readonly ?int $netM2 = null,
        public readonly ?int $odaSayisi = null,
        public readonly ?int $banyoSayisi = null,
        public readonly ?int $binaYasi = null,
        public readonly ?string $isitma = null,
        public readonly array $fotograflar = [],
        public readonly array $ozellikler = [],
        public readonly int $visionScore = 0,
        public readonly array $raw = [],
        public readonly array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'channel'      => 'hepsiemlak',
            'ilan_id'      => $this->ilanId,
            'baslik'      => $this->baslik,
            'aciklama'    => $this->aciklama,
            'fiyat'       => $this->fiyat,
            'para_birimi' => $this->paraBirimi,
            'konum' => [
                'il'   => $this->il,
                'ilce' => $this->ilce,
            ],
            'ozellikler' => [
                'net_m2'     => $this->netM2,
                'oda_sayisi'  => $this->odaSayisi,
                'banyo_sayisi' => $this->banyoSayisi,
                'bina_yasi'  => $this->binaYasi,
                'isitma'     => $this->isitma,
            ],
            'fotograflar'   => $this->fotograflar,
            'ai_ozellikler' => $this->ozellikler,
            'vision_score'   => $this->visionScore,
            'raw'           => $this->raw,
            'errors'        => $this->errors,
            'is_valid'      => empty($this->errors),
            'uretildi'     => now()->toIso8601String(),
        ];
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
