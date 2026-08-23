<?php

namespace App\Enums;

/**
 * VccStatus — C5.1 / Booking.com VCC Lifecycle
 *
 * Booking.com Virtual Credit Card (VCC) durumları.
 * VCC ayrı bir lifecycle taşır — banka transferi gibi değerlendirilemez.
 *
 * Booking.com VCC endpoint'leri ayrı chargeable/refundable VCC'leri yönetir.
 * Canonical state machine (C5 Charter):
 *   ACTIVE           → check-in sonrası VCC aktif, henüz charge yok
 *   FUNDED           → VCC fonlanmış (alternatif terim)
 *   PARTIALLY_CHARGED → VCC kısmen çekilmiş
 *   FULLY_CHARGED    → VCC tamamen çekilmiş
 *   EXPIRED          → VCC süresi dolmuş (işlem yapılamaz)
 *   BLOCKED          → VCC bloke edilmiş (fraud/onarı koruması)
 *   CANCELLED        → VCC iptal edilmiş (kullanılmayan bakiye iade)
 *
 * SAAB C5.1 Invariant: VCC durumu asla PayoutStatus ile birleştirilemez.
 * VCC charge lifecycle bağımsızdır — banka hesabına para girişi ayrı izlenir.
 */
enum VccStatus: string
{
    // VCC aktif ve kullanılabilir
    case ACTIVE             = 'active';
    // VCC fonlanmış (Booking.com terminolojisinde alternatif)
    case FUNDED             = 'funded';
    // VCC kısmen çekilmiş
    case PARTIALLY_CHARGED  = 'partially_charged';
    // VCC tamamen çekilmiş
    case FULLY_CHARGED      = 'fully_charged';
    // VCC süresi dolmuş
    case EXPIRED            = 'expired';
    // VCC bloke edilmiş (fraud/onarı koruması)
    case BLOCKED            = 'blocked';
    // VCC iptal edilmiş (kullanılmayan bakiye iade)
    case CANCELLED          = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE             => 'Aktif',
            self::FUNDED             => 'Fonlanmış',
            self::PARTIALLY_CHARGED  => 'Kısmi Çekim',
            self::FULLY_CHARGED      => 'Tam Çekim',
            self::EXPIRED            => 'Süresi Dolmuş',
            self::BLOCKED            => 'Bloke Edilmiş',
            self::CANCELLED          => 'İptal',
        };
    }

    /**
     * VCC'nin kullanılabilir olup olmadığını döner.
     * Sadece ACTIVE ve FUNDED durumlarında yeni charge yapılabilir.
     */
    public function isChargeable(): bool
    {
        return in_array($this, [self::ACTIVE, self::FUNDED], true);
    }

    /**
     * VCC'nin tamamen kapatılmış (terminal) olup olmadığını döner.
     * Terminal durumlar: tam çekim, süre dolumu, iptal, bloke.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::FULLY_CHARGED,
            self::EXPIRED,
            self::BLOCKED,
            self::CANCELLED,
        ], true);
    }
}
