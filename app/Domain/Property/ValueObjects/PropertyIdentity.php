<?php

namespace App\Domain\Property\ValueObjects;

/**
 * PropertyIdentity Value Object
 *
 * Sprint 12D — Structured property identity.
 * Stored as JSON in properties.property_identity_json.
 *
 * This is a VALUE OBJECT — immutable, no identity of its own.
 */
final readonly class PropertyIdentity
{
    public function __construct(
        public ?string $siteAdi = null,
        public ?string $blokAdi = null,
        public ?string $buildingAdi = null,
        public ?string $buildingKodu = null,
        public ?string $girisi = null,
        public ?int $kat = null,
        public ?string $daireNo = null,
        public ?string $bagimsizBolumNo = null,
        public ?string $ada = null,
        public ?string $parsel = null,
    ) {}

    /**
     * Create from database JSON array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            siteAdi: $data['site_adi'] ?? null,
            blokAdi: $data['blok_adi'] ?? null,
            buildingAdi: $data['building_adi'] ?? null,
            buildingKodu: $data['building_kodu'] ?? null,
            girisi: $data['girisi'] ?? null,
            kat: isset($data['kat']) ? (int) $data['kat'] : null,
            daireNo: $data['daire_no'] ?? null,
            bagimsizBolumNo: $data['bagimsiz_bolum_no'] ?? null,
            ada: $data['ada'] ?? null,
            parsel: $data['parsel'] ?? null,
        );
    }

    /**
     * Convert to database JSON array
     */
    public function toArray(): array
    {
        return array_filter([
            'site_adi' => $this->siteAdi,
            'blok_adi' => $this->blokAdi,
            'building_adi' => $this->buildingAdi,
            'building_kodu' => $this->buildingKodu,
            'girisi' => $this->girisi,
            'kat' => $this->kat,
            'daire_no' => $this->daireNo,
            'bagimsiz_bolum_no' => $this->bagimsizBolumNo,
            'ada' => $this->ada,
            'parsel' => $this->parsel,
        ], fn ($v) => $v !== null);
    }

    /**
     * Human-readable short description
     */
    public function shortDescription(): string
    {
        $parts = [];
        if ($this->siteAdi) $parts[] = $this->siteAdi;
        if ($this->blokAdi) $parts[] = 'Blok ' . $this->blokAdi;
        if ($this->daireNo) $parts[] = 'Daire ' . $this->daireNo;
        return implode(' — ', $parts) ?: 'Tanımsız';
    }
}
