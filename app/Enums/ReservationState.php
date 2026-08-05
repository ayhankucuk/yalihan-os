<?php

namespace App\Enums;

/**
 * 🎫 Reservation State Enum (SAB Foundation)
 *
 * RESERVATION_CORE Phase 1: COMPLETED ve NO_SHOW state'leri eklendi
 */
enum ReservationState: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case BLOCKED = 'blocked';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';   // Checkout yapıldı
    case NO_SHOW = 'no_show';       // Misafir gelmedi

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Bekliyor',
            self::CONFIRMED => 'Onaylandı',
            self::BLOCKED => 'Bloke',
            self::CANCELLED => 'İptal',
            self::COMPLETED => 'Tamamlandı',
            self::NO_SHOW => 'Gelmedi',
        };
    }

    /**
     * Terminal state kontrolü — bu state'lerden geçiş yapılamaz
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::CANCELLED,
            self::COMPLETED,
            self::NO_SHOW,
        ]);
    }
}
