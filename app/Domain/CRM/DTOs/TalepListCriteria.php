<?php

namespace App\Domain\CRM\DTOs;

use DateTimeInterface;

/**
 * TalepListCriteria — Immutable query criteria for Talep listing.
 *
 * Encapsulates all filter state so the controller passes a single object
 * instead of a fragile array.
 */
final readonly class TalepListCriteria
{
    public function __construct(
        public ?string            $search = null,
        public ?string            $status = null,
        public ?int               $ilId = null,
        public ?int               $kategoriId = null,
        public ?int               $danismanId = null,
        public ?DateTimeInterface $tarihFrom = null,
        public ?DateTimeInterface $tarihTo = null,
        public int                $perPage = 20,
    ) {}

    public static function fromArray(array $filters): self
    {
        return new self(
            search:     $filters['search'] ?? null,
            status:     $filters['status'] ?? null,
            ilId:       isset($filters['il_id']) ? (int) $filters['il_id'] : null,
            kategoriId: isset($filters['kategori_id']) ? (int) $filters['kategori_id'] : null,
            danismanId: isset($filters['danisman_id']) ? (int) $filters['danisman_id'] : null,
            tarihFrom:  isset($filters['tarih_from']) ? new \DateTimeImmutable($filters['tarih_from']) : null,
            tarihTo:    isset($filters['tarih_to']) ? new \DateTimeImmutable($filters['tarih_to']) : null,
            perPage:    (int) ($filters['per_page'] ?? 20),
        );
    }

    public function isEmpty(): bool
    {
        return $this->search === null
            && $this->status === null
            && $this->ilId === null
            && $this->kategoriId === null
            && $this->danismanId === null
            && $this->tarihFrom === null
            && $this->tarihTo === null;
    }

    public function toArray(): array
    {
        return array_filter([
            'search'       => $this->search,
            'status'       => $this->status,
            'il_id'        => $this->ilId,
            'kategori_id'  => $this->kategoriId,
            'danisman_id'  => $this->danismanId,
            'tarih_from'   => $this->tarihFrom?->format('Y-m-d'),
            'tarih_to'     => $this->tarihTo?->format('Y-m-d'),
        ], fn($v) => $v !== null);
    }
}
