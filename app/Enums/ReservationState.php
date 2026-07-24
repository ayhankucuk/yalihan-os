<?php

namespace App\Enums;

/**
 * 🎫 Reservation State Enum (SAAB Canonical State Machine)
 */
enum ReservationState: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case CLOSED = 'closed';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Bekliyor',
            self::CONFIRMED => 'Onaylandı',
            self::CHECKED_IN => 'Giriş Yapıldı',
            self::CHECKED_OUT => 'Çıkış Yapıldı',
            self::CLOSED => 'Kapandı',
            self::EXPIRED => 'Zaman Aşımı',
            self::CANCELLED => 'İptal',
            self::BLOCKED => 'Bloke',
        };
    }

    /**
     * Validates if transition from current state to target state is allowed by state machine rules.
     */
    public function canTransitionTo(ReservationState $targetState): bool
    {
        if ($this === $targetState) {
            return true;
        }

        return match ($this) {
            self::PENDING => in_array($targetState, [self::CONFIRMED, self::CANCELLED, self::EXPIRED], true),
            self::CONFIRMED => in_array($targetState, [self::CHECKED_IN, self::CANCELLED], true),
            self::CHECKED_IN => in_array($targetState, [self::CHECKED_OUT], true),
            self::CHECKED_OUT => in_array($targetState, [self::CLOSED], true),
            self::CLOSED, self::CANCELLED, self::EXPIRED => false, // Terminal states
            self::BLOCKED => in_array($targetState, [self::CANCELLED], true),
        };
    }
}
