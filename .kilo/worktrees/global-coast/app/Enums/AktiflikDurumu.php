<?php

namespace App\Enums;

use App\Enums\IlanDurumu;

/**
 * 🌳 Aktiflik Durumu Enum (SAB Foundation)
 *
 * Sorumluluk: Tüm sistem genelinde boolean benzeri aktiflik durumlarını standardize eder.
 * Rule: Durum alanları ENUM değil, tinyint olmalıdır (Rule 6).
 */
enum AktiflikDurumu: int
{
    case PASIF = 0;
    case AKTIF = 1;

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::PASIF => 'Pasif',
            self::AKTIF => IlanDurumu::YAYINDA->value,
        };
    }

    /**
     * Get color for UI
     */
    public function color(): string
    {
        return match ($this) {
            self::PASIF => 'red',
            self::AKTIF => 'green',
        };
    }

    /**
     * Get icon
     */
    public function icon(): string
    {
        return match ($this) {
            self::PASIF => '❌',
            self::AKTIF => '✅',
        };
    }

    /**
     * Check if active
     */
    public function isActive(): bool
    {
        return $this === self::AKTIF;
    }

    /**
     * Static helper: Get values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
