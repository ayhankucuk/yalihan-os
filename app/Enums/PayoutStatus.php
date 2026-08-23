<?php

namespace App\Enums;

/**
 * PayoutStatus — C5.1 / Booking.com Payment Details API
 *
 * Booking.com payout durumu: PENDING | PAID | PARTIALLY_PAID | CANCELLED | UNKNOWN
 * SAAB C5.1 Invariant: PayoutStatus PAID ≠ ReconciliationExecution RECONCILED.
 * Payout status banka onayını, reconciliation sonucu mutabakatı gösterir.
 * Bu iki kavram kesinlikle birleştirilemez.
 */
enum PayoutStatus: string
{
    case PENDING         = 'pending';
    case PAID            = 'paid';
    case PARTIALLY_PAID  = 'partially_paid';
    case CANCELLED       = 'cancelled';
    case UNKNOWN         = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::PENDING        => 'Bekliyor',
            self::PAID           => 'Ödendi',
            self::PARTIALLY_PAID => 'Kısmi Ödeme',
            self::CANCELLED      => 'İptal',
            self::UNKNOWN        => 'Bilinmiyor',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::PAID, self::CANCELLED], true);
    }
}
