<?php

namespace App\Enums;

/**
 * Görev Durumu Enum
 *
 * Context7: Takım yönetimi görev yayin_durumu type-safe enumeration
 */
enum GorevDurumu: string
{
    case BEKLEMEDE = 'beklemede';
    case DEVAM_EDIYOR = 'devam_ediyor';
    case TAMAMLANDI = 'tamamlandi';
    case DURDURULDU = 'durduruldu';
    case BASLADI = 'basladi';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::BEKLEMEDE => 'Beklemede',
            self::DEVAM_EDIYOR => 'Devam Ediyor',
            self::TAMAMLANDI => 'Tamamlandı',
            self::DURDURULDU => 'Durduruldu',
            self::BASLADI => 'Başladı',
        };
    }
}
