<?php

namespace App\Enums;

/**
 * PayoutType — C5.1 / Booking.com Payment Details API
 *
 * Booking.com payout tipi: GROSS | NET | UNKNOWN
 * GROSS  = OTA tüm gross tutarı muhasebeleştirir, commission kendi alır
 * NET    = OTA net tutarı aktarır (channel fee düşülmüş)
 * UNKNOWN = tip belirsiz (API yanıtı yetersiz)
 */
enum PayoutType: string
{
    case GROSS   = 'gross';
    case NET     = 'net';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::GROSS   => 'Brüt Ödeme',
            self::NET     => 'Net Ödeme',
            self::UNKNOWN => 'Bilinmiyor',
        };
    }
}
