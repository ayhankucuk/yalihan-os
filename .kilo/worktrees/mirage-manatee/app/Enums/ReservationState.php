<?php

namespace App\Enums;

/**
 * 🎫 Reservation State Enum (SAB Foundation)
 */
enum ReservationState: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case BLOCKED = 'blocked';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Bekliyor',
            self::CONFIRMED => 'Onaylandı',
            self::BLOCKED => 'Bloke',
            self::CANCELLED => 'İptal',
        };
    }
}
