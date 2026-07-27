<?php

namespace App\Domain\CommercialOffering\ValueObjects;

use App\Domain\Shared\ValueObjects\Money;
use InvalidArgumentException;

/**
 * DateRange Value Object for CommercialOffering validity period.
 * Enforces that end date must be after or equal to start date.
 */
readonly class DateRange
{
    public function __construct(
        private ?\DateTimeInterface $baslangicTarihi,
        private ?\DateTimeInterface $bitisTarihi
    ) {
        if ($baslangicTarihi !== null && $bitisTarihi !== null) {
            if ($bitisTarihi < $baslangicTarihi) {
                throw new InvalidArgumentException(
                    'Bitiş tarihi başlangıç tarihinden önce olamaz.'
                );
            }
        }
    }

    public function getBaslangicTarihi(): ?\DateTimeInterface
    {
        return $this->baslangicTarihi;
    }

    public function getBitisTarihi(): ?\DateTimeInterface
    {
        return $this->bitisTarihi;
    }

    public function isValid(?\DateTimeInterface $checkDate = null): bool
    {
        $checkDate ??= new \DateTimeImmutable();

        if ($this->baslangicTarihi !== null && $checkDate < $this->baslangicTarihi) {
            return false;
        }

        if ($this->bitisTarihi !== null && $checkDate > $this->bitisTarihi) {
            return false;
        }

        return true;
    }

    public function isActive(): bool
    {
        return $this->isValid();
    }

    public function toArray(): array
    {
        return [
            'baslangic_tarihi' => $this->baslangicTarihi?->format('Y-m-d'),
            'bitis_tarihi' => $this->bitisTarihi?->format('Y-m-d'),
        ];
    }

    public function equals(self $other): bool
    {
        if ($this->baslangicTarihi === null && $other->baslangicTarihi !== null) {
            return false;
        }
        if ($this->baslangicTarihi !== null && $other->baslangicTarihi === null) {
            return false;
        }
        if ($this->baslangicTarihi !== null && $this->baslangicTarihi->format('Y-m-d') !== $other->baslangicTarihi->format('Y-m-d')) {
            return false;
        }

        if ($this->bitisTarihi === null && $other->bitisTarihi !== null) {
            return false;
        }
        if ($this->bitisTarihi !== null && $other->bitisTarihi === null) {
            return false;
        }
        if ($this->bitisTarihi !== null && $this->bitisTarihi->format('Y-m-d') !== $other->bitisTarihi->format('Y-m-d')) {
            return false;
        }

        return true;
    }
}
