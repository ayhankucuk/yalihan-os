<?php

namespace App\Enums;

/**
 * Finansal İşlem Durumu Enum
 *
 * Context7: Finans modülü islem_statusu type-safe enumeration
 */
enum FinansalIslemDurumu: string
{
    case BEKLIYOR = 'bekliyor';
    case ONAYLANDI = 'onaylandi';
    case REDDEDILDI = 'reddedildi';
    case TAMAMLANDI = 'tamamlandi';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::BEKLIYOR => 'Bekliyor',
            self::ONAYLANDI => 'Onaylandı',
            self::REDDEDILDI => 'Reddedildi',
            self::TAMAMLANDI => 'Tamamlandı',
        };
    }
}
