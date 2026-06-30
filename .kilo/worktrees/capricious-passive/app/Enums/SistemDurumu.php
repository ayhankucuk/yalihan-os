<?php

namespace App\Enums;

/**
 * Sistem Durumu Enum
 *
 * Context7: PropertyHub registry ve sistem sağlık durumu type-safe enumeration
 */
enum SistemDurumu: string
{
    case HEALTHY = 'HEALTHY';
    case CRITICAL = 'CRITICAL';
    case DEGRADED = 'DEGRADED';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::HEALTHY => 'Sağlıklı',
            self::CRITICAL => 'Kritik',
            self::DEGRADED => 'Bozulmuş',
        };
    }
}
