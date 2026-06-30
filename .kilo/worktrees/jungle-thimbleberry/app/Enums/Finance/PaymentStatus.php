<?php

namespace App\Enums\Finance;

/**
 * PaymentStatus Enum
 * 🛡️ SAB §12: Finance Domain Hardening
 */
enum PaymentStatus: string
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case PAID     = 'paid';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::PENDING  => 'Beklemede',
            self::APPROVED => 'Onaylandı',
            self::PAID     => 'Ödendi',
            self::REJECTED => 'Reddedildi',
        };
    }
}
