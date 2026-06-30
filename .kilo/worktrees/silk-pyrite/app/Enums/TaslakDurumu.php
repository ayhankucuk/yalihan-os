<?php

namespace App\Enums;

/**
 * Taslak Durumu Enum
 *
 * Context7: AI taslak ve mesaj yayin_durumu type-safe enumeration
 */
enum TaslakDurumu: string
{
    case TASLAK = 'draft';
    case BEKLEMEDE = 'pending';
    case ONAYLANDI = 'approved';
    case REDDEDILDI = 'rejected';
    case GONDERILDI = 'sent';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::TASLAK => 'Taslak',
            self::BEKLEMEDE => 'Beklemede',
            self::ONAYLANDI => 'Onaylandı',
            self::REDDEDILDI => 'Reddedildi',
            self::GONDERILDI => 'Gönderildi',
        };
    }
}
